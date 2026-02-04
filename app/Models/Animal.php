<?php

namespace App\Models;

use App\Domain\Shared\Enums\Sex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Animal extends Model
{
    protected $fillable = array (
  0 => 'name',
  1 => 'second_name',
  2 => 'sex',
  3 => 'date_of_birth',
  4 => 'animal_type_id',
  5 => 'litter_id',
  6 => 'feed_id',
  7 => 'feed_interval',
  8 => 'animal_category_id',
  9 => 'public_profile',
  10 => 'public_profile_tag',
  11 => 'web_gallery',
);

    protected $casts = array (
  'date_of_birth' => 'date',
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function animalCategory(): BelongsTo
    {
        return $this->belongsTo(AnimalCategory::class, 'animal_category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class, 'animal_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

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
    public function feedings(): HasMany
    {
        return $this->hasMany(AnimalFeeding::class, 'animal_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function weights(): HasMany
    {
        return $this->hasMany(AnimalWeight::class, 'animal_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function molts(): HasMany
    {
        return $this->hasMany(AnimalMolt::class, 'animal_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photos(): HasMany
    {
        return $this->hasMany(AnimalPhotoGallery::class, 'animal_id');
    }

    public function mainPhoto(): HasOne
    {
        return $this->hasOne(AnimalPhotoGallery::class, 'animal_id')->where('main_profil_photo', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function offers(): HasMany
    {
        return $this->hasMany(AnimalOffer::class, 'animal_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class, 'animal_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function winterings(): HasMany
    {
        return $this->hasMany(Wintering::class, 'animal_id');
    }

    public function genotypes(): HasMany
    {
        return $this->hasMany(AnimalGenotype::class, 'animal_id');
    }

    public function colorGroups(): BelongsToMany
    {
        return $this->belongsToMany(ColorGroup::class, 'animal_color_group', 'animal_id', 'color_group_id')
            ->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function litterPlanPairsAsFemale(): HasMany
    {
        return $this->hasMany(LitterPlanPair::class, 'female_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function litterPlanPairsAsMale(): HasMany
    {
        return $this->hasMany(LitterPlanPair::class, 'male_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectStagesAsFemale(): HasMany
    {
        return $this->hasMany(ProjectStage::class, 'parent_female_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectStagesAsMale(): HasMany
    {
        return $this->hasMany(ProjectStage::class, 'parent_male_id');
    }

    public function getSexLabelAttribute(): string
    {
        return Sex::label((int) $this->sex);
    }

}
