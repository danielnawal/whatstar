<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DeviceHealthController extends Controller
{
    public function index()
    {
        $devices = Device::where('user_id', Auth::id())->get();
        return view('wacore::user.health.index', compact('devices'));
    }

    public function statuses()
    {
        $devices = Device::where('user_id', Auth::id())->get(['id', 'name', 'phone', 'status']);
        $waUrl = env('WA_SERVER_URL', 'http://127.0.0.1:8000');

        $out = [];
        foreach ($devices as $d) {
            $live = $this->probe($waUrl, $d->id);
            $out[] = [
                'id'           => $d->id,
                'name'         => $d->name,
                'phone'        => $d->phone,
                'db_status'    => (int) $d->status,
                'live_status'  => $live['status'],
                'connection'   => $live['connection'],
                'last_seen'    => $live['last_seen'],
                'health'       => $this->healthLabel($live['status'], $d->status),
            ];
        }
        return response()->json(['devices' => $out, 'checked_at' => now()->toIso8601String()]);
    }

    public function reconnect(Request $request, $id)
    {
        $device = Device::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $waUrl  = env('WA_SERVER_URL', 'http://127.0.0.1:8000');

        try {
            Http::timeout(8)->get("$waUrl/sessions/del/device_{$device->id}");
        } catch (\Throwable $e) { /* idempotente */ }

        try {
            $resp = Http::timeout(20)->post("$waUrl/sessions/add", [
                'id'       => 'device_' . $device->id,
                'typeAuth' => 'qr',
            ]);
            $body = $resp->json();
            return response()->json([
                'success' => $resp->successful(),
                'qr'      => $body['data']['qr'] ?? null,
                'pairing' => $body['data']['code'] ?? null,
                'raw'     => $body,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function qr($id)
    {
        $device = Device::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $waUrl  = env('WA_SERVER_URL', 'http://127.0.0.1:8000');

        try {
            $resp = Http::timeout(8)->get("$waUrl/sessions/status/device_{$device->id}");
            $body = $resp->json();
            $liveStatus = strtolower($body['data']['status'] ?? $body['status'] ?? '');
            return response()->json([
                'qr'         => $body['data']['qr'] ?? $body['qr'] ?? null,
                'connected'  => in_array($liveStatus, ['authenticated', 'connected', 'open'], true),
                'raw'        => $body,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveAlertNumber(Request $request, $id)
    {
        $device = Device::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        // Normaliza: deja solo dígitos (código país + número, sin +, espacios ni guiones).
        $raw = (string) $request->input('number', '');
        $number = preg_replace('/\D+/', '', $raw);

        if ($number !== '' && strlen($number) < 8) {
            return response()->json(['success' => false, 'error' => 'Número inválido. Usa código de país + número.'], 422);
        }

        $device->disconnect_alert_number = $number !== '' ? $number : null;
        $device->save();

        return response()->json(['success' => true, 'number' => $device->disconnect_alert_number]);
    }

    private function probe(string $waUrl, int $deviceId): array
    {
        try {
            $resp = Http::timeout(5)->get("$waUrl/sessions/status/device_{$deviceId}");
            if (!$resp->successful()) {
                return ['status' => 'unreachable', 'connection' => null, 'last_seen' => null];
            }
            $body = $resp->json();
            // El servidor WhatsApp responde { success, message, data: { status } }.
            // El estado viene ANIDADO en data.status (no en la raíz) y en minúsculas
            // (authenticated, connecting, connected, disconnecting, disconnected).
            return [
                'status'     => $body['data']['status'] ?? $body['status'] ?? 'unknown',
                'connection' => $body['data']['connection'] ?? $body['connection'] ?? null,
                'last_seen'  => $body['data']['lastSeen'] ?? $body['lastSeen'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'connection' => null, 'last_seen' => $e->getMessage()];
        }
    }

    private function healthLabel(string $liveStatus, int $dbStatus): string
    {
        // Normalizamos a minúsculas: el servidor WA usa 'authenticated'/'connected',
        // no 'AUTHENTICATED'. Un bot con sesión abierta se considera saludable.
        $s = strtolower(trim($liveStatus));
        if (in_array($s, ['authenticated', 'connected', 'open'], true)) return 'healthy';
        if (in_array($s, ['connecting', 'disconnecting'], true))        return 'connecting';
        if ($dbStatus !== 1) return 'inactive';
        return 'disconnected';
    }
}
