<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeFundStructure extends Model
{
    /** @use HasFactory<\Database\Factories\FeeFundStructureFactory> */
    use HasFactory;
    use \App\Traits\HasIsDeleted;

    protected $table = 'fee_fund_structure';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'region_id',
        'school_class_id',
        'fee_fund_category_id',
        'fee_fund_head_id',
        'fee_head_amounts',
        'total',
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
            'fee_head_amounts' => 'array',
            'total' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the fee fund head group.
     */
    public function feeFundHead(): BelongsTo
    {
        return $this->belongsTo(FeeFundHead::class, 'fee_fund_head_id');
    }

    /**
     * Get the fee fund category.
     */
    public function feeFundCategory(): BelongsTo
    {
        return $this->belongsTo(FeeFundCategory::class, 'fee_fund_category_id');
    }

    /**
     * Get the region.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the school class.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * Get the profile details using this structure.
     */
    public function profileDetails(): HasMany
    {
        return $this->hasMany(ProfileDetail::class, 'fee_fund_structure_id');
    }

    /**
     * Get the transaction ledger histories for this structure.
     */
    public function transactionLedgerHistories(): HasMany
    {
        return $this->hasMany(ChallanTransactionLedgerHistory::class, 'fee_fund_structure_id');
    }
}
