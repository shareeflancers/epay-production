<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallanHistory extends Model
{
    protected $table = 'challan_history';

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

    public function yearSession()
    {
        return $this->belongsTo(YearSession::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function feeFundStructure()
    {
        return $this->belongsTo(FeeFundStructure::class, 'fee_fund_structure_id');
    }
}
