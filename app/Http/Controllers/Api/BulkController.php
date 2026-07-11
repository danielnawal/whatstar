<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Smstransaction;
use App\Models\Smstesttransactions;
use App\Http\Requests\Bulkrequest;
use App\Models\User;
use App\Models\App;
use App\Models\Device;
use App\Models\Contact;
use App\Models\Template;
use App\Models\Reply;
use App\Models\Webhook;
use App\Models\ChatbotSession;
use App\Models\ChatbotLead;
use App\Models\ChatbotHandoff;
use App\Models\Group;
use App\Models\Groupcontact;
use App\Services\ErpNextLeadService;
use App\Services\BotAiFallbackService;
use App\Services\AgentDispatcherService;
use App\Services\BotConversationalService;
use App\Services\BotAudioTranscribeService;
use App\Services\BotVisionService;
use App\Services\ClientSegmentService;
use App\Services\CotizacionPdfService;
use App\Services\LeadScoringService;
use App\Services\OutboundWebhookService;
use App\Services\SpamDetectionService;
use Carbon\Carbon;
use App\Traits\Whatsapp;
use Http;
use Auth;
use Str;
use DB;
use Session;
class BulkController extends Controller
{
    use Whatsapp;

    
    /**
     * sent message
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function submitRequest(Bulkrequest $request)
    {

       
        $user=User::where('status',1)->where('will_expire','>',now())->where('authkey',$request->authkey)->first();
        $app=App::where('key',$request->appkey)->whereHas('device')->with('device')->where('status',1)->first();

        if ($user == null || $app == null) {
            return response()->json(['error'=>'Invalid Auth and AppKey'],401);
        }

        if (getUserPlanData('messages_limit', $user->id) == false) {
            return response()->json([
                'message'=>__('Maximum Monthly Messages Limit Exceeded')
            ],401);  
        }

        if (!empty($request->template_id)) {

            $template = Template::where('user_id',$user->id)->where('uuid',$request->template_id)->where('status',1)->first();
            if (empty($template)) {
                return response()->json(['error'=>'Template Not Found'],401);
            }

            if (isset($template->body['text'])) {
                $body = $template->body;
                $text=$this->formatText($template->body['text'],[],$user);
                $text=$this->formatCustomText($text,$request->variables ?? []);
                $body['text'] = $text;
            }
            else{
                $body=$template->body;
            }
            $type = $template->type;

            
        }
        else{
            
            $text=$this->formatText($request->message);
            if(!empty($request->file)){
               
           
                    $explode=explode('.', $request->file);
                    $file_type=strtolower(end($explode));
                    $extentions=[
                        'jpg'=>'image',
                        'jpeg'=>'image',
                        'png'=>'image',
                        'webp'=>'image',
                        'pdf'=>'document',
                        'docx'=>'document',
                        'xlsx'=>'document',
                        'csv'=>'document',
                        'txt'=>'document'
                    ];
                   
                    if(!isset($extentions[$file_type])){
                        $validators['error'] = 'file type should be jpg,jpeg,png,webp,pdf,docx,xlsx,csv,txt';
                        return response()->json($validators,403);
                    }

                
                $body[$extentions[$file_type]]=['url' => $request->file];
                $body['caption'] = $text;
                $type='text-with-media';
            }
            else{
                $body['text'] = $text;
                $type='plain-text';
            }
            
        }

        if (!isset($body)) {
            return response()->json(['error'=>'Request Failed'],401);
        }    

        try {

            $response= $this->messageSend($body,$app->device_id,$request->to,$type,true);

            if (!is_array($response)) {
                return response()->json(['error'=>'Device not connected or unavailable'],422);
            }

            if (($response['status'] ?? null) == 200) {
                
                $logs['user_id']=$user->id;
                $logs['device_id']=$app->device_id;
                $logs['app_id']=$app->id;
                $logs['from']=$app->device->phone ?? null;
                $logs['to']=$request->to;
                $logs['template_id']=$template->id ?? null;
                $logs['type']='from_api';

                $this->saveLog($logs);

                return response()->json(['message_status'=>'Success','data'=>[
                    'from'=>$app->device->phone ?? null,
                    'to'=>$request->to,                
                    'status_code'=>200,
                ]],200);
            }
            else{
                return response()->json(['error'=>'Request Failed'],401);

            }

        } catch (\Throwable $e) {

         return response()->json(['error'=>'Request Failed'],401);
     }

 }


 /**
  * set status device
  * @param  \Illuminate\Http\Request  $request
  * @return \Illuminate\Http\Response
  */
  public function setStatus($device_id,$status){

       $device_id=str_replace('device_','',$device_id);

       $device=Device::where('id',$device_id)->first();
       if (!empty($device)) {
          $wasConnected = (int) $device->status === 1;
          $device->status=$status;
          $device->save();

          // Aviso de desconexión al número que configuró el cliente (solo en la
          // transición conectado -> desconectado, para no repetir avisos).
          if ($wasConnected && (int) $status === 0 && !empty($device->disconnect_alert_number)) {
              try { $this->notifyDisconnect($device); } catch (\Throwable $e) { /* nunca romper el webhook */ }
          }
       }


  }

  /**
   * Envía un aviso de "tu número se desconectó" al número configurado por el
   * cliente. Se envía desde OTRO número activo (nunca desde el que se cayó):
   * primero un número notificador del sistema (env DISCONNECT_NOTIFIER_DEVICE_ID),
   * si no, otro dispositivo activo del mismo usuario.
   */
  private function notifyDisconnect($device)
  {
      $to = preg_replace('/\D+/', '', (string) $device->disconnect_alert_number);
      $name = $device->name ?: ('device ' . $device->id);

      // Idioma de la cuenta: portugués si el número del bot o el destino es de Brasil (+55).
      $devPhone = preg_replace('/\D+/', '', (string) $device->phone);
      $isBrazil = (strpos($devPhone, '55') === 0) || (strpos($to, '55') === 0);

      if ($isBrazil) {
          $waText = "\u{1F6A8} *Suas alertas de WhatsApp foram desconectadas. Reconecte agora.*\n\n"
                  . "O numero \"{$name}\", que e o telefone de alertas da sua plataforma GPS, perdeu a conexao e parou de enviar alertas.\n"
                  . "Para reativar, entre em \"Saude dos bots\" ou \"Meus Dispositivos\" e escaneie o codigo QR (leva 1 minuto, voce nao perde regras nem contatos).";
          $mailSubject = 'Suas alertas de WhatsApp foram desconectadas';
      } else {
          $waText = "\u{1F6A8} *Se desconectaron tus alertas de WhatsApp. Reconecta ahora.*\n\n"
                  . "El numero \"{$name}\", que es el telefono de alertas de tu plataforma GPS, perdio la conexion y dejo de enviar alertas.\n"
                  . "Para reactivarlo entra a \"Salud de bots\" o \"Mis Dispositivos\" y escanea el codigo QR (toma 1 minuto, no pierdes reglas ni contactos).";
          $mailSubject = 'Se desconectaron tus alertas de WhatsApp';
      }

      // 1) WhatsApp: desde un número activo distinto del que se cayó.
      if ($to !== '') {
          $senderId = null;
          $notifier = env('DISCONNECT_NOTIFIER_DEVICE_ID');
          if (!empty($notifier)) {
              $n = Device::where('id', $notifier)->where('status', 1)->first();
              if ($n) { $senderId = $n->id; }
          }
          if (!$senderId) {
              $other = Device::where('user_id', $device->user_id)
                  ->where('status', 1)->where('id', '!=', $device->id)->first();
              if ($other) { $senderId = $other->id; }
          }
          if ($senderId) {
              // instant=true evita el sleep (esto corre dentro del webhook que envía node).
              try { $this->messageSend(['text' => $waText], $senderId, $to, 'plain-text', true, 0, true); }
              catch (\Throwable $e) { \Log::warning('[disconnect-alert] fallo WhatsApp', ['e' => $e->getMessage()]); }
          } else {
              \Log::warning('[disconnect-alert] sin remitente WhatsApp activo', ['device' => $device->id]);
          }
      }

      // 2) Refuerzo por correo al email de la cuenta (llega aunque WhatsApp falle).
      try {
          $user = User::where('id', $device->user_id)->first();
          if ($user && !empty($user->email)) {
              $emailBody = str_replace(['*', "\u{1F6A8} "], '', $waText);
              \Illuminate\Support\Facades\Mail::raw($emailBody, function ($m) use ($user, $mailSubject) {
                  $m->to($user->email)->subject($mailSubject);
              });
          }
      } catch (\Throwable $e) {
          \Log::warning('[disconnect-alert] fallo email', ['e' => $e->getMessage()]);
      }
  }


