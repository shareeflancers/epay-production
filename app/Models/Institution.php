<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $fillable = [
        'id',
        'name',
        'region_id',
        'level_id',
        'principal_name',
        'principal_cnic',
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

    public function yearSessions()
    {
        return $this->hasMany(YearSession::class, 'institution_id');
    }

    public function activeChallans()
    {
        return $this->hasMany(ActiveChallan::class, 'institution_id');
    }
}
