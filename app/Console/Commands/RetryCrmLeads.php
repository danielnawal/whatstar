<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatbotLead;
use App\Services\ErpNextLeadService;

class RetryCrmLeads extends Command
{
    protected $signature = 'crm:retry-leads {--limit=50 : Cuántos leads reintentar} {--max-attempts=10 : Tope de intentos por lead}';

    protected $description = 'Reintenta enviar a ERPNext los leads del chatbot que aún no se han sincronizado.';

    public function handle(): int
    {
        $limit       = (int) $this->option('limit');
        $maxAttempts = (int) $this->option('max-attempts');
        $service     = new ErpNextLeadService;

        if (!$service->isConfigured()) {
            $this->warn('ERPNext no configurado en .env — abortando.');
            return self::FAILURE;
        }

        $leads = ChatbotLead::where('synced_to_crm', 0)
            ->where('crm_sync_attempts', '<', $maxAttempts)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($leads->isEmpty()) {
            $this->info('No hay leads pendientes de sincronizar.');
            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;
        foreach ($leads as $lead) {
            if ($service->push($lead)) {
                $ok++;
                $this->line("  ✓ Lead {$lead->id} → ERPNext {$lead->crm_lead_id}");
            } else {
                $fail++;
                $this->line("  ✗ Lead {$lead->id} (intento #{$lead->crm_sync_attempts}): " . substr($lead->crm_last_error ?? '', 0, 120));
            }
        }

        $this->info("Hecho. OK={$ok} Fallidos={$fail} Total procesados=" . $leads->count());
        return self::SUCCESS;
    }
}
