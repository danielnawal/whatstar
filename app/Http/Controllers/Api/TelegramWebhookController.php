<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatbotLead;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Models\Device;
use App\Models\Reply;
use App\Services\AgentDispatcherService;
use App\Services\BotConversationalService;
use App\Services\LeadScoringService;
use App\Services\OutboundWebhookService;
use App\Services\TelegramSendService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Recibe webhooks de Telegram Bot API y los procesa con la misma inteligencia
 * del bot WhatsApp (BotConversational, BANT, sentiment, scoring, reglas).
 *
 * Setup:
 *   1. Crear bot en BotFather → obtener TOKEN
 *   2. .env: TELEGRAM_BOT_TOKEN=... TELEGRAM_BOT_DEVICE_ID=100
 *   3. Setear webhook:
 *      curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
 *           -d "url=https://tu-dominio/api/telegram-webhook/<TOKEN>"
 *
 * Auth: el TOKEN va en la URL (Telegram lo recomienda) — protege contra spam.
 *
 * Sesiones: se guardan con channel='telegram' y contact='tg_<chat_id>' para no
 * colisionar con sesiones WhatsApp del mismo número.
 */
class TelegramWebhookController extends Controller
{
    public function webhook(Request $request, string $token)
    {
        // Buscar device por telegram_token (multi-tenant).
        // Fallback a env vars globales para compatibilidad con instancias legacy.
        $device = Device::where('telegram_token', $token)->first();
        if (!$device) {
            $legacyToken = env('TELEGRAM_BOT_TOKEN');
            if (!$legacyToken || !hash_equals($legacyToken, $token)) {
                return response()->json(['ok' => true]);
            }
            $deviceId = (int) env('TELEGRAM_BOT_DEVICE_ID', 0);
            $device   = $deviceId ? Device::find($deviceId) : null;
        }
        if (!$device) {
            Log::warning('TelegramWebhook: no device found for token');
            return response()->json(['ok' => true]);
        }

        // Parsear update
        $update = $request->all();
        $msg    = $update['message'] ?? $update['edited_message'] ?? null;
        if (!$msg) return response()->json(['ok' => true]);

        $chatId   = (string) ($msg['chat']['id'] ?? '');
        $text     = trim((string) ($msg['text'] ?? $msg['caption'] ?? ''));
        $userInfo = $msg['from'] ?? [];
        $pushName = trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''));

        if (!$chatId || $text === '') {
            // Por ahora solo procesamos texto. Audio/imagen pendiente para Ola futura.
            return response()->json(['ok' => true]);
        }

        // Sesión con prefijo tg_ para evitar colisión con WhatsApp
        $contact = 'tg_' . $chatId;

        $session = ChatbotSession::firstOrNew([
            'device_id' => $device->id,
            'contact'   => $contact,
        ]);
        if (!$session->exists) {
            $session->is_new_contact = 1;
            $session->channel        = 'telegram';
            $session->save();
        }

        // Si la sesión está pausada, ignorar
        if ($session->is_paused) {
            $expired = $session->paused_until && Carbon::parse($session->paused_until)->lte(Carbon::now());
            if (!$expired) return response()->json(['ok' => true]);
            $session->is_paused = 0;
            $session->paused_until = null;
            $session->save();
        }

        // BANT en progreso
        if ($session->bant_step) {
            $this->advanceBantViaTelegram($device, $contact, $chatId, $text, $session);
            return response()->json(['ok' => true]);
        }

        // LLM conversacional
        $convSvc = new BotConversationalService();
        $conv    = $convSvc->process($text, $session, $device);

        if ($conv) {
            // Auto-poblar lead_data
            if (!empty($conv['lead_data'])) {
                $current = json_decode($session->lead_data ?? '{}', true) ?: [];
                $changed = false;
                foreach ($conv['lead_data'] as $k => $v) {
                    if ($v !== null && $v !== '' && empty($current[$k])) {
                        $current[$k] = is_string($v) ? mb_substr($v, 0, 80) : $v;
                        $changed = true;
                    }
                }
                if ($changed) {
                    $session->lead_data = json_encode($current, JSON_UNESCAPED_UNICODE);
                    $session->save();
                }
            }
            if (!$session->locked_language && in_array($conv['language'] ?? '', ['es', 'en', 'pt'])) {
                $session->locked_language = $conv['language'];
                $session->save();
            }
        }

        // Persistir mensaje entrante
        $convSvc->recordMessage($session, $device, 'user', $text, $conv['intent'] ?? null, null, $conv['sentiment'] ?? null);

        // Buscar regla por intent o keyword
        $reply = null;
        if ($conv && !empty($conv['rule_id'])) {
            $reply = Reply::find($conv['rule_id']);
        }
        if (!$reply) {
            $reply = $this->findRule($device->id, $text);
        }

        // reply_text directo si LLM produjo respuesta natural
        if (!$reply && $conv && !empty($conv['reply_text'])) {
            (new TelegramSendService())->sendText($expected, $chatId, $conv['reply_text']);
            $convSvc->recordMessage($session, $device, 'assistant', $conv['reply_text'], $conv['intent'] ?? null);
            $session->is_new_contact = 0;
            $session->last_reply_at  = Carbon::now();
            $session->save();
            return response()->json(['ok' => true]);
        }

        if (!$reply) return response()->json(['ok' => true]);

        // Aplicar variantes idioma + variabilidad
        $replyText = $reply->reply ?? '';
        $lang      = $session->locked_language ?: 'es';
        if ($lang === 'en' && !empty($reply->reply_en)) $replyText = $reply->reply_en;
        if ($lang === 'pt' && !empty($reply->reply_pt)) $replyText = $reply->reply_pt;

        // Sustituir placeholders
        $replyText = $this->personalizeText($replyText, $session);

        // Trigger handoff si la regla lo pide
        if ($reply->trigger_handoff) {
            $this->triggerHandoffTelegram($device, $session, $text, $expected, $chatId);
        }

        // BANT: si matchea precio (#11) y faltan datos
        if ($reply->id == 11 && !$this->hasBantData($session)) {
            $session->bant_step = 'nombre';
            $session->save();
            (new TelegramSendService())->sendText($expected, $chatId,
                "👋 ¡Perfecto! Para darte el precio que más te conviene, necesito 2 datos rápidos.\n\n*¿Cómo te llamas?*"
            );
            $convSvc->recordMessage($session, $device, 'assistant', '[BANT pregunta nombre]', 'pricing', 11);
            return response()->json(['ok' => true]);
        }

        // Enviar respuesta
        (new TelegramSendService())->sendText($expected, $chatId, $replyText);
        $convSvc->recordMessage($session, $device, 'assistant', mb_substr($replyText, 0, 500), $conv['intent'] ?? null, $reply->id);

        $session->is_new_contact = 0;
        $session->last_reply_at  = Carbon::now();
        $session->save();

        return response()->json(['ok' => true]);
    }

    private function findRule(int $deviceId, string $message): ?Reply
    {
        $msgLower = mb_strtolower(trim($message));
        $rules = Reply::where('device_id', $deviceId)
            ->whereNull('parent_reply_id')
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $r) {
            $keywords = array_filter(array_map('trim', explode(',', mb_strtolower($r->keyword ?? ''))));
            if (empty($keywords)) continue;
            foreach ($keywords as $kw) {
                if ($r->match_type === 'equal' && $msgLower === $kw) return $r;
                if ($r->match_type === 'like'  && str_contains($msgLower, $kw)) return $r;
            }
            if ($r->match_type === 'any') return $r; // catch-all
        }
        return null;
    }

    private function hasBantData(ChatbotSession $session): bool
    {
        $d = json_decode($session->lead_data ?? '{}', true) ?: [];
        return !empty($d['nombre']) && (!empty($d['cantidad_dispositivos']) || !empty($d['cantidad']));
    }

    private function advanceBantViaTelegram(Device $device, string $contact, string $chatId, string $value, ChatbotSession $session): void
    {
        $tg = new TelegramSendService();
        $token = $device->telegram_token ?: env('TELEGRAM_BOT_TOKEN');
        $data  = json_decode($session->lead_data ?? '{}', true) ?: [];

        if ($session->bant_step === 'nombre') {
            if (mb_strlen($value) < 2 || preg_match('/^\d+$/', $value)) {
                $tg->sendText($token, $chatId, "Disculpa, no entendí tu nombre 😅 ¿Solo letras por favor?");
                return;
            }
            $data['nombre'] = mb_substr($value, 0, 80);
            $session->lead_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $session->bant_step = 'cantidad';
            $session->save();
            $tg->sendText($token, $chatId, "Gracias *{$data['nombre']}* 🙌\n\n*¿Cuántos GPS necesitas aproximadamente?* (ej: 5, 50, 200)");
            return;
        }

        if ($session->bant_step === 'cantidad') {
            if (!preg_match('/(\d{1,6})/', $value, $m)) {
                $tg->sendText($token, $chatId, "Necesito un número 😊 (ej: *10*, *50*, *200*).");
                return;
            }
            $qty = (int) $m[1];
            $data['cantidad_dispositivos'] = $qty;
            $session->lead_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $session->bant_step = null;
            $session->save();

            // Crear/actualizar lead
            $lead = ChatbotLead::firstOrNew(['device_id'=>$device->id, 'contact'=>$contact]);
            $lead->user_id = $device->user_id;
            $lead->status  = 'new';
            $lead->contact_name = $data['nombre'] ?? '';
            $lead->interest = 'precio_' . $qty . '_dispositivos';
            $lead->full_data = json_encode(['lead_data'=>$data, 'channel'=>'telegram'], JSON_UNESCAPED_UNICODE);
            $lead->save();
            try { (new LeadScoringService())->applyToLead($lead, $session); } catch (\Throwable $e) {}

            // Enviar precio personalizado
            $price = $this->personalizedPrice($qty, $data['nombre'] ?? '');
            $tg->sendText($token, $chatId, $price);
        }
    }

    private function personalizedPrice(int $qty, string $name): string
    {
        // Mismo cálculo que en BulkController.personalizedPriceMessage
        if ($qty >= 200) {
            $tier = ['plan'=>'EMPRESA','range'=>'200+','m'=>0.50,'a'=>5.00,'app_free'=>true];
        } elseif ($qty >= 101) {
            $tier = ['plan'=>'PRO','range'=>'101–200','m'=>0.75,'a'=>6.00,'app_free'=>false];
        } elseif ($qty >= 26) {
            $tier = ['plan'=>'CRECE','range'=>'26–100','m'=>1.00,'a'=>6.00,'app_free'=>false];
        } else {
            $tier = ['plan'=>'INICIA','range'=>'1–25','m'=>1.00,'a'=>7.00,'app_free'=>false];
        }
        $hi = $name ? "*{$name}*, " : '';
        $monthlyTotal = (int) round($tier['m'] * $qty);
        $annualTotal  = (int) round($tier['a'] * $qty);
        $appPerk = $tier['app_free']
            ? "✅ App móvil personalizada *GRATIS* (incluida con plan EMPRESA)"
            : "✅ App móvil personalizada disponible: \$155 USD pago único";

        return "💰 *Precio personalizado para {$qty} GPS*\n\n"
             . "{$hi}según tu volumen — Plan *{$tier['plan']}* ({$tier['range']} unidades):\n\n"
             . "🔸 Mensual: *\${$tier['m']} USD/u/mes* → ~\${$monthlyTotal} USD/mes\n"
             . "🔸 Anual: *\${$tier['a']} USD/u/año* → ~\${$annualTotal} USD/año\n\n"
             . $appPerk . "\n\n"
             . "📲 Escribe *demo* para ver la plataforma, *asesor* para hablar con un humano.";
    }

    private function personalizeText(string $text, ChatbotSession $session): string
    {
        $data = json_decode($session->lead_data ?? '{}', true) ?: [];
        return str_replace(
            ['{nombre}', '{telefono}', '{calendly_link}', '{paypal_link}', '{wompi_link}'],
            [
                $data['nombre'] ?? 'amigo',
                $session->contact,
                env('APPOGIO_CALENDLY_URL') ?: 'Escribe *asesor* y te enviamos el link',
                '(escribe *asesor* para link PayPal)',
                '(escribe *asesor* para link Wompi)',
            ],
            $text
        );
    }

    private function triggerHandoffTelegram(Device $device, ChatbotSession $session, string $lastMessage, string $token, string $chatId): void
    {
        $session->is_paused        = 1;
        $session->paused_until     = Carbon::now()->addHours(24);
        $session->human_handoff_at = Carbon::now();
        $session->save();

        $agentNumber = (new AgentDispatcherService())->pickAgent($device, null);
        if ($agentNumber) {
            $leadData = json_decode($session->lead_data ?? '{}', true) ?: [];
            $name = $leadData['nombre'] ?? $session->contact;
            $note = "🔔 *Lead via TELEGRAM*\n"
                  . "Nombre: {$name}\n"
                  . "Chat ID: {$chatId}\n"
                  . "Mensaje: " . mb_substr($lastMessage, 0, 200) . "\n\n"
                  . "Para responder al cliente:\n"
                  . "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text=Tu+mensaje";

            // Notificar al agente vía WhatsApp del bot (assume device->id sigue activo)
            \Illuminate\Support\Facades\Http::timeout(5)->post(
                env('WA_SERVER_URL') . '/chats/send?id=device_' . $device->id,
                ['receiver' => $agentNumber, 'message' => ['text' => $note]]
            );
        }

        (new OutboundWebhookService())->emit('handoff.opened', [
            'device_id' => $device->id,
            'contact'   => $session->contact,
            'channel'   => 'telegram',
            'chat_id'   => $chatId,
            'lead_data' => json_decode($session->lead_data ?? '{}', true) ?: [],
        ]);
    }
}
