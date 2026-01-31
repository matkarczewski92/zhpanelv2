<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalGenotypeTrait extends Model
{
    protected $table = 'animal_genotype_traits';
    protected $fillable = ['name', 'number_of_traits'];
    public $timestamps = true;

    public function genes()
    {
        return $this->hasMany(AnimalGenotypeTraitsDictionary::class, 'trait_id');
    }
}
