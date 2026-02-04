<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ColorGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function animals(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'animal_color_group', 'color_group_id', 'animal_id')
            ->withTimestamps();
    }
}
