<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileDetail extends Model
{
    /** @use HasFactory<\Database\Factories\ProfileDetailFactory> */
    use HasFactory;
    use \App\Traits\HasIsDeleted;

    protected $table = 'profile_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'profile_type',
        'consumer_id',
        'name',
        'father_or_guardian_name',
        'region_name',
        'institution_name',
        'institution_level',
        'class',
        'section',
        'fee_fund_category_ids',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'fee_fund_category_ids' => 'array',
        ];
    }

    /**
     * Get the consumer.
     */
    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class, 'consumer_id');
    }
}
