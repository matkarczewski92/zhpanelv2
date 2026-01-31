<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectStage extends Model
{
    protected $table = 'projects_stages';

    protected $fillable = array (
  0 => 'season',
  1 => 'project_id',
  2 => 'parent_male_id',
  3 => 'parent_male_name',
  4 => 'parent_female_id',
  5 => 'parent_female_name',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentMale(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'parent_male_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentFemale(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'parent_female_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nfs(): HasMany
    {
        return $this->hasMany(ProjectStageNfs::class, 'stage_id');
    }

}
