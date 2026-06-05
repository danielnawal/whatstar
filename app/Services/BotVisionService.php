<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Vision inbound: cuando el cliente manda una foto al bot, la describe con
 * Gemini Vision y devuelve una descripción enriquecida que el LLM
 * conversacional puede procesar como texto normal.
 *
 * Casos de uso típicos:
 *   - Cliente manda foto de un GPS roto → bot describe el modelo
 *   - Cliente manda screenshot de cotización competidor → bot extrae datos
 *   - Cliente manda foto del vehículo → bot identifica tipo
 *   - Cliente manda imagen con texto → bot extrae el texto (OCR implícito)
 *
 * Pipeline:
 *   1. Descargar imagen del WA Server local (/chats/download-media)
 *   2. Enviar base64 a Gemini Vision con prompt acotado al producto APPOGIO
 *   3. Devolver descripción string para procesar como texto en el flujo normal
 *
 * Fail-safe: cualquier error → null. El caller hace handoff humano.
 */
class BotVisionService
{
    private const TIMEOUT_DOWNLOAD   = 15;
    private const TIMEOUT_VISION     = 25;
    private const MAX_IMAGE_BYTES    = 10 * 1024 * 1024; // 10MB
    private const MAX_OUTPUT_TOKENS  = 350;

    public function describe(Device $device, string $remoteJid, string $messageId, ?string $caption = null): ?string
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            Log::info('BotVision: GEMINI_API_KEY no configurada, skip');
            return null;
        }

        // Paso 1: descargar imagen del WA Server local
        $media = $this->downloadImage($device, $remoteJid, $messageId);
        if (!$media) return null;

        $base64   = $media['base64'];
        $mimeType = $media['mimetype'] ?: 'image/jpeg';

        try {
            // Paso 2: Gemini Vision con prompt específico
            $model  = env('GEMINI_VISION_MODEL', 'gemini-2.5-flash-lite');
            $url    = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $prompt = $this->visionPrompt($caption);

            $resp = Http::timeout(self::TIMEOUT_VISION)->post($url, [
                'contents' => [[
                    'role'  => 'user',
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => [
                            'mime_type' => $mimeType,
                            'data'      => $base64,
                        ]],
                    ],
                ]],
                'generationConfig' => [
                    'temperature'     => 0.2,
                    'maxOutputTokens' => self::MAX_OUTPUT_TOKENS,
                    'thinkingConfig'  => ['thinkingBudget' => 0],
                ],
            ]);

            if (!$resp->ok()) {
                Log::warning('BotVision Gemini error', [
                    'status' => $resp->status(),
                    'body'   => mb_substr($resp->body(), 0, 200),
                ]);
                return null;
            }

            $description = trim((string) ($resp->json('candidates.0.content.parts.0.text') ?? ''));
            if ($description === '') return null;

            // Truncar para no abrumar al LLM conversacional
            return mb_substr($description, 0, 800);
        } catch (\Throwable $e) {
            Log::warning('BotVision exception: ' . $e->getMessage());
            return null;
        }
    }

    private function downloadImage(Device $device, string $remoteJid, string $messageId): ?array
    {
        try {
            $waUrl = str_replace('localhost', '127.0.0.1', env('WA_SERVER_URL', 'http://127.0.0.1:8000'));

            $resp = Http::timeout(self::TIMEOUT_DOWNLOAD)->post(
                $waUrl . '/chats/download-media?id=device_' . $device->id,
                ['remoteJid' => $remoteJid, 'messageId' => $messageId]
            );

            if (!$resp->ok()) {
                Log::warning('BotVision download error', ['status' => $resp->status()]);
                return null;
            }

            $payload = $resp->json();
            $base64  = $payload['data']['base64']
                    ?? $payload['base64']
                    ?? null;
            $mime    = $payload['data']['mimetype']
                    ?? $payload['mimetype']
                    ?? null;

            if (!$base64) {
                Log::warning('BotVision: no base64 in response');
                return null;
            }

            $bytes = strlen($base64) * 3 / 4;
            if ($bytes > self::MAX_IMAGE_BYTES) {
                Log::warning('BotVision: imagen muy grande, descartando', ['bytes' => $bytes]);
                return null;
            }

            return ['base64' => $base64, 'mimetype' => $mime];
        } catch (\Throwable $e) {
            Log::warning('BotVision download exception: ' . $e->getMessage());
            return null;
        }
    }

    private function visionPrompt(?string $caption): string
    {
        $prompt = "Eres parte de un bot de venta de la plataforma SaaS de rastreo GPS APPOGIO. "
                . "Un cliente acaba de enviarte una imagen. Describe BREVEMENTE en español lo que ves "
                . "y, si aplica, extrae información comercialmente útil:\n\n"
                . "- Si es foto de un dispositivo GPS: indica modelo/marca si es visible (Coban, Concox, Teltonika, etc).\n"
                . "- Si es captura de pantalla de otra plataforma o cotización: extrae precios, marca o features visibles.\n"
                . "- Si es foto de un vehículo: indica el tipo (auto, moto, camión, taxi, flota).\n"
                . "- Si tiene texto: transcríbelo exactamente.\n"
                . "- Si es algo no relacionado con GPS/vehículos: solo describe en 1 línea.\n\n"
                . "Formato de salida: máximo 4 líneas. Sin markdown. Empieza con \"📷 Imagen: \".";

        if ($caption && trim($caption) !== '') {
            $prompt .= "\n\nEl cliente escribió como caption: \"" . mb_substr($caption, 0, 200) . "\"";
        }

        return $prompt;
    }
}
