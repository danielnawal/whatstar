<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'app_id', 'event_type', 'unit',
        'channel', 'destination', 'status', 'reason', 'created_at',
    ];
}
