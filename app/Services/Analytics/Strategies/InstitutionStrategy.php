<?php
namespace App\Services\Analytics\Strategies;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstitutionStrategy extends BaseStandardStrategy
{
    protected function fetchResults(Builder $query, string $tableName): Collection
    {
        return $query->join('institutions', $tableName . '.institution_id', '=', 'institutions.id')
            ->select([
                'institutions.id as group_id',
                'institutions.id as institution_id',
                'institutions.name as group_name',
                'fee_fund_category.category_title',
                DB::raw('count(case when ' . $tableName . '.status = "P" then 1 end) as paid_count'),
                DB::raw('count(case when ' . $tableName . '.status = "U" then 1 end) as unpaid_count'),
                DB::raw('GROUP_CONCAT(case when ' . $tableName . '.status = "P" then CAST(consumers.sis_student_id AS CHAR) end) as paid_student_ids'),
                DB::raw('GROUP_CONCAT(case when ' . $tableName . '.status = "U" then CAST(consumers.sis_student_id AS CHAR) end) as unpaid_student_ids'),
            ])
            ->groupBy('institutions.id', 'institutions.name', 'fee_fund_category.category_title')
            ->get();
    }

    protected function getType(): string
    {
        return 'institution';
    }
}
