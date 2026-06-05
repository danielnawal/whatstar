<?php

namespace App\Console\Commands;

use App\Models\ChatbotLead;
use App\Services\ErpNextLeadService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Sincroniza estado de leads desde ERPNext (CRM bidireccional).
 *
 * Lee el status actual de cada lead en CRM y actualiza chatbot_leads.status local.
 * Esto permite que el sistema:
 *   - Excluir de masivos los leads ya cerrados (won/lost) en CRM
 *   - Re-engagement targetado solo a 'new' o 'in_progress'
 *   - Reportes locales precisos sin tener que consultar CRM cada vez
 *
 * Uso: php artisan crm:sync-status [--limit=200]
 * Schedule: cada 30 min (en Kernel)
 */
class CrmSyncStatus extends Command
{
    protected $signature   = 'crm:sync-status {--limit=200 : Cuántos leads sincronizar por corrida} {--age=14 : Sincronizar leads modificados en últimos N días}';
    protected $description = 'Sincroniza el status local de los leads desde ERPNext (lectura bidireccional).';

    public function handle(): int
    {
        $svc = new ErpNextLeadService();
        if (!$svc->isConfigured()) {
            $this->warn('ERPNext no configurado, abortando');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $age   = (int) $this->option('age');

        // Solo leads sincronizados que no están ya cerrados localmente
        // (no tiene sentido revisar leads won/lost — ya terminaron)
        $leads = ChatbotLead::whereNotNull('crm_lead_id')
            ->whereNotIn('status', ['won', 'lost'])
            ->where('updated_at', '>=', Carbon::now()->subDays($age))
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        if ($leads->isEmpty()) {
            $this->info('Sin leads que sincronizar');
            return self::SUCCESS;
        }

        $changed = 0;
        $errors  = 0;
        foreach ($leads as $lead) {
            try {
                if ($svc->syncStatusFromCrm($lead)) {
                    $changed++;
                }
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        $this->info("Sync completado: {$changed} leads actualizados, {$errors} errores, " . count($leads) . " revisados");
        return self::SUCCESS;
    }
}
