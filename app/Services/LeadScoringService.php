<?php

namespace App\Services;

use App\Models\ChatbotHandoff;
use App\Models\ChatbotLead;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use Illuminate\Support\Facades\DB;

/**
 * Calcula un score 0-100 para cada lead, basado en señales heurísticas.
 *
 * Categorías:
 *   - cold (0-30): poco interés, lead frío
 *   - warm (31-60): interés moderado, vale follow-up
 *   - hot (61-100): alta probabilidad de cierre, atender YA
 *
 * Señales (suman/restan al score):
 *   + cantidad GPS (0 a +50): mayor volumen = mayor score
 *   + país conocido (+10): tiene prefijo identificable
 *   + sentiment positive/urgent (+15) o negative (-10)
 *   + intent fuerte de compra (+15): pricing, checkout, schedule_demo
 *   + pidió asesor (+20)
 *   + repeticiones (+10): cliente que volvió 2+ veces
 *   + tiene nombre + empresa (+10)
 *   + NPS previo ≥4 (+15): cliente recurrente positivo
 *   + sesión activa <24h (+5): caliente
 *
 * Usar:
 *   $score = (new LeadScoringService())->score($lead, $session);
 *   // El score se persiste en chatbot_leads.score y .score_category
 */
class LeadScoringService
{
    /** @return array{score:int, category:string, breakdown:array} */
    public function score(ChatbotLead $lead, ?ChatbotSession $session = null): array
    {
        $session = $session ?? ChatbotSession::where('device_id', $lead->device_id)
            ->where('contact', $lead->contact)
            ->first();

        $leadData = is_array($lead->full_data ?? null)
            ? ($lead->full_data['lead_data'] ?? [])
            : (json_decode($lead->full_data ?? '{}', true)['lead_data'] ?? []);

        $score = 0;
        $breakdown = [];

        // 1. Cantidad GPS (0-50 pts)
        $qty = (int) ($leadData['cantidad_dispositivos'] ?? 0);
        $qtyScore = match (true) {
            $qty >= 200 => 50,
            $qty >= 101 => 40,
            $qty >= 26  => 25,
            $qty >= 5   => 15,
            $qty >= 1   => 10,
            default     => 0,
        };
        $score += $qtyScore;
        $breakdown['quantity'] = ['value' => $qty, 'pts' => $qtyScore];

        // 2. País conocido (+10)
        $country = $this->detectCountry($lead->contact);
        if ($country) {
            $score += 10;
            $breakdown['country'] = ['value' => $country, 'pts' => 10];
        }

        // 3. Tiene nombre + empresa (+10)
        $hasName = !empty($lead->contact_name) || !empty($leadData['nombre']);
        $hasCompany = !empty($leadData['empresa']);
        if ($hasName && $hasCompany) {
            $score += 10;
            $breakdown['name_company'] = ['pts' => 10];
        } elseif ($hasName) {
            $score += 5;
            $breakdown['name_only'] = ['pts' => 5];
        }

        // Si hay sesión, evaluar señales conversacionales
        if ($session) {
            // 4. Último sentiment del cliente (-10 a +15)
            $lastMsg = ChatbotMessage::where('session_id', $session->id)
                ->where('role', 'user')
                ->orderByDesc('id')->first();
            if ($lastMsg) {
                $sentScore = match ($lastMsg->sentiment) {
                    'urgent'   => 15,
                    'positive' => 12,
                    'neutral'  => 5,
                    'negative' => -10,
                    default    => 0,
                };
                $score += $sentScore;
                if ($sentScore !== 0) {
                    $breakdown['sentiment'] = ['value' => $lastMsg->sentiment, 'pts' => $sentScore];
                }

                // 5. Intent de compra (+15 si reciente)
                $buyIntents = ['pricing', 'checkout', 'schedule_demo', 'wants_demo', 'has_business'];
                if (in_array($lastMsg->intent, $buyIntents, true)) {
                    $score += 15;
                    $breakdown['intent'] = ['value' => $lastMsg->intent, 'pts' => 15];
                }
            }

            // 6. Pidió asesor (+20) — vía handoff o intent reciente
            $handoffExists = ChatbotHandoff::where('device_id', $lead->device_id)
                ->where('contact', $lead->contact)->exists();
            if ($handoffExists) {
                $score += 20;
                $breakdown['handoff'] = ['pts' => 20];
            }

            // 7. Sesión muy reciente (+5) — está caliente AHORA
            if ($session->updated_at && $session->updated_at->diffInHours(now()) < 1) {
                $score += 5;
                $breakdown['hot_now'] = ['pts' => 5];
            }
        }

        // 8. NPS previo ≥4 (+15) — cliente recurrente positivo
        $bestNps = ChatbotHandoff::where('device_id', $lead->device_id)
            ->where('contact', $lead->contact)
            ->whereNotNull('nps')
            ->max('nps');
        if ($bestNps && $bestNps >= 4) {
            $score += 15;
            $breakdown['nps_history'] = ['value' => $bestNps, 'pts' => 15];
        }

        // 9. Repeticiones — cliente volvió N veces (+10 si 2+ leads históricos)
        $totalLeads = ChatbotLead::where('device_id', $lead->device_id)
            ->where('contact', $lead->contact)->count();
        if ($totalLeads >= 2) {
            $score += 10;
            $breakdown['repeat_visits'] = ['value' => $totalLeads, 'pts' => 10];
        }

        // Cap a 100, floor a 0
        $score = max(0, min(100, $score));

        $category = match (true) {
            $score >= 61 => 'hot',
            $score >= 31 => 'warm',
            default      => 'cold',
        };

        return [
            'score'     => $score,
            'category'  => $category,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Persiste score y categoría en el lead.
     */
    public function applyToLead(ChatbotLead $lead, ?ChatbotSession $session = null): array
    {
        $result = $this->score($lead, $session);
        $lead->score          = $result['score'];
        $lead->score_category = $result['category'];
        $lead->save();
        return $result;
    }

    /**
     * Detecta país por prefijo telefónico de los más comunes en LATAM/internacionales.
     */
    private function detectCountry(string $phone): ?string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) < 8) return null;

        $prefixes = [
            '57'  => 'CO', // Colombia
            '593' => 'EC', // Ecuador
            '52'  => 'MX', // México
            '51'  => 'PE', // Perú
            '54'  => 'AR', // Argentina
            '56'  => 'CL', // Chile
            '55'  => 'BR', // Brasil
            '58'  => 'VE', // Venezuela
            '591' => 'BO', // Bolivia
            '595' => 'PY', // Paraguay
            '598' => 'UY', // Uruguay
            '506' => 'CR', // Costa Rica
            '507' => 'PA', // Panamá
            '503' => 'SV', // El Salvador
            '502' => 'GT', // Guatemala
            '34'  => 'ES', // España
            '1'   => 'US', // USA/Canadá
        ];

        foreach (['593','591','595','598','506','507','503','502'] as $p) {
            if (str_starts_with($clean, $p)) return $prefixes[$p];
        }
        foreach (['57','52','51','54','56','55','58','34'] as $p) {
            if (str_starts_with($clean, $p)) return $prefixes[$p];
        }
        if (str_starts_with($clean, '1')) return $prefixes['1'];

        return null;
    }
}
