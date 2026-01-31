<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalOfferReservation extends Model
{
    protected $fillable = array (
  0 => 'offer_id',
  1 => 'deposit',
  2 => 'booker',
  3 => 'adnotations',
  4 => 'expiration_date',
);

    protected $casts = array (
  'expiration_date' => 'date',
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(AnimalOffer::class, 'offer_id');
    }

}
