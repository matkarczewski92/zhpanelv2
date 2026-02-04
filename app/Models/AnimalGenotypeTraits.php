<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalGenotypeTraits extends Model
{
    protected $table = 'animal_genotype_traits';

    public function getTraitsDictionary(): HasMany
    {
        return $this->hasMany(AnimalGenotypeTraitsDictionary::class, 'trait_id', 'id');
    }
}

