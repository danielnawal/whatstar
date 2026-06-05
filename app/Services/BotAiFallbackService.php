<?php

namespace App\Services;

use App\Models\ChatbotSession;
use App\Models\Device;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fallback AI multi-provider: cuando ningún reply estático cubre el mensaje
 * del cliente, pide a un LLM una respuesta natural acotada al producto APPOGIO.
 *
 * Providers soportados (en orden de preferencia, usa el primero configurado):
 *   1. GROQ_API_KEY      → Llama 3.1 70B (gratis hasta 14400 req/día)
 *   2. GEMINI_API_KEY    → Gemini 1.5 Flash (gratis hasta 1500 req/día)
 *   3. ANTHROPIC_API_KEY → Claude Haiku 4.5 (de pago)
 *
 * Fail-safe: sin ningún provider configurado o ante cualquier error devuelve null,
 * permitiendo que el flujo principal caiga al catch-all genérico.
 *
 * Cache: respuestas idénticas se cachean 7 días por (lang, hash(message)).
 * Rate limit: 30 llamadas/contacto/día.
 */
class BotAiFallbackService
{
    private const TIMEOUT_SECONDS         = 8;
    private const CACHE_TTL_SECONDS       = 604800; // 7 días
    private const PER_CONTACT_DAILY_LIMIT = 30;
    private const MAX_INPUT_CHARS         = 500;
    private const MAX_TOKENS              = 400;

    public function answer(string $message, ChatbotSession $session, Device $device): ?string
    {
        $message = trim($message);
        if ($message === '') {
            return null;
        }
        if (mb_strlen($message) > self::MAX_INPUT_CHARS) {
            $message = mb_substr($message, 0, self::MAX_INPUT_CHARS);
        }

        $lang     = $session->locked_language ?: 'es';
        $cacheKey = 'ai_fb:' . $lang . ':' . md5(mb_strtolower($message));

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Rate limit por contacto/día
        $rlKey = 'ai_rl:' . $device->id . ':' . $session->contact . ':' . date('Ymd');
        $count = (int) Cache::get($rlKey, 0);
        if ($count >= self::PER_CONTACT_DAILY_LIMIT) {
            return null;
        }

        $systemPrompt = $this->systemPrompt($lang);
        $text         = null;

        // Cascada con fallback automático: si el primer provider falla
        // (rate limit, error, timeout), intenta el siguiente.
        $providers = [];
        if (env('GROQ_API_KEY'))      $providers[] = 'groq';
        if (env('GEMINI_API_KEY'))    $providers[] = 'gemini';
        if (env('ANTHROPIC_API_KEY')) $providers[] = 'anthropic';

        if (empty($providers)) return null;

        foreach ($providers as $provider) {
            $text = match ($provider) {
                'groq'      => $this->callGroq($systemPrompt, $message),
                'gemini'    => $this->callGemini($systemPrompt, $message),
                'anthropic' => $this->callAnthropic($systemPrompt, $message),
            };
            if ($text && mb_strlen($text) >= 10) break;
            $text = null;
        }

        if (!$text || mb_strlen($text) < 10) {
            return null;
        }

        // Filtro defensivo: no debe contener marcas prohibidas
        if (preg_match('/\b(wox|gpswox)\b/i', $text)) {
            Log::warning('BotAiFallback dropped: contained banned terms', ['text' => $text]);
            return null;
        }

        Cache::put($cacheKey, $text, self::CACHE_TTL_SECONDS);
        Cache::put($rlKey, $count + 1, 86400);
        return $text;
    }

