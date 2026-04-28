<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasIsDeleted;

class YearSession extends Model
{
    use HasFactory;
    use HasIsDeleted;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'school_class_id',
        'institution_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_active'  => 'boolean',
        ];
    }

    /**
     * Get the school class.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * Get the institution.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    /**
     * Get the challans for this session.
     */
    public function activeChallans(): HasMany
    {
        return $this->hasMany(ActiveChallan::class, 'year_session_id');
    }
}