  /**
  * receive webhook response
  * @param  \Illuminate\Http\Request  $request
  * @return \Illuminate\Http\Response
  */
  public function webHook(Request $request, $device_id)
  {
      $session    = $device_id;
      $device_id  = (int) str_replace('device_', '', $device_id);

      $device = Device::with('user')
          ->whereHas('user', fn($q) => $q->where('will_expire', '>', now()))
          ->where('id', $device_id)
          ->first();

      // ── Actualización de estado de conexión ────────────────────────
      if ($request->type === 'CONNECTION_UPDATE') {
          if (isset($request->data['connection']) && $device) {
              $device->status = $request->data['connection'] === 'open' ? 1 : 0;
              $device->save();
          }
          return response()->json(['ok' => true]);
      }

      if (!$device) {
          return response()->json(['ok' => true]);
      }

      // ── Bot solo activo en devices que tienen reglas configuradas ───
      // Multi-tenant: cada user de WhatStar configura sus reglas vía panel.
      // Sin reglas = sin bot, no se ejecuta LLM ni captura ni matching.
      $hasBot = \DB::table('replies')->where('device_id', $device->id)->exists();
      if (!$hasBot) {
          return response()->json(['ok' => true]);
      }

      // ── Extraer remitente ───────────────────────────────────────────
      $jid  = $request->data[0]['key']['remoteJidAlt']
            ?? $request->data[0]['key']['remoteJid']
            ?? '';
      if (!$jid) return response()->json(['ok' => true]);

      // Ignorar mensajes de grupos y broadcast
      if (str_contains($jid, '@g.us') || str_contains($jid, '@broadcast')) {
          return response()->json(['ok' => true]);
      }
      // Detección extra de grupos modernos (WhatsApp ahora usa @lid o sufijo vacío
      // para algunos grupos). Los IDs de grupo siempre empiezan con "120363" o
      // tienen el formato antiguo "<digits>-<digits>" antes del @.
      $baseId = explode('@', $jid)[0];
      if (str_starts_with($baseId, '120363')
          || (preg_match('/^\d+-\d+$/', $baseId))
          || (ctype_digit($baseId) && strlen($baseId) >= 17)) {
          return response()->json(['ok' => true]);
      }

      $from = str_ends_with($jid, '@lid')
          ? $jid
          : (explode('@', $jid)[0] ?? null);

      if (!$from) return response()->json(['ok' => true]);

      // ── Extraer nombre WhatsApp (pushName) si vino en el payload ────
      $pushName = trim((string) ($request->data[0]['pushName'] ?? ''));

      // ── Deduplicación de webhooks duplicados de WhatsApp ───────────
      // WhatsApp a veces reenvía el mismo webhook 2 veces. Usamos el key.id
      // del mensaje como llave de idempotencia (TTL 60s).
      $waMsgId = $request->data[0]['key']['id'] ?? $request->data[0]['key']['ID'] ?? null;
      if ($waMsgId) {
          $dedupKey = 'wa_msg_' . $device->id . '_' . $waMsgId;
          if (\Illuminate\Support\Facades\Cache::has($dedupKey)) {
              return response()->json(['ok' => true]);
          }
          \Illuminate\Support\Facades\Cache::put($dedupKey, 1, 60);
      }

      // ── Capturar TODOS los contactos entrantes en lista de difusión ─
      $this->captureInboundContact($device, $from, $pushName);

      // ── Extraer texto (incluyendo respuestas interactivas) ──────────
      $interactive = $request->data[0]['_interactive'] ?? null; // añadido por Node.js

      $message = trim(
          $interactive['selectedText']
          ?? $interactive['selectedId']
          ?? $request->data[0]['message']['conversation']
          ?? $request->data[0]['message']['extendedTextMessage']['text']
          ?? ''
      );

      // También guardar el ID seleccionado para matching exacto en flujos
      $selectedId = $interactive['selectedId'] ?? null;

      // ── Guardar en hook externo si está configurado ─────────────────
      if ($device->hook_url && isset($request->data[0]['message'])) {
          $hook            = new Webhook;
          $hook->device_id = $device->id;
          $hook->user_id   = $device->user_id;
          $hook->payload   = json_encode([
              'payload'  => $request->all(),
              'sender'   => $from,
              'receiver' => $device->phone ?? '',
          ]);
          $hook->hook = $device->hook_url;
          $hook->save();
      }

      // ── Detección de mensaje multimedia (audio/imagen/video/PDF/ubicación) ─
      $mediaType = $this->detectMediaType($request->data[0]['message'] ?? []);

      // ── Voice notes: si es audio sin texto, transcribir y procesar como texto ──
      // Si Whisper transcribe OK, $message se rellena y el flujo continúa normal
      // (LLM conversacional, BANT, reglas, etc) sin pasar por handoff.
      // Si la transcripción falla, cae al handoff multimedia (comportamiento previo).
      $isVoiceNote = false;
      $isImage     = false;
      if ($mediaType === 'audio' && $message === '') {
          $messageId = $request->data[0]['key']['id']
                    ?? $request->data[0]['key']['ID']
                    ?? null;
          $rawJid    = $request->data[0]['key']['remoteJid'] ?? $jid;
          if ($messageId && $rawJid) {
              $transcribed = (new BotAudioTranscribeService())->transcribe($device, $rawJid, $messageId);
              if ($transcribed) {
                  $message     = $transcribed;
                  $isVoiceNote = true;
              }
          }
      }

      // ── Vision: si es imagen, describir con Gemini Vision y procesar como texto ──
      // El LLM conversacional recibe la descripción enriquecida y puede responder
      // en contexto (ej: "veo que mandaste foto de un GPS Coban GT06...").
      // Si Vision falla, cae al handoff multimedia.
      if ($mediaType === 'image') {
          $messageId = $request->data[0]['key']['id']
                    ?? $request->data[0]['key']['ID']
                    ?? null;
          $rawJid    = $request->data[0]['key']['remoteJid'] ?? $jid;
          $caption   = $request->data[0]['message']['imageMessage']['caption'] ?? '';

          if ($messageId && $rawJid) {
              $visionDesc = (new BotVisionService())->describe($device, $rawJid, $messageId, $caption);
              if ($visionDesc) {
                  // Combinar descripción + caption (si hubo) como texto del cliente
                  $message = $visionDesc;
                  if (trim($caption) !== '') {
                      $message .= "\n\n[Caption del cliente]: " . $caption;
                  }
                  $isImage = true;
              }
          }
      }

      if ($message === '' && !$selectedId) {
          // Otros media (imagen/video/doc/ubicación) o audio que falló transcripción → handoff
          if ($mediaType) {
              $this->handleInboundMedia($device, $from, $mediaType, $request->data[0]['message'] ?? []);
          }
          return response()->json(['ok' => true]);
      }

      // ── Sesión del contacto (crea si no existe) ─────────────────────
      $isNew       = !ChatbotSession::where('device_id', $device->id)->where('contact', $from)->exists();
      $chatSession = ChatbotSession::firstOrNew(['device_id' => $device->id, 'contact' => $from]);
      if ($isNew) {
          $chatSession->is_new_contact = 1;
          $chatSession->save();
      }

      // ── Números con código de país +55 (Brasil) → portugués fijo ────
      if (!$chatSession->locked_language && ctype_digit($from) && str_starts_with($from, '55')) {
          $chatSession->locked_language = 'pt';
          $chatSession->save();
      }

      // ── Si el bot está pausado (handoff activo), no responder ───────
      // Comando #bot reactiva al instante. Si paused_until expiró, reactivar automáticamente.
      if ($chatSession->is_paused) {
          if (strtolower(trim($message)) === '#bot') {
              $chatSession->is_paused = 0;
              $chatSession->paused_until = null;
              $chatSession->save();
              $this->sendRawText($device, $from, '✅ Bot reactivado. El asistente automático vuelve a estar activo.');
              return response()->json(['ok' => true]);
          }

          $expired = $chatSession->paused_until
              && Carbon::parse($chatSession->paused_until)->lte(Carbon::now());

          if ($expired) {
              $chatSession->is_paused = 0;
              $chatSession->paused_until = null;
              $chatSession->save();
              // Cae al flujo normal abajo
          } else {
              return response()->json(['ok' => true]);
          }
      }

      // ── Anti-spam: bloquear contactos que abusan ─────────────────────
      // Si score >= THRESHOLD_BLOCK, no responder ni guardar nada (silencioso).
      // Si throttle, ignora hasta que pase el cooldown. warn solo loggea.
      if ($message !== '') {
          $spam = (new SpamDetectionService())->evaluate($message, $chatSession);
          if ($spam['action'] === 'block') {
              \Illuminate\Support\Facades\Log::info("[Spam BLOCK] device_{$device->id} {$from}: {$spam['reason']} (score={$spam['score']})");
              return response()->json(['ok' => true]);
          }
          if ($spam['action'] === 'throttle') {
              \Illuminate\Support\Facades\Log::info("[Spam THROTTLE] device_{$device->id} {$from}: {$spam['reason']} (score={$spam['score']})");
              return response()->json(['ok' => true]);
          }
          if ($spam['action'] === 'warn') {
              \Illuminate\Support\Facades\Log::warning("[Spam WARN] device_{$device->id} {$from}: {$spam['reason']} (score={$spam['score']})");
              // continuar normal
          }
      }

      // ── Captura de campo en progreso ────────────────────────────────
      if ($chatSession->capturing_field && $message !== '') {
          $this->processCaptureField($device, $from, $message, $chatSession, $session);
          return response()->json(['ok' => true]);
      }

      // ── Flujo BANT en progreso (captura nombre/cantidad antes del precio) ──
      if ($chatSession->bant_step && $message !== '') {
          $this->advanceBantFlow($device, $from, $message, $chatSession);
          return response()->json(['ok' => true]);
      }

      // ── Respuesta NPS pendiente: si vino un número 1-5, guardarlo ─────
      if ($chatSession->nps_pending && $message !== '') {
          if ($this->captureNpsResponse($device, $from, $message, $chatSession)) {
              return response()->json(['ok' => true]);
          }
          // Si el cliente escribió otra cosa, sigue al flujo normal
      }

      // Asegurar que la sesión esté persistida (necesitamos su id para historial)
      if (!$chatSession->id) {
          $chatSession->save();
      }

      // ── Capa conversacional con memoria (LLM intent + history) ──────
      // Antes de intentar matching de keywords, usamos el LLM para clasificar
      // intención y extraer lead_data manteniendo coherencia con el historial.
      // Si decide una regla → usamos esa regla. Si no, cae al matching clásico.
      // Si el LLM falla por cualquier razón → comportamiento original (keywords).
      $conv      = null;
      $convReply = null;
      $convSvc   = new BotConversationalService();
      // Bypass LLM para selecciones de menú numéricas (1-9 o comandos cortos como #bot)
      // — el LLM no tiene contexto del menú y clasifica "2" como unknown, generando
      //   respuesta "no entendí" en lugar de dejar que la regla #7 (equal "2") dispare.
      $skipLlmNumeric = preg_match('/^\d{1,2}$/', trim($message));
      if ($message !== '' && !$selectedId && !$skipLlmNumeric) {
          $conv = $convSvc->process($message, $chatSession, $device);

          if ($conv) {
              // Auto-poblar lead_data si el LLM extrajo nombre/cantidad/empresa
              if (!empty($conv['lead_data'])) {
                  $current = json_decode($chatSession->lead_data ?? '{}', true) ?: [];
                  $changed = false;
                  foreach ($conv['lead_data'] as $k => $v) {
                      if ($v !== null && $v !== '' && empty($current[$k])) {
                          $current[$k] = is_string($v) ? mb_substr($v, 0, 80) : $v;
                          $changed = true;
                      }
                  }
                  if ($changed) {
                      $chatSession->lead_data = json_encode($current, JSON_UNESCAPED_UNICODE);
                      $chatSession->save();
                      // Si el LLM completó datos BANT (nombre + cantidad), persistir el lead
                      // y enviar PDF de cotización si la regla a despachar es de pricing.
                      if ($this->hasBantData($chatSession)) {
                          $this->upsertLeadFromBant($device, $from, $chatSession, $current);
                          if (($conv['rule_id'] ?? null) === 11) {
                              // Mandar PDF como adjunto cuando va a recibir precio
                              $this->sendCotizacionPdf($device, $from, $current, $chatSession->locked_language ?: 'es');
                          }
                      }
                  }
              }

              // Lock del idioma si el LLM lo detectó y aún no estaba bloqueado
              if (!$chatSession->locked_language && in_array($conv['language'] ?? '', ['es', 'en', 'pt'])) {
                  $chatSession->locked_language = $conv['language'];
                  $chatSession->save();
              }

              if ($conv['rule_id']) {
                  $convReply = Reply::where('device_id', $device->id)
                      ->where('id', $conv['rule_id'])
                      ->with('template')
                      ->first();
              }
          }
      }

      // Persistir mensaje entrante en historial conversacional + alerta sentiment
      if ($message !== '') {
          $convSvc->recordMessage(
              $chatSession, $device, 'user', $message,
              $conv['intent'] ?? null, null,
              $conv['sentiment'] ?? null
          );

          // Alerta al agente si sentiment negative/urgent (solo en horario humano,
          // y máximo 1 vez por sesión por hora para evitar spam)
          $sent = $conv['sentiment'] ?? null;
          if (in_array($sent, ['negative', 'urgent'], true) && $this->isWithinHumanHours()) {
              $this->maybeAlertSentiment($device, $chatSession, $message, $sent);
          }
      }

      // ── Buscar regla que coincida ───────────────────────────────────
      // Prioridad: (1) regla del LLM si la decidió, (2) selectedId interactivo,
      // (3) matching de keywords clásico (fallback)
      $reply = $convReply;
      if (!$reply && $selectedId) {
          $reply = $this->findMatchingReply($device->id, $selectedId, $chatSession);
      }
      if (!$reply && $message !== '') {
          $reply = $this->findMatchingReply($device->id, $message, $chatSession);
      }

      // Si el LLM no eligió regla pero produjo un reply_text natural (intent=unknown
      // resuelto por el LLM), enviarlo directamente sin pasar por el catch-all.
      if (!$convReply && $conv && !empty($conv['reply_text'])) {
          $this->sendRawText($device, $from, $conv['reply_text']);
          $chatSession->last_reply_at  = Carbon::now();
          $chatSession->is_new_contact = 0;
          $chatSession->save();
          $convSvc->recordMessage(
              $chatSession, $device, 'assistant', $conv['reply_text'],
              $conv['intent'] ?? null
          );
          return response()->json(['ok' => true]);
      }

      // ── Mensaje de bienvenida para primer contacto ──────────────────
      if (!$reply && $chatSession->is_new_contact) {
          $reply = Reply::where('device_id', $device->id)
              ->whereNull('parent_reply_id')
              ->where('match_type', 'first_contact')
              ->with('template')
              ->orderByDesc('priority')
              ->first();
      }

      // ── AI fallback antes del catch-all ─────────────────────────────
      // Si la regla matcheada es el catch-all genérico (match_type=any),
      // intentamos primero una respuesta natural con Claude Haiku.
      // Si la AI devuelve algo válido, lo usamos en vez del catch-all.
      // Si falla (sin API key, error, banned terms), cae al catch-all.
      if ($reply && $reply->match_type === 'any' && $message !== '' && !$selectedId) {
          $aiText = (new BotAiFallbackService())->answer($message, $chatSession, $device);
          if ($aiText) {
              $this->sendRawText($device, $from, $aiText);
              $chatSession->last_reply_at = Carbon::now();
              $chatSession->is_new_contact = 0;
              $chatSession->save();
              return response()->json(['ok' => true]);
          }
      }

      if (!$reply) {
          return response()->json(['ok' => true]);
      }

      // Persistir en historial qué regla se va a despachar (para memoria conversacional)
      if ($message !== '') {
          $variantIdx = $reply->getAttribute('_chosen_variant_index');
          $convSvc->recordMessage(
              $chatSession, $device, 'assistant',
              mb_substr($reply->reply ?? '', 0, 500),
              $conv['intent'] ?? null,
              $reply->id,
              null,
              $variantIdx === null ? null : (int) $variantIdx
          );
      }

      // ── Auto-detección de idioma: si la regla tiene variante (reply_en/reply_pt)
      //    y el mensaje del cliente es EN o PT, usar esa variante. Si no hay variante,
      //    se mantiene el reply original (fallback transparente).
      if ($message !== '') {
          $this->applyLanguageVariant($reply, $message, $chatSession);
      }

      // ── Variabilidad de respuestas: si la regla tiene reply_variants (JSON
      //    array), elegir uno al azar para que cada cliente vea texto distinto.
      //    Fallback transparente: si no hay variantes o son inválidas, se usa
      //    el reply original.
      $this->applyReplyVariant($reply);

      // ── Verificar horario de atención ──────────────────────────────
      if ($reply->schedule_enabled) {
          $now       = Carbon::now();
          $days      = explode(',', $reply->schedule_days ?? '1,2,3,4,5,6,7');
          $dayOfWeek = $now->isoFormat('E');
          $inSchedule = in_array($dayOfWeek, $days)
              && $now->format('H:i:s') >= ($reply->schedule_start ?? '00:00:00')
              && $now->format('H:i:s') <= ($reply->schedule_end   ?? '23:59:59');
          if (!$inSchedule) {
              if ($reply->out_of_hours_reply) {
                  $this->sendRawText($device, $from, $reply->out_of_hours_reply);
              }
              return response()->json(['ok' => true]);
          }
      }

      // ── Verificar cooldown ──────────────────────────────────────────
      if ($reply->cooldown_minutes > 0 && $chatSession->last_reply_at) {
          $next = Carbon::parse($chatSession->last_reply_at)->addMinutes($reply->cooldown_minutes);
          if (Carbon::now()->lt($next)) return response()->json(['ok' => true]);
      }

      // ── Verificar only_once ─────────────────────────────────────────
      if ($reply->only_once && in_array($reply->id, $chatSession->getRepliedIdsArray())) {
          return response()->json(['ok' => true]);
      }

      // ── BANT: si matchea regla precio (#11) y faltan datos, capturar primero ──
      if ($reply->id == 11 && !$this->hasBantData($chatSession)) {
          $this->startBantFlow($device, $from, $chatSession);
          return response()->json(['ok' => true]);
      }

      // ── Disparar handoff si corresponde ────────────────────────────
      // Solo dentro del horario humano (8:00–19:00 hora Colombia). Fuera de
      // ese rango, enviamos un mensaje de "fuera de horario" en el idioma
      // detectado y NO pausamos el bot — el cliente puede seguir consultando
      // hasta que un asesor esté disponible.
      if ($reply->trigger_handoff) {
          if ($this->isWithinHumanHours()) {
              $this->triggerHandoff($device, $from, $message, $chatSession);
              $body = ['text' => $this->personalizeText($reply->reply ?? '', $chatSession)];
              $this->dispatchReply($device, $from, $body, 'plain-text', $chatSession, $reply, $session);
          } else {
              $lang = $this->resolveLanguage($message, $chatSession);
              $this->sendRawText($device, $from, $this->outOfHoursMessage($lang));
          }
          return response()->json(['ok' => true]);
      }

      // ── Si la regla captura un campo, preparar la siguiente pregunta ─
      if ($reply->capture_field) {
          $chatSession->capturing_field = $reply->capture_field;
          // No guardamos todavía, dispatchReply lo hará
      }

      // ── Preparar y enviar respuesta ─────────────────────────────────
      $this->buildAndDispatch($device, $from, $reply, $chatSession, $session);

      // ── Captura de lead para reglas de alto interés (sin handoff) ──
      // Criterio: reglas con media adjunta (PDF) o keyword de intención de compra.
      // Las reglas con trigger_handoff ya capturan su lead dentro de triggerHandoff().
      $this->captureLeadIfHighIntent($device, $from, $reply, $message, $chatSession);

      return response()->json(['ok' => true]);
  }

