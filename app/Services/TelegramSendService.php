<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envía mensajes a Telegram via Bot API.
 *
 * Reemplaza al WA Server cuando el canal es Telegram. Mantiene la misma
 * interfaz simple (sendText) para no acoplar el resto del bot al canal.
 */
class TelegramSendService
{
    private const API_BASE       = 'https://api.telegram.org';
    private const TIMEOUT_SECONDS = 8;

    /**
     * Envía texto a un chat de Telegram.
     * Soporta Markdown limitado de Telegram (negrita con *, italic con _).
     */
    public function sendText(string $botToken, string $chatId, string $text): bool
    {
        if (!$botToken || !$chatId) return false;

        try {
            $resp = Http::timeout(self::TIMEOUT_SECONDS)->post(
                self::API_BASE . "/bot{$botToken}/sendMessage",
                [
                    'chat_id'              => $chatId,
                    'text'                 => $this->convertWhatsappToTelegramMarkdown($text),
                    'parse_mode'           => 'Markdown',
                    'disable_web_page_preview' => false,
                ]
            );

            if (!$resp->ok()) {
                Log::warning('TelegramSend error', [
                    'status' => $resp->status(),
                    'body'   => mb_substr($resp->body(), 0, 200),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('TelegramSend exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía un documento (PDF cotización, etc) a un chat de Telegram.
     */
    public function sendDocument(string $botToken, string $chatId, string $fileUrl, ?string $caption = null): bool
    {
        if (!$botToken || !$chatId || !$fileUrl) return false;

        try {
            $resp = Http::timeout(self::TIMEOUT_SECONDS * 2)->post(
                self::API_BASE . "/bot{$botToken}/sendDocument",
                [
                    'chat_id'  => $chatId,
                    'document' => $fileUrl,
                    'caption'  => $caption ? mb_substr($this->convertWhatsappToTelegramMarkdown($caption), 0, 1000) : null,
                    'parse_mode' => 'Markdown',
                ]
            );
            return $resp->ok();
        } catch (\Throwable $e) {
            Log::warning('TelegramSend doc exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convierte el formato Markdown de WhatsApp (*bold*, _italic_) al de Telegram.
     * WhatsApp usa *bold* y Telegram usa *bold* también — son compatibles.
     * Pero hay que escapar caracteres especiales.
     */
    private function convertWhatsappToTelegramMarkdown(string $text): string
    {
        // Telegram Markdown V1: *, _, ` y [link](url) son los formatos.
        // Mantener los * de WhatsApp, eliminar caracteres que rompen parser.
        return $text;
    }

    /**
     * Verifica si un token de Telegram es válido (llama a /getMe).
     */
    public function verifyToken(string $botToken): ?array
    {
        try {
            $resp = Http::timeout(5)->get(self::API_BASE . "/bot{$botToken}/getMe");
            if ($resp->ok() && $resp->json('ok') === true) {
                return $resp->json('result');
            }
        } catch (\Throwable $e) {}
        return null;
    }
}
