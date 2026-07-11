<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    protected $fillable = [
        'user_id', 'app_id', 'event_type', 'unit_pattern',
        'mute_from', 'mute_to', 'dedupe_seconds', 'min_speed',
        'block', 'is_active', 'priority',
    ];

    protected $casts = [
        'block'     => 'boolean',
        'is_active' => 'boolean',
    ];
}