  // ============================================================
  //  MÉTODOS AUXILIARES DEL CHATBOT
  // ============================================================

  /**
   * Construye el mensaje y lo envía según el tipo de la regla
   * (texto, template, lista interactiva, botones).
   */
  private function buildAndDispatch(Device $device, string $from, Reply $reply,
                                    ChatbotSession $session, string $sessionId): void
  {
      $replyText = $this->personalizeText($reply->reply ?? '', $session);

      // Mensaje interactivo tipo LISTA
      if ($reply->interactive_type === 'list') {
          $opts     = json_decode($reply->interactive_options ?? '[]', true);
          $sections = [];
          $rows     = [];
          foreach ($opts as $opt) {
              $rows[] = [
                  'rowId'       => $opt['id']          ?? Str::slug($opt['text'] ?? ''),
                  'title'       => $opt['text']         ?? '',
                  'description' => $opt['description']  ?? '',
              ];
          }
          $sections[] = ['title' => '', 'rows' => $rows];

          Http::post(env('WA_SERVER_URL') . '/chats/send-list?id=device_' . $device->id, [
              'receiver'   => $from,
              'title'      => $replyText,
              'body'       => $reply->reply_footer ?? '',
              'buttonText' => $reply->button_label ?? 'Ver opciones',
              'sections'   => $sections,
          ]);

          $this->dispatchMediaIfAny($device, $from, $reply);
          $this->finishDispatch($device, $from, $session, $reply);
          return;
      }

      // Mensaje interactivo tipo BOTONES
      if ($reply->interactive_type === 'buttons') {
          $opts    = json_decode($reply->interactive_options ?? '[]', true);
          $buttons = array_map(fn($o) => [
              'id'   => $o['id']   ?? Str::slug($o['text'] ?? ''),
              'text' => $o['text'] ?? '',
          ], $opts);

          Http::post(env('WA_SERVER_URL') . '/chats/send-buttons?id=device_' . $device->id, [
              'receiver' => $from,
              'text'     => $replyText,
              'buttons'  => array_slice($buttons, 0, 3),
          ]);

          $this->dispatchMediaIfAny($device, $from, $reply);
          $this->finishDispatch($device, $from, $session, $reply);
          return;
      }

      // Mensaje con template
      if ($reply->reply_type === 'template' && $reply->template) {
          $template = $reply->template;
          $body     = $template->body;
          if (isset($body['text'])) {
              $body['text'] = $this->personalizeText($body['text'], $session);
          }
          $this->messageSend($body, $device->id, $from, $template->type, true, 0, true);
          $this->dispatchMediaIfAny($device, $from, $reply);
          $this->finishDispatch($device, $from, $session, $reply, $template->id ?? null);
          return;
      }

      // Texto plano (con o sin media adjunta).
      // Si el reply tiene media_path, se envía como text-with-media (texto = caption del adjunto)
      // en un solo mensaje. Si no, va como plain-text como antes.
      if (!empty($reply->media_path)) {
          $this->messageSend(
              [
                  'message'         => $replyText,
                  'attachment'      => $reply->media_path,
                  'attachment_name' => $reply->media_filename ?? basename($reply->media_path),
              ],
              $device->id, $from, 'text-with-media', false, 0, true
          );
      } else {
          $this->messageSend(['text' => $replyText], $device->id, $from, 'plain-text', true, 0, true);
      }
      $this->finishDispatch($device, $from, $session, $reply);
  }

  /**
   * Envía el archivo adjunto (PDF, imagen, etc.) sin caption — útil después
   * de mensajes interactivos (list/buttons) o templates donde el texto ya fue enviado.
   */
  private function dispatchMediaIfAny(Device $device, string $from, Reply $reply): void
  {
      if (empty($reply->media_path)) return;

      $this->messageSend(
          [
              'message'         => '',
              'attachment'      => $reply->media_path,
              'attachment_name' => $reply->media_filename ?? basename($reply->media_path),
          ],
          $device->id, $from, 'text-with-media', false, 0, true
      );
  }

  /**
   * Actualiza la sesión y guarda el log después de enviar.
   */
  private function finishDispatch(Device $device, string $from, ChatbotSession $session,
                                   Reply $reply, ?int $templateId = null): void
  {
      $hasChildren = Reply::where('parent_reply_id', $reply->id)->exists();
      $session->current_reply_id = $hasChildren ? $reply->id : null;
      $session->last_reply_at    = now();
      $session->is_new_contact   = 0;
      if ($reply->only_once) $session->addRepliedId($reply->id);
      if (!$reply->capture_field) $session->capturing_field = null;
      $session->save();

      $this->saveLog([
          'user_id'     => $device->user_id,
          'device_id'   => $device->id,
          'from'        => $device->phone ?? null,
          'to'          => $from,
          'type'        => 'chatbot',
          'template_id' => $templateId,
      ]);
  }

  /**
   * Procesa la captura de un campo de lead cuando el bot está esperando respuesta.
   */
  private function processCaptureField(Device $device, string $from, string $value,
                                        ChatbotSession $session, string $sessionId): void
  {
      $field = $session->capturing_field;

      // Obtener o crear el lead
      $lead = ChatbotLead::firstOrNew(['device_id' => $device->id, 'contact' => $from]);
      $lead->user_id = $device->user_id;
      $lead->setDataField($field, $value);
      $lead->save();

      // Sincronizar lead_data en la sesión
      $leadData         = json_decode($session->lead_data ?? '{}', true);
      $leadData[$field] = $value;
      $session->lead_data      = json_encode($leadData, JSON_UNESCAPED_UNICODE);
      $session->capturing_field = null;

      // Avanzar al siguiente paso en el flujo (si hay una regla hija de tipo capture siguiente)
      if ($session->current_reply_id) {
          $nextCapture = Reply::where('device_id', $device->id)
              ->where('parent_reply_id', $session->current_reply_id)
              ->whereNotNull('capture_field')
              ->orderByDesc('priority')
              ->first();

          if ($nextCapture) {
              $session->capturing_field = $nextCapture->capture_field;
              $session->save();
              $this->buildAndDispatch($device, $from, $nextCapture, $session, $sessionId);
              return;
          }
      }

      $session->save();
  }

  /**
   * ¿Hay asesores humanos disponibles ahora? Horario fijo 8:00–19:00 hora Colombia.
   */
  private function isWithinHumanHours(): bool
  {
      $now = Carbon::now('America/Bogota');
      $hour = (int) $now->format('H');
      return $hour >= 8 && $hour < 19;
  }

