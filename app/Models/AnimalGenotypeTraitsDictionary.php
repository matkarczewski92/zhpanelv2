<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimalGenotypeTraitsDictionary extends Model
{
    protected $table = 'animal_genotype_traits_dictionary';
    protected $fillable = ['trait_id', 'category_id'];
    public $timestamps = true;

    public function trait()
    {
        return $this->belongsTo(AnimalGenotypeTrait::class, 'trait_id');
    }

    public function genotypeCategory()
    {
        return $this->belongsTo(AnimalGenotypeCategory::class, 'category_id');
    }

    public function category()
    {
        return $this->belongsTo(AnimalGenotypeCategory::class, 'category_id');
    }
}
