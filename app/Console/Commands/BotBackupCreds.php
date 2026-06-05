<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Backup automático de credenciales WhatsApp Multi-Device.
 *
 * Si WhatsApp deslogue una sesión, el código del whatstar BORRA la carpeta
 * automáticamente (whatsapp.js línea 245). Sin backup, hay que escanear QR.
 * Con backup, se restaura la carpeta del último snapshot y la sesión revive.
 *
 * Uso: php artisan bot:backup-creds
 * Schedule: cada 6 horas
 */
class BotBackupCreds extends Command
{
    protected $signature   = 'bot:backup-creds {--keep=14 : Cuántos snapshots mantener}';
    protected $description = 'Backup de las carpetas de credenciales WhatsApp (sessions/md_device_*).';

    private const SOURCE_DIR  = '/var/www/html/whatstar/sessions';
    private const BACKUP_BASE = '/backup/whatstar_sessions';

    public function handle(): int
    {
        if (!is_dir(self::SOURCE_DIR)) {
            $this->error('Source dir no existe: ' . self::SOURCE_DIR);
            return self::FAILURE;
        }

        if (!is_dir(self::BACKUP_BASE) && !@mkdir(self::BACKUP_BASE, 0755, true)) {
            $this->error('No pude crear ' . self::BACKUP_BASE);
            return self::FAILURE;
        }

        $stamp     = date('Ymd_His');
        $targetDir = self::BACKUP_BASE . '/snap_' . $stamp;

        if (!@mkdir($targetDir, 0755, true)) {
            $this->error('No pude crear snapshot dir');
            return self::FAILURE;
        }

        // Copiar solo carpetas md_device_* (no los .json gigantes de store)
        $count = 0;
        foreach (glob(self::SOURCE_DIR . '/md_device_*') as $sessionDir) {
            if (!is_dir($sessionDir)) continue;
            $name   = basename($sessionDir);
            $dest   = $targetDir . '/' . $name;
            $cmd    = 'cp -r ' . escapeshellarg($sessionDir) . ' ' . escapeshellarg($dest);
            $output = []; $code = 0;
            exec($cmd . ' 2>&1', $output, $code);
            if ($code === 0) {
                $count++;
            } else {
                $this->warn("Error copiando {$name}: " . implode(' ', $output));
            }
        }

        if ($count === 0) {
            @rmdir($targetDir);
            $this->warn('Ningún device session encontrado, snapshot vacío descartado');
            return self::SUCCESS;
        }

        // Housekeeping: mantener solo los últimos N snapshots
        $keep = (int) $this->option('keep');
        $snaps = glob(self::BACKUP_BASE . '/snap_*');
        sort($snaps);
        $toDelete = array_slice($snaps, 0, max(0, count($snaps) - $keep));
        foreach ($toDelete as $old) {
            exec('rm -rf ' . escapeshellarg($old));
        }

        $this->info("✅ Snapshot {$stamp}: {$count} devices respaldados. Mantenidos {$keep} snapshots.");
        return self::SUCCESS;
    }
}
