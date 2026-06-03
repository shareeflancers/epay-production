<?php

namespace App\Services\Analytics\Traits;

use App\Models\ActiveChallan;

trait AnalyticsHistoryTrait
{
    /**
     * Determine if we should query the history table instead of the active table.
     */
    protected function shouldQueryHistory(array $filters): bool
    {
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;

        // Force fallback to history if querying a month/year that is not the current running month/year
        if ($month && (int)$month !== (int)now()->month) {
            return true;
        }
        if ($year && (int)$year !== (int)now()->year) {
            return true;
        }

        $institutionId = $filters['institution_id'] ?? null;
        $classId = $filters['school_class_id'] ?? null;
        $regionId = $filters['region_id'] ?? null;
        $section = $filters['section'] ?? null;
        $feeFundCategoryId = $filters['fee_fund_category_id'] ?? null;
        $yearSession = $filters['year_session'] ?? null;

        $query = ActiveChallan::query();

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        if ($classId) {
            $query->where('school_class_id', $classId);
        }
        if ($regionId) {
            $query->where('region_id', $regionId);
        }
        if ($feeFundCategoryId) {
            $query->where('fee_fund_category_id', $feeFundCategoryId);
        }
        if ($month) {
            $query->whereMonth('due_date', $month);
        }
        if ($year) {
            $query->whereYear('due_date', $year);
        }
        if ($yearSession) {
            $query->whereHas('yearSession', function ($q) use ($yearSession) {
                $q->where('name', $yearSession);
            });
        }
        if ($section) {
            $query->where('section', $section);
        }

        return !$query->exists();
    }
}
