<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint',
        'method',
        'request_payload',
        'response_payload',
        'status_code',
        'ip_address',
        'country',
        'city',
        'isp',
        'duration_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];
}
