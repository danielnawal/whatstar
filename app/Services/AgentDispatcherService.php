<?php

namespace App\Services;

use App\Models\ChatbotAgent;
use App\Models\Device;
use Carbon\Carbon;

/**
 * Asigna handoffs a agentes humanos con estrategia round-robin / por carga.
 *
 * Si el device tiene 1+ agentes activos en `chatbot_agents`, distribuye así:
 *   1. Si el lead tiene país detectado → preferir agentes de esa región
 *   2. De los candidatos, elegir el que menos handoffs recibió HOY
 *   3. Empate → el que recibió hace más tiempo (last_assigned_at)
 *
 * Si NO hay agentes registrados → fallback al `meta.agent_number` del device
 * (comportamiento legacy, single-agent).
 */
class AgentDispatcherService
{
    /**
     * @return string|null  número de WhatsApp del agente asignado, o null si no hay
     */
    public function pickAgent(Device $device, ?string $leadCountry = null): ?string
    {
        $agents = ChatbotAgent::where('device_id', $device->id)
            ->where('is_active', true)
            ->get();

        if ($agents->isEmpty()) {
            // Legacy: usar meta.agent_number
            $meta = json_decode($device->meta ?? '{}', true);
            return $meta['agent_number'] ?? null;
        }

        // Reset contador diario si pasó medianoche
        $this->resetDailyCounters($agents);

        // Filtrar por región si tiene país
        $candidates = $agents;
        if ($leadCountry) {
            $regional = $agents->where('region', $leadCountry);
            if ($regional->isNotEmpty()) $candidates = $regional;
        }

        // Round-robin: el que menos handoffs recibió hoy + el más antiguo
        $picked = $candidates
            ->sortBy([
                ['handoffs_received_today', 'asc'],
                ['last_assigned_at',        'asc'],
            ])
            ->first();

        if (!$picked) return null;

        // Actualizar contadores
        $picked->update([
            'last_assigned_at'         => Carbon::now(),
            'handoffs_received_today'  => $picked->handoffs_received_today + 1,
            'handoffs_received_total'  => $picked->handoffs_received_total + 1,
        ]);

        return $picked->phone;
    }

    /**
     * Reset de contador diario para agentes que no se actualizaron hoy.
     */
    private function resetDailyCounters($agents): void
    {
        $today = Carbon::now()->toDateString();
        foreach ($agents as $a) {
            $lastDate = optional($a->last_assigned_at)->toDateString();
            if ($lastDate && $lastDate !== $today && $a->handoffs_received_today > 0) {
                $a->update(['handoffs_received_today' => 0]);
            }
        }
    }
}
