<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wintering extends Model
{
    protected $fillable = array (
  0 => 'animal_id',
  1 => 'season',
  2 => 'planned_start_date',
  3 => 'planned_end_date',
  4 => 'start_date',
  5 => 'end_date',
  6 => 'annotations',
  7 => 'stage_id',
  8 => 'custom_duration',
  9 => 'archive',
);

    protected $casts = array (
  'planned_start_date' => 'date',
  'planned_end_date' => 'date',
  'start_date' => 'date',
  'end_date' => 'date',
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
    public function stage(): BelongsTo
    {
        return $this->belongsTo(WinteringStage::class, 'stage_id');
    }

}
