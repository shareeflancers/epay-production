<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeFundCategory extends Model
{
    /** @use HasFactory<\Database\Factories\FeeFundCategoryFactory> */
    use HasFactory;
    use \App\Traits\HasIsDeleted;

    protected $table = 'fee_fund_category';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_title',
        'details',
        'is_active',
        'display_order',
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
        ];
    }

    /**
     * Get the fee fund structures for this category.
     */
    public function feeFundStructures(): HasMany
    {
        return $this->hasMany(FeeFundStructure::class, 'fee_fund_category_id');
    }

    /**
     * Get the transaction ledger histories for this category.
     */
    public function transactionLedgerHistories(): HasMany
    {
        return $this->hasMany(ChallanTransactionLedgerHistory::class, 'fee_fund_category_id');
    }
}
