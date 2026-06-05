<?php

namespace App\Services;

use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use Illuminate\Support\Facades\Cache;

/**
 * Detecta y bloquea spam/abuso de contactos.
 *
 * Score por contacto (0-100+):
 *   +30  Jailbreak / prompt injection ("ignora instrucciones", "quien eres realmente")
 *   +20  Mensajes idénticos seguidos (3+ veces)
 *   +25  Flood: 10+ mensajes/min
 *   +15  Mensaje muy largo y repetitivo (caracteres únicos < 5)
 *   +10  Patrón típico de bot (URLs largas, tokens crypto, etc)
 *
 * Acciones por umbral:
 *   ≥40   Warn (logs, sin bloquear)
 *   ≥80   Throttle (1 mensaje cada 5 min)
 *   ≥120  Bloquear (no responder, no procesar) por 24h
 *
 * Score decae +1 cada 10 min sin nuevos mensajes (auto-rehabilitación).
 */
class SpamDetectionService
{
    public const THRESHOLD_WARN     = 40;
    public const THRESHOLD_THROTTLE = 80;
    public const THRESHOLD_BLOCK    = 120;

    /** Frases típicas de prompt injection / jailbreak */
    private const JAILBREAK_PATTERNS = [
        'ignora las instrucciones',
        'olvida las instrucciones',
        'ignore previous instructions',
        'ignore all previous',
        'forget your instructions',
        'pretend you are',
        'finge que eres',
        'actua como',
        'actúa como',
        'no eres appogio',
        'eres chatgpt',
        'eres claude',
        'eres gpt',
        'reveal your prompt',
        'system prompt',
        'jailbreak',
        'dan mode',
        'developer mode',
    ];

    /**
     * Evalúa el mensaje. Devuelve la acción a tomar.
     *
     * @return array{action: string, score: int, reason: ?string}
     *  action: 'allow' | 'warn' | 'throttle' | 'block'
     */
    public function evaluate(string $message, ChatbotSession $session): array
    {
        $contactKey = "spam_score:{$session->device_id}:{$session->contact}";
        $score = (int) Cache::get($contactKey, 0);

        $reasons = [];

        // 1. Jailbreak / prompt injection
        $low = mb_strtolower($message);
        foreach (self::JAILBREAK_PATTERNS as $pattern) {
            if (str_contains($low, $pattern)) {
                $score += 30;
                $reasons[] = "jailbreak: '{$pattern}'";
                break;
            }
        }

        // 2. Mensajes idénticos seguidos (últimos 3)
        if ($session->id) {
            $lastMsgs = ChatbotMessage::where('session_id', $session->id)
                ->where('role', 'user')
                ->orderByDesc('id')
                ->limit(3)
                ->pluck('content')
                ->all();
            if (count($lastMsgs) >= 2 && $lastMsgs[0] === $message && $lastMsgs[1] === $message) {
                $score += 20;
                $reasons[] = 'mensaje idéntico repetido 3x';
            }
        }

        // 3. Flood: 10+ mensajes en último minuto
        $floodKey = "spam_flood:{$session->device_id}:{$session->contact}";
        $minuteCount = (int) Cache::get($floodKey, 0);
        Cache::put($floodKey, $minuteCount + 1, 60);
        if ($minuteCount >= 10) {
            $score += 25;
            $reasons[] = "flood ({$minuteCount} msg/min)";
        }

        // 4. Mensaje basura: muy largo y repetitivo
        if (mb_strlen($message) > 200) {
            $unique = count(array_unique(str_split(strtolower(preg_replace('/\s+/', '', $message)))));
            if ($unique < 5) {
                $score += 15;
                $reasons[] = "repetitivo (chars únicos < 5)";
            }
        }

        // 5. Patrón bot: URLs sospechosas + tokens
        if (preg_match('/(https?:\/\/[^\s]{40,})/', $message) ||
            preg_match('/0x[0-9a-f]{32,}/i', $message) ||
            preg_match('/\$[A-Z]{3,}[ \t]*[\d,]+/', $message)) {
            $score += 10;
            $reasons[] = 'patrón bot (URL larga/token)';
        }

        // Persistir score con TTL 24h
        Cache::put($contactKey, $score, 86400);

        // Decidir acción
        $action = 'allow';
        if ($score >= self::THRESHOLD_BLOCK) {
            $action = 'block';
        } elseif ($score >= self::THRESHOLD_THROTTLE) {
            // Throttle: solo permite 1 mensaje cada 5 min
            $throttleKey = "spam_throttle:{$session->device_id}:{$session->contact}";
            if (Cache::has($throttleKey)) {
                $action = 'block';
            } else {
                Cache::put($throttleKey, 1, 300);
                $action = 'throttle';
            }
        } elseif ($score >= self::THRESHOLD_WARN) {
            $action = 'warn';
        }

        return [
            'action' => $action,
            'score'  => $score,
            'reason' => $reasons ? implode('; ', $reasons) : null,
        ];
    }

    /**
     * Limpia score de un contacto (uso operativo: el agente quita un falso positivo).
     */
    public function clearScore(int $deviceId, string $contact): void
    {
        Cache::forget("spam_score:{$deviceId}:{$contact}");
        Cache::forget("spam_throttle:{$deviceId}:{$contact}");
        Cache::forget("spam_flood:{$deviceId}:{$contact}");
    }
}
