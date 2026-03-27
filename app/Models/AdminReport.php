<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminReport extends Model
{
    protected $fillable = [
        'report_type',
        'report_name',
        'generated_at',
        'date_from',
        'date_to',
        'report_date',
        'item_count',
        'file_name',
        'pdf_path',
        'filters_payload',
        'report_payload',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'date_from' => 'date',
        'date_to' => 'date',
        'report_date' => 'date',
        'item_count' => 'integer',
        'filters_payload' => 'array',
        'report_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
