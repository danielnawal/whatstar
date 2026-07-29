<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\App;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;
use Http;
use App\Services\AlertDispatcherService;

class AppogioController extends Controller
{
    /**
     * Página pública de conexión para clientes de Appogio.
     * URL: /appogio/connect/{app_uuid}
     */
    public function connect($appUuid)
    {
        $app = App::where('uuid', $appUuid)->with('device')->first();
        abort_if(empty($app), 404, 'Integración no encontrada.');

        $device = $app->device;
        $meta   = $device ? json_decode($device->meta ?? '{}', true) : [];
        $outkey = $meta['appogio_token'] ?? null;

        return view('appogio.connect', compact('app', 'device', 'outkey'));
    }

    /**
     * AJAX público: solicita QR al servidor de WhatsApp.
     * POST /appogio/get-qr/{app_uuid}
     */
    public function publicGetQr($appUuid)
    {
        $app    = App::where('uuid', $appUuid)->with('device')->first();
        $device = $app?->device;

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        $response = Http::post(env('WA_SERVER_URL') . '/sessions/add', [
            'id'       => 'device_' . $device->id,
            'typeAuth' => 'qr',
        ]);

        if ($response->status() === 200) {
            $body = json_decode($response->body());
            if (isset($body->data->qrcode)) {
                $device->qr = $body->data->qrcode;
                $device->save();
                return response()->json(['qr' => $body->data->qrcode, 'message' => $body->message]);
            }
        } elseif ($response->status() === 409) {
            return response()->json([
                'qr'      => $device->qr,
                'message' => 'QR listo, escanea con tu WhatsApp.',
            ]);
        }

        return response()->json(['error' => 'No se pudo obtener el QR.'], 500);
    }

    /**
     * AJAX público: verifica si el dispositivo ya está conectado.
     * POST /appogio/check-session/{app_uuid}
     */
    public function publicCheckSession($appUuid)
    {
        $app    = App::where('uuid', $appUuid)->with('device')->first();
        $device = $app?->device;

        if (!$device) {
            return response()->json(['connected' => false, 'message' => null]);
        }

        $response = Http::get(env('WA_SERVER_URL') . '/sessions/status/device_' . $device->id);

        if ($response->status() === 200) {
            $res = json_decode($response->body());
            if (isset($res->data->status)) {
                $connected = $res->data->status === 'authenticated';

                $device->status = $connected ? 1 : 0;
                $device->qr     = $connected ? null : $device->qr;
                $device->save();

                if ($connected) {
                    return response()->json([
                        'connected' => true,
                        'message'   => 'WhatsApp conectado correctamente.',
                        'appkey'    => $app->key,
                        'outkey'    => json_decode($device->meta ?? '{}', true)['appogio_token'] ?? null,
                    ]);
                }

                return response()->json(['connected' => false, 'message' => null]);
            }
        }

        return response()->json(['connected' => false, 'message' => null]);
    }

    /**
     * Genera o regenera el outkey (appogio_token) del dispositivo.
     * POST /appogio/generate-token/{app_uuid}
     */
    public function generateToken($appUuid)
    {
        $app    = App::where('uuid', $appUuid)->with('device')->first();
        $device = $app?->device;

        if (!$device) {
            return response()->json(['error' => 'Dispositivo no encontrado.'], 404);
        }

        $token = Str::random(40);
        $meta  = json_decode($device->meta ?? '{}', true);
        $meta['appogio_token'] = $token;
        $device->meta = json_encode($meta);
        $device->save();

        // Sincronizar gateway SMS en el software GPS automáticamente
        $gatewaySynced = $this->syncGpsGateway($app, $token);

        return response()->json([
            'outkey'         => $token,
            'message'        => 'Token generado correctamente.',
            'gateway_synced' => $gatewaySynced,
        ]);
    }

