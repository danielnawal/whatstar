<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotAgent extends Model
{
    protected $table = 'chatbot_agents';

    protected $fillable = [
        'device_id', 'user_id', 'name', 'phone', 'role', 'region',
        'is_active', 'last_assigned_at',
        'handoffs_received_today', 'handoffs_received_total',
    ];

    protected $casts = [
        'last_assigned_at' => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
