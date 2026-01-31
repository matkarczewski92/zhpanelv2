<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    protected $table = 'finances_category';

    protected $fillable = array (
  0 => 'name',
);

    protected $casts = array (
  'created_at' => 'datetime',
  'updated_at' => 'datetime',
);

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class, 'finances_category_id');
    }

}
