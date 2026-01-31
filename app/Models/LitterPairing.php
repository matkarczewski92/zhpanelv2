<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LitterPairing extends Model
{
    protected $table = 'litters_pairings';

    protected $fillable = array (
  0 => 'percent',
  1 => 'title_vis',
  2 => 'title_het',
  3 => 'litter_id',
  4 => 'value',
  5 => 'img_url',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class, 'litter_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function summaries(): HasMany
    {
        return $this->hasMany(LitterPairingSummary::class, 'pairings_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notForSales(): HasMany
    {
        return $this->hasMany(NotForSale::class, 'pairing_id');
    }

}
