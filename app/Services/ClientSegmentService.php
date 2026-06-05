<?php

namespace App\Services;

use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use Illuminate\Support\Facades\DB;

/**
 * Detecta el segmento del cliente y aplica multipliers al precio.
 *
 * Cada device puede tener N segmentos en `client_segments` con keywords y
 * multiplier para mensual/anual. Si el LLM extrajo lead_data o el cliente
 * mencionó keywords del segmento en mensajes recientes, se aplica.
 *
 * Ejemplos default (creados para device 100):
 *   - Estudiante  → 30% descuento (multiplier 0.70)
 *   - ONG         → 25% descuento (multiplier 0.75)
 *   - Gobierno    → +20% (licitaciones, multiplier 1.20)
 *   - Recurrente  → 15% descuento (multiplier 0.85)
 */
class ClientSegmentService
{
    /**
     * Detecta segmento aplicable analizando últimos mensajes del cliente.
     *
     * @return array{
     *   name: string|null,
     *   discount_pct: int,
     *   multiplier_monthly: float,
     *   multiplier_annual: float,
     *   badge: string|null
     * }
     */
    public function detect(int $deviceId, ChatbotSession $session): array
    {
        $segments = DB::table('client_segments')
            ->where('device_id', $deviceId)
            ->where('is_active', 1)
            ->get();

        if ($segments->isEmpty()) return $this->noSegment();

        // Recopilar texto reciente del cliente (últimos 15 mensajes)
        $messages = ChatbotMessage::where('session_id', $session->id)
            ->where('role', 'user')
            ->orderByDesc('id')
            ->limit(15)
            ->pluck('content')
            ->all();

        $haystack = mb_strtolower(implode(' ', $messages) . ' ' . ($session->lead_data ?? ''));

        foreach ($segments as $seg) {
            $keywords = array_filter(array_map('trim', explode(',', mb_strtolower($seg->keywords ?? ''))));
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains($haystack, $kw)) {
                    return [
                        'name'               => $seg->name,
                        'discount_pct'       => (int) $seg->discount_pct,
                        'multiplier_monthly' => (float) $seg->multiplier_monthly,
                        'multiplier_annual'  => (float) $seg->multiplier_annual,
                        'badge'              => $seg->badge_emoji,
                    ];
                }
            }
        }

        return $this->noSegment();
    }

    private function noSegment(): array
    {
        return [
            'name'               => null,
            'discount_pct'       => 0,
            'multiplier_monthly' => 1.0,
            'multiplier_annual'  => 1.0,
            'badge'              => null,
        ];
    }
}