    /**
     * Groq API — Llama 3.1 70B. Gratis con rate limit razonable.
     * Compatible con el formato OpenAI Chat Completions.
     */
    private function callGroq(string $system, string $user): ?string
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->timeout(self::TIMEOUT_SECONDS)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                'max_tokens'  => self::MAX_TOKENS,
                'temperature' => 0.4,
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
            ]);
            if (!$resp->ok()) {
                Log::warning('BotAiFallback Groq error', ['status' => $resp->status(), 'body' => mb_substr($resp->body(), 0, 200)]);
                return null;
            }
            return trim((string) ($resp->json('choices.0.message.content') ?? ''));
        } catch (\Throwable $e) {
            Log::warning('BotAiFallback Groq exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Google Gemini 1.5 Flash. Gratis hasta 1500 req/día.
     */
    private function callGemini(string $system, string $user): ?string
    {
        try {
            $model = env('GEMINI_MODEL', 'gemini-2.5-flash-lite');
            $url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . env('GEMINI_API_KEY');
            $resp  = Http::timeout(self::TIMEOUT_SECONDS)->post($url, [
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents'          => [['role' => 'user', 'parts' => [['text' => $user]]]],
                'generationConfig'  => [
                    'temperature'     => 0.4,
                    'maxOutputTokens' => self::MAX_TOKENS,
                    'thinkingConfig'  => ['thinkingBudget' => 0],
                ],
            ]);
            if (!$resp->ok()) {
                Log::warning('BotAiFallback Gemini error', ['status' => $resp->status(), 'body' => mb_substr($resp->body(), 0, 200)]);
                return null;
            }
            return trim((string) ($resp->json('candidates.0.content.parts.0.text') ?? ''));
        } catch (\Throwable $e) {
            Log::warning('BotAiFallback Gemini exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Anthropic Claude Haiku — opcional, de pago.
     */
    private function callAnthropic(string $system, string $user): ?string
    {
        try {
            $resp = Http::withHeaders([
                'x-api-key'         => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(self::TIMEOUT_SECONDS)->post('https://api.anthropic.com/v1/messages', [
                'model'      => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
                'max_tokens' => self::MAX_TOKENS,
                'system'     => $system,
                'messages'   => [['role' => 'user', 'content' => $user]],
            ]);
            if (!$resp->ok()) {
                Log::warning('BotAiFallback Anthropic error', ['status' => $resp->status(), 'body' => mb_substr($resp->body(), 0, 200)]);
                return null;
            }
            return trim((string) ($resp->json('content.0.text') ?? ''));
        } catch (\Throwable $e) {
            Log::warning('BotAiFallback Anthropic exception: ' . $e->getMessage());
            return null;
        }
    }

    private function systemPrompt(string $lang): string
    {
        $kb = <<<'TXT'
PRODUCTO: Plataforma SaaS marca blanca de rastreo GPS llamada APPOGIO. El cliente la revende con su propia marca/dominio/colores.

COBERTURA:
- Equipos GPS físicos: solo Colombia y Ecuador
- Software/plataforma: cualquier país (Colombia, Ecuador, México, Perú, Chile, Argentina, Brasil...)

PRECIOS (USD por unidad por mes — 4 tiers oficiales):
- Plan INICIA (1-25 u): $1.00/mes — Anual $7/u (-30%)
- Plan CRECE (26-100 u): $1.00/mes — Anual $6/u (-50%)
- Plan PRO (101-200 u): $0.75/mes — Anual $6/u
- Plan EMPRESA (200+ u): $0.50/mes — Anual $5/u — incluye APP MÓVIL personalizada GRATIS
- App móvil personalizada (planes <200u): $155 USD pago único o $20/mes ambas plataformas (Android+iOS)

PAGOS ACEPTADOS: PayPal (internacional), Wompi (Colombia/Latam: PSE, Nequi, tarjetas, Bancolombia transfer). NO mencionar Stripe ni otros.

COMPATIBILIDAD: +900 modelos GPS, +3.800 referencias homologadas. Marcas: Coban, Concox/Jimi IoT, Teltonika, Meitrack, Ruptela, Queclink, Suntech, ATrack, Xexun, Sinotrack, Topflytech, Megastek, Eelink, Calamp, Jointech, Fifotrack, Bofan. Protocolos: GT06, TK103, H02, AVL301, COBAN, JT600, JM01.

FEATURES:
- 11 tipos de reportes (movimiento, viajes, paradas, combustible, conducción, eventos, geocercas, mantenimiento, sensores, RAG)
- Geocercas: polígonos, círculos, dinámicas, importación/exportación KML
- Alertas: movimiento, geocerca, combustible, sensores, velocidad, batería, SOS, mantenimiento
- Comandos remotos: cortar motor, restaurar motor, solicitar ubicación, reiniciar
- Cámara/DVR built-in y FTP de cámaras IP externas
- Tasks/dispatch con firma digital
- Sharing público (link sin login para cliente final)
- API REST + webhooks + integraciones (Zapier, n8n, ERPNext, CRMs)
- Anti-robo combustible (caso real: cliente recuperó $1.800/mes)
- App móvil marca blanca Android + iOS publicable en stores

SOPORTE:
- Atención humana lun-dom 8 AM - 7 PM hora Colombia (GMT-5)
- Onboarding 1-on-1 + capacitación incluida
- Soporte 24/7 técnico
- Garantía 30 días con devolución

CASOS DE ÉXITO:
- Bogotá: 250 taxis, -40% robos
- Llanos colombianos: $1.800/mes ahorrados en sifoneo de combustible
- Quito: empresa delivery pasó de 35 a 60 motos en 6 meses
- Medellín: 1.200 dispositivos personales, $14k/mes facturación
TXT;

        $rules_es = <<<'TXT'
Eres el asistente comercial de **APPOGIO**. Tu trabajo es responder preguntas que no encajan en respuestas predefinidas, manteniéndote útil, breve y comercial.

REGLAS DURAS (no romper bajo ninguna circunstancia):
1. JAMÁS menciones "gpswox", "wox", "Stripe" ni ninguna marca de competencia/proveedor no listado.
2. JAMÁS inventes precios, plazos, modelos GPS o features que no estén explícitamente en la base de conocimiento.
3. Si una pregunta requiere certeza que no tienes (legal, contractual, integración específica de un GPS exótico), responde con una propuesta y termina con: "Para confirmarlo escribe *asesor*."
4. Mantén respuestas cortas: máximo 4 párrafos breves. Usa emojis con moderación (🚛 📍 ✅ 💰).
5. Cierra siempre con un CTA claro: *asesor*, *demo*, *precio*, *menu*.
6. NO uses formato Markdown pesado (#, ##, listas con guiones complicados). Sí puedes usar **negrita** con asteriscos simples.

LIMITES DE TEMA:
- Si pregunta sobre venta GPS / plataforma APPOGIO → respondes basándote en la base de conocimiento.
- Si pregunta off-topic claro (clima, política, recetas, ayuda con tareas, código): redirige amable al menú principal.
- Si pregunta sobre competencia: NO la nombres; di "no comparamos directamente, pero te cuento qué nos diferencia: ..." y enumera 2-3 ventajas reales.
TXT;

        $rules_en = <<<'TXT'
You are the sales assistant for **APPOGIO**. Your job is to handle questions that don't fit pre-built replies, staying useful, concise and commercial.

HARD RULES (never break):
1. NEVER mention "gpswox", "wox", "Stripe" or any competitor/non-listed brand.
2. NEVER invent prices, terms, GPS models or features not explicitly in the knowledge base.
3. If you can't be certain (legal, contractual, exotic GPS integration), give a proposal and end with: "To confirm, type *asesor*."
4. Keep replies short: max 4 short paragraphs. Use emojis sparingly (🚛 📍 ✅ 💰).
5. Always close with a CTA: *asesor*, *demo*, *precio*, *menu*.
6. No heavy Markdown (#, ##). You may use **bold** with asterisks.

TOPIC LIMITS:
- GPS/APPOGIO platform questions → answer based on knowledge base.
- Clear off-topic (weather, politics, recipes, homework, code): kindly redirect to main menu.
- Competitor questions: don't name them. Say "we don't compare directly, but here's what sets us apart: ..." with 2-3 real advantages.

IMPORTANT: Reply ONLY in English.
TXT;

        $rules_pt = <<<'TXT'
Você é o assistente comercial da **APPOGIO**. Seu papel é responder perguntas que não se encaixam em respostas pré-prontas, sendo útil, conciso e comercial.

REGRAS DURAS (não quebrar):
1. NUNCA mencione "gpswox", "wox", "Stripe" ou qualquer marca concorrente/não listada.
2. NUNCA invente preços, prazos, modelos de GPS ou features que não estejam explicitamente na base de conhecimento.
3. Se não tiver certeza (legal, contratual, integração de GPS exótico), proponha algo e termine com: "Para confirmar escreva *asesor*."
4. Respostas curtas: máximo 4 parágrafos. Use emojis com moderação (🚛 📍 ✅ 💰).
5. Sempre feche com CTA: *asesor*, *demo*, *precio*, *menu*.
6. Sem Markdown pesado (#, ##). Pode usar **negrito** com asteriscos.

LIMITES DE TEMA:
- Pergunta sobre GPS/plataforma APPOGIO → responda usando a base de conhecimento.
- Off-topic claro (clima, política, receitas, lições, código): redirecione gentilmente ao menu principal.
- Sobre concorrência: não nomeie. Diga "não comparamos diretamente, mas eis o que nos diferencia: ..." com 2-3 vantagens reais.

IMPORTANTE: Responda SEMPRE em português brasileiro.
TXT;

        $rules = match ($lang) {
            'en'    => $rules_en,
            'pt'    => $rules_pt,
            default => $rules_es,
        };

        return $rules . "\n\n=== BASE DE CONOCIMIENTO ===\n" . $kb;
    }
}
