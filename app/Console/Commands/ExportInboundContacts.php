<?php

namespace App\Console\Commands;

use App\Models\ChatbotHandoff;
use App\Models\ChatbotLead;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Models\Contact;
use App\Models\Groupcontact;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Exporta la lista de contactos inbound del WhatsApp del bot a CSV.
 *
 * Uso:
 *   php artisan chatbot:export-contacts                       # CSV a stdout
 *   php artisan chatbot:export-contacts --out=/tmp/list.csv   # CSV a archivo
 *   php artisan chatbot:export-contacts --device=100          # filtrar por device
 *   php artisan chatbot:export-contacts --group=17            # filtrar por grupo
 *   php artisan chatbot:export-contacts --since=30            # últimos N días
 *   php artisan chatbot:export-contacts --lang=es             # solo idioma X
 *   php artisan chatbot:export-contacts --has-lead            # solo los que hicieron BANT
 *   php artisan chatbot:export-contacts --nps-min=4           # solo promotores NPS≥4
 *
 * Columnas: phone, name, first_seen, last_seen, language, has_lead, lead_qty, nps,
 *           had_handoff, last_intent, last_sentiment, total_messages
 */
class ExportInboundContacts extends Command
{
    protected $signature = 'chatbot:export-contacts
        {--out=          : Ruta de salida (vacío = stdout)}
        {--device=       : Filtrar por device_id}
        {--group=        : Filtrar por group_id}
        {--since=365     : Solo contactos vistos en últimos N días}
        {--lang=         : Filtrar por idioma (es/en/pt)}
        {--has-lead      : Solo contactos que completaron BANT}
        {--nps-min=      : Solo contactos con NPS >= N}';

    protected $description = 'Exporta lista de contactos WhatsApp inbound a CSV (para n8n/campañas).';

    public function handle(): int
    {
        $rows = $this->buildRows([
            'device'   => $this->option('device') ? (int) $this->option('device') : null,
            'group'    => $this->option('group')  ? (int) $this->option('group')  : null,
            'since'    => (int) $this->option('since'),
            'lang'     => $this->option('lang'),
            'has_lead' => (bool) $this->option('has-lead'),
            'nps_min'  => $this->option('nps-min') ? (int) $this->option('nps-min') : null,
        ]);

        if (empty($rows)) {
            $this->warn('No hay contactos que coincidan con los filtros.');
            return self::SUCCESS;
        }

        $headers = array_keys($rows[0]);
        $outPath = $this->option('out');
        $fp = $outPath ? fopen($outPath, 'w') : fopen('php://stdout', 'w');

        fputcsv($fp, $headers);
        foreach ($rows as $row) fputcsv($fp, $row);
        fclose($fp);

        if ($outPath) {
            $this->info("Exportados " . count($rows) . " contactos a {$outPath}");
        }
        return self::SUCCESS;
    }

    public function buildRows(array $filters = []): array
    {
        $deviceId       = $filters['device']        ?? null;
        $groupId        = $filters['group']         ?? null;
        $since          = (int) ($filters['since']  ?? 365);
        $lang           = $filters['lang']          ?? null;
        $hasLead        = $filters['has_lead']      ?? false;
        $npsMin         = $filters['nps_min']       ?? null;
        $excludeClosed  = $filters['exclude_closed'] ?? true; // CRM bidireccional: excluye won/lost por default

        // Base: contactos del grupo "WhatsApp Inbound" (o todos si no se filtra)
        $contactsQuery = Contact::query()->select('contacts.*');

        if ($groupId) {
            $contactsQuery->whereExists(function($q) use ($groupId) {
                $q->select(\DB::raw(1))->from('groupcontacts')
                  ->whereColumn('groupcontacts.contact_id', 'contacts.id')
                  ->where('groupcontacts.group_id', $groupId);
            });
        }

        if ($since > 0) {
            $contactsQuery->where('contacts.created_at', '>=', Carbon::now()->subDays($since));
        }

        $contacts = $contactsQuery->limit(50000)->get();

        $rows = [];
        foreach ($contacts as $c) {
            $phone = ltrim($c->phone, '+');

            // Buscar la sesión del chatbot por phone (puede tener formato "phone" o "phone@lid")
            $session = ChatbotSession::query()
                ->when($deviceId, fn($q) => $q->where('device_id', $deviceId))
                ->where(function($q) use ($phone) {
                    $q->where('contact', $phone)->orWhere('contact', 'like', $phone . '%');
                })
                ->orderByDesc('updated_at')
                ->first();

            // Filtros que requieren sesión
            if ($lang && $session && $session->locked_language !== $lang) continue;
            if ($lang && !$session) continue;

            // CRM bidireccional: excluir leads cerrados (won/lost) si flag activo
            if ($excludeClosed && $session) {
                $leadStatus = ChatbotLead::where('device_id', $session->device_id)
                    ->where('contact', $session->contact)
                    ->whereIn('status', ['won', 'lost'])
                    ->exists();
                if ($leadStatus) continue;
            }

            $leadData = $session ? json_decode($session->lead_data ?? '{}', true) : [];
            $hasLeadData = !empty($leadData['nombre']) && !empty($leadData['cantidad_dispositivos']);
            if ($hasLead && !$hasLeadData) continue;

            // NPS más reciente del contacto
            $nps = null;
            if ($session) {
                $nps = ChatbotHandoff::where('device_id', $session->device_id)
                    ->where('contact', $session->contact)
                    ->whereNotNull('nps')->orderByDesc('updated_at')->value('nps');
            }
            if ($npsMin !== null && (int) $nps < $npsMin) continue;

            // Último intent + sentiment
            $lastMsg = $session ? ChatbotMessage::where('session_id', $session->id)
                ->where('role', 'user')
                ->orderByDesc('id')->first() : null;
            $totalMsgs = $session ? ChatbotMessage::where('session_id', $session->id)->count() : 0;

            $hadHandoff = $session ? ChatbotHandoff::where('device_id', $session->device_id)
                ->where('contact', $session->contact)->exists() : false;

            $rows[] = [
                'phone'           => $phone,
                'name'            => $c->name ?: ($leadData['nombre'] ?? ''),
                'first_seen'      => $c->created_at?->toDateTimeString(),
                'last_seen'       => $session?->updated_at?->toDateTimeString(),
                'language'        => $session->locked_language ?? '',
                'lead_name'       => $leadData['nombre'] ?? '',
                'lead_qty'        => $leadData['cantidad_dispositivos'] ?? '',
                'has_lead'        => $hasLeadData ? 'yes' : 'no',
                'nps'             => $nps ?? '',
                'had_handoff'     => $hadHandoff ? 'yes' : 'no',
                'last_intent'     => $lastMsg?->intent ?? '',
                'last_sentiment'  => $lastMsg?->sentiment ?? '',
                'total_messages'  => $totalMsgs,
            ];
        }

        return $rows;
    }
}
