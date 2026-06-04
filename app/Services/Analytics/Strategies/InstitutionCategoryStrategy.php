<?php
namespace App\Services\Analytics\Strategies;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstitutionCategoryStrategy extends BaseStandardStrategy
{
    protected function fetchResults(Builder $query, string $tableName): Collection
    {
        return $query->join('institutions', $tableName . '.institution_id', '=', 'institutions.id')
            ->join('school_classes', $tableName . '.school_class_id', '=', 'school_classes.id')
            ->select([
                DB::raw('CONCAT(' . $tableName . '.institution_id, "-", ' . $tableName . '.school_class_id, "-", ' . $tableName . '.section) as group_id'),
                $tableName . '.institution_id',
                $tableName . '.school_class_id',
                'school_classes.name as class_name',
                'institutions.name as group_name',
                $tableName . '.section',
                'fee_fund_category.category_title',
                DB::raw('count(case when ' . $tableName . '.status = "P" then 1 end) as paid_count'),
                DB::raw('count(case when ' . $tableName . '.status = "U" then 1 end) as unpaid_count'),
                DB::raw('GROUP_CONCAT(case when ' . $tableName . '.status = "P" then CAST(consumers.sis_student_id AS CHAR) end) as paid_student_ids'),
                DB::raw('GROUP_CONCAT(case when ' . $tableName . '.status = "U" then CAST(consumers.sis_student_id AS CHAR) end) as unpaid_student_ids'),
            ])
            ->groupBy(
                $tableName . '.institution_id',
                $tableName . '.school_class_id',
                $tableName . '.section',
                'school_classes.name',
                'fee_fund_category.category_title'
            )
            ->orderBy('school_classes.display_order')
            ->get();
    }

    protected function getType(): string
    {
        return 'institution_category';
    }
}
