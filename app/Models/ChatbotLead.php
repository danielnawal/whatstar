<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotLead extends Model
{
    protected $fillable = [
        'device_id', 'contact', 'contact_name', 'contact_email',
        'contact_extra', 'interest', 'full_data', 'status', 'user_id',
        'synced_to_crm', 'crm_lead_id', 'crm_sync_attempts', 'crm_last_error', 'crm_synced_at',
    ];

    public function device() { return $this->belongsTo(Device::class); }
    public function user()   { return $this->belongsTo(User::class); }

    /** Devuelve el JSON de datos como array */
    public function getDataArray(): array
    {
        return json_decode($this->full_data ?? '{}', true) ?? [];
    }

    /** Actualiza un campo dentro del JSON full_data */
    public function setDataField(string $field, string $value): void
    {
        $data         = $this->getDataArray();
        $data[$field] = $value;
        $this->full_data = json_encode($data, JSON_UNESCAPED_UNICODE);

        // Mapear campos conocidos a columnas directas
        match ($field) {
            'nombre', 'name'  => $this->contact_name  = $value,
            'email'           => $this->contact_email = $value,
            'interes','interés','producto','servicio' => $this->interest = $value,
            default           => $this->contact_extra = $value,
        };
    }
}
