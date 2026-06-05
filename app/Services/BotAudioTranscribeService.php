<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transcribe audios entrantes de WhatsApp usando Groq Whisper.
 *
 * Pipeline:
 *   1. Llamar al WA Server Node local (/chats/download-media) para obtener base64
 *   2. Decodificar y guardar como archivo temporal .ogg
 *   3. POST multipart/form-data al endpoint Whisper de Groq
 *   4. Devolver string con la transcripción
 *
 * Fail-safe: cualquier error → null. El caller hace fallback a handoff humano.
 *
 * Costo: Groq Whisper (whisper-large-v3-turbo) está incluido en el free tier
 * que ya estamos usando para Llama (mismo GROQ_API_KEY).
 */
class BotAudioTranscribeService
{
    private const TIMEOUT_DOWNLOAD     = 15;
    private const TIMEOUT_TRANSCRIBE   = 30;
    private const MAX_AUDIO_BYTES      = 20 * 1024 * 1024; // 20MB
    private const MIN_TRANSCRIPT_CHARS = 2;

    public function transcribe(Device $device, string $remoteJid, string $messageId): ?string
    {
        $apiKey = env('GROQ_API_KEY');
        if (!$apiKey) {
            Log::info('BotAudioTranscribe: GROQ_API_KEY no configurada, skip');
            return null;
        }

        // Paso 1: descargar el audio del WA Server local
        $audioBytes = $this->downloadAudio($device, $remoteJid, $messageId);
        if (!$audioBytes) return null;

        // Paso 2: guardar a archivo temporal
        $tmpPath = sys_get_temp_dir() . '/wa_audio_' . substr(md5($messageId . microtime(true)), 0, 12) . '.ogg';
        if (!@file_put_contents($tmpPath, $audioBytes)) {
            Log::warning('BotAudioTranscribe: no pude escribir tmp file');
            return null;
        }

        try {
            // Paso 3: POST multipart a Groq Whisper
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(self::TIMEOUT_TRANSCRIBE)
              ->attach('file', file_get_contents($tmpPath), basename($tmpPath))
              ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                  'model'       => env('GROQ_WHISPER_MODEL', 'whisper-large-v3-turbo'),
                  'language'    => 'es',  // hint, Whisper detecta auto si no
                  'temperature' => '0',
                  'response_format' => 'json',
              ]);

            if (!$resp->ok()) {
                Log::warning('BotAudioTranscribe Groq error', [
                    'status' => $resp->status(),
                    'body'   => mb_substr($resp->body(), 0, 200),
                ]);
                return null;
            }

            $text = trim((string) ($resp->json('text') ?? ''));
            if (mb_strlen($text) < self::MIN_TRANSCRIPT_CHARS) {
                return null;
            }
            return $text;
        } catch (\Throwable $e) {
            Log::warning('BotAudioTranscribe exception: ' . $e->getMessage());
            return null;
        } finally {
            @unlink($tmpPath);
        }
    }

    /**
     * Descarga el binario del audio desde el WA Server Node local.
     * Devuelve null si falla.
     */
    private function downloadAudio(Device $device, string $remoteJid, string $messageId): ?string
    {
        try {
            $waUrl = env('WA_SERVER_URL', 'http://127.0.0.1:8000');
            // Forzar IPv4 para evitar issues con ::1 (visto en n8n)
            $waUrl = str_replace('localhost', '127.0.0.1', $waUrl);

            $resp = Http::timeout(self::TIMEOUT_DOWNLOAD)->post(
                $waUrl . '/chats/download-media?id=device_' . $device->id,
                ['remoteJid' => $remoteJid, 'messageId' => $messageId]
            );

            if (!$resp->ok()) {
                Log::warning('downloadAudio WA error', ['status' => $resp->status()]);
                return null;
            }

            $payload = $resp->json();
            $base64  = $payload['data']['base64']
                    ?? $payload['base64']
                    ?? null;

            if (!$base64) {
                Log::warning('downloadAudio: no base64 in response', ['payload' => array_slice($payload, 0, 5)]);
                return null;
            }

            $bytes = base64_decode($base64, true);
            if ($bytes === false) {
                Log::warning('downloadAudio: base64 inválido');
                return null;
            }

            if (strlen($bytes) > self::MAX_AUDIO_BYTES) {
                Log::warning('downloadAudio: audio muy grande, descartando', ['bytes' => strlen($bytes)]);
                return null;
            }

            return $bytes;
        } catch (\Throwable $e) {
            Log::warning('downloadAudio exception: ' . $e->getMessage());
            return null;
        }
    }
}
