<?php

namespace App\Console\Commands;

use App\Models\ChatbotHandoff;
use App\Models\ChatbotLead;
use App\Models\ChatbotSession;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * chatbot:follow-up — corre cada hora vía cron.
 *
 * Tres seguimientos automáticos:
 *
 * 1. PING AL AGENTE: handoff status=pending sin resolución >2h durante horario
 *    humano (8-19 Bogotá) → recuerda al agent_number que hay un lead esperando.
 *
 * 2. RE-ENGAGEMENT POST-HANDOFF: handoff status=closed sin actividad del cliente
 *    en su sesión >24h pero <48h → mensaje "¿pudiste ver lo que conversamos?"
 *
 * 3. CART RECOVERY (cubre Ola 3 #9): lead con interest=precio_* y >48h sin
 *    actividad en sesión, sin handoff cerrado posterior → mensaje recovery.
 *
 * Idempotente: marca cada follow-up enviado en chatbot_handoffs.full_data
 * (JSON) y chatbot_leads.full_data para no duplicar.
 */
class ChatbotFollowUp extends Command
{
    protected $signature   = 'chatbot:follow-up {--dry-run : No envía mensajes, solo lista}';
    protected $description = 'Recordatorios automáticos: ping a agentes, re-engagement de clientes y cart recovery.';

    private bool $dryRun = false;
    private int  $sent   = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $this->pingAgentsForPendingHandoffs();
        $this->reEngagePostHandoff();
        $this->cartRecovery();
        $this->npsSurvey();

        $this->info("Follow-ups enviados: {$this->sent}" . ($this->dryRun ? ' (DRY-RUN)' : ''));
        return self::SUCCESS;
    }

    /**
     * Encuesta NPS 48h después de cerrar un handoff. Una sola vez por handoff.
     */
    private function npsSurvey(): void
    {
        $cutoffMin = Carbon::now()->subHours(72);
        $cutoffMax = Carbon::now()->subHours(48);

        $rows = ChatbotHandoff::where('status', 'closed')
            ->whereNull('nps')
            ->whereBetween('resolved_at', [$cutoffMin, $cutoffMax])
            ->limit(50)
            ->get();

        foreach ($rows as $h) {
            $meta = $this->loadMeta($h, 'nps_sent_at');
            if (!empty($meta['nps_sent_at'])) continue;

            $device = Device::find($h->device_id);
            if (!$device) continue;

            $session = ChatbotSession::where('device_id', $h->device_id)
                ->where('contact', $h->contact)
                ->first();
            $lang = $session?->locked_language ?: 'es';
            $name = $this->leadName($h->device_id, $h->contact);
            $hi   = $name ? "*{$name}*, " : '';

            $msg = match ($lang) {
                'en' => "🌟 {$hi}quick favor: how was your experience with our advisor?\n\n"
                      . "Reply with a number from *1* (poor) to *5* (excellent). It takes 3 seconds and helps us improve. 🙏",
                'pt' => "🌟 {$hi}um pequeno favor: como foi sua experiência com nosso consultor?\n\n"
                      . "Responda com um número de *1* (ruim) a *5* (excelente). Leva 3 segundos e nos ajuda a melhorar. 🙏",
                default => "🌟 {$hi}un favor rápido: ¿cómo estuvo tu experiencia con nuestro asesor?\n\n"
                      . "Responde con un número del *1* (malo) al *5* (excelente). Toma 3 segundos y nos ayuda a mejorar. 🙏",
            };

            if (!$this->dryRun) {
                $this->sendWa($device, $h->contact, $msg);
                $this->saveMeta($h, ['nps_sent_at' => Carbon::now()->toIso8601String()]);
                if ($session) {
                    $session->nps_pending = 1;
                    $session->save();
                }
            }
            $this->sent++;
            $this->line("  → NPS survey {$h->contact} ({$lang})");
        }
    }

    private function pingAgentsForPendingHandoffs(): void
    {
        if (!$this->isHumanHoursNow()) {
            $this->line('Skip ping agentes: fuera de 8-19 Bogotá.');
            return;
        }

        $cutoff = Carbon::now()->subHours(2);

        $rows = ChatbotHandoff::where('status', 'pending')
            ->where('updated_at', '<=', $cutoff)
            ->limit(50)
            ->get();

        foreach ($rows as $h) {
            $meta = $this->loadMeta($h, 'agent_pinged_at');
            if (!empty($meta['agent_pinged_at'])) {
                continue; // ya se notificó este recordatorio
            }

            $device = Device::find($h->device_id);
            if (!$device) continue;
            $agentNumber = json_decode($device->meta ?? '{}', true)['agent_number'] ?? null;
            if (!$agentNumber) continue;

            $name = $this->leadName($h->device_id, $h->contact);
            $age  = Carbon::parse($h->updated_at)->diffForHumans();
            $msg  = "⏰ *Recordatorio handoff pendiente*\n"
                  . "👤 {$name} ({$h->contact})\n"
                  . "💬 Último mensaje: " . mb_substr($h->last_message ?? '', 0, 120) . "\n"
                  . "🕐 Esperando hace {$age}\n\n"
                  . "Responde directamente a este número o escribe *#bot* al chat para reactivar el bot.";

            if (!$this->dryRun) {
                $this->sendWa($device, $agentNumber, $msg);
                $this->saveMeta($h, ['agent_pinged_at' => Carbon::now()->toIso8601String()]);
            }
            $this->sent++;
            $this->line("  → ping agente {$agentNumber} sobre {$h->contact}");
        }
    }

    private function reEngagePostHandoff(): void
    {
        $cutoffMin = Carbon::now()->subHours(48);
        $cutoffMax = Carbon::now()->subHours(24);

        $rows = ChatbotHandoff::where('status', 'closed')
            ->whereBetween('resolved_at', [$cutoffMin, $cutoffMax])
            ->limit(50)
            ->get();

        foreach ($rows as $h) {
            $meta = $this->loadMeta($h, 'reengage_sent_at');
            if (!empty($meta['reengage_sent_at'])) continue;

            $device = Device::find($h->device_id);
            if (!$device) continue;

            $session = ChatbotSession::where('device_id', $h->device_id)
                ->where('contact', $h->contact)
                ->first();
            $lang = $session?->locked_language ?: 'es';

            $name = $this->leadName($h->device_id, $h->contact);
            $hi   = $name ? "*{$name}*, " : '';

            $msg = match ($lang) {
                'en' => "👋 {$hi}quick check-in: were we able to clear up your questions about APPOGIO GPS?\n\n"
                      . "If you'd like to keep moving forward — type *demo* to see the platform live or *precio* for personalized pricing.",
                'pt' => "👋 {$hi}rápida verificação: conseguimos esclarecer suas dúvidas sobre APPOGIO GPS?\n\n"
                      . "Se quiser seguir em frente — digite *demo* para ver a plataforma ao vivo ou *precio* para preços personalizados.",
                default => "👋 {$hi}para retomar: ¿pudimos resolver tus dudas sobre APPOGIO GPS?\n\n"
                      . "Si quieres seguir avanzando — escribe *demo* para ver la plataforma en vivo o *precio* para precios personalizados.",
            };

            if (!$this->dryRun) {
                $this->sendWa($device, $h->contact, $msg);
                $this->saveMeta($h, ['reengage_sent_at' => Carbon::now()->toIso8601String()]);
            }
            $this->sent++;
            $this->line("  → re-engage cliente {$h->contact} ({$lang})");
        }
    }

    private function cartRecovery(): void
    {
        $cutoffMin = Carbon::now()->subHours(120);
        $cutoffMax = Carbon::now()->subHours(48);

        // Leads con interest precio_* sin actividad de sesión 48-120h
        $leads = ChatbotLead::where('interest', 'like', 'precio_%')
            ->whereBetween('updated_at', [$cutoffMin, $cutoffMax])
            ->limit(50)
            ->get();

        foreach ($leads as $lead) {
            $full = json_decode($lead->full_data ?? '{}', true) ?: [];
            if (!empty($full['cart_recovery_sent_at'])) continue;

            // Skip si hay handoff cerrado posterior al lead (ya se manejó re-engage)
            $hasClosedHandoff = ChatbotHandoff::where('device_id', $lead->device_id)
                ->where('contact', $lead->contact)
                ->where('status', 'closed')
                ->where('resolved_at', '>=', $lead->updated_at)
                ->exists();
            if ($hasClosedHandoff) continue;

            $device = Device::find($lead->device_id);
            if (!$device) continue;

            $session = ChatbotSession::where('device_id', $lead->device_id)
                ->where('contact', $lead->contact)
                ->first();
            $lang = $session?->locked_language ?: 'es';
            $name = $lead->contact_name ?: '';
            $hi   = $name ? "*{$name}*, " : '';

            $qty  = $full['lead_data']['cantidad_dispositivos'] ?? null;
            $qtyText = $qty ? " ({$qty} GPS)" : '';

            $msg = match ($lang) {
                'en' => "👋 {$hi}circling back on your APPOGIO GPS quote{$qtyText}.\n\n"
                      . "Anything I can help clarify? *Promo activa*: pay annually, save up to 50%.\n\n"
                      . "Type *asesor* to talk to a sales rep or *demo* to test the platform.",
                'pt' => "👋 {$hi}voltando sobre sua cotação APPOGIO GPS{$qtyText}.\n\n"
                      . "Posso esclarecer alguma dúvida? *Promo ativa*: pagamento anual, economize até 50%.\n\n"
                      . "Digite *asesor* para falar com um consultor ou *demo* para testar a plataforma.",
                default => "👋 {$hi}retomando tu cotización de APPOGIO GPS{$qtyText}.\n\n"
                      . "¿Hay algo que pueda aclararte? *Promo activa*: pago anual, ahorras hasta 50%.\n\n"
                      . "Escribe *asesor* para hablar con un vendedor o *demo* para probar la plataforma.",
            };

            if (!$this->dryRun) {
                $this->sendWa($device, $lead->contact, $msg);
                $full['cart_recovery_sent_at'] = Carbon::now()->toIso8601String();
                $lead->full_data = json_encode($full, JSON_UNESCAPED_UNICODE);
                $lead->save();
            }
            $this->sent++;
            $this->line("  → recovery {$lead->contact} ({$lang}, qty={$qty})");
        }
    }

    private function isHumanHoursNow(): bool
    {
        $h = (int) Carbon::now('America/Bogota')->format('H');
        return $h >= 8 && $h < 19;
    }

    private function leadName(int $deviceId, string $contact): string
    {
        $lead = ChatbotLead::where('device_id', $deviceId)->where('contact', $contact)->first();
        return $lead?->contact_name ?: $contact;
    }

    private function loadMeta(ChatbotHandoff $h, string $checkKey): array
    {
        $raw = DB::table('chatbot_handoffs')->where('id', $h->id)->value('full_data');
        return json_decode($raw ?? '{}', true) ?: [];
    }

    private function saveMeta(ChatbotHandoff $h, array $patch): void
    {
        $raw = DB::table('chatbot_handoffs')->where('id', $h->id)->value('full_data');
        $current = json_decode($raw ?? '{}', true) ?: [];
        $merged  = array_merge($current, $patch);
        DB::table('chatbot_handoffs')->where('id', $h->id)->update([
            'full_data'  => json_encode($merged, JSON_UNESCAPED_UNICODE),
            'updated_at' => Carbon::now(),
        ]);
    }

    private function sendWa(Device $device, string $to, string $text): void
    {
        try {
            Http::timeout(8)->post(env('WA_SERVER_URL') . '/chats/send?id=device_' . $device->id, [
                'receiver' => $to,
                'message'  => ['text' => $text],
            ]);
        } catch (\Throwable $e) {
            $this->warn("WA send failed for {$to}: " . $e->getMessage());
        }
    }
}
