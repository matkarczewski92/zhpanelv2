<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LitterPregnancyShed extends Model
{
    protected $table = 'litters_pregnancy_sheds';

    protected $fillable = [
        'litter_id',
        'shed_date',
    ];

    protected $casts = [
        'shed_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function litter(): BelongsTo
    {
        return $this->belongsTo(Litter::class, 'litter_id');
    }
}
