<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolClassFactory> */
    use HasFactory;
    use \App\Traits\HasIsDeleted;

    protected $table = 'school_classes';

    protected $fillable = [
        'name',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    /**
     * Get the year sessions for this class.
     */
    public function yearSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(YearSession::class, 'school_class_id');
    }
}
