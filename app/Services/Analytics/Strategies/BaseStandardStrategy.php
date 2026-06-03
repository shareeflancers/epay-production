<?php

namespace App\Services\Analytics\Strategies;

use App\Services\Analytics\Contracts\AnalyticsStrategyInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

abstract class BaseStandardStrategy implements AnalyticsStrategyInterface
{
    /**
     * Define the query logic for the strategy.
     *
     * @param Builder $query
     * @param string $tableName
     * @return Collection
     */
    abstract protected function fetchResults(Builder $query, string $tableName): Collection;

    /**
     * Get the type identifier for formatting logic rules.
     *
     * @return string
     */
    abstract protected function getType(): string;

    public function execute(Builder $query, string $tableName, array $filters): array
    {
        $results = $this->fetchResults($query, $tableName);
        return $this->formatAnalyticsData($results, $filters, $this->getType());
    }

    /**
     * Format the standard analytics data results.
     */
    protected function formatAnalyticsData(Collection $results, array $filters = [], string $type = 'overall'): array
    {
        $formatted = [];
        $fee_fund_category_id = $filters['fee_fund_category_id'] ?? null;
        $institution_id = $filters['institution_id'] ?? null;
        $region_id = $filters['region_id'] ?? null;
        $school_class_id = $filters['school_class_id'] ?? null;
        $section = $filters['section'] ?? null;

        foreach ($results as $row) {
            $groupId = $row->group_id;

            if (!isset($formatted[$groupId])) {
                $formatted[$groupId] = [];

                $isGroupNameRedundant = false;
                if ($type === 'institution' && $institution_id) {
                    $isGroupNameRedundant = true;
                } elseif ($type === 'region' && $region_id) {
                    $isGroupNameRedundant = true;
                } elseif ($type === 'institution_category' && $institution_id) {
                    $isGroupNameRedundant = true;
                }

                if (!$isGroupNameRedundant) {
                    $formatted[$groupId]['group_name'] = $row->group_name;
                }

                if (isset($row->institution_id)) {
                    $formatted[$groupId]['institution_id'] = (int) $row->institution_id;
                }
                if (isset($row->region_id)) {
                    $formatted[$groupId]['region_id'] = (int) $row->region_id;
                }
                if (isset($row->school_class_id)) {
                    $formatted[$groupId]['school_class_id'] = (int) $row->school_class_id;
                    $formatted[$groupId]['class_id'] = (int) $row->school_class_id;
                }

                $formatted[$groupId]['total_paid'] = [
                    'count' => 0,
                    'student_ids' => []
                ];
                $formatted[$groupId]['total_unpaid'] = [
                    'count' => 0,
                    'student_ids' => []
                ];

                if (!$fee_fund_category_id) {
                    $formatted[$groupId]['categories'] = [];
                }

                if (isset($row->class_name) && !$school_class_id) {
                    $formatted[$groupId]['class_name'] = $row->class_name;
                }

                if (isset($row->section) && !$section) {
                    $formatted[$groupId]['section'] = $row->section;
                }
            }

            $formatted[$groupId]['total_paid']['count'] += $row->paid_count;
            $formatted[$groupId]['total_unpaid']['count'] += $row->unpaid_count;

            $paidIds = [];
            if (isset($row->paid_student_ids) && $row->paid_student_ids !== null && $row->paid_student_ids !== '') {
                $paidIds = array_filter(array_map('intval', explode(',', $row->paid_student_ids)));
            }

            $unpaidIds = [];
            if (isset($row->unpaid_student_ids) && $row->unpaid_student_ids !== null && $row->unpaid_student_ids !== '') {
                $unpaidIds = array_filter(array_map('intval', explode(',', $row->unpaid_student_ids)));
            }

            $formatted[$groupId]['total_paid']['student_ids'] = array_values(array_unique(array_merge(
                $formatted[$groupId]['total_paid']['student_ids'],
                $paidIds
            )));

            $formatted[$groupId]['total_unpaid']['student_ids'] = array_values(array_unique(array_merge(
                $formatted[$groupId]['total_unpaid']['student_ids'],
                $unpaidIds
            )));

            if (!$fee_fund_category_id && isset($row->category_title) && $row->category_title) {
                $formatted[$groupId]['categories'][] = [
                    'name' => $row->category_title,
                    'paid' => [
                        'count' => $row->paid_count,
                        'student_ids' => array_values(array_unique($paidIds))
                    ],
                    'unpaid' => [
                        'count' => $row->unpaid_count,
                        'student_ids' => array_values(array_unique($unpaidIds))
                    ]
                ];
            }
        }

        return array_values($formatted);
    }
}
