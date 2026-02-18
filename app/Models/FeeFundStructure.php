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
        'level_id',
        'fee_fund_category_id',
        'admission_fee',
        'slc',
        'tution_fee',
        'idf',
        'exam_fee',
        'it_fee',
        'csf',
        'rdf',
        'cdf',
        'security_fund',
        'bs_fund',
        'prep_fund',
        'donation_fund',
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
            'admission_fee' => 'decimal:2',
            'slc' => 'decimal:2',
            'tution_fee' => 'decimal:2',
            'idf' => 'decimal:2',
            'exam_fee' => 'decimal:2',
            'it_fee' => 'decimal:2',
            'csf' => 'decimal:2',
            'rdf' => 'decimal:2',
            'cdf' => 'decimal:2',
            'security_fund' => 'decimal:2',
            'bs_fund' => 'decimal:2',
            'prep_fund' => 'decimal:2',
            'donation_fund' => 'decimal:2',
            'total' => 'decimal:2',
            'is_active' => 'boolean',
        ];
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
     * Get the level.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
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
