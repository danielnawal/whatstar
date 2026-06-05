<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id', 'device_id', 'contact', 'role', 'content',
        'intent', 'sentiment', 'matched_reply_id', 'variant_index',
        'tokens_used', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
