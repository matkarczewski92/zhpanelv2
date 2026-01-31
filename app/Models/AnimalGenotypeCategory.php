<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalGenotypeCategory extends Model
{
    protected $table = 'animal_genotype_category';

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    public function genotypes(): HasMany
    {
        return $this->hasMany(AnimalGenotype::class, 'genotype_id');
    }
}
