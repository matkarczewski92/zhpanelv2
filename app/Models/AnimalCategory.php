<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalCategory extends Model
{
    protected $table = 'animal_category';

    protected $fillable = array (
  0 => 'name',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'animal_category_id');
    }

}
