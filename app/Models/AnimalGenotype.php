<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalGenotype extends Model
{
    protected $table = 'animal_genotype';

    protected $fillable = [
        'animal_id',
        'genotype_id',
        'type',
    ];

    public $timestamps = false;

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AnimalGenotypeCategory::class, 'genotype_id');
    }
}