  /**
   * Mensaje de fuera-de-horario localizado, con la próxima hora de atención.
   */
  private function outOfHoursMessage(string $lang): string
  {
      $now  = Carbon::now('America/Bogota');
      $next = (int) $now->format('H') < 8
          ? $now->copy()->setTime(8, 0)
          : $now->copy()->addDay()->setTime(8, 0);

      $diff = (int) abs($now->diffInMinutes($next));
      $h    = intdiv($diff, 60);
      $m    = $diff % 60;

      return match ($lang) {
          'en' => "🕐 Our human advisors are available *Mon–Sun, 8:00 AM – 7:00 PM (Colombia time / GMT-5)*.\n"
                . "Right now we're outside of those hours — next slot in about *{$h}h {$m}m*.\n\n"
                . "I'll keep helping you here in the meantime. Tell me what you need (pricing, demo, GPS hardware, integrations, etc.) and an advisor will reach out as soon as we open. ✅",
          'pt' => "🕐 Nossos consultores humanos atendem *seg–dom, das 8:00 às 19:00 (horário Colômbia / GMT-5)*.\n"
                . "Agora estamos fora desse horário — próxima janela em cerca de *{$h}h {$m}min*.\n\n"
                . "Posso continuar te ajudando por aqui. Me conta o que precisa (preços, demo, equipamentos GPS, integrações, etc.) e um consultor entra em contato assim que abrirmos. ✅",
          default => "🕐 Nuestros asesores humanos atienden *lun–dom de 8:00 AM a 7:00 PM (hora Colombia / GMT-5)*.\n"
                . "Ahora estamos fuera de horario — próxima ventana en aproximadamente *{$h}h {$m}m*.\n\n"
                . "Mientras tanto puedo seguir ayudándote por aquí. Cuéntame qué necesitas (precios, demo, equipos GPS, integraciones, etc.) y un asesor te contacta apenas abramos. ✅",
      };
  }

  /**
   * Captura respuesta NPS 1-5 del cliente. Devuelve true si se procesó como NPS.
   * Si el mensaje no es un número 1-5, devuelve false y el flujo sigue normal.
   */
  private function captureNpsResponse(Device $device, string $from, string $message, ChatbotSession $session): bool
  {
      if (!preg_match('/^\s*([1-5])\s*$/', $message, $m)) {
          return false;
      }
      $score = (int) $m[1];

      // Buscar el handoff más reciente cerrado con NPS pendiente
      $handoff = ChatbotHandoff::where('device_id', $device->id)
          ->where('contact', $from)
          ->where('status', 'closed')
          ->whereNull('nps')
          ->orderByDesc('resolved_at')
          ->first();

      if ($handoff) {
          $handoff->nps = $score;
          $handoff->save();
      }

      $session->nps_pending = 0;
      $session->save();

      // Webhook salida: evento nps.received
      (new OutboundWebhookService())->emit('nps.received', [
          'handoff_id' => $handoff?->id,
          'device_id'  => $device->id,
          'contact'    => $from,
          'score'      => $score,
          'language'   => $this->sessionLang($session),
      ]);

      $lang = $this->sessionLang($session);
      $thanks = match (true) {
          $score >= 4 => match ($lang) {
              'en' => "🙏 Thanks for the *{$score}/5*! Glad to hear it. If you need anything else, type *menu* anytime.",
              'pt' => "🙏 Obrigado pelo *{$score}/5*! Que bom! Se precisar de algo, digite *menu* a qualquer momento.",
              default => "🙏 ¡Gracias por el *{$score}/5*! Nos alegra saberlo. Si necesitas algo más, escribe *menu* cuando quieras.",
          },
          $score === 3 => match ($lang) {
              'en' => "🙏 Thanks for the *{$score}/5*. We'd love to know what we can improve — feel free to share. Type *asesor* to talk to us.",
              'pt' => "🙏 Obrigado pelo *{$score}/5*. Gostaríamos de saber o que podemos melhorar — fique à vontade para compartilhar. Digite *asesor*.",
              default => "🙏 Gracias por el *{$score}/5*. Nos encantaría saber qué podemos mejorar — cuéntanos. Escribe *asesor*.",
          },
          default => match ($lang) {
              'en' => "🙏 Thanks for the *{$score}/5* — we want to make this right. A senior advisor will reach out shortly.",
              'pt' => "🙏 Obrigado pelo *{$score}/5* — queremos resolver isso. Um consultor sênior vai te procurar em breve.",
              default => "🙏 Gracias por el *{$score}/5* — queremos solucionarlo. Un asesor sénior te contactará pronto.",
          },
      };

      $this->sendRawText($device, $from, $thanks);

      // Si el NPS es bajo (≤2), levantar handoff prioritario al agente
      if ($score <= 2 && $handoff) {
          $meta        = json_decode($device->meta ?? '{}', true);
          $agentNumber = $meta['agent_number'] ?? null;
          if ($agentNumber) {
              $name = $handoff->contact_name ?: $from;
              $alert = "🚨 *NPS BAJO ({$score}/5)*\n"
                     . "👤 {$name} ({$from})\n"
                     . "💬 Handoff #{$handoff->id} cerrado el " . Carbon::parse($handoff->resolved_at)->format('d/m H:i') . "\n\n"
                     . "Ofrecer recuperación urgente.";
              try {
                  Http::post(env('WA_SERVER_URL') . '/chats/send?id=device_' . $device->id, [
                      'receiver' => $agentNumber,
                      'message'  => ['text' => $alert],
                  ]);
              } catch (\Throwable $e) {
                  // no-op
              }
          }
      }

      return true;
  }

  /**
   * Detecta el tipo de media en un payload de mensaje Baileys.
   * Devuelve 'audio'|'image'|'video'|'document'|'location'|'sticker'|'contact' o null.
   */
  private function detectMediaType(array $message): ?string
  {
      if (empty($message)) return null;
      foreach ([
          'audioMessage' => 'audio',
          'imageMessage' => 'image',
          'videoMessage' => 'video',
          'documentMessage' => 'document',
          'documentWithCaptionMessage' => 'document',
          'locationMessage' => 'location',
          'liveLocationMessage' => 'location',
          'stickerMessage' => 'sticker',
          'contactMessage' => 'contact',
          'contactsArrayMessage' => 'contact',
      ] as $key => $type) {
          if (isset($message[$key])) return $type;
      }
      return null;
  }

  /**
   * Maneja un mensaje multimedia entrante: ack al cliente y notifica al agente.
   */
  private function handleInboundMedia(Device $device, string $from, string $mediaType, array $message): void
  {
      $session = ChatbotSession::firstOrNew(['device_id' => $device->id, 'contact' => $from]);
      if (!$session->exists) {
          $session->is_new_contact = 1;
      }
      $session->save();

      // Si la sesión ya está pausada (humano atendiendo), no enviamos
      // duplicación: el agente verá el media en su panel.
      if ($session->is_paused) {
          $expired = $session->paused_until && Carbon::parse($session->paused_until)->lte(Carbon::now());
          if (!$expired) return;
      }

      $lang  = $session->locked_language ?: 'es';
      $label = $this->mediaLabel($mediaType, $lang);

      $caption = $message[$mediaType . 'Message']['caption']
              ?? $message['documentMessage']['caption']
              ?? '';

      // Dentro de horario: handoff con notificación al agente
      if ($this->isWithinHumanHours()) {
          $ackText = match ($lang) {
              'en' => "📎 Got your *{$label}*. An advisor will review it and reply shortly. ✅",
              'pt' => "📎 Recebemos seu *{$label}*. Um consultor vai analisar e responder em breve. ✅",
              default => "📎 Recibimos tu *{$label}*. Un asesor lo revisa y te responde enseguida. ✅",
          };
          $this->sendRawText($device, $from, $ackText);
          $this->triggerHandoff(
              $device,
              $from,
              "[{$mediaType}] " . ($caption ?: '(sin texto)'),
              $session
          );
          // Dar opciones de texto mientras espera al asesor
          $menuHint = match ($lang) {
              'en' => "Meanwhile you can write a word to get instant info:\n\n💰 *PRICE* — plans and costs\n🎮 *DEMO* — free trial\n📋 *MENU* — all options",
              'pt' => "Enquanto isso, escreva uma palavra para info rápida:\n\n💰 *PREÇO* — planos e custos\n🎮 *DEMO* — teste grátis\n📋 *MENU* — todas as opções",
              default => "Mientras esperas, también puedes escribir una palabra:\n\n💰 *PRECIO* — ver planes y costos\n🎮 *DEMO* — acceso de prueba\n📋 *MENÚ* — ver todas las opciones",
          };
          $this->sendRawText($device, $from, $menuHint);
          return;
      }

      // Fuera de horario: ack + opciones de texto para no dejar al cliente sin respuesta
      $offHours = $this->outOfHoursMessage($lang);
      $ackText = match ($lang) {
          'en' => "📎 Got your *{$label}*. {$offHours}",
          'pt' => "📎 Recebemos seu *{$label}*. {$offHours}",
          default => "📎 Recibimos tu *{$label}*. {$offHours}",
      };
      $this->sendRawText($device, $from, $ackText);
      $menuHint = match ($lang) {
          'en' => "Meanwhile, write a word for instant info:\n\n💰 *PRICE* — plans and costs\n🎮 *DEMO* — free trial\n📋 *MENU* — all options",
          'pt' => "Enquanto isso, escreva uma palavra:\n\n💰 *PREÇO* — planos\n🎮 *DEMO* — teste\n📋 *MENU* — opções",
          default => "Mientras tanto, puedes escribir:\n\n💰 *PRECIO* — ver planes\n🎮 *DEMO* — acceso de prueba\n📋 *MENÚ* — todas las opciones",
      };
      $this->sendRawText($device, $from, $menuHint);
  }

  /**
   * Etiqueta legible del tipo de media en el idioma del cliente.
   */
  private function mediaLabel(string $type, string $lang): string
  {
      $map = [
          'audio'    => ['es' => 'audio',          'en' => 'voice note',  'pt' => 'áudio'],
          'image'    => ['es' => 'imagen',         'en' => 'image',       'pt' => 'imagem'],
          'video'    => ['es' => 'video',          'en' => 'video',       'pt' => 'vídeo'],
          'document' => ['es' => 'documento',      'en' => 'document',    'pt' => 'documento'],
          'location' => ['es' => 'ubicación',      'en' => 'location',    'pt' => 'localização'],
          'sticker'  => ['es' => 'sticker',        'en' => 'sticker',     'pt' => 'figurinha'],
          'contact'  => ['es' => 'contacto',       'en' => 'contact',     'pt' => 'contato'],
      ];
      return $map[$type][$lang] ?? $map[$type]['es'] ?? $type;
  }

  /**
   * Si la regla define reply_variants (JSON array de strings), reemplaza
   * $reply->reply con una variante al azar. Si no hay variantes válidas,
   * deja el reply original intacto.
   */
  private function applyReplyVariant(Reply $reply): void
  {
      if (empty($reply->reply_variants)) return;

      $variants = json_decode($reply->reply_variants, true);
      if (!is_array($variants) || count($variants) === 0) return;

      $valid = array_values(array_filter($variants, fn($v) => is_string($v) && trim($v) !== ''));
      if (count($valid) === 0) return;

      // Incluir el reply original como una opción más para mantener control editorial
      if (!empty($reply->reply) && !in_array($reply->reply, $valid, true)) {
          $valid[] = $reply->reply;
      }

      $idx = array_rand($valid);
      $reply->reply = $valid[$idx];
      // Marcar la variante elegida en el modelo (no persistente, solo en runtime)
      // para que recordMessage la guarde junto al matched_reply_id.
      $reply->setAttribute('_chosen_variant_index', $idx);
  }

  /**
   * ¿La sesión ya tiene los datos BANT mínimos (nombre + cantidad)?
   */
  private function hasBantData(ChatbotSession $session): bool
  {
      $data = json_decode($session->lead_data ?? '{}', true) ?: [];
      $hasName  = !empty($data['nombre']) || !empty($data['name']);
      $hasQty   = !empty($data['cantidad_dispositivos']) || !empty($data['cantidad']) || !empty($data['quantity']);
      return $hasName && $hasQty;
  }

