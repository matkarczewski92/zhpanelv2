<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LitterPlan extends Model
{
    protected $fillable = array (
  0 => 'name',
  1 => 'planned_year',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pairs(): HasMany
    {
        return $this->hasMany(LitterPlanPair::class, 'litter_plan_id');
    }

}
