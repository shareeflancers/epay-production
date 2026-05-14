<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\OneLinkHelper;

class ActiveChallan extends Model
{
    /** @use HasFactory<\Database\Factories\ActiveChallanFactory> */
    use HasFactory;
    use \App\Traits\HasIsDeleted;

    protected $table = 'active_challans';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'consumer_id',
        'challan_no',
        'tran_ref_number',
        'status',
        'tran_auth_id',
        'bank_mnemonic',
        'due_date',
        'amount_base',
        'amount_arrears',
        'amount_within_dueDate',
        'amount_after_dueDate',
        'date_paid',
        'fee_type',
        'reserved',
        'institution_id',
        'region_id',
        'fee_fund_category_id',
        'fee_fund_head_id',
        'fee_fund_structure_id',
        'school_class_id',
        'section',
        'level_id',
        'year_session_id',
        'challan_snapshot',
        'is_active',
        'sms_sync',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'date_paid' => 'date',
            'amount_base' => 'decimal:2',
            'amount_arrears' => 'decimal:2',
            'amount_within_dueDate' => 'decimal:2',
            'amount_after_dueDate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the consumer.
     */
    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class, 'consumer_id');
    }

    /**
     * Get the institution.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    /**
     * Get the region.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Get the fee fund category.
     */
    public function feeFundCategory(): BelongsTo
    {
        return $this->belongsTo(FeeFundCategory::class, 'fee_fund_category_id');
    }

    /**
     * Get the fee fund head.
     */
    public function feeFundHead(): BelongsTo
    {
        return $this->belongsTo(FeeFundHead::class, 'fee_fund_head_id');
    }

    /**
     * Get the fee fund structure.
     */
    public function feeFundStructure(): BelongsTo
    {
        return $this->belongsTo(FeeFundStructure::class, 'fee_fund_structure_id');
    }

    /**
     * Get the school class.
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    /**
     * Get the level.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    /**
     * Get the year session.
     */
    public function yearSession(): BelongsTo
    {
        return $this->belongsTo(YearSession::class, 'year_session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | 1Link API Helpers & Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Format the challan into 1LINK Inquiry Success Response.
     */
    public function toOneLinkInquiryResponse(): array
    {
        $studentName = $this->consumer->profileDetails->where('is_active', true)->first()->name ?? 'Student';

        return OneLinkHelper::inquiryResponse([
            'consumer_name'        => $studentName,
            'status'               => $this->status,
            'due_date'             => $this->due_date,
            'amount_within_dueDate'=> $this->amount_within_dueDate,
            'amount_after_dueDate' => $this->amount_after_dueDate,
            'date_paid'            => $this->date_paid,
            'tran_auth_id'         => $this->tran_auth_id,
            'reserved'             => $this->reserved,
            'institution_id'       => $this->institution_id,
            'region_id'            => $this->region_id,
        ]);
    }

    /**
     * Format the challan into 1LINK Payment Success Response.
     */
    public function toOneLinkPaymentResponse(): array
    {
        return OneLinkHelper::paymentResponse([
            'tran_auth_id' => $this->tran_auth_id,
            'reserved'     => $this->reserved,
        ]);
    }

    /**
     * Generate a snapshot of the challan data.
     * 
     * @return array
     */
    public function generateSnapshot(): array
    {
        $consumer = $this->consumer;
        $profile = $consumer->profileDetails()->where('is_active', true)->first() ?: $consumer->profileDetails()->first();

        // Get category IDs from profile or the challan itself
        $categoryIds = $profile?->fee_fund_category_ids ?? [];
        if (empty($categoryIds) && $this->fee_fund_category_id) {
            $categoryIds = [$this->fee_fund_category_id];
        }

        $feeStructures = FeeFundStructure::where('is_active', true)
            ->where('region_id', $this->region_id)
            ->where('school_class_id', $this->school_class_id)
            ->whereIn('fee_fund_category_id', $categoryIds)
            ->get();

        // Arrears details
        $latestUnpaidHistory = self::where('consumer_id', $this->consumer_id)
            ->where('status', 'U')
            ->where('id', '!=', $this->id)
            ->latest()
            ->first();

        $arrearsDetails = [];
        if ($latestUnpaidHistory) {
            $arrearsDetails = [
                'challan_no' => $latestUnpaidHistory->challan_no,
                'due_date'   => $latestUnpaidHistory->due_date?->toDateString(),
                'amount'     => $latestUnpaidHistory->amount_within_dueDate,
            ];
        }

        return [
            'arrears_calculation' => [
                'amount_arrears' => $this->amount_arrears,
                'details'        => $arrearsDetails,
            ],
            'consumer' => [
                'id'              => $consumer->id,
                'consumer_number' => $consumer->consumer_number,
                'consumer_type'   => $consumer->consumer_type,
                'identification_number' => $consumer->identification_number,
                'region_id'       => $consumer->region_id,
                'institution_id'  => $consumer->institution_id,
            ],
            'profile' => $profile ? [
                'id'                      => $profile->id,
                'name'                    => $profile->name,
                'father_or_guardian_name' => $profile->father_or_guardian_name,
                'class'                   => $profile->class,
                'school_class_id'         => $profile->school_class_id,
                'section'                 => $this->section ?? $profile->section,
                'level_id'                => $profile->level_id,
                'region_name'             => $profile->region_name ?? null,
                'fee_fund_category_ids'   => $profile->fee_fund_category_ids,
            ] : null,
            'institution' => $this->institution ? [
                'id'     => $this->institution->id,
                'name'   => $this->institution->name,
                'region_id' => $this->institution->region_id,
                'level_id'  => $this->institution->level_id,
            ] : null,
            'region' => $this->region ? [
                'id'   => $this->region->id,
                'name' => $this->region->name ?? $this->region->region ?? null,
            ] : null,
            'year_session' => $this->yearSession ? [
                'id'         => $this->yearSession->id,
                'name'       => $this->yearSession->name,
                'start_date' => $this->yearSession->due_date?->toDateString(), // Using due_date as fallback if session dates missing
                'end_date'   => $this->yearSession->end_date?->toDateString(),
            ] : null,
            'fee_structures' => $feeStructures->map(fn ($s) => [
                'id'                  => $s->id,
                'fee_fund_category_id'=> $s->fee_fund_category_id,
                'fee_fund_category'   => $s->feeFundCategory?->category_title,
                'fee_fund_head_id'    => $s->fee_fund_head_id,
                'fee_fund_head'       => $s->feeFundHead?->head_identifier ?? null,
                'fee_head_amounts'    => $s->fee_head_amounts ?? [],
                'total'               => $s->total,
                'region_id'           => $s->region_id,
                'school_class_id'     => $s->school_class_id,
            ])->values()->toArray(),
            'fee_categories' => FeeFundCategory::whereIn('id', $categoryIds)
                ->get(['id', 'category_title'])
                ->toArray(),
        ];
    }

    /**
     * Scope to get unpaid challans for a consumer.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'U');
    }

    /**
     * Scope to get challans by consumer number.
     * Note: Accessing consumer relationship
     */
    public function scopeByConsumerNumber($query, $consumerNumber)
    {
        return $query->whereHas('consumer', function ($q) use ($consumerNumber) {
            $q->where('consumer_number', $consumerNumber);
        });
    }

    /**
     * Scope to get challans ordered by due date (latest first for fast inquiry).
     */
    public function scopeOrderedForInquiry($query)
    {
        return $query->orderBy('due_date', 'desc')->orderBy('challan_no');
    }
}
