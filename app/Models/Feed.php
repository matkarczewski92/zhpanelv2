<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feed extends Model
{
    protected $fillable = array (
  0 => 'name',
  1 => 'feeding_interval',
  2 => 'amount',
  3 => 'last_price',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function animalFeedings(): HasMany
    {
        return $this->hasMany(AnimalFeeding::class, 'feed_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'feed_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class, 'feed_id');
    }

}
