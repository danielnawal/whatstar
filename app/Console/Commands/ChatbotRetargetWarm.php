<?php

namespace App\Console\Commands;

use App\Models\ChatbotLead;
use App\Models\ChatbotSession;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * chatbot:retarget-warm — corre 1 vez al día (cron).
 *
 * Busca leads con score_category='warm' (31-60) que:
 *   - Lleven 3+ días sin actividad en la sesión
 *   - No tengan handoff activo (pending)
 *   - No hayan recibido este retargeting ya
 *
 * Envía mensaje personalizado según idioma y volumen del lead.
 * Límite: 50 por ejecución para no sobrecargar el servidor WA.
 */
class ChatbotRetargetWarm extends Command
{
    protected $signature   = 'chatbot:retarget-warm {--dry-run : Solo lista, no envía}';
    protected $description = 'Re-targeting de leads warm (score 31-60) sin actividad 3+ días.';

    private bool $dryRun = false;
    private int  $sent   = 0;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $cutoff = Carbon::now()->subDays(3);

        $leads = ChatbotLead::where('score_category', 'warm')
            ->where('updated_at', '<=', $cutoff)
            ->whereNotIn('status', ['closed', 'lost'])
            ->limit(50)
            ->get();

        foreach ($leads as $lead) {
            $full = json_decode($lead->full_data ?? '{}', true) ?: [];
            if (!empty($full['warm_retarget_sent_at'])) continue;

            // Skip si hay handoff pendiente activo
            $hasPending = \App\Models\ChatbotHandoff::where('device_id', $lead->device_id)
                ->where('contact', $lead->contact)
                ->where('status', 'pending')
                ->exists();
            if ($hasPending) continue;

            $device = Device::find($lead->device_id);
            if (!$device) continue;

            $session = ChatbotSession::where('device_id', $lead->device_id)
                ->where('contact', $lead->contact)
                ->first();
            $lang = $session?->locked_language ?: 'es';

            $name  = $lead->contact_name ?: '';
            $hi    = $name ? "*{$name}*, " : '';
            $score = $lead->score ?? 0;

            // Personalizar según volumen si está disponible
            $leadData = json_decode($full['lead_data'] ?? '{}', true) ?: [];
            $qty      = $leadData['cantidad_dispositivos'] ?? null;
            $qtyText  = $qty ? " con *{$qty} dispositivos*" : '';

            $msg = $this->buildMessage($lang, $hi, $qtyText, $score);

            if (!$this->dryRun) {
                $this->sendWa($device, $lead->contact, $msg);
                $full['warm_retarget_sent_at'] = Carbon::now()->toIso8601String();
                $lead->full_data = json_encode($full, JSON_UNESCAPED_UNICODE);
                $lead->save();
            }

            $this->sent++;
            $this->line("  → retarget warm [{$score}pts] {$lead->contact} ({$lang})" . ($qty ? ", qty={$qty}" : ''));
        }

        $label = $this->dryRun ? ' (DRY-RUN)' : '';
        $this->info("Re-targeting warm completado: {$this->sent} enviados{$label}.");
        return self::SUCCESS;
    }

    private function buildMessage(string $lang, string $hi, string $qtyText, int $score): string
    {
        // Score 50-60: mensaje más directo (cerca de hot)
        $urgent = $score >= 50;

        return match ($lang) {
            'en' => $urgent
                ? "👋 {$hi}checking in on your APPOGIO GPS interest{$qtyText}.\n\n"
                  . "We have a limited-time promo running right now — *annual plans up to 50% off*.\n\n"
                  . "Want me to send a personalized quote? Just type *precio* or reply here."
                : "👋 {$hi}still thinking about APPOGIO GPS{$qtyText}?\n\n"
                  . "Happy to answer any questions or send you a custom quote.\n\n"
                  . "Type *demo* to test the platform or *asesor* to talk to a human.",

            'pt' => $urgent
                ? "👋 {$hi}voltando sobre seu interesse em APPOGIO GPS{$qtyText}.\n\n"
                  . "Temos uma promo por tempo limitado — *planos anuais com até 50% de desconto*.\n\n"
                  . "Quer que eu envie uma cotação personalizada? Digite *precio* ou responda aqui."
                : "👋 {$hi}ainda pensando no APPOGIO GPS{$qtyText}?\n\n"
                  . "Posso responder dúvidas ou enviar uma cotação personalizada.\n\n"
                  . "Digite *demo* para testar a plataforma ou *asesor* para falar com um consultor.",

            default => $urgent
                ? "👋 {$hi}retomando tu interés en APPOGIO GPS{$qtyText}.\n\n"
                  . "Tenemos una promo activa ahora — *planes anuales hasta 50% de ahorro*.\n\n"
                  . "¿Te envío una cotización personalizada? Escribe *precio* o responde aquí."
                : "👋 {$hi}¿sigues evaluando APPOGIO GPS{$qtyText}?\n\n"
                  . "Puedo resolver dudas o enviarte una cotización a medida.\n\n"
                  . "Escribe *demo* para probar la plataforma o *asesor* para hablar con un humano.",
        };
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
