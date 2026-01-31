<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LitterPairingSummary extends Model
{
    protected $table = 'litters_pairings_summary';

    protected $fillable = array (
  0 => 'pairings_id',
  1 => 'litter_id',
  2 => 'vis_amount',
  3 => 'het_amount',
  4 => 'scaleless',
  5 => 'tessera',
  6 => 'stripe',
  7 => 'motley',
  8 => 'okeetee',
  9 => 'extreme_okeetee',
  10 => 'multiplier',
);

    protected $casts = array (
  'scaleless' => 'boolean',
  'tessera' => 'boolean',
  'stripe' => 'boolean',
  'motley' => 'boolean',
  'okeetee' => 'boolean',
  'extreme_okeetee' => 'boolean',
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pairing(): BelongsTo
    {
        return $this->belongsTo(LitterPairing::class, 'pairings_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class, 'litter_id');
    }

}
