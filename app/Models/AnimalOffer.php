<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AnimalOffer extends Model
{
    protected $fillable = array (
  0 => 'animal_id',
  1 => 'price',
  2 => 'sold_date',
);

    protected $casts = array (
  'sold_date' => 'date',
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(AnimalOfferReservation::class, 'offer_id');
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(AnimalOfferReservation::class, 'offer_id')->latestOfMany();
    }

}