    /**
     * Llama al GPS para registrar el gateway de WhatsApp (appkey + outkey).
     * El GPS enviará alertas a /appogio/alert validando con estos tokens.
     * Silencioso: si falla, no interrumpe al usuario — solo registra el error.
     */
    private function syncGpsGateway(App $app, string $outkey): bool
    {
        try {
            $owner   = \App\Models\User::find($app->user_id);
            $meta    = json_decode($owner?->meta ?? '{}', true) ?: [];
            $gpsHash = $meta['gps_api_hash'] ?? null;
            $gpsBase = rtrim(env('APPOGIO_GPS_URL', 'https://gpssoftwarenumberone.com/api'), '/');

            if (!$gpsHash) {
                \Illuminate\Support\Facades\Log::info('[WhatStar] syncGpsGateway: sin gps_api_hash para user ' . ($owner?->id ?? 'null'));
                return false;
            }

            // El GPS API requiere user_api_hash con bcrypt — usar asForm() para encoding correcto
            $resp = \Illuminate\Support\Facades\Http::timeout(8)
                ->asForm()
                ->post("{$gpsBase}/set_whatsapp_gateway", [
                    'user_api_hash' => $gpsHash,
                    'appkey'        => $app->key,
                    'outkey'        => $outkey,
                ]);

            $status = $resp->status();
            $body   = substr($resp->body(), 0, 300);
            \Illuminate\Support\Facades\Log::info("[WhatStar] syncGpsGateway user={$owner->id} app={$app->key} HTTP={$status} resp={$body}");

            return $resp->successful() && ($resp->json('status') == 1);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[WhatStar] syncGpsGateway falló: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Webhook receptor de alertas de Appogio.
     * GET/POST /appogio/alert
     *
     * Parámetros esperados (GET o POST):
     *   appkey   - Clave de la app en WhatStar (apps.key)
     *   outkey   - Token secreto (appogio_token en devices.meta)
     *   to       - Número(s) de WhatsApp destino (ej: 573001234567 o 573001234567,573009876543)
     *   unit     - Nombre de la unidad GPS
     *   event    - Tipo de evento (speeding, geofence, ignition, etc.)
     *   speed    - Velocidad (opcional)
     *   lat      - Latitud (opcional)
     *   lng      - Longitud (opcional)
     *   address  - Dirección (opcional)
     *   time     - Hora del evento (opcional)
     *   message  - Mensaje personalizado completo (opcional, sobreescribe el formato automático)
     */
    public function alert(Request $request)
    {
        // Rate limiting: máx 60 alertas por minuto por appkey (o por IP si no hay appkey)
        $rateLimitKey = 'appogio-alert:' . ($request->input('appkey') ?: $request->ip());
        if (RateLimiter::tooManyAttempts($rateLimitKey, 60)) {
            return response()->json([
                'success' => false,
                'message' => 'Límite de solicitudes excedido. Máximo 60 por minuto.',
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $appkey = $request->input('appkey');
        $outkey = $request->input('outkey');
        $to     = $request->input('to');

        if (!$appkey || !$outkey || !$to) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros requeridos: appkey, outkey, to',
            ], 400);
        }

        // Validar appkey
        $app = App::where('key', $appkey)->where('status', 1)->with('device')->first();
        if (!$app) {
            return response()->json(['success' => false, 'message' => 'appkey inválida.'], 401);
        }

        $device = $app->device;
        if (!$device || $device->status != 1) {
            return response()->json(['success' => false, 'message' => 'Dispositivo no conectado.'], 403);
        }

        // Validar outkey
        $meta        = json_decode($device->meta ?? '{}', true);
        $storedToken = $meta['appogio_token'] ?? null;

        if (!$storedToken || $storedToken !== $outkey) {
            return response()->json(['success' => false, 'message' => 'outkey inválida.'], 401);
        }

        // Construir mensaje
        $customMessage = $request->input('message');

        // Si el usuario YA configuró plantillas o reglas, usar el dispatcher multi-canal.
        // Si no, comportamiento legacy zero-downtime.
        $hasCustomConfig = \App\Models\AlertTemplate::where('user_id', $app->user_id)->exists()
            || \App\Models\AlertRule::where('user_id', $app->user_id)->exists();

        if ($hasCustomConfig && !$customMessage) {
            $payload = [
                'unit'    => $request->input('unit', 'N/A'),
                'event'   => $request->input('event', 'default'),
                'speed'   => $request->input('speed'),
                'lat'     => $request->input('lat'),
                'lng'     => $request->input('lng'),
                'address' => $request->input('address'),
                'time'    => $request->input('time', now()->format('Y-m-d H:i:s')),
                'brand'   => $app->title,
            ];

            $destinations = [];
            foreach (array_map('trim', explode(',', $to)) as $entry) {
                if (empty($entry)) continue;
                if (str_contains($entry, '@')) {
                    $destinations[] = ['channel' => 'email', 'to' => $entry];
                } elseif (str_starts_with($entry, 'tg:')) {
                    $destinations[] = ['channel' => 'telegram', 'to' => substr($entry, 3)];
                } elseif (str_starts_with($entry, 'sms:')) {
                    $destinations[] = ['channel' => 'sms', 'to' => preg_replace('/\D/', '', substr($entry, 4))];
                } else {
                    $destinations[] = ['channel' => 'whatsapp', 'to' => preg_replace('/\D/', '', $entry)];
                }
            }

            $r = (new AlertDispatcherService())->dispatch($app, $payload, $destinations);
            return response()->json(['success' => true, 'dispatch' => $r]);
        }

        // Legacy: construcción de mensaje fijo + solo WhatsApp
        $messageText = $customMessage ?: $this->buildAlertMessage($request, $app->title);

        $recipients = array_map('trim', explode(',', $to));
        $results    = [];

        foreach ($recipients as $phone) {
            $phone = preg_replace('/\D/', '', $phone);
            if (empty($phone)) continue;

            $response = Http::post(
                env('WA_SERVER_URL') . '/chats/send?id=device_' . $device->id,
                [
                    'receiver' => $phone,
                    'message'  => ['text' => $messageText],
                ]
            );

            $results[] = [
                'to'     => $phone,
                'status' => $response->status() === 200 ? 'sent' : 'failed',
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    // ================================================================
    //  SSO — Inicio de sesión sin contraseña desde Appogio
    // ================================================================

    /**
     * Login automático desde el launcher de Appogio.
     * GET /appogio/sso?token={sso_token}
     *
     * El administrador de Appogio configura esta URL en el launcher
     * del cliente, reemplazando {sso_token} con el token único de
     * cada usuario. Al hacer clic, el cliente entra directamente
     * a su dashboard sin escribir usuario ni contraseña.
     */
    public function sso(Request $request)
    {
        $gpHash        = trim((string) $request->input('hash', ''));
        $gpSecret      = trim((string) $request->input('gp', ''));
        $level         = (int) $request->input('level', 2); // 1=primer nivel, 2=sub-gerente
        $primarySecret = env('APPOGIO_PRIMARY_SECRET', '');
        $gpsApiBase    = rtrim(env('APPOGIO_GPS_URL', 'https://gpssoftwarenumberone.com/api'), '/');

        // Protección contra fuerza bruta: máx 10 intentos por IP/minuto
        $rateLimitKey = 'appogio-sso:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            abort(429, 'Demasiados intentos. Espera un momento.');
        }
        RateLimiter::hit($rateLimitKey, 60);

        if (empty($gpHash) || empty($gpSecret)) {
            \Illuminate\Support\Facades\Log::warning('[SSO] fallo: hash o secret vacío', ['hash_len' => strlen($gpHash), 'gp_len' => strlen($gpSecret)]);
            return redirect('/pricing');
        }

        if (!hash_equals($primarySecret, $gpSecret)) {
            \Illuminate\Support\Facades\Log::warning('[SSO] fallo: secret no coincide', ['gp' => $gpSecret, 'expected_len' => strlen($primarySecret)]);
            return redirect('/pricing');
        }

        // Validar hash contra la API del GPS y obtener datos del usuario
        try {
            $gpsResp = \Illuminate\Support\Facades\Http::timeout(8)
                ->get("{$gpsApiBase}/get_user_data", ['user_api_hash' => $gpHash]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[SSO] fallo: excepción GPS API', ['error' => $e->getMessage()]);
            return redirect('/pricing');
        }

        if (!$gpsResp->successful()) {
            \Illuminate\Support\Facades\Log::warning('[SSO] fallo: GPS API no exitosa', ['status' => $gpsResp->status(), 'body' => substr($gpsResp->body(), 0, 200)]);
            return redirect('/pricing');
        }

        $gpsUser = $gpsResp->json();
        $email   = trim((string) ($gpsUser['email'] ?? ''));
        $name    = trim((string) ($gpsUser['name']  ?? ''));

        if (empty($email)) {
            \Illuminate\Support\Facades\Log::warning('[SSO] fallo: email vacío en respuesta GPS', ['body' => substr($gpsResp->body(), 0, 200)]);
            return redirect('/pricing');
        }

        $isFirstLevel = ($level === 1);
        $starlink     = $isFirstLevel
            ? \Illuminate\Support\Facades\DB::table('plans')->where('id', 4)->first()
            : null;

        // Buscar usuario existente (cualquier rol — admin también puede entrar)
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'            => $name ?: explode('@', $email)[0],
                'email'           => $email,
                'password'        => Hash::make(Str::random(32)),
                'authkey'         => Str::random(20),
                'appogio_manager' => $isFirstLevel ? 1 : 0,
            ]);

            $user->role              = 'user';
            $user->status            = 1;
            $user->email_verified_at = Carbon::now();

            if ($isFirstLevel && $starlink) {
                $user->plan_id     = 4;
                $user->plan        = $starlink->data;
                $user->will_expire = Carbon::now()->addYears(100)->toDateString();
            }

            $user->save();
        } else {
            if ($user->status != 1) {
                $user->status = 1;
            }

            if ($isFirstLevel) {
                $user->appogio_manager = 1;
                // Asignar Starlink si no tiene plan activo
                if (!$user->plan_id || Carbon::parse($user->will_expire)->isPast()) {
                    $user->plan_id     = 4;
                    $user->plan        = $starlink->data;
                    $user->will_expire = Carbon::now()->addYears(100)->toDateString();
                }
            }

            $user->save();
        }

        // Guardar el GPS api_hash para poder llamar al GPS API después (ej. configurar gateway SMS)
        $meta = json_decode($user->meta ?? '{}', true) ?: [];
        if ($gpHash !== ($meta['gps_api_hash'] ?? '')) {
            $meta['gps_api_hash'] = $gpHash;
            $user->meta = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $user->save();
        }

        RateLimiter::clear($rateLimitKey);
        Auth::login($user, true);

        return redirect($user->role === 'admin' ? '/admin/dashboard' : '/user/dashboard');
    }

    /**
     * Genera (o regenera) el SSO token del usuario autenticado.
     * POST /appogio/sso/generate
     * Requiere autenticación normal de Laravel.
     */
    public function generateSsoToken(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        $token = Str::random(48);
        $user->sso_token = $token;
        $user->save();

        $ssoUrl = url('/appogio/sso') . '?token=' . $token;

        return response()->json([
            'token'   => $token,
            'sso_url' => $ssoUrl,
            'message' => 'Token SSO generado. Configura esta URL en el launcher de Appogio.',
        ]);
    }

    /**
     * Genera el SSO token de un usuario específico (solo admin).
     * POST /admin/appogio/sso/generate/{user_id}
     */
    public function adminGenerateSsoToken(Request $request, $userId)
    {
        $user = User::where('role', 'user')->findOrFail($userId);

        $token = Str::random(48);
        $user->sso_token = $token;
        $user->save();

        $ssoUrl = url('/appogio/sso') . '?token=' . $token;

        return response()->json([
            'token'   => $token,
            'sso_url' => $ssoUrl,
            'message' => 'Token SSO generado para ' . $user->name,
        ]);
    }

    /**
     * Construye el mensaje de alerta en formato WhatsApp
     * a partir de los parámetros recibidos de Appogio.
     */
    private function buildAlertMessage(Request $request, string $appTitle = ''): string
    {
        $unit    = $request->input('unit', 'N/A');
        $event   = $request->input('event', 'Alerta');
        $speed   = $request->input('speed');
        $lat     = $request->input('lat');
        $lng     = $request->input('lng');
        $address = $request->input('address');
        $time    = $request->input('time', now()->format('Y-m-d H:i:s'));

        $eventLabels = [
            'speeding'           => 'Exceso de velocidad',
            'geofence_in'        => 'Entrada a geocerca',
            'geofence_out'       => 'Salida de geocerca',
            'ignition_on'        => 'Motor encendido',
            'ignition_off'       => 'Motor apagado',
            'sos'                => 'SOS / Emergencia',
            'stop'               => 'Vehículo detenido',
            'movement'           => 'Movimiento detectado',
            'harsh_acceleration' => 'Aceleración brusca',
            'harsh_brake'        => 'Frenado brusco',
        ];

        $eventLabel = $eventLabels[strtolower($event)] ?? $event;
        $brand      = !empty($appTitle) ? $appTitle : 'Alerta GPS';

        $lines   = [];
        $lines[] = "*{$brand}*";
        $lines[] = "*Unidad:* {$unit}";
        $lines[] = "*Evento:* {$eventLabel}";

        if ($speed !== null && $speed !== '') {
            $lines[] = "*Velocidad:* {$speed} km/h";
        }
        if ($address) {
            $lines[] = "*Dirección:* {$address}";
        }
        if ($lat && $lng) {
            $lines[] = "*Mapa:* https://maps.google.com/?q={$lat},{$lng}";
        }

        $lines[] = "*Hora:* {$time}";

        return implode("\n", $lines);
    }

    /**
     * Envío de RECORDATORIO (ej. vencimiento de unidad anual) desde el número del DISTRIBUIDOR,
     * resuelto por su CORREO. Lo llama Remindo (server-to-server, firmado con APPOGIO_PRIMARY_SECRET).
     * No necesita que el llamante pase appkey/outkey: whatstar resuelve el número conectado por email.
     * POST /appogio/reminder  { email, to, message, secret }
     *   to = uno o varios números separados por , o ;  (máximo 4, como permite WhatsApp).
     * Devuelve {success, reason?, from?, results[]}. Solo envía si el distribuidor tiene su
     * número CONECTADO en whatstar; si no, responde 'numero_no_conectado' y no envía nada.
     */
    public function reminder(Request $request)
    {
        $secret   = (string) $request->input('secret', '');
        $expected = (string) env('APPOGIO_PRIMARY_SECRET', '');
        if ($expected === '' || !hash_equals($expected, $secret)) {
            return response()->json(['success' => false, 'reason' => 'secreto_invalido'], 401);
        }

        $rlKey = 'appogio-reminder:' . ($request->input('email') ?: $request->ip());
        if (RateLimiter::tooManyAttempts($rlKey, 60)) {
            return response()->json(['success' => false, 'reason' => 'rate_limit'], 429);
        }
        RateLimiter::hit($rlKey, 60);

        $email   = strtolower(trim((string) $request->input('email', '')));
        $to      = (string) $request->input('to', '');
        $message = trim((string) $request->input('message', ''));
        if ($email === '' || $to === '' || $message === '') {
            return response()->json(['success' => false, 'reason' => 'datos_incompletos'], 400);
        }

        // Resolver distribuidor por correo -> su app -> su dispositivo (número) CONECTADO.
        $user = User::where('email', $email)->where('status', 1)->first();
        if (!$user) {
            return response()->json(['success' => false, 'reason' => 'distribuidor_sin_whatstar'], 200);
        }
        $app = App::where('user_id', $user->id)->where('status', 1)
                  ->whereHas('device')->with('device')->first();
        $device = $app ? $app->device : null;
        if (!$device) {
            return response()->json(['success' => false, 'reason' => 'sin_dispositivo'], 200);
        }
        if ((int) $device->status !== 1) {
            return response()->json(['success' => false, 'reason' => 'numero_no_conectado'], 200);
        }
        // Antes se enviaba a ciegas: el gateway aceptaba la petición y devolvía
        // results[].status = 'failed', así que quien llamaba creía que había salido.
        $viva = $this->sesionViva($device->id);
        $this->corregirSiMiente($device, $viva);
        if ($viva !== true) {
            return response()->json([
                'success' => false,
                'reason'  => $viva === false ? 'sesion_caida' : 'servidor_whatsapp_no_responde',
                'from'    => $device->phone,
            ], 200);
        }

        // Destinatarios: separar por , o ; ; limpiar a dígitos; máximo 4.
        $recipients = preg_split('/[,;]+/', $to);
        $results = [];
        $count = 0;
        foreach ($recipients as $phone) {
            $phone = preg_replace('/\D/', '', (string) $phone);
            if ($phone === '') { continue; }
            if ($count >= 4) { break; }
            $count++;
            try {
                $resp = Http::timeout(20)->post(
                    env('WA_SERVER_URL') . '/chats/send?id=device_' . $device->id,
                    ['receiver' => $phone, 'message' => ['text' => $message]]
                );
                $results[] = ['to' => $phone, 'status' => $resp->status() === 200 ? 'sent' : 'failed'];
            } catch (\Throwable $e) {
                $results[] = ['to' => $phone, 'status' => 'error'];
            }
        }

        return response()->json([
            'success' => true,
            'from'    => $device->phone ?? null,
            'results' => $results,
        ]);
    }

    /**
     * Estado del número de alertas de un distribuidor: ¿tiene su WhatsApp CONECTADO?
     * Lo llama el panel GPS (server-to-server, firmado) para mostrar un indicador
     * "WhatsApp de alertas: conectado / no conectado". Resuelve por `appkey` (que el panel ya
     * tiene guardado en sms_gateway_url) o por `email` del distribuidor.
     * POST /appogio/status  { appkey | email, secret }  →  { success, connected, phone }
     */
    public function status(Request $request)
    {
        $secret   = (string) $request->input('secret', '');
        $expected = (string) env('APPOGIO_PRIMARY_SECRET', '');
        if ($expected === '' || !hash_equals($expected, $secret)) {
            return response()->json(['success' => false, 'reason' => 'secreto_invalido'], 401);
        }

        $appkey = trim((string) $request->input('appkey', ''));
        $email  = strtolower(trim((string) $request->input('email', '')));

        $app = null;
        if ($appkey !== '') {
            $app = App::where('key', $appkey)->with('device')->first();
        } elseif ($email !== '') {
            $user = User::where('email', $email)->where('status', 1)->first();
            if ($user) {
                $app = App::where('user_id', $user->id)->where('status', 1)
                          ->whereHas('device')->with('device')->first();
            }
        } else {
            return response()->json(['success' => false, 'reason' => 'falta_appkey_o_email'], 400);
        }

        $device = $app ? $app->device : null;
        if (!$device) {
            return response()->json(['success' => true, 'connected' => false, 'reason' => 'sin_dispositivo']);
        }
        // La verdad la tiene el servidor de WhatsApp, no la columna `status`.
        $viva = $this->sesionViva($device->id);
        $this->corregirSiMiente($device, $viva);
        $conectado = ((int) $device->status === 1) && ($viva === true);
        $motivo = null;
        if (!$conectado) {
            if ($viva === false)      { $motivo = 'sesion_caida'; }
            elseif ($viva === null)   { $motivo = 'servidor_whatsapp_no_responde'; }
            else                      { $motivo = 'numero_no_conectado'; }
        }
        return response()->json(array_filter([
            'success'   => true,
            'connected' => $conectado,
            'phone'     => $device->phone,
            'name'      => $device->name,
            'reason'    => $motivo,
        ], function ($v) { return $v !== null; }));
    }

    /**
     * ¿La sesión de WhatsApp de este número está VIVA en el servidor?
     * Devuelve: true (viva), false (el servidor dice que no existe), null (no se pudo saber).
     * El null importa: si el servidor de WhatsApp no contesta, no se puede afirmar que el
     * cliente esté desconectado, y mucho menos corregirle la base por una suposición.
     */
    private function sesionViva($deviceId)
    {
        try {
            $r = Http::timeout(4)->get(env('WA_SERVER_URL') . '/sessions/status/device_' . $deviceId);
            if ($r->status() === 404) { return false; }
            if (!$r->successful()) { return null; }
            $estado = data_get($r->json(), 'data.status');
            return $estado === 'authenticated';
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * La base decía "conectado" y el servidor dice que esa sesión no existe: se corrige.
     * No se avisa al cliente desde aquí a propósito: esto es una consulta, y una consulta no
     * debe mandarle un WhatsApp a nadie. El aviso lo hace el trabajo de reconciliación.
     */
    private function corregirSiMiente($device, $viva)
    {
        if ($viva === false && (int) $device->status === 1) {
            $device->status = 0;
            $device->save();
            \Log::info('[appogio] device ' . $device->id . ' decía conectado y su sesión no existe: corregido a 0');
        }
    }

}
