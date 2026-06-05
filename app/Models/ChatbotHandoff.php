<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotHandoff extends Model
{
    protected $fillable = [
        'device_id', 'contact', 'contact_name', 'agent_number',
        'last_message', 'status', 'user_id', 'resolved_at',
    ];

    protected $dates = ['resolved_at'];

    public function device() { return $this->belongsTo(Device::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
