<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LitterRoadmap extends Model
{
    protected $fillable = [
        'name',
        'search_input',
        'generations',
        'expected_traits',
        'target_reachable',
        'matched_traits',
        'missing_traits',
        'steps',
        'completed_generations',
        'last_refreshed_at',
    ];

    protected $casts = [
        'expected_traits' => 'array',
        'target_reachable' => 'boolean',
        'matched_traits' => 'array',
        'missing_traits' => 'array',
        'steps' => 'array',
        'completed_generations' => 'array',
        'last_refreshed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
