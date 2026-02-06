<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EwelinkDevice extends Model
{
    protected $table = 'ewelink_devices';

    protected $fillable = [
        'device_id',
        'name',
        'description',
        'device_type',
        'thing_payload',
        'status_payload',
        'last_synced_at',
        'last_error',
    ];

    protected $casts = [
        'thing_payload' => 'array',
        'status_payload' => 'array',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
