<?php

namespace App\Services;

use App\Models\ChatbotLead;
use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpNextLeadService
{
    private string $url;
    private string $apiKey;
    private string $apiSecret;
    private string $source;
    private string $leadOwner;
    private int $timeout;
    private string $doctype;
    private string $doctypePath;

    public function __construct()
    {
        $this->url       = rtrim((string) config('services.erpnext.url'), '/');
        $this->apiKey    = (string) config('services.erpnext.api_key');
        $this->apiSecret = (string) config('services.erpnext.api_secret');
        $this->source    = (string) config('services.erpnext.lead_source', 'WhatsApp Bot');
        $this->leadOwner = (string) config('services.erpnext.lead_owner', '');
        $this->timeout   = (int) config('services.erpnext.timeout', 5);
        // Doctype destino: 'CRM Lead' (Frappe CRM) por defecto, configurable a 'Lead' (ERPNext clásico)
        $this->doctype     = (string) config('services.erpnext.lead_doctype', env('ERPNEXT_LEAD_DOCTYPE', 'CRM Lead'));
        $this->doctypePath = '/api/resource/' . str_replace(' ', '%20', $this->doctype);
    }

    public function isConfigured(): bool
    {
        return $this->url !== '' && $this->apiKey !== '' && $this->apiSecret !== '';
    }

    /**
     * Lee el estado actual del lead desde ERPNext.
     * Útil para sincronización bidireccional: si el equipo cerró el lead
     * (status=Converted/Lost) en CRM, el bot lo puede excluir de masivos.
     *
     * @return array|null  ['status'=>?, 'lead_owner'=>?, 'last_sync'=>?] o null si falla
     */
    public function fetchStatus(string $crmLeadId): ?array
    {
        if (!$this->isConfigured()) return null;

        try {
            $resp = Http::withHeaders([
                'Authorization' => 'token ' . $this->apiKey . ':' . $this->apiSecret,
                'Accept'        => 'application/json',
            ])->timeout($this->timeout)->get(
                $this->url . $this->doctypePath . '/' . urlencode($crmLeadId),
                ['fields' => '["status","lead_owner","modified"]']
            );

            if (!$resp->ok()) {
                Log::info('ErpNext fetchStatus error', ['status' => $resp->status(), 'lead_id' => $crmLeadId]);
                return null;
            }

            $data = $resp->json('data') ?: [];
            return [
                'status'     => $data['status']     ?? null,
                'lead_owner' => $data['lead_owner'] ?? null,
                'modified'   => $data['modified']   ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('ErpNext fetchStatus exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sincroniza el status local de un lead leyéndolo del CRM.
     * Mapping: Converted/Won → 'won', Lost/Junk → 'lost', otros → mantener.
     */
    public function syncStatusFromCrm(ChatbotLead $lead): bool
    {
        if (empty($lead->crm_lead_id)) return false;

        $remote = $this->fetchStatus($lead->crm_lead_id);
        if (!$remote || !$remote['status']) return false;

        $crmStatus = strtolower($remote['status']);
        $newLocal  = match (true) {
            in_array($crmStatus, ['converted', 'won', 'closed-won']) => 'won',
            in_array($crmStatus, ['lost', 'junk', 'do not contact', 'closed-lost']) => 'lost',
            in_array($crmStatus, ['interested', 'contacted', 'open', 'replied']) => 'in_progress',
            default => $lead->status,
        };

        if ($newLocal !== $lead->status) {
            $lead->status = $newLocal;
            $lead->save();
            Log::info("[ErpNext sync] lead {$lead->id} status: {$lead->status} → {$newLocal} (CRM={$crmStatus})");
            return true;
        }
        return false;
    }

    /**
     * Crea o actualiza un Lead en ERPNext a partir de un ChatbotLead local.
     * Devuelve true si quedó sincronizado, false si falló (el caller
     * debe persistir crm_last_error y crm_sync_attempts si quiere reintentar).
     */
    public function push(ChatbotLead $lead): bool
    {
        if (!$this->isConfigured()) {
            $this->markFailure($lead, 'ERPNext no configurado (.env vacío)');
            return false;
        }

        $payload = $this->buildPayload($lead);
        $isUpdate = !empty($lead->crm_lead_id);

        try {
            if ($isUpdate) {
                $response = $this->client()
                    ->put($this->url . $this->doctypePath . '/' . rawurlencode($lead->crm_lead_id), $payload);
            } else {
                $response = $this->client()
                    ->post($this->url . $this->doctypePath, $payload);
            }
        } catch (\Throwable $e) {
            $this->markFailure($lead, 'Excepción HTTP: ' . $e->getMessage());
            return false;
        }

        // Si el update falla con 404 (el Lead remoto fue borrado), reintenta con POST
        if ($isUpdate && $response->status() === 404) {
            $lead->crm_lead_id = null;
            try {
                $response = $this->client()
                    ->post($this->url . $this->doctypePath, $payload);
            } catch (\Throwable $e) {
                $this->markFailure($lead, 'Excepción HTTP (fallback POST): ' . $e->getMessage());
                return false;
            }
        }

        if ($response->successful()) {
            $name = data_get($response->json(), 'data.name');
            if (!$name) {
                $this->markFailure($lead, 'Respuesta sin data.name: ' . substr($response->body(), 0, 500));
                return false;
            }
            $lead->crm_lead_id      = $name;
            $lead->synced_to_crm    = 1;
            $lead->crm_synced_at    = now();
            $lead->crm_last_error   = null;
            $lead->crm_sync_attempts = ($lead->crm_sync_attempts ?? 0) + 1;
            $lead->save();
            return true;
        }

        $body = $response->body();
        $excType = data_get($response->json(), 'exc_type');
        $errSnippet = $excType ?: substr($body, 0, 500);
        $this->markFailure($lead, "HTTP {$response->status()}: {$errSnippet}");
        return false;
    }

    private function client()
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'token ' . $this->apiKey . ':' . $this->apiSecret,
            ]);
    }

    private function buildPayload(ChatbotLead $lead): array
    {
        $data = $lead->getDataArray();
        $lang = $data['language'] ?? 'es';
        $lastMessage = $data['last_message'] ?? '';
        $sessionId = (string) ($data['session_id'] ?? '');

        $idiomaLabel = match ($lang) {
            'en' => 'English',
            'pt' => 'Português',
            default => 'Español',
        };

        $cleanPhone = $this->normalizePhone($lead->contact);
        $name = $lead->contact_name ?: $cleanPhone;

        $deviceLabel = '';
        if ($lead->device_id) {
            $device = Device::find($lead->device_id);
            $deviceLabel = $device?->name ?? ('device_' . $lead->device_id);
        }

        // Status válido en CRM Lead: New | Contacted | Nurture | Qualified | Converted | Unqualified
        // En Lead clásico: Lead. Si el doctype es CRM Lead usamos 'New', sino 'Lead'.
        $defaultStatus = ($this->doctype === 'CRM Lead') ? 'New' : 'Lead';

        $payload = [
            'mobile_no'          => $cleanPhone,
            'source'             => $this->source,
            'status'             => $defaultStatus,
            'idioma_whatsapp'    => $idiomaLabel,
            'ultimo_mensaje_bot' => $lastMessage,
            'sesion_chatbot'     => $sessionId,
        ];

        // Nombre: en CRM Lead usar first_name; en Lead clásico usar lead_name
        if ($this->doctype === 'CRM Lead') {
            $payload['first_name'] = $name;
        } else {
            $payload['lead_name'] = $name;
        }

        if ($this->leadOwner !== '') {
            $payload['lead_owner'] = $this->leadOwner;
        }

        // Email: CRM Lead usa 'email'; Lead clásico usa 'email_id'
        if ($lead->contact_email) {
            $payload[$this->doctype === 'CRM Lead' ? 'email' : 'email_id'] = $lead->contact_email;
        }

        // Organization en CRM Lead, company_name no aplica directamente
        if (!empty($data['empresa']) && $this->doctype === 'CRM Lead') {
            $payload['organization'] = $data['empresa'];
        }

        // Notes solo para Lead clásico (CRM Lead las maneja distinto)
        if ($this->doctype !== 'CRM Lead') {
            $notes = [];
            if ($lead->interest)  $notes[] = 'Interés: ' . $lead->interest;
            if ($deviceLabel)     $notes[] = 'Dispositivo bot: ' . $deviceLabel;
            if ($notes) {
                $payload['notes'] = [['note' => implode(' | ', $notes)]];
            }
        }

        return $payload;
    }

    /**
     * Limpia números de WhatsApp: quita @s.whatsapp.net, @lid, sufijos, deja
     * solo dígitos con + adelante para que ERPNext lo acepte como mobile_no.
     */
    private function normalizePhone(?string $contact): string
    {
        if (!$contact) return '';
        $base = preg_replace('/@.*$/', '', $contact);
        $base = preg_replace('/[^\d]/', '', (string) $base);
        return $base ? ('+' . $base) : '';
    }

    private function markFailure(ChatbotLead $lead, string $error): void
    {
        $lead->synced_to_crm = 0;
        $lead->crm_sync_attempts = ($lead->crm_sync_attempts ?? 0) + 1;
        $lead->crm_last_error = mb_substr($error, 0, 1000);
        $lead->save();
        Log::warning('ErpNextLeadService push falló', [
            'lead_id' => $lead->id,
            'error'   => $error,
        ]);
    }
}
