<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotForSale extends Model
{
    protected $fillable = array (
  0 => 'pairing_id',
  1 => 'sex',
  2 => 'annotations',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pairing(): BelongsTo
    {
        return $this->belongsTo(LitterPairing::class, 'pairing_id');
    }

}
