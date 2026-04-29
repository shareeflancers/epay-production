<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcedureSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'step_name',
        'snapshot_data',
        'batch_id',
        'is_rolled_back',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'is_rolled_back' => 'boolean',
    ];
}
