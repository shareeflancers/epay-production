<?php
namespace App\Services\Analytics\Strategies;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RegionStrategy extends BaseStandardStrategy
{
    protected function fetchResults(Builder $query, string $tableName): Collection
    {
        return $query->join('regions', $tableName . '.region_id', '=', 'regions.id')
            ->select([
                'regions.id as group_id',
                'regions.id as region_id',
                'regions.name as group_name',
                'fee_fund_category.category_title',
                DB::raw('count(case when ' . $tableName . '.status = "P" then 1 end) as paid_count'),
                DB::raw('count(case when ' . $tableName . '.status = "U" then 1 end) as unpaid_count'),
                DB::raw('GROUP_CONCAT(case when ' . $tableName . '.status = "P" then CAST(consumers.sis_student_id AS CHAR) end) as paid_student_ids'),
                DB::raw('GROUP_CONCAT(case when ' . $tableName . '.status = "U" then CAST(consumers.sis_student_id AS CHAR) end) as unpaid_student_ids'),
            ])
            ->groupBy('regions.id', 'regions.name', 'fee_fund_category.category_title')
            ->get();
    }

    protected function getType(): string
    {
        return 'region';
    }
}
