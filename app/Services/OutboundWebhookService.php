<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook de salida para n8n / Zapier / cualquier endpoint externo.
 *
 * Eventos soportados:
 *   - lead.created     → cuando se crea un nuevo lead (tras BANT)
 *   - lead.updated     → cuando se actualiza datos de un lead existente
 *   - handoff.opened   → cuando un cliente pide asesor en horario humano
 *   - handoff.closed   → cuando un agente marca handoff como cerrado
 *   - nps.received     → cuando un cliente responde NPS
 *   - sentiment.alert  → cuando se detecta sentiment negative/urgent
 *
 * Configurable vía env APPOGIO_OUTBOUND_WEBHOOK_URL. Si no está set, no-op.
 *
 * Fail-safe: cualquier error de red/timeout es loggeado pero NO interrumpe el flujo.
 * Llamadas son fire-and-forget (timeout corto).
 */
class OutboundWebhookService
{
    private const TIMEOUT_SECONDS = 3;

    public function emit(string $event, array $payload): void
    {
        $url = env('APPOGIO_OUTBOUND_WEBHOOK_URL');
        if (!$url) return;

        try {
            $body = [
                'event'     => $event,
                'timestamp' => Carbon::now()->toIso8601String(),
                'payload'   => $payload,
            ];

            $secret = env('APPOGIO_OUTBOUND_WEBHOOK_SECRET');
            $headers = ['Content-Type' => 'application/json'];
            if ($secret) {
                $headers['X-Appogio-Signature'] = hash_hmac('sha256', json_encode($body), $secret);
            }

            Http::withHeaders($headers)->timeout(self::TIMEOUT_SECONDS)->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning("OutboundWebhook[{$event}] failed: " . $e->getMessage());
        }
    }
}
