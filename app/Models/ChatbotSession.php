<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotSession extends Model
{
    protected $fillable = [
        'device_id', 'contact', 'current_reply_id', 'last_reply_at', 'replied_ids',
        'locked_language', 'is_paused', 'paused_until', 'human_handoff_at',
        'lead_data', 'capturing_field', 'is_new_contact', 'bant_step', 'nps_pending',
    ];

    protected $dates = ['last_reply_at'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function currentReply()
    {
        return $this->belongsTo(Reply::class, 'current_reply_id');
    }

    /** Devuelve array de IDs de reglas ya respondidas a este contacto */
    public function getRepliedIdsArray(): array
    {
        if (empty($this->replied_ids)) return [];
        return array_map('intval', explode(',', $this->replied_ids));
    }

    /** Agrega un ID a la lista de ya respondidas */
    public function addRepliedId(int $replyId): void
    {
        $ids   = $this->getRepliedIdsArray();
        $ids[] = $replyId;
        $this->replied_ids = implode(',', array_unique($ids));
    }
}
