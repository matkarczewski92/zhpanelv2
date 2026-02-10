<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WinteringStage extends Model
{
    protected $table = 'winterings_stage';

    protected $fillable = array (
  0 => 'scheme',
  1 => 'order',
  2 => 'title',
  3 => 'duration',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function winterings(): HasMany
    {
        return $this->hasMany(Wintering::class, 'stage_id');
    }

}
