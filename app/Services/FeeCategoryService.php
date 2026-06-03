<?php

namespace App\Services;

use App\Models\FeeFundCategory;

class FeeCategoryService
{
    /**
     * Get active fee categories.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveFeeCategories()
    {
        return FeeFundCategory::select([
            'id as category_id',
            'category_title',
            'details as category_description',
        ])
        ->where('is_active', 1)
        ->get();
    }
}
