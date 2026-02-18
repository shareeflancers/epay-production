<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $fillable = [
        'name',
        'region_id',
        'level_id',
        'display_order',
        'is_active',
        'is_deleted',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