  /**
   * Detecta el idioma de la sesión (con fallback a 'es').
   */
  private function sessionLang(ChatbotSession $session): string
  {
      return $session->locked_language ?: 'es';
  }

  /**
   * Empieza el flujo BANT: pregunta lo que falte (nombre o cantidad).
   */
  private function startBantFlow(Device $device, string $from, ChatbotSession $session): void
  {
      $data    = json_decode($session->lead_data ?? '{}', true) ?: [];
      $lang    = $this->sessionLang($session);
      $hasName = !empty($data['nombre']) || !empty($data['name']);

      if (!$hasName) {
          $session->bant_step = 'nombre';
          $session->save();
          $this->sendRawText($device, $from, $this->bantPrompt('nombre', $lang));
          return;
      }

      $session->bant_step = 'cantidad';
      $session->save();
      $this->sendRawText($device, $from, $this->bantPrompt('cantidad', $lang, $data));
  }

  /**
   * Avanza el flujo BANT: guarda valor capturado y pasa al siguiente paso o entrega precio.
   */
  private function advanceBantFlow(Device $device, string $from, string $value, ChatbotSession $session): void
  {
      $value = trim($value);
      $data  = json_decode($session->lead_data ?? '{}', true) ?: [];
      $lang  = $this->sessionLang($session);
      $step  = $session->bant_step;

      // Comandos de escape: si el cliente vuelve al menú, abortar BANT
      $lc = mb_strtolower($value);
      if (in_array($lc, ['menu', 'menú', 'cancelar', 'salir', 'cancel', 'exit', 'voltar'])) {
          $session->bant_step = null;
          $session->save();
          $reply = Reply::where('device_id', $device->id)->where('id', 13)->first();
          if ($reply) {
              $body = ['text' => $this->personalizeText($reply->reply ?? '', $session)];
              $this->sendRawText($device, $from, $body['text']);
          }
          return;
      }

      if ($step === 'nombre') {
          // Validación mínima: nombre razonable (2-60 chars, no solo dígitos)
          if (mb_strlen($value) < 2 || preg_match('/^\d+$/', $value)) {
              $this->sendRawText($device, $from, $this->bantPrompt('nombre_invalido', $lang));
              return;
          }
          $data['nombre'] = mb_substr($value, 0, 80);
          $session->lead_data = json_encode($data, JSON_UNESCAPED_UNICODE);
          $session->bant_step = 'cantidad';
          $session->save();

          // Persistir parcialmente en lead
          $this->upsertLeadFromBant($device, $from, $session, $data);

          $this->sendRawText($device, $from, $this->bantPrompt('cantidad', $lang, $data));
          return;
      }

      if ($step === 'cantidad') {
          // Extraer número del mensaje
          if (!preg_match('/(\d{1,6})/', $value, $m)) {
              $this->sendRawText($device, $from, $this->bantPrompt('cantidad_invalida', $lang));
              return;
          }
          $qty = (int) $m[1];
          if ($qty < 1) {
              $this->sendRawText($device, $from, $this->bantPrompt('cantidad_invalida', $lang));
              return;
          }
          $data['cantidad_dispositivos'] = $qty;
          $session->lead_data = json_encode($data, JSON_UNESCAPED_UNICODE);
          $session->bant_step = null;
          $session->save();

          $this->upsertLeadFromBant($device, $from, $session, $data);

          // Detectar segmento (estudiante/ONG/gobierno/recurrente) para aplicar multiplier
          $segment = (new ClientSegmentService())->detect($device->id, $session);

          // Entregar precio personalizado (con segmento si detectado)
          $this->sendRawText($device, $from, $this->personalizedPriceMessage($qty, $data, $lang, $segment));

          // Enviar PDF de cotización formal con los mismos datos
          $this->sendCotizacionPdf($device, $from, $data, $lang);
          return;
      }

      // Estado inválido: limpiar
      $session->bant_step = null;
      $session->save();
  }

  /**
   * Persiste/actualiza el lead con datos BANT capturados (idempotente).
   */
  private function upsertLeadFromBant(Device $device, string $from, ChatbotSession $session, array $data): void
  {
      $lead = ChatbotLead::where('device_id', $device->id)
          ->where('contact', $from)
          ->whereIn('status', ['new', 'in_progress'])
          ->first();

      if (!$lead) {
          $lead = new ChatbotLead;
          $lead->device_id = $device->id;
          $lead->contact   = $from;
          $lead->user_id   = $device->user_id;
          $lead->status    = 'new';
          $lead->interest  = 'precio';
      }
      if (!empty($data['nombre']))               $lead->contact_name = $data['nombre'];
      if (!empty($data['cantidad_dispositivos'])) $lead->interest    = 'precio_' . $data['cantidad_dispositivos'] . '_dispositivos';

      $existingFull = json_decode($lead->full_data ?? '{}', true) ?: [];
      $merged = array_merge($existingFull, [
          'lead_data'    => $data,
          'bant_updated' => Carbon::now()->toIso8601String(),
          'language'     => $this->sessionLang($session),
      ]);
      $lead->full_data = json_encode($merged, JSON_UNESCAPED_UNICODE);
      $isNewLead = !$lead->exists;
      $lead->save();

      // Calcular y persistir score 0-100 (cold/warm/hot)
      try {
          (new LeadScoringService())->applyToLead($lead, $session);
      } catch (\Throwable $e) {
          // no-op: scoring no debe romper el flujo principal
      }

      $this->pushLeadToCrmSafely($lead);

      // Webhook salida: lead.created o lead.updated
      (new OutboundWebhookService())->emit($isNewLead ? 'lead.created' : 'lead.updated', [
          'lead_id'      => $lead->id,
          'device_id'    => $device->id,
          'contact'      => $from,
          'contact_name' => $lead->contact_name,
          'interest'     => $lead->interest,
          'status'       => $lead->status,
          'lead_data'    => $data,
          'language'     => $this->sessionLang($session),
      ]);
  }

  /**
   * Textos BANT triple idioma.
   */
  private function bantPrompt(string $kind, string $lang, array $data = []): string
  {
      $name = $data['nombre'] ?? '';

      $messages = [
          'nombre' => [
              'es' => "👋 ¡Perfecto! Para darte el precio que más te conviene, necesito 2 datos rápidos.\n\n*¿Cómo te llamas?*",
              'en' => "👋 Got it! To give you the best pricing, I just need 2 quick things.\n\n*What's your name?*",
              'pt' => "👋 Ótimo! Para te passar o melhor preço, preciso de 2 informações rápidas.\n\n*Qual é o seu nome?*",
          ],
          'nombre_invalido' => [
              'es' => "Disculpa, no entendí tu nombre 😅 ¿Me lo puedes escribir solo con letras? (Ej: María, Carlos)",
              'en' => "Sorry, I didn't catch your name 😅 Could you write it with letters only? (e.g., María, Carlos)",
              'pt' => "Desculpa, não entendi seu nome 😅 Pode escrever só com letras? (Ex: María, Carlos)",
          ],
          'cantidad' => [
              'es' => ($name ? "Gracias *{$name}* 🙌\n\n" : '')
                    . "*¿Cuántos GPS necesitas aproximadamente?* (ej: 5, 50, 200)\n\nEsto me ayuda a darte el plan correcto y el descuento por volumen.",
              'en' => ($name ? "Thanks *{$name}* 🙌\n\n" : '')
                    . "*Roughly how many GPS units do you need?* (e.g., 5, 50, 200)\n\nThis helps me match you to the right plan and volume discount.",
              'pt' => ($name ? "Obrigado *{$name}* 🙌\n\n" : '')
                    . "*Quantos GPS você precisa aproximadamente?* (ex: 5, 50, 200)\n\nIsso me ajuda a indicar o plano certo e o desconto por volume.",
          ],
          'cantidad_invalida' => [
              'es' => "Necesito un número 😊 (ej: *10*, *50*, *200*). ¿Cuántos GPS aprox?",
              'en' => "I need a number 😊 (e.g., *10*, *50*, *200*). Roughly how many GPS units?",
              'pt' => "Preciso de um número 😊 (ex: *10*, *50*, *200*). Quantos GPS aprox?",
          ],
      ];

      return $messages[$kind][$lang] ?? $messages[$kind]['es'];
  }

  /**
   * Alerta al agent_number cuando se detecta sentimiento negativo/urgente.
   * Throttle 1h por contacto via Cache para no spamear al agente.
   */
  private function maybeAlertSentiment(Device $device, ChatbotSession $session, string $message, string $sentiment): void
  {
      $cacheKey = 'sentiment_alert:' . $device->id . ':' . $session->contact;
      if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
          return;
      }
      \Illuminate\Support\Facades\Cache::put($cacheKey, 1, 3600);

      $meta        = json_decode($device->meta ?? '{}', true);
      $agentNumber = $meta['agent_number'] ?? null;
      if (!$agentNumber) return;

      $leadData = json_decode($session->lead_data ?? '{}', true) ?: [];
      $name     = $leadData['nombre'] ?? $session->contact;
      $emoji    = $sentiment === 'urgent' ? '🚨' : '⚠️';
      $label    = $sentiment === 'urgent' ? 'URGENCIA detectada' : 'Cliente con queja/frustración';

      $alert = "{$emoji} *{$label}*\n"
             . "👤 {$name} ({$session->contact})\n"
             . "💬 \"" . mb_substr($message, 0, 200) . "\"\n\n"
             . "Considera tomar el chat ahora para evitar pérdida.";

      try {
          \Illuminate\Support\Facades\Http::timeout(5)->post(
              env('WA_SERVER_URL') . '/chats/send?id=device_' . $device->id,
              ['receiver' => $agentNumber, 'message' => ['text' => $alert]]
          );
      } catch (\Throwable $e) {
          // no-op
      }

