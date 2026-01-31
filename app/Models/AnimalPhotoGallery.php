<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalPhotoGallery extends Model
{
    protected $table = 'animal_photo_gallery';

    protected $fillable = array (
  0 => 'animal_id',
  1 => 'url',
  2 => 'main_profil_photo',
  3 => 'banner_possition',
  4 => 'webside',
);

    protected $casts = array (
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

}
