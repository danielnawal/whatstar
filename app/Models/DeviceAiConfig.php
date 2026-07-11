<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceAiConfig extends Model
{
    protected $fillable = [
        'device_id', 'business_name', 'industry', 'business_context',
        'custom_system_prompt', 'ai_enabled', 'use_default_prompt',
        'banned_terms', 'allowed_intents',
    ];

    protected $casts = [
        'ai_enabled'         => 'boolean',
        'use_default_prompt' => 'boolean',
        'banned_terms'       => 'array',
        'allowed_intents'    => 'array',
    ];
}
