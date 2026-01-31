<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LitterPlanPair extends Model
{
    protected $fillable = array (
  0 => 'litter_plan_id',
  1 => 'female_id',
  2 => 'male_id',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function litterPlan(): BelongsTo
    {
        return $this->belongsTo(LitterPlan::class, 'litter_plan_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function female(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'female_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function male(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'male_id');
    }

}
