<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeFundHead extends Model
{
    /** @use HasFactory<\Database\Factories\FeeFundHeadFactory> */
    use HasFactory;
    use \App\Traits\HasIsDeleted;

    protected $table = 'fee_fund_heads';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'head_identifier',
        'fee_head',
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
            'fee_head' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
