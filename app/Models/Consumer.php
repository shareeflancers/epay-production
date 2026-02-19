<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ActiveChallan;

class Consumer extends Model
{
    /** @use HasFactory<\Database\Factories\ConsumerFactory> */
    use HasFactory;
    use \App\Traits\HasIsDeleted;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'consumer_type',
        'identification_number',
        'consumer_number',
        'institution_id',
        'region_id',
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
        ];
    }

    /**
     * Get the profile details for this consumer.
     */
    public function profileDetails(): HasMany
    {
        return $this->hasMany(ProfileDetail::class, 'consumer_id');
    }

    public function activeChallans(): HasMany
    {
        return $this->hasMany(ActiveChallan::class, 'consumer_id');
    }

    /**
     * Get the region this consumer belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the institution this consumer belongs to.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the transaction ledger histories for this consumer.
     */
    public function transactionLedgerHistories(): HasMany
    {
        return $this->hasMany(ChallanTransactionLedgerHistory::class, 'consumer_id');
    }
}
