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
     * Get the transaction ledger histories.
     */
    public function transactionLedgerHistories(): HasMany
    {
        return $this->hasMany(ChallanTransactionLedgerHistory::class, 'challan_id');
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
