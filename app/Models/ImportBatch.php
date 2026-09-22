<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uploaded_by',
        'original_filename',
        'row_count',
        'status',
    ];
}