<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Heartbeat: hace una llamada GET al WA Server para "tocar" la sesión sin
 * enviar mensaje al usuario. Esto reduce desconexiones por inactividad.
 *
 * NO manda WhatsApp al admin (sería molesto). Solo:
 *   1. GET /chats?id=device_X — verifica conexión
 *   2. Si responde, la sesión está viva. WhatsApp registra actividad.
 *
 * Uso: php artisan bot:heartbeat
 * Schedule: cada 6 horas
 */
class BotHeartbeat extends Command
{
    protected $signature   = 'bot:heartbeat {--device= : Solo este device, default todos los activos}';
    protected $description = 'Mantiene viva la sesión WhatsApp con un ping al WA Server (sin enviar mensaje al usuario).';

    public function handle(): int
    {
        $waUrl = str_replace('localhost', '127.0.0.1', env('WA_SERVER_URL', 'http://127.0.0.1:8000'));

        $query = Device::where('status', 1);
        if ($id = $this->option('device')) {
            $query->where('id', $id);
        }
        $devices = $query->get();

        // Filtrar: solo devices con carpeta de sesión real (no fantasmas con status=1 sin creds)
        $devices = $devices->filter(function($d) {
            return is_dir("/var/www/html/whatstar/sessions/md_device_{$d->id}");
        });

        if ($devices->isEmpty()) {
            $this->warn('No hay devices con status=1');
            return self::SUCCESS;
        }

        $alive = 0;
        $dead  = 0;
        foreach ($devices as $d) {
            try {
                $resp = Http::timeout(8)->get($waUrl . '/chats?id=device_' . $d->id);
                if ($resp->successful()) {
                    $alive++;
                    $this->line("  ✅ device_{$d->id} ({$d->phone}): vivo");
                } else {
                    $dead++;
                    $this->warn("  ⚠️ device_{$d->id} ({$d->phone}): HTTP {$resp->status()}");
                }
            } catch (\Throwable $e) {
                $dead++;
                $this->warn("  ⚠️ device_{$d->id}: " . $e->getMessage());
            }
        }

        $this->info("Heartbeat: {$alive} vivos, {$dead} fallidos");
        return self::SUCCESS;
    }
}
