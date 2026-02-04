<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalGenotypeException extends Model
{
    protected $table = 'animal_genotype_exceptions';

    protected $fillable = [
        'name',
        'description',
        'species_id',
        'priority',
        'is_enabled',
        'match_json',
        'effect_json',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'match_json' => 'array',
        'effect_json' => 'array',
    ];
}

