<?php

namespace App\Services;

use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\AlertTemplate;
use App\Models\App as WhatsappApp;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertDispatcherService
{
 public const VARIABLES = [
 '{brand}', '{unit}', '{event}', '{speed}', '{address}',
 '{lat}', '{lng}', '{map_link}', '{time}', '{date}',
 ];

 public function dispatch(WhatsappApp $app, array $payload, array $destinations): array
 {
 $eventType = strtolower((string) ($payload['event'] ?? 'default'));
 $userId = $app->user_id;

 $blocked = $this->applyRules($userId, $app->id, $eventType, $payload);
 if ($blocked['block']) {
 $this->logAttempt($userId, $app->id, $eventType, $payload['unit'] ?? null, 'rule', '-', 'blocked', $blocked['reason']);
 return ['sent' => 0, 'skipped' => count($destinations), 'reason' => $blocked['reason']];
 }

 $dedupeKey = $this->dedupeKey($userId, $app->id, $eventType, $payload['unit'] ?? '');
 if ($dedupeSeconds = $this->getDedupeSeconds($userId, $app->id, $eventType)) {
 if (Cache::has($dedupeKey)) {
 $this->logAttempt($userId, $app->id, $eventType, $payload['unit'] ?? null, 'rule', '-', 'deduped', "<=$dedupeSeconds s");
 return ['sent' => 0, 'skipped' => count($destinations), 'reason' => 'deduplicated'];
 }
 Cache::put($dedupeKey, 1, $dedupeSeconds);
 }

 $results = ['sent' => 0, 'failed' => 0];

 foreach ($destinations as $dest) {
 $channel = $dest['channel'] ?? 'whatsapp';
 $to = $dest['to'] ?? null;
 if (!$to) continue;

 $template = $this->resolveTemplate($userId, $app->id, $eventType, $channel, $dest['language'] ?? 'es');
 $message = $this->renderTemplate($template, $payload, $app);

 $ok = $this->sendByChannel($channel, $app, $to, $message, $payload);
 $results[$ok ? 'sent' : 'failed']++;

 $this->logAttempt(
 $userId, $app->id, $eventType, $payload['unit'] ?? null,
 $channel, $to, $ok ? 'sent' : 'failed', null
 );
 }

 return $results;
 }

 public function renderTemplate(string $template, array $payload, ?WhatsappApp $app = null): string
 {
 $lat = $payload['lat'] ?? null;
 $lng = $payload['lng'] ?? null;
 $mapLink = ($lat && $lng) ? "https://maps.google.com/?q={$lat},{$lng}" : '';

 $vars = [
 '{brand}' => $app && $app->title ? $app->title : ($payload['brand'] ?? 'Alerta GPS'),
 '{unit}' => (string) ($payload['unit'] ?? 'N/A'),
 '{event}' => $this->labelEvent($payload['event'] ?? 'Alerta'),
 '{speed}' => (string) ($payload['speed'] ?? '0'),
 '{address}' => (string) ($payload['address'] ?? '-'),
 '{lat}' => (string) ($lat ?? ''),
 '{lng}' => (string) ($lng ?? ''),
 '{map_link}' => $mapLink,
 '{time}' => (string) ($payload['time'] ?? now()->format('Y-m-d H:i:s')),
 '{date}' => now()->format('Y-m-d'),
 ];

 return strtr($template, $vars);
 }

 public function resolveTemplate(int $userId, ?int $appId, string $eventType, string $channel, string $language): string
 {
 $tpl = AlertTemplate::where('user_id', $userId)
 ->where('channel', $channel)
 ->where('language', $language)
 ->where('is_active', true)
 ->where(function ($q) use ($appId) {
 $q->whereNull('app_id')->orWhere('app_id', $appId);
 })
 ->where(function ($q) use ($eventType) {
 $q->where('event_type', $eventType)->orWhere('event_type', 'default');
 })
 ->orderByDesc('app_id')
 ->orderByRaw("CASE WHEN event_type = ? THEN 0 ELSE 1 END", [$eventType])
 ->first();

 return $tpl?->template_text ?: AlertTemplate::DEFAULT_TEMPLATE;
 }

 private function sendByChannel(string $channel, WhatsappApp $app, string $to, string $message, array $payload): bool
 {
 try {
 return match ($channel) {
 'whatsapp' => $this->sendWhatsapp($app, $to, $message),
 'telegram' => $this->sendTelegram($app, $to, $message),
 'sms' => $this->sendSms($to, $message),
 'email' => $this->sendEmail($to, $payload['brand'] ?? 'Alerta', $message),
 default => false,
 };
 } catch (\Throwable $e) {
 Log::warning("AlertDispatcher [$channel] error: " . $e->getMessage());
 return false;
 }
 }

 private function sendWhatsapp(WhatsappApp $app, string $to, string $message): bool
 {
 $device = $app->device;
 if (!$device) return false;

 $url = env('WA_SERVER_URL', 'http://127.0.0.1:8000') . '/chats/send?id=device_' . $device->id;
 $resp = Http::timeout(15)->post($url, [
 'receiver' => $to,
 'message' => ['text' => $message],
 'isGroup' => false,
 'customId' => 0,
 'replyMessage'=> null,
 ]);
 return $resp->successful();
 }

 private function sendTelegram(WhatsappApp $app, string $chatId, string $message): bool
 {
 $token = $app->device?->telegram_token ?: env('TELEGRAM_BOT_TOKEN');
 if (!$token) return false;
 $resp = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
 'chat_id' => $chatId,
 'text' => $message,
 'parse_mode' => 'Markdown',
 ]);
 return $resp->successful();
 }

 private function sendSms(string $phone, string $message): bool
 {
 $key = env('SMS_API_KEY');
 $url = env('SMS_API_URL');
 if (!$key || !$url) return false;
 $resp = Http::timeout(10)->withHeaders(['Authorization' => "Bearer $key"])
 ->post($url, ['to' => $phone, 'message' => $message]);
 return $resp->successful();
 }

 private function sendEmail(string $email, string $subject, string $body): bool
 {
 if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
 try {
 Mail::raw($body, function ($m) use ($email, $subject) {
 $m->to($email)->subject($subject);
 });
 return true;
 } catch (\Throwable $e) {
 return false;
 }
 }

 private function applyRules(int $userId, int $appId, string $eventType, array $payload): array
 {
 $rules = AlertRule::where('user_id', $userId)
 ->where('is_active', true)
 ->where(function ($q) use ($appId) { $q->whereNull('app_id')->orWhere('app_id', $appId); })
 ->where(function ($q) use ($eventType) { $q->whereNull('event_type')->orWhere('event_type', $eventType); })
 ->orderByDesc('priority')->get();

 $now = Carbon::now();
 foreach ($rules as $rule) {
 if ($rule->unit_pattern && !empty($payload['unit'])) {
 if (!fnmatch(strtolower($rule->unit_pattern), strtolower((string) $payload['unit']))) {
 continue;
 }
 }
 if ($rule->mute_from && $rule->mute_to) {
 $h = $now->format('H:i:s');
 $in = $rule->mute_from <= $rule->mute_to
 ? ($h >= $rule->mute_from && $h <= $rule->mute_to)
 : ($h >= $rule->mute_from || $h <= $rule->mute_to);
 if ($in && $rule->block) return ['block' => true, 'reason' => 'muted_by_schedule'];
 }
 if ($rule->min_speed && isset($payload['speed']) && (float) $payload['speed'] < (float) $rule->min_speed) {
 if ($rule->block) return ['block' => true, 'reason' => 'speed_below_threshold'];
 }
 if ($rule->block && !$rule->mute_from && !$rule->min_speed) {
 return ['block' => true, 'reason' => 'blocked_by_rule_' . $rule->id];
 }
 }
 return ['block' => false, 'reason' => null];
 }

 private function getDedupeSeconds(int $userId, int $appId, string $eventType): int
 {
 $rule = AlertRule::where('user_id', $userId)
 ->where('is_active', true)
 ->where(function ($q) use ($appId) { $q->whereNull('app_id')->orWhere('app_id', $appId); })
 ->where(function ($q) use ($eventType) { $q->whereNull('event_type')->orWhere('event_type', $eventType); })
 ->where('dedupe_seconds', '>', 0)
 ->orderByDesc('priority')
 ->first();
 return $rule?->dedupe_seconds ?? 0;
 }

 private function dedupeKey(int $userId, int $appId, string $eventType, string $unit): string
 {
 return "alert_dedupe:{$userId}:{$appId}:{$eventType}:" . md5($unit);
 }

 private function logAttempt(int $userId, ?int $appId, string $event, ?string $unit, string $channel, string $dest, string $status, ?string $reason): void
 {
 AlertLog::create([
 'user_id' => $userId,
 'app_id' => $appId,
 'event_type' => $event,
 'unit' => $unit,
 'channel' => $channel,
 'destination' => mb_substr($dest, 0, 100),
 'status' => $status,
 'reason' => $reason,
 'created_at' => now(),
 ]);
 }

 private function labelEvent(string $event): string
 {
 $event = strtolower($event);
 return AlertTemplate::EVENT_TYPES[$event] ?? ucfirst($event);
 }
}