      // Webhook salida: sentiment.alert
      (new OutboundWebhookService())->emit('sentiment.alert', [
          'device_id' => $device->id,
          'contact'   => $session->contact,
          'sentiment' => $sentiment,
          'message'   => mb_substr($message, 0, 300),
          'name'      => $name,
          'language'  => $session->locked_language,
      ]);
  }

  /**
   * Genera PDF de cotización personalizado y lo envía al cliente como documento WhatsApp.
   * Fail-safe: cualquier error no rompe el flujo (el precio en texto ya fue enviado).
   */
  private function sendCotizacionPdf(Device $device, string $from, array $data, string $lang): void
  {
      try {
          $name    = $data['nombre'] ?? '';
          $qty     = (int) ($data['cantidad_dispositivos'] ?? 0);
          $company = $data['empresa'] ?? null;
          if ($qty < 1 || $name === '') return;

          $pdfPath = (new CotizacionPdfService())->generate($name, $qty, $company, $from, $lang);
          if (!file_exists($pdfPath)) return;

          // Mover a public/uploads/cotizaciones/ con nombre único para acceso por URL
          $publicDir = public_path('uploads/cotizaciones');
          if (!is_dir($publicDir)) {
              @mkdir($publicDir, 0755, true);
          }
          $filename  = 'cotizacion_' . substr(md5($from . microtime(true)), 0, 12) . '.pdf';
          $dest      = $publicDir . '/' . $filename;
          if (!@rename($pdfPath, $dest)) {
              @copy($pdfPath, $dest);
              @unlink($pdfPath);
          }

          $appUrl = rtrim(env('APP_URL', 'http://' . request()->getHost()), '/');
          $url    = $appUrl . '/uploads/cotizaciones/' . $filename;

          $caption = match ($lang) {
              'en' => "📄 Here's your formal quote for *{$qty} GPS units*. Valid for 30 days. Forward it to your team or accountant. ✅",
              'pt' => "📄 Aqui está sua cotação formal para *{$qty} GPS*. Válida por 30 dias. Encaminhe para sua equipe ou contador. ✅",
              default => "📄 Aquí va tu cotización formal para *{$qty} GPS*. Válida 30 días — puedes reenviarla a tu equipo o contador. ✅",
          };

          $this->messageSend([
              'document' => ['url' => $url],
              'fileName' => 'Cotizacion-APPOGIO-' . $qty . 'GPS.pdf',
              'caption'  => $caption,
              'mimetype' => 'application/pdf',
          ], $device->id, $from, 'document', true, 0, true);

          // Housekeeping: borrar PDFs viejos (>24h) en background
          $this->cleanupOldCotizaciones($publicDir);
      } catch (\Throwable $e) {
          \Illuminate\Support\Facades\Log::warning('sendCotizacionPdf failed: ' . $e->getMessage());
      }
  }

  private function cleanupOldCotizaciones(string $dir): void
  {
      try {
          if (!is_dir($dir)) return;
          $cutoff = time() - 86400; // 24h
          foreach (glob($dir . '/cotizacion_*.pdf') ?: [] as $f) {
              if (filemtime($f) < $cutoff) @unlink($f);
          }
      } catch (\Throwable $e) {}
  }

  /**
   * Mensaje de precio personalizado según volumen + segmento opcional.
   * $segment: ['name'=>?, 'discount_pct'=>0, 'multiplier_monthly'=>1.0, 'multiplier_annual'=>1.0, 'badge'=>?]
   */
  private function personalizedPriceMessage(int $qty, array $data, string $lang, ?array $segment = null): string
  {
      $name = $data['nombre'] ?? '';

      // Selección de tier — precios alineados con regla #11 (fuente única de verdad).
      // 200+ = plan EMPRESA con app móvil personalizada GRATIS incluida.
      if ($qty >= 200) {
          $tier = ['monthly' => 0.50, 'annual' => 5.00, 'plan_es' => 'EMPRESA',  'plan_en' => 'ENTERPRISE', 'plan_pt' => 'EMPRESA',
                   'label_es' => '200+ unidades',   'label_en' => '200+ units',    'label_pt' => '200+ unidades'];
      } elseif ($qty >= 101) {
          $tier = ['monthly' => 0.75, 'annual' => 6.00, 'plan_es' => 'PRO',      'plan_en' => 'PRO',         'plan_pt' => 'PRO',
                   'label_es' => '101–200 unidades','label_en' => '101–200 units','label_pt' => '101–200 unidades'];
      } elseif ($qty >= 26) {
          $tier = ['monthly' => 1.00, 'annual' => 6.00, 'plan_es' => 'CRECE',    'plan_en' => 'GROWTH',      'plan_pt' => 'CRESCE',
                   'label_es' => '26–100 unidades', 'label_en' => '26–100 units', 'label_pt' => '26–100 unidades'];
      } else {
          $tier = ['monthly' => 1.00, 'annual' => 7.00, 'plan_es' => 'INICIA',   'plan_en' => 'START',       'plan_pt' => 'INÍCIO',
                   'label_es' => '1–25 unidades',   'label_en' => '1–25 units',   'label_pt' => '1–25 unidades'];
      }

      // Aplicar multiplier de segmento (estudiante, ONG, gobierno, etc) si aplica
      $multM = (float) ($segment['multiplier_monthly'] ?? 1.0);
      $multA = (float) ($segment['multiplier_annual']  ?? 1.0);
      $effMonthly = round($tier['monthly'] * $multM, 2);
      $effAnnual  = round($tier['annual']  * $multA, 2);

      $monthlyTotal = $effMonthly * $qty;
      $annualTotal  = $effAnnual  * $qty;

      // Ahorro real anual vs pagar 12 mensualidades
      $savePct = $effMonthly > 0
          ? (int) round((1 - ($effAnnual / ($effMonthly * 12))) * 100)
          : 0;

      // App móvil GRATIS solo en plan EMPRESA (200+); en otros tiers es opcional
      $appFree     = $qty >= 200;
      $monthlyStr  = '$' . number_format($effMonthly, 2);
      $annualStr   = '$' . number_format($effAnnual, 2);
      $hi          = $name ? "*{$name}*, " : '';

      // Banner de segmento (si aplica)
      $segBanner = '';
      if (!empty($segment['name']) && (int) ($segment['discount_pct'] ?? 0) !== 0) {
          $disc  = (int) $segment['discount_pct'];
          $emoji = $segment['badge'] ?? '✨';
          $segBanner = match ($lang) {
              'en' => "{$emoji} *{$segment['name']} discount applied: {$disc}% off*\n\n",
              'pt' => "{$emoji} *Desconto de {$segment['name']} aplicado: {$disc}% off*\n\n",
              default => "{$emoji} *Descuento {$segment['name']} aplicado: {$disc}% off*\n\n",
          };
      }

      return match ($lang) {
          'en' => $segBanner . "💰 *Personalized pricing for {$qty} GPS units*\n\n"
                . "{$hi}based on your volume — Plan *{$tier['plan_en']}* ({$tier['label_en']}):\n\n"
                . "🔸 Monthly: *{$monthlyStr} USD/unit/month* → ~\$" . number_format($monthlyTotal, 0) . " USD/mo\n"
                . "🔸 Annual: *{$annualStr} USD/unit/year* → ~\$" . number_format($annualTotal, 0) . " USD/yr"
                . ($savePct > 0 ? " (saves ~{$savePct}%)" : '') . "\n\n"
                . ($appFree
                    ? "✅ Custom mobile app *FREE* — included with the ENTERPRISE plan"
                    : "✅ Custom mobile app available: \$155 USD one-time or \$20/mo for both platforms")
                . "\n\n"
                . "📲 Want to see the platform live? Type *demo*\n"
                . "👨‍💼 Ready to talk to a sales rep? Type *asesor*",
          'pt' => $segBanner . "💰 *Preços personalizados para {$qty} GPS*\n\n"
                . "{$hi}com base no seu volume — Plano *{$tier['plan_pt']}* ({$tier['label_pt']}):\n\n"
                . "🔸 Mensal: *{$monthlyStr} USD/unidade/mês* → ~\$" . number_format($monthlyTotal, 0) . " USD/mês\n"
                . "🔸 Anual: *{$annualStr} USD/unidade/ano* → ~\$" . number_format($annualTotal, 0) . " USD/ano"
                . ($savePct > 0 ? " (economiza ~{$savePct}%)" : '') . "\n\n"
                . ($appFree
                    ? "✅ App móvel personalizado *GRÁTIS* — incluído no plano EMPRESA"
                    : "✅ App móvel personalizado disponível: \$155 USD pagamento único ou \$20/mês ambas plataformas")
                . "\n\n"
                . "📲 Quer ver a plataforma ao vivo? Digite *demo*\n"
                . "👨‍💼 Pronto para falar com um consultor? Digite *asesor*",
          default => $segBanner . "💰 *Precio personalizado para {$qty} GPS*\n\n"
                . "{$hi}según tu volumen — Plan *{$tier['plan_es']}* ({$tier['label_es']}):\n\n"
                . "🔸 Mensual: *{$monthlyStr} USD/unidad/mes* → ~\$" . number_format($monthlyTotal, 0) . " USD/mes\n"
                . "🔸 Anual: *{$annualStr} USD/unidad/año* → ~\$" . number_format($annualTotal, 0) . " USD/año"
                . ($savePct > 0 ? " (ahorras ~{$savePct}%)" : '') . "\n\n"
                . ($appFree
                    ? "✅ App móvil personalizada *GRATIS* — incluida con el plan EMPRESA"
                    : "✅ App móvil personalizada disponible: \$155 USD pago único o \$20/mes ambas plataformas")
                . "\n\n"
                . "📲 ¿Quieres ver la plataforma en vivo? Escribe *demo*\n"
                . "👨‍💼 ¿Listo para hablar con un asesor? Escribe *asesor*",
      };
  }

  /**
   * Dispara la transferencia a agente humano.
   */
  private function triggerHandoff(Device $device, string $from, string $lastMessage,
                                   ChatbotSession $session): void
  {
      // Pausa el bot por 24h. El humano puede:
      //  - escribir #bot al chat para reactivar al instante
      //  - responder al cliente desde el panel (resetea el timer a +24h)
      //  - dejar pasar 24h sin actividad → el bot reactiva automáticamente al
      //    siguiente mensaje del cliente
      $session->is_paused        = 1;
      $session->paused_until     = Carbon::now()->addHours(24);
      $session->human_handoff_at = Carbon::now();
      $session->save();

      // Webhook salida: evento handoff.opened
      (new OutboundWebhookService())->emit('handoff.opened', [
          'device_id' => $device->id,
          'contact'   => $from,
          'session_id'=> $session->id,
          'last_message' => $lastMessage,
          'lead_data' => json_decode($session->lead_data ?? '{}', true) ?: [],
          'language'  => $session->locked_language,
      ]);

      // Registrar el handoff
      $handoff              = new ChatbotHandoff;
      $handoff->device_id   = $device->id;
      $handoff->contact     = $from;
      $handoff->last_message = $lastMessage;
      $handoff->status      = 'pending';
      $handoff->user_id     = $device->user_id;
      $handoff->save();

      $leadData = json_decode($session->lead_data ?? '{}', true);
      $nombre   = $leadData['nombre'] ?? $leadData['name'] ?? null;
      $email    = $leadData['email'] ?? null;
      $detected = $this->resolveLanguage($lastMessage, $session);

      // Crear o actualizar lead en CRM (chatbot_leads).
      // Idempotente: si ya hay un lead "new" o "in_progress" para este contacto+device,
      // actualizamos en vez de duplicar.
      $lead = ChatbotLead::where('device_id', $device->id)
          ->where('contact', $from)
          ->whereIn('status', ['new', 'in_progress'])
          ->first();

      if (!$lead) {
          $lead = new ChatbotLead;
          $lead->device_id = $device->id;
          $lead->contact   = $from;
          $lead->user_id   = $device->user_id;
          $lead->status    = 'new';
      }
      if ($nombre) $lead->contact_name  = $nombre;
      if ($email)  $lead->contact_email = $email;
      $lead->interest = 'asesor';
      $lead->full_data = json_encode([
          'last_message' => $lastMessage,
          'language'     => $detected,
          'lead_data'    => $leadData,
          'session_id'   => $session->id ?? null,
          'triggered_at' => now()->toIso8601String(),
      ], JSON_UNESCAPED_UNICODE);
      $lead->save();

      // Calcular score 0-100 antes de notificar al agente (priorización)
      $scoreData = ['score' => 0, 'category' => 'cold'];
      try {
          $scoreData = (new LeadScoringService())->applyToLead($lead, $session);
      } catch (\Throwable $e) {
          // no-op
      }

      $this->pushLeadToCrmSafely($lead);

      // Asignar agente: si hay agentes en chatbot_agents usa round-robin,
      // sino fallback a meta.agent_number (single-agent legacy).
      $agentNumber = (new AgentDispatcherService())->pickAgent($device, null);

      if ($agentNumber) {
          $langLabel = match ($detected) {
              'en' => '🇺🇸 INGLÉS — responde en inglés',
              'pt' => '🇧🇷 PORTUGUÉS — responde en portugués',
              default => '🇪🇸 ESPAÑOL',
          };

          // Indicador visual del score: 🔥 hot, 🌡️ warm, ❄️ cold
          $scoreEmoji = match ($scoreData['category']) {
              'hot'  => '🔥',
              'warm' => '🌡️',
              default => '❄️',
          };
          $scoreLabel = match ($scoreData['category']) {
              'hot'  => 'CALIENTE — atender YA',
              'warm' => 'TIBIO — buen prospecto',
              default => 'FRÍO — bajo interés',
          };

          $displayName = $nombre ?: $from;
          $text = "🔔 *Solicitud de atención*\n"
                . "{$scoreEmoji} Score: *{$scoreData['score']}/100* — {$scoreLabel}\n"
                . "👤 Contacto: {$displayName} ({$from})\n"
                . "🌐 Idioma: {$langLabel}\n"
                . "💬 Último mensaje: {$lastMessage}\n"
                . "📱 Dispositivo: {$device->name}\n\n"
                . "Responde directamente a este número o escribe *#bot* al chat para reactivar el bot.";

          Http::post(env('WA_SERVER_URL') . '/chats/send?id=device_' . $device->id, [
              'receiver' => $agentNumber,
              'message'  => ['text' => $text],
          ]);
      }
  }

  /**
   * Envía texto plano directamente sin pasar por el sistema de reglas.
   */
  private function sendRawText(Device $device, string $to, string $text): void
  {
      // Anti-duplicado: si la MISMA respuesta se envió a este contacto en los
      // últimos 10 min, no la repite. Evita loops cuando el AI genera la misma
      // respuesta para mensajes similares o cuando un contacto manda repetidos.
      $cacheKey = 'sendraw:' . $device->id . ':' . md5($to . '|' . $text);
      if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
          \Illuminate\Support\Facades\Log::info(
              "[AntiDup] skip repeated reply to {$to} on device {$device->id}"
          );
          return;
      }
      \Illuminate\Support\Facades\Cache::put($cacheKey, 1, 600);

      $this->messageSend(['text' => $text], $device->id, $to, 'plain-text', true, 0, true);
  }

  /**
   * Reemplaza variables en el texto de respuesta con datos reales del contacto/sesión.
   */
  private function personalizeText(string $text, ChatbotSession $session): string
  {
      $leadData = json_decode($session->lead_data ?? '{}', true);
      $now      = Carbon::now();
      $days     = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

      $vars = [
          '{nombre}'         => $leadData['nombre']   ?? $leadData['name'] ?? $session->contact,
          '{email}'          => $leadData['email']     ?? '',
          '{telefono}'       => $session->contact,
          '{fecha}'          => $now->format('d/m/Y'),
          '{hora}'           => $now->format('H:i'),
          '{dia}'            => $days[$now->isoFormat('E')] ?? '',
          '{mes}'            => $now->locale('es')->isoFormat('MMMM'),
          '{calendly_link}'  => env('APPOGIO_CALENDLY_URL') ?: 'Escribe *asesor* y te enviamos el link',
          '{paypal_link}'    => $this->buildPaypalLink($session, $leadData) ?: '(escribe *asesor* para link PayPal)',
          '{wompi_link}'     => $this->buildWompiLink($session, $leadData) ?: '(escribe *asesor* para link Wompi)',
      ];

      return str_replace(array_keys($vars), array_values($vars), $text);
  }

  /**
   * Genera link de pago PayPal a partir de plantilla en env var.
   * Soporta placeholders {amount} y {currency} en la plantilla.
   */
  private function buildPaypalLink(ChatbotSession $session, array $leadData): string
  {
      $tpl = env('APPOGIO_PAYPAL_LINK_TEMPLATE', '');
      if (!$tpl) return '';
      return $this->fillPaymentTemplate($tpl, $leadData);
  }

  private function buildWompiLink(ChatbotSession $session, array $leadData): string
  {
      $tpl = env('APPOGIO_WOMPI_LINK_TEMPLATE', '');
      if (!$tpl) return '';
      return $this->fillPaymentTemplate($tpl, $leadData);
  }

  private function fillPaymentTemplate(string $tpl, array $leadData): string
  {
      $qty = (int) ($leadData['cantidad_dispositivos'] ?? 1);
      // Cálculo simple: año en USD según tier
      // Precios anuales alineados con regla #11 / personalizedPriceMessage()
      $unitAnnual = $qty >= 200 ? 5.00 : ($qty >= 101 ? 6.00 : ($qty >= 26 ? 6.00 : 7.00));
      $amount     = number_format($unitAnnual * max($qty, 1), 2, '.', '');

      return str_replace(
          ['{amount}', '{currency}', '{qty}'],
          [$amount,   'USD',         (string) $qty],
          $tpl
      );
  }

  /**
   * Busca la regla que corresponde al mensaje recibido.
   * Primero revisa las reglas hijas del flujo activo;
   * si no hay coincidencia, busca en las reglas de nivel superior.
   */
  private function findMatchingReply(int $deviceId, string $message, ChatbotSession $session): ?Reply
  {
      // 1. Si el contacto está dentro de un flujo, buscar en reglas hijas
      if ($session->current_reply_id) {
          $reply = $this->matchReply(
              Reply::where('device_id', $deviceId)
                   ->where('parent_reply_id', $session->current_reply_id)
                   ->orderByDesc('priority'),
              $message
          );
          if ($reply) return $reply;

          // No coincidió con ninguna opción del flujo: cerrar el flujo
          $session->current_reply_id = null;
      }

      // 2. Buscar en reglas de nivel superior (sin padre)
      return $this->matchReply(
          Reply::where('device_id', $deviceId)
               ->whereNull('parent_reply_id')
               ->orderByDesc('priority'),
          $message
      );
  }

  /**
   * Aplica los distintos tipos de coincidencia a un query de Reply.
   * Normaliza acentos y puntuación, y aplica fallback fuzzy si nada matchea.
   */
  private function matchReply($query, string $message): ?Reply
  {
      $msgLower = mb_strtolower(trim($message));
      $msgNorm  = $this->normalize($message);

      $rules = $query->with('template')->get();

      $fuzzyBest      = null;
      $fuzzyBestScore = 0;
      $anyFallback    = null; // rule con match_type=any, último recurso

      foreach ($rules as $rule) {
          $keyword  = mb_strtolower(trim($rule->keyword ?? ''));

          // Las reglas con match_type=any son fallback global: NO matchean en el
          // loop, se guardan para después de los matches explícitos + fuzzy.
          // Esto evita que 'any' eclipse a fuzzy o keywords específicas.
          if ($rule->match_type === 'any') {
              if ($anyFallback === null) $anyFallback = $rule;
              continue;
          }

          if ($keyword === '') continue;
          $kwNorm = $this->normalize($keyword);

          $matched = match ($rule->match_type) {
              // Coincidencia exacta normalizada (insensible a acentos/puntuación)
              'equal'       => $msgNorm === $kwNorm
                               || $this->keywordInList($keyword, $msgNorm),

              // El mensaje EMPIEZA con la palabra clave (normalizado)
              'starts_with' => $kwNorm !== '' && str_starts_with($msgNorm, $kwNorm),

              // La palabra clave aparece como palabra completa en el mensaje
              'contains'    => $kwNorm !== '' && (bool) preg_match(
                                   '/\b' . preg_quote($kwNorm, '/') . '\b/u',
                                   $msgNorm
                               ),

              // Expresión regular (sobre el mensaje original, sin normalizar)
              'regex'       => (bool) @preg_match('/' . $rule->keyword . '/ui', $message),

              // Coincidencia fuzzy explícita: tolera typos vía Levenshtein
              'fuzzy'       => $this->fuzzyMatchKeyword($keyword, $msgNorm, 80),

              // Compatibilidad con 'like' anterior (palabra similar)
              'like'        => $kwNorm !== '' && (bool) preg_match(
                                   '/\b' . preg_quote($kwNorm, '/') . '/u',
                                   $msgNorm
                               ),

              default       => false,
          };

          if ($matched) return $rule;

          // Trackear el mejor candidato fuzzy para el fallback global.
          if (in_array($rule->match_type, ['equal','contains','starts_with','like'], true)) {
              $score = $this->bestFuzzyScore($keyword, $msgNorm);
              if ($score > $fuzzyBestScore) {
                  $fuzzyBestScore = $score;
                  $fuzzyBest      = $rule;
              }
          }
      }

      // Fallback fuzzy global: si nada matcheó pero hay un candidato razonable (≥80%),
      // devolverlo. Tolera typos de 1-2 letras en palabras de 5+ chars.
      if ($fuzzyBest && $fuzzyBestScore >= 80) {
          return $fuzzyBest;
      }

      // Último recurso: regla 'any' como fallback "no entendí"
      if ($anyFallback) return $anyFallback;

      return null;
  }

  /** Verifica si $needle está en una lista separada por comas (normalizado) */
  private function keywordInList(string $list, string $needle): bool
  {
      $needleNorm = $this->normalize($needle);
      foreach (explode(',', $list) as $item) {
          if ($this->normalize($item) === $needleNorm) return true;
      }
      return false;
  }

  /**
   * Normaliza texto para matching: lowercase, strip acentos UTF-8 → ASCII,
   * strip puntuación común, y colapsa espacios. Sin librerías externas.
   */
  private function normalize(string $text): string
  {
      $text = mb_strtolower(trim($text));
      // Transliteración acentos → ASCII (requiere locale UTF-8, viene con php)
      $clean = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
      if ($clean !== false && $clean !== '') {
          $text = $clean;
      }
      // Quitar puntuación común
      $text = preg_replace('/[¿?¡!.,;:"\'\(\)\[\]\{\}]+/u', ' ', $text) ?? $text;
      // Colapsar espacios múltiples
      $text = preg_replace('/\s+/', ' ', $text) ?? $text;
      return trim($text);
  }

  /**
   * Devuelve true si alguna palabra del mensaje matchea fuzzy con algún item
   * de la keyword-list (separada por comas). Threshold = % de similitud mínimo.
   */
  private function fuzzyMatchKeyword(string $keywordList, string $msgNorm, int $threshold = 80): bool
  {
      return $this->bestFuzzyScore($keywordList, $msgNorm) >= $threshold;
  }

  /**
   * Calcula el mejor score (%) de similitud entre cualquier item de la
   * keyword-list y cualquier palabra (o el mensaje completo) del mensaje.
   * Usa Levenshtein normalizado por longitud máxima.
   * Items/palabras de 2 chars: solo valen para match exacto (sin tolerancia a typos),
   * para soportar saludos como "hi", "oi" sin generar falsos positivos.
   * Items/palabras de 1 char: ignorados (números/letras sueltas las maneja `equal`).
   */
  private function bestFuzzyScore(string $keywordList, string $msgNorm): int
  {
      $items = [];
      foreach (explode(',', $keywordList) as $k) {
          $n = $this->normalize($k);
          if ($n !== '' && strlen($n) >= 2) $items[] = $n;
      }
      if (!$items || $msgNorm === '') return 0;

      $words   = preg_split('/\s+/', $msgNorm) ?: [];
      $words[] = $msgNorm; // incluir el mensaje completo como candidato
      $best    = 0;

      foreach ($items as $item) {
          $itemLen = strlen($item);
          if ($itemLen > 200) continue; // levenshtein se rompe > 255

          // Match de frase: items multi-palabra (ej: "how much", "bom dia") matchean
          // si aparecen como substring en el mensaje. Levenshtein no escala bien con frases.
          if (str_contains($item, ' ') && str_contains($msgNorm, $item)) {
              return 100;
          }

          foreach ($words as $word) {
              $wordLen = strlen($word);
              if ($wordLen < 2 || $wordLen > 200) continue;

              // Match exacto normalizado = 100
              if ($word === $item) return 100;

              // Para items o palabras de 2 chars, solo permitimos match exacto
              // (Levenshtein con tan pocos chars genera demasiados falsos positivos).
              if ($itemLen < 3 || $wordLen < 3) continue;

              $dist = levenshtein($word, $item);
              $maxLen = max($wordLen, $itemLen);
              $score  = (int) round((1 - $dist / $maxLen) * 100);
              if ($score > $best) $best = $score;
          }
      }
      return $best;
  }

  /**
   * Resuelve el idioma usando la sesión cuando es posible:
   *   - Si la sesión ya tiene locked_language, lo devuelve (idioma sticky).
   *   - Si no, detecta. Cuando la detección encuentra al menos un marker
   *     fuerte (score>0), bloquea el idioma en la sesión para futuros mensajes
   *     ambiguos del mismo contacto.
   *   - Si no hay markers, devuelve 'es' SIN bloquear (esperamos a un mensaje claro).
   */
  private function resolveLanguage(string $message, ?ChatbotSession $session): string
  {
      if ($session && !empty($session->locked_language)) {
          return $session->locked_language;
      }

      [$lang, $score] = $this->detectLanguageWithScore($message);
      if ($session && $score > 0 && in_array($lang, ['es','en','pt'], true)) {
          $session->locked_language = $lang;
          $session->save();
      }
      return $lang;
  }

  /**
   * Detecta el idioma de un mensaje entrante: 'es' | 'en' | 'pt'.
   * Heurística sin librerías ni APIs externas:
   *   - Marcadores ortográficos exclusivos pesan 3 (ç, ã, õ, ñ, ¿, ¡)
   *   - Palabras léxicas distintivas pesan 1 cada coincidencia
   *   - Si todo da 0, devuelve 'es' (idioma por defecto)
   */
  private function detectLanguage(string $message): string
  {
      return $this->detectLanguageWithScore($message)[0];
  }

  /**
   * Igual que detectLanguage pero devuelve [idioma, score_ganador]
   * para que resolveLanguage pueda decidir si "bloquear".
   *
   * @return array{0: string, 1: int}
   */
  private function detectLanguageWithScore(string $message): array
  {
      $msg = trim($message);
      if ($msg === '') return 'es';
      $msg = mb_strtolower($msg);

      $scores = ['es' => 0, 'en' => 0, 'pt' => 0];

      // Marcadores ortográficos fuertes (peso 3)
      if (preg_match('/[ãõç]|ção|inho|inha/u', $msg)) $scores['pt'] += 3;
      if (preg_match('/[ñ]|¿|¡/u', $msg))             $scores['es'] += 3;

      // Léxico (sobre forma normalizada para tolerar variaciones de acentos)
      $msgNorm = $this->normalize($msg);
      $words   = preg_split('/\s+/', $msgNorm, -1, PREG_SPLIT_NO_EMPTY) ?: [];

      $markers = [
          'es' => ['hola','gracias','buenos','buenas','soy','estoy','cuanto','tambien','adios',
                   'quiero','necesito','dias','muchas','donde','como','que','aqui',
                   'llamo','llamar','hablar','ahora','tienen','estan','vale','usted','tu',
                   'espanol','si','tardes','noches','dame','contigo','ustedes','vosotros',
                   'puedo','puede','pueden','podria','gustaria','gusto','tarjeta','dinero',
                   'haber','hacer','llevar','traer','ojala','siempre','nunca','tambien',
                   'movil','aplicacion','aplicaciones','telefono','telefonos','servicio','servicios',
                   'producto','productos','contacto','contactanos'],
          'en' => ['hello','hi','hey','thanks','thank','please','what','how','where','when','why',
                   'are','you','your','the','and','this','that','for','with','would','could',
                   'should','want','need','today','tomorrow','yes','about','help','price','cost',
                   'morning','evening','night','very','more','from','have','has','can','do','does',
                   'me','my','we','our','their','them','is','was','were','will','going','am','get',
                   'got','take','make','see','know','look','work','time','day','week','month',
                   'here','there','sorry','okay','sure','available','customer','service',
                   'mobile','app','application','phone','smartphone','android','iphone'],
          'pt' => ['ola','oi','obrigado','obrigada','voce','voces','nao','tudo','bem','bom','boa',
                   'sou','estou','quero','preciso','quanto','custa','onde','tambem','noite',
                   'tarde','muito','agora','falar','pode','sim','comigo','ali','seu','sua',
                   'esta','estao','aqui','conosco','portugues','brasileiro',
                   'dia','dias','ajuda','ajudar','posso','tenho','temos','uma','com','para','mais',
                   'mas','aplicativo','celular','telefone','conversa','mensagem','duvida','duvidas',
                   'fazer','faco','irei','vou','vamos','ele','ela','eles','elas','isso','aquilo',
                   'gosto','gostaria','gostei','qualquer','alguma','algum','todos','todas','hoje',
                   'amanha','ontem','talvez','meu','minha','muita','muitas','muitos','queria','queriam'],
      ];

      foreach ($words as $w) {
          foreach ($markers as $lang => $set) {
              if (in_array($w, $set, true)) $scores[$lang]++;
          }
      }

      arsort($scores);
      $top = array_key_first($scores);
      $topScore = $scores[$top];
      if ($topScore === 0) return ['es', 0];
      return [$top, $topScore];
  }

  /**
   * Crea/actualiza un lead si la regla disparada indica intención de compra.
   * Criterios: la regla tiene media adjunta (PDF/imagen sugiere oferta concreta)
   * o sus keywords contienen palabras de intent ("precio", "demo", "asesor",
   * "cotizacion", "quote", "price", etc.). Idempotente: actualiza el lead
   * existente para el mismo contacto+device en lugar de duplicar.
   */
  private function captureLeadIfHighIntent(Device $device, string $from, Reply $reply,
                                            string $message, ChatbotSession $session): void
  {
      // Las reglas con handoff ya crean su lead en triggerHandoff()
      if ($reply->trigger_handoff) return;

      $intentTerms = ['asesor','advisor','consultor','vendedor','demo','prueba','probar',
                      'price','precio','preco','cost','costo','custa','cuanto','quanto',
                      'cotizacion','cotizacao','quote','orcamento','app','aplicativo'];
      $kwLower = mb_strtolower($reply->keyword ?? '');
      $hasIntent = false;
      foreach ($intentTerms as $iw) {
          if (mb_strpos($kwLower, $iw) !== false) { $hasIntent = true; break; }
      }

      // Sin media y sin intent en keywords → no es lead, salimos
      if (!$reply->media_path && !$hasIntent) return;

      $lang = $this->resolveLanguage($message, $session);

      // Idempotente: actualizar lead existente si lo hay
      $lead = ChatbotLead::where('device_id', $device->id)
          ->where('contact', $from)
          ->whereIn('status', ['new','in_progress'])
          ->first();

      $isNew = !$lead;
      if (!$lead) {
          $lead = new ChatbotLead;
          $lead->device_id = $device->id;
          $lead->contact   = $from;
          $lead->user_id   = $device->user_id;
          $lead->status    = 'new';
      }

      // Derivar interest legible de la primera keyword del rule
      $firstKw = trim(explode(',', $reply->keyword)[0] ?? '');
      $lead->interest = $firstKw ?: ($lead->interest ?? 'consulta');

      $existing = $lead->full_data ? json_decode($lead->full_data, true) : [];
      $existing['last_message']  = $message;
      $existing['last_rule_id']  = $reply->id;
      $existing['language']      = $lang;
      $existing['updated_at_ts'] = now()->toIso8601String();
      $existing['session_id']    = $session->id ?? ($existing['session_id'] ?? null);
      $lead->full_data = json_encode($existing, JSON_UNESCAPED_UNICODE);
      $lead->save();

      $this->pushLeadToCrmSafely($lead);
  }

  /**
   * Empuja el lead a ERPNext sin propagar excepciones — el chatbot debe
   * seguir respondiendo al cliente aunque el CRM falle. Los fallos quedan
   * en chatbot_leads.crm_last_error para reintento posterior.
   */
  private function pushLeadToCrmSafely(ChatbotLead $lead): void
  {
      try {
          (new ErpNextLeadService)->push($lead);
      } catch (\Throwable $e) {
          \Log::warning('pushLeadToCrmSafely error inesperado', [
              'lead_id' => $lead->id,
              'error'   => $e->getMessage(),
          ]);
      }
  }

  /**
   * Guarda CADA número que escribe al WhatsApp del bot en la tabla `contacts`
   * (la misma que usa el módulo de campañas masivas), y lo asocia al grupo
   * "📥 WhatsApp Inbound — device {id}" del usuario dueño del dispositivo.
   *
   * Idempotente: si el número ya existe, solo se actualiza updated_at + name.
   * No propaga excepciones — un fallo aquí no debe interrumpir la respuesta del bot.
   */
  private function captureInboundContact(Device $device, string $jidOrNumber, string $pushName = ''): void
  {
      try {
          // Saltar JIDs anónimos (@lid) — no tienen número público utilizable para difusión
          if (str_ends_with($jidOrNumber, '@lid')) return;

          // Normalizar a formato +E.164 simple (solo dígitos con +)
          $digits = preg_replace('/[^\d]/', '', $jidOrNumber);
          if (strlen($digits) < 8) return; // descartar números demasiado cortos
          $phone = '+' . $digits;

          // 1. Buscar/crear el grupo "WhatsApp Inbound" del usuario dueño del device
          $groupName = '📥 WhatsApp Inbound — ' . ($device->name ?: ('device ' . $device->id));
          $group = Group::firstOrCreate(
              ['user_id' => $device->user_id, 'name' => $groupName]
          );

          // 2. firstOrCreate del contacto por (user_id, phone)
          $contact = Contact::firstOrNew([
              'user_id' => $device->user_id,
              'phone'   => $phone,
          ]);

          $isNew = !$contact->exists;
          if ($isNew && $pushName !== '') {
              $contact->name = mb_substr($pushName, 0, 191);
          } elseif (!$isNew && $pushName !== '' && empty($contact->name)) {
              // Solo completar el nombre si lo teníamos vacío (no sobrescribir manuales)
              $contact->name = mb_substr($pushName, 0, 191);
          }
          $contact->updated_at = now();
          if ($isNew) $contact->created_at = now();
          $contact->save();

          // 3. Asociar al grupo si no está ya
          Groupcontact::firstOrCreate([
              'group_id'   => $group->id,
              'contact_id' => $contact->id,
          ]);
      } catch (\Throwable $e) {
          \Log::warning('captureInboundContact error', [
              'device_id' => $device->id,
              'jid'       => $jidOrNumber,
              'error'     => $e->getMessage(),
          ]);
      }
  }

  /**
   * Si la regla tiene variantes de idioma (reply_en / reply_pt) y el mensaje
   * detecta ese idioma, sustituye in-place $reply->reply por la variante.
   * Si no hay variante, deja el reply original (fallback).
   *
   * Idioma sticky: si la sesión ya tiene locked_language, lo respeta.
   */
  private function applyLanguageVariant(Reply $reply, string $message, ?ChatbotSession $session = null): string
  {
      $lang = $this->resolveLanguage($message, $session);
      if ($lang === 'en' && !empty($reply->reply_en)) {
          $reply->reply = $reply->reply_en;
      } elseif ($lang === 'pt' && !empty($reply->reply_pt)) {
          $reply->reply = $reply->reply_pt;
      }
      return $lang;
  }

  /**
   * Envía el mensaje, guarda log y actualiza la sesión del contacto.
   */
  private function dispatchReply(
      Device $device,
      string $from,
      array $body,
      string $type,
      ChatbotSession $session,
      ?Reply $reply,
      string $sessionId
  ): void {
      $this->messageSend($body, $device->id, $from, $type, true, 0, true);

      $logs = [
          'user_id'     => $device->user_id,
          'device_id'   => $device->id,
          'from'        => $device->phone ?? null,
          'to'          => $from,
          'type'        => 'chatbot',
          'template_id' => $reply?->template_id ?? null,
      ];
      $this->saveLog($logs);

      if ($reply) {
          // Actualizar sesión: si esta regla tiene hijos, el contacto entra al flujo
          $hasChildren = Reply::where('parent_reply_id', $reply->id)->exists();
          $session->current_reply_id = $hasChildren ? $reply->id : null;
          $session->last_reply_at    = now();

          if ($reply->only_once) {
              $session->addRepliedId($reply->id);
          }

          $session->save();
      }
  }
}
