<?php

namespace App\Console\Commands;

use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Health-check: verifica cada 5 min que el bot está operativo.
 *
 * Si detecta problema:
 *   1. Log de alerta
 *   2. Si tiene WaSender API configurada → manda WhatsApp al admin alterno
 *   3. Si tiene email → manda email
 *   4. Throttle de 30 min entre alertas (no spamear)
 *
 * Uso: php artisan bot:health-check
 * Schedule: cada 5 minutos
 */
class BotHealthCheck extends Command
{
    protected $signature   = 'bot:health-check
        {--device=    : Solo este device (opcional). Si omitido, monitorea TODOS los devices con uso reciente.}
        {--include-inactive : Incluir devices con status=0 (por default solo monitorea los que estuvieron activos)}';
    protected $description = 'Verifica estado de TODOS los devices WhatsApp del whatstar. Alerta cuando uno se cae. Throttle 30 min por device.';

    public function handle(): int
    {
        $deviceId = $this->option('device');

        if ($deviceId) {
            // Modo single
            $devices = Device::where('id', (int) $deviceId)->get();
        } else {
            // Monitorear TODOS los devices que alguna vez estuvieron activos.
            // Criterio: status=1 en BD, O tienen store.json en sessions/
            // (el store.json se crea al primer mensaje — es prueba de que el device estuvo activo).
            // NO filtrar por status=1 solo, porque cuando un device se desconecta el
            // servidor Node lo pone en status=0 y el health-check dejaría de monitorearlo
            // (circulo vicioso: cae → status=0 → nadie detecta que sigue caído).
            $devices = Device::query();
            if (!$this->option('include-inactive')) {
                $devices->where('status', 1);
            }
            $devices = $devices->get();

            // Ampliar con devices status=0 que tengan store.json (estuvieron activos pero se cayeron)
            if (!$this->option('include-inactive')) {
                $sessionsDir = '/var/www/html/whatstar/sessions/';
                $storeFiles  = glob($sessionsDir . 'device_*_store.json') ?: [];
                $idsFromStore = [];
                foreach ($storeFiles as $f) {
                    if (preg_match('/device_(\d+)_store\.json$/', $f, $m)) {
                        $idsFromStore[] = (int) $m[1];
                    }
                }
                if ($idsFromStore) {
                    $extra = Device::whereIn('id', $idsFromStore)
                        ->where('status', 0)
                        ->get();
                    $devices = $devices->merge($extra)->unique('id');
                }
            } else {
                $devices = $devices->get();
            }

            // Mantener solo devices con carpeta md_device_X (creds baileys) O store.json.
            // Los que no tienen ninguno nunca se conectaron → ignorar.
            $sessionsDir = '/var/www/html/whatstar/sessions/';
            $devices = $devices->filter(function($d) use ($sessionsDir) {
                return is_dir("{$sessionsDir}md_device_{$d->id}")
                    || file_exists("{$sessionsDir}device_{$d->id}_store.json");
            });
        }

        if ($devices->isEmpty()) {
            $this->info('No hay devices que monitorear');
            return self::SUCCESS;
        }

        $totalOk    = 0;
        $totalFail  = 0;
        $waUrl      = str_replace('localhost', '127.0.0.1', env('WA_SERVER_URL', 'http://127.0.0.1:8000'));

        // Pre-cargar lista de sesiones activas una sola vez (evita N llamadas al servidor Node)
        $activeSessions = [];
        try {
            $sessListResp = Http::timeout(5)->get($waUrl . '/sessions/list');
            if ($sessListResp->successful()) {
                $activeSessions = $sessListResp->json('data') ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('[BotHealthCheck] No se pudo obtener /sessions/list: ' . $e->getMessage());
        }

        foreach ($devices as $device) {
            $result = $this->checkDevice($device, $waUrl, $activeSessions);
            if ($result['ok']) {
                $totalOk++;
                Cache::forget("bot_alert_throttle:{$device->id}");
                continue;
            }

            // Intento de auto-recovery ANTES de notificar al usuario
            if ($this->tryAutoRecover($device, $result['issues'])) {
                $this->info("  esperando que WA Server detecte creds restauradas...");
                sleep(15);
                // Refrescar lista de sesiones tras el recovery
                try {
                    $refreshed = Http::timeout(5)->get($waUrl . '/sessions/list');
                    if ($refreshed->successful()) {
                        $activeSessions = $refreshed->json('data') ?? $activeSessions;
                    }
                } catch (\Throwable $e) { /* usar la lista anterior */ }

                $reCheck = $this->checkDevice($device, $waUrl, $activeSessions);
                if ($reCheck['ok']) {
                    $this->info("  device_{$device->id} auto-recovered desde backup");
                    Log::info("[BotHealthCheck] device_{$device->id} auto-recovered desde backup");
                    Cache::forget("bot_alert_throttle:{$device->id}");
                    $totalOk++;
                    continue;
                }
                $result = $reCheck;
            }

            $totalFail++;
            $this->handleFailure($device, $result['issues']);
        }

        $this->info("Health-check: {$totalOk} ok / {$totalFail} fallos / " . count($devices) . " total");
        return $totalFail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function checkDevice(Device $device, string $waUrl, array $activeSessions = []): array
    {
        $issues = [];

        // Check 1: presente en la lista de sesiones activas del servidor Node
        // Esta es la única fuente de verdad — si no está en la lista, no puede recibir mensajes.
        $sessionKey = "device_{$device->id}";
        if (!empty($activeSessions) && !in_array($sessionKey, $activeSessions)) {
            $issues[] = "no está en sesiones activas del WA Server";
        } elseif (empty($activeSessions)) {
            // Si no pudimos obtener la lista, fallback al endpoint individual
            try {
                $resp = Http::timeout(8)->get($waUrl . '/sessions/find/' . $sessionKey);
                if (!$resp->successful()) {
                    $issues[] = "sesión no encontrada en WA Server (HTTP {$resp->status()})";
                }
            } catch (\Throwable $e) {
                $issues[] = "WA Server unreachable: " . mb_substr($e->getMessage(), 0, 80);
            }
        }

        // Check 2: status en BD (sincronizar si está desfasado)
        if ($device->status != 1) {
            $issues[] = "DB status={$device->status} (esperado 1)";
        }

        if (empty($issues)) {
            // Si el device está OK pero la BD dice 0 (caso raro), corregir BD
            if ($device->status != 1) {
                \DB::table('devices')->where('id', $device->id)->update(['status' => 1]);
            }
            $this->line("  device_{$device->id} ({$device->phone}): ok");
            return ['ok' => true];
        }

        return ['ok' => false, 'issues' => $issues];
    }

    private function handleFailure(Device $device, array $issues): void
    {
        $this->error("device_{$device->id}: " . implode(' | ', $issues));
        Log::warning("[BotHealthCheck] device_{$device->id} ({$device->phone}): " . implode(' | ', $issues));

        // Throttle 6h por device (humano no necesita alerta cada 5 min)
        $throttleKey = "bot_alert_throttle:{$device->id}";
        if (Cache::has($throttleKey)) {
            return;
        }

        $sent = $this->notifyDeviceOwner($device, $issues);

        // Canal opcional: webhook outbound (n8n / Zapier)
        if (env('APPOGIO_OUTBOUND_WEBHOOK_URL')) {
            try {
                Http::timeout(3)->post(env('APPOGIO_OUTBOUND_WEBHOOK_URL'), [
                    'event'     => 'bot.health_check_failed',
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'payload'   => [
                        'device_id'    => $device->id,
                        'device_name'  => $device->name,
                        'phone'        => $device->phone,
                        'user_id'      => $device->user_id,
                        'issues'       => $issues,
                    ],
                ]);
                $sent = true;
            } catch (\Throwable $e) { /* no-op */ }
        }

        if ($sent) {
            Cache::put($throttleKey, 1, 21600); // 6 horas
        }
    }

    /**
     * Notifica al dueño del device caído enviándole un WhatsApp con instrucciones.
     *
     * Estrategia: usa OTRO device del whatstar como emisor (cualquier device con
     * creds.json y status=1 distinto al caído). Eso permite alertar al dueño
     * por su mismo número de WhatsApp aunque el bot esté caído.
     *
     * Prioridad de "device emisor":
     *   1. Otro device del mismo usuario (si tiene varios)
     *   2. Cualquier otro device activo del whatstar
     *
     * Fallback si no hay devices activos: WaSender API + log.
     */
    private function notifyDeviceOwner(Device $device, array $issues): bool
    {
        $reconnectUrl = env('APP_URL', 'http://167.86.111.199')
                      . '/user/device/' . $device->uuid . '/qr';

        $waUrl = str_replace('localhost', '127.0.0.1', env('WA_SERVER_URL', 'http://127.0.0.1:8000'));

        // Pre-arrancar la sesión en modo QR para que el panel muestre el código inmediatamente.
        // Si la sesión ya existe (reconectando) la ignoramos.
        try {
            $sessCheck = Http::timeout(4)->get("{$waUrl}/sessions/find/device_{$device->id}");
            if (!$sessCheck->successful()) {
                Http::timeout(10)->post("{$waUrl}/sessions/add", [
                    'id'       => "device_{$device->id}",
                    'typeAuth' => 'qr',
                ]);
                Log::info("[BotHealthCheck] Sesión QR pre-arrancada para device_{$device->id}");
            }
        } catch (\Throwable $e) {
            Log::warning("[BotHealthCheck] No se pudo pre-arrancar sesión: " . $e->getMessage());
        }

        // Mensaje específico según plan del usuario:
        //  - Plan Starlink (id=4): el número se usa para alertas GPS automáticas a clientes
        //  - Plan BASICO/EMPRESA (5,6): el número es un BOT que responde clientes
        $userPlanId = optional($device->user)->plan_id;
        $msg = $this->buildOwnerMessage($device, $reconnectUrl, $userPlanId);

        // Itera hasta 5 emisores candidatos, usa el primero que responda
        $emitters = $this->findEmitterCandidates($device, 5);

        foreach ($emitters as $emitter) {
            try {
                $resp = Http::timeout(6)->post(
                    $waUrl . '/chats/send?id=device_' . $emitter->id,
                    [
                        'receiver' => $device->phone,
                        'message'  => ['text' => $msg],
                    ]
                );
                if ($resp->successful() && ($resp->json('success') === true)) {
                    $this->info("  ✉️ alerta enviada al {$device->phone} via device_{$emitter->id}");
                    return true;
                }
            } catch (\Throwable $e) {
                // continuar con el siguiente
            }
        }

        $this->warn('  no hay devices activos para emitir alerta WhatsApp (probados ' . count($emitters) . ')');

        // Fallback: WaSender API si está configurada
        return $this->alertViaWaSender($msg, $device);
    }

    /**
     * Genera el mensaje a enviar al dueño según su plan.
     *
     * Plan id=4 (Starlink): el WhatsApp se usa para alertas GPS automáticas a sus clientes/vehículos.
     * Plan id=5/6 (EMPRESA/BASICO): el WhatsApp es un BOT que responde a clientes.
     */
    private function buildOwnerMessage(Device $device, string $reconnectUrl, ?int $planId): string
    {
        if ($planId === 4) {
            // Plan Starlink — alertas GPS
            return "📵 *Se desconectó su número telefónico de las alertas de WhatsApp conectado al software GPS.*\n\n"
                 . "Mientras esté desconectado:\n"
                 . "• Sus clientes y vehículos NO recibirán las notificaciones automáticas por WhatsApp.\n"
                 . "• Los mensajes entrantes a este número no serán respondidos por el sistema.\n\n"
                 . "*Para seguir usándolo debe escanear el código QR de nuevo:*\n\n"
                 . "1️⃣ Ingrese a su panel del software GPS\n"
                 . "2️⃣ Vaya a la sección Devices / Dispositivos\n"
                 . "3️⃣ Seleccione *{$device->name}* y pulse Reconectar\n"
                 . "4️⃣ Escanee el código QR con la cámara de su WhatsApp\n\n"
                 . "🔗 Link directo:\n{$reconnectUrl}\n\n"
                 . "_Mensaje automático del sistema. No responda a este chat._";
        }

        // Planes BASICO ($20) / EMPRESA ($50) — bot conversacional
        return "📵 *Su bot de WhatsApp se desconectó.*\n\n"
             . "El número *{$device->phone}* ({$device->name}) ha perdido conexión con WhatsApp.\n\n"
             . "Mientras esté desconectado:\n"
             . "• Los mensajes entrantes NO serán respondidos por su bot.\n"
             . "• Las campañas y mensajes programados no se enviarán.\n"
             . "• Sus clientes pueden quedarse sin atención automática.\n\n"
             . "*Para reconectar el bot, escanee el código QR de nuevo:*\n\n"
             . "1️⃣ Ingrese a su panel\n"
             . "2️⃣ Vaya a Devices / Dispositivos\n"
             . "3️⃣ Seleccione *{$device->name}* y pulse Reconectar\n"
             . "4️⃣ Escanee el QR con la cámara de su WhatsApp\n\n"
             . "🔗 Link directo:\n{$reconnectUrl}\n\n"
             . "_Mensaje automático del sistema. No responda a este chat._";
    }

    /**
     * Busca candidatos a emisores (devices con creds.json y status=1, distintos al caído).
     * Devuelve hasta $limit ordenados: primero los del mismo usuario, luego los demás.
     *
     * @return Device[]
     */
    private function findEmitterCandidates(Device $broken, int $limit = 5): array
    {
        $all = Device::where('id', '!=', $broken->id)
            ->where('status', 1)
            ->get()
            ->filter(function ($d) {
                return file_exists("/var/www/html/whatstar/sessions/md_device_{$d->id}/creds.json");
            });

        $sameUser  = $all->where('user_id', $broken->user_id)->values();
        $otherUser = $all->where('user_id', '!=', $broken->user_id)->values();

        return array_slice($sameUser->merge($otherUser)->all(), 0, $limit);
    }

    /**
     * Si la falla es "creds.json missing" Y existe backup reciente, restaura
     * la carpeta del último snapshot de bot:backup-creds. El WA Server
     * reconecta automáticamente al detectar las creds.
     *
     * Casos donde NO funciona:
     * - WhatsApp deslogeó la sesión completamente (creds inválidas en lado WA)
     * - No hay snapshots
     * - La carpeta sessions/md_device_X tiene permisos rotos
     */
    private function tryAutoRecover(Device $device, array $issues): bool
    {
        // Solo intentar si el problema es creds faltantes
        $needsRestore = false;
        foreach ($issues as $issue) {
            if (str_contains($issue, 'creds.json missing')) {
                $needsRestore = true;
                break;
            }
        }
        if (!$needsRestore) return false;

        $sessionDir   = "/var/www/html/whatstar/sessions/md_device_{$device->id}";
        $backupBase   = '/backup/whatstar_sessions';

        if (!is_dir($backupBase)) return false;

        // Encontrar el snapshot más reciente que tenga este device
        $snaps = glob($backupBase . '/snap_*');
        rsort($snaps); // del más nuevo al más viejo

        foreach ($snaps as $snap) {
            $candidateDir = $snap . "/md_device_{$device->id}";
            if (!is_dir($candidateDir)) continue;
            if (!file_exists($candidateDir . '/creds.json')) continue;

            // Throttle: no recovery más de 1 vez cada 2 horas por device
            // (evita loops si el restore no resuelve el problema)
            $rKey = "bot_recover_throttle:{$device->id}";
            if (Cache::has($rKey)) {
                $this->warn("  device_{$device->id}: recovery throttled (intentado hace <2h)");
                return false;
            }
            Cache::put($rKey, 1, 7200);

            // Restaurar
            if (!is_dir($sessionDir)) {
                @mkdir($sessionDir, 0755, true);
                @chown($sessionDir, 'root');
            }

            $cmd = 'cp -r ' . escapeshellarg($candidateDir . '/.') . ' ' . escapeshellarg($sessionDir . '/');
            $output = []; $code = 0;
            exec($cmd . ' 2>&1', $output, $code);

            if ($code === 0) {
                $this->info("  📦 device_{$device->id}: restaurado desde " . basename($snap));
                Log::info("[BotHealthCheck] device_{$device->id} restored from " . basename($snap));
                return true;
            }

            $this->warn("  device_{$device->id}: restore falló: " . implode(' ', $output));
            Log::warning("[BotHealthCheck] device_{$device->id} restore failed");
            return false;
        }

        $this->warn("  device_{$device->id}: sin backups disponibles");
        return false;
    }

    private function alertViaWaSender(string $msg, Device $device): bool
    {
        $apiKey   = env('WASENDER_API_KEY');
        $alertTo  = env('APPOGIO_ALERT_PHONE', $device->phone); // mismo bot por default
        if (!$apiKey) return false;

        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(5)->post('https://www.wasenderapi.com/api/send-message', [
                'to'   => $alertTo,
                'text' => $msg,
            ]);
            return $resp->successful() && ($resp->json('success') === true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
