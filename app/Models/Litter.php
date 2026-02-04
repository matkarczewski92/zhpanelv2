<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Litter extends Model
{
    protected $fillable = array (
  0 => 'category',
  1 => 'litter_code',
  2 => 'connection_date',
  3 => 'laying_date',
  4 => 'hatching_date',
  5 => 'laying_eggs_total',
  6 => 'laying_eggs_ok',
  7 => 'hatching_eggs',
  8 => 'season',
  9 => 'adnotations',
  10 => 'parent_male',
  11 => 'parent_female',
  12 => 'planned_connection_date',
);

    protected $casts = array (
  'connection_date' => 'date',
  'laying_date' => 'date',
  'hatching_date' => 'date',
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
  'planned_connection_date' => 'date',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'litter_id');
    }

    public function maleParent(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'parent_male');
    }

    public function femaleParent(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'parent_female');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pairings(): HasMany
    {
        return $this->hasMany(LitterPairing::class, 'litter_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adnotations(): HasMany
    {
        return $this->hasMany(LitterAdnotation::class, 'litter_id');
    }

    public function adnotation(): HasOne
    {
        return $this->hasOne(LitterAdnotation::class, 'litter_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(LitterGallery::class, 'litter_id');
    }

    public function mainPhoto(): HasOne
    {
        return $this->hasOne(LitterGallery::class, 'litter_id')->where('main_photo', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pairingSummaries(): HasMany
    {
        return $this->hasMany(LitterPairingSummary::class, 'litter_id');
    }

}
