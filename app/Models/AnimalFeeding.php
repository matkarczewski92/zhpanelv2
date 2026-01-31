<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalFeeding extends Model
{
    protected $fillable = array (
  0 => 'animal_id',
  1 => 'feed_id',
  2 => 'amount',
);

    protected $casts = array (
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

}
