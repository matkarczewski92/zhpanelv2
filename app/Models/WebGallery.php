<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebGallery extends Model
{
    protected $table = 'web_gallery';

    protected $fillable = array (
  0 => 'url',
  1 => 'description',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

}
