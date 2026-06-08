<?php

namespace App\Services\Analytics\Strategies;

use App\Services\Analytics\Contracts\AnalyticsStrategyInterface;
use App\Services\FeeCategoryService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DetailedFundheadStrategy implements AnalyticsStrategyInterface
{
    protected FeeCategoryService $feeCategoryService;
    protected string $type;

    public function __construct(FeeCategoryService $feeCategoryService, string $type = 'overall')
    {
        $this->feeCategoryService = $feeCategoryService;
        $this->type = $type;
    }

    public function execute(Builder $query, string $tableName, array $filters): array
    {
        $institutionId = $filters['institution_id'] ?? null;
        $regionId = $filters['region_id'] ?? null;
        $classId = $filters['school_class_id'] ?? null;
        $section = $filters['section'] ?? null;
        $fee_fund_category_id = $filters['fee_fund_category_id'] ?? null;
        $month = $filters['month'] ?? null;
        $year = $filters['year'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;
        $yearSession = $filters['year_session'] ?? null;

        $activeCategories = $this->feeCategoryService->getActiveFeeCategories();

        $feeHeadsQuery = \App\Models\FeeFundStructure::with('feeFundHead')->where('is_active', 1);

        $tempRegionId = $regionId;
        if ($institutionId && !$tempRegionId) {
            $institution = \App\Models\Institution::find($institutionId);
            if ($institution) {
                $tempRegionId = $institution->region_id;
            }
        }

        if ($tempRegionId) {
            $feeHeadsQuery->where('region_id', $tempRegionId);
        }
        if ($classId) {
            $feeHeadsQuery->where('school_class_id', $classId);
        }
        if ($fee_fund_category_id) {
            $feeHeadsQuery->where('fee_fund_category_id', $fee_fund_category_id);
        }

        $feeStructuresDb = $feeHeadsQuery->get();
        $overallFeeHeadsMap = [];

        foreach ($feeStructuresDb as $struct) {
            if ($struct->feeFundHead && is_array($struct->feeFundHead->fee_head)) {
                foreach ($struct->feeFundHead->fee_head as $headName) {
                    $overallFeeHeadsMap[$headName] = true;
                }
            }
        }

        $query->join('institutions', $tableName . '.institution_id', '=', 'institutions.id')
            ->join('regions', 'institutions.region_id', '=', 'regions.id')
            ->join('school_classes', $tableName . '.school_class_id', '=', 'school_classes.id')
            ->leftJoin('consumers', $tableName . '.consumer_id', '=', 'consumers.id')
            ->leftJoin('fee_fund_category', $tableName . '.fee_fund_category_id', '=', 'fee_fund_category.id');

        if ($institutionId) {
            $query->where($tableName . '.institution_id', $institutionId);
        }
        if ($regionId) {
            $query->where($tableName . '.region_id', $regionId);
        }
        if ($classId) {
            $query->where($tableName . '.school_class_id', $classId);
        }
        if ($section) {
            $query->where($tableName . '.section', $section);
        }
        if ($fee_fund_category_id) {
            $query->where($tableName . '.fee_fund_category_id', $fee_fund_category_id);
        }
        if ($month) {
            $query->whereMonth($tableName . '.due_date', $month);
        }
        if ($year) {
            $query->whereYear($tableName . '.due_date', $year);
        }
        if ($fromDate && $toDate) {
            try {
                $start = \Carbon\Carbon::parse($fromDate)->startOfMonth()->format('Y-m-d');
                $end = \Carbon\Carbon::parse($toDate)->endOfMonth()->format('Y-m-d');
                $query->whereBetween($tableName . '.due_date', [$start, $end]);
            } catch (\Exception $e) {
                // fallback if unparseable
            }
        }
        if ($yearSession) {
            $query->join('year_sessions', $tableName . '.year_session_id', '=', 'year_sessions.id')
                  ->where('year_sessions.name', $yearSession);
        }

        $query->select([
            $tableName . '.id',
            $tableName . '.institution_id',
            'institutions.name as institution_name',
            'regions.id as region_id',
            'regions.name as region_name',
            $tableName . '.school_class_id',
            'school_classes.name as class_name',
            $tableName . '.section',
            $tableName . '.status',
            $tableName . '.challan_snapshot',
            'consumers.sis_student_id',
            'fee_fund_category.category_title as category_name',
        ]);

        $query->orderBy($tableName . '.id');

        $data = [];

        $query->lazy()->each(function ($challan) use (&$data, $activeCategories, $overallFeeHeadsMap) {

            $groupId = null;
            $groupName = '';
            $subGroupId = null;
            $subGroupName = '';

            switch ($this->type) {
                case 'overall':
                    $groupId = $challan->region_id;
                    $groupName = $challan->region_name;
                    break;
                case 'region':
                    $groupId = $challan->region_id;
                    $groupName = $challan->region_name;
                    $subGroupId = $challan->institution_id;
                    $subGroupName = $challan->institution_name;
                    break;
                case 'institution':
                    $groupId = $challan->institution_id;
                    $groupName = $challan->institution_name;
                    $subGroupId = $challan->school_class_id . '-' . $challan->section;
                    $subGroupName = $challan->class_name . ' - ' . $challan->section;
                    break;
                case 'class_section':
                    $groupId = $challan->school_class_id . '-' . $challan->section;
                    $groupName = $challan->class_name . ' - ' . $challan->section;
                    break;
                default:
                    $groupId = $challan->region_id;
                    $groupName = $challan->region_name;
                    $subGroupId = $challan->institution_id;
                    $subGroupName = $challan->institution_name;
                    break;
            }

            if (!isset($data[$groupId])) {
                $categoriesBreakdown = [];
                foreach ($activeCategories as $cat) {
                    $categoriesBreakdown[$cat->category_title] = [
                        'total_students' => 0, 'total_paid' => 0, 'total_unpaid' => 0,
                        'total_paid_amount' => 0, 'total_amount' => 0,
                        'paid_student_ids' => [], 'unpaid_student_ids' => []
                    ];
                }

                $overallFundBreakdown = [];
                foreach (array_keys($overallFeeHeadsMap) as $headName) {
                    $overallFundBreakdown[$headName] = 0;
                }

                $data[$groupId] = [
                    'group_id' => $groupId,
                    'group_name' => $groupName,
                    'sub_groups' => [],
                    'overall_stats' => [
                        'total_sub_groups' => 0,
                        'total_students_paid' => 0,
                        'total_students_unpaid' => 0,
                        'total_paid_amount' => 0,
                        'paid_student_ids' => [],
                        'unpaid_student_ids' => [],
                        'category_wise_breakdown_count' => $categoriesBreakdown,
                        'fee_fund_head_wise_breakdown' => $overallFundBreakdown
                    ],
                    '_sub_group_keys' => []
                ];
            }

            if ($subGroupId && !isset($data[$groupId]['sub_groups'][$subGroupId])) {
                $categoriesBreakdown = [];
                foreach ($activeCategories as $cat) {
                    $categoriesBreakdown[$cat->category_title] = [
                        'total_students' => 0, 'total_paid' => 0, 'total_unpaid' => 0,
                        'total_paid_amount' => 0, 'total_amount' => 0,
                        'paid_student_ids' => [], 'unpaid_student_ids' => []
                    ];
                }

                $subFundBreakdown = [];
                foreach (array_keys($overallFeeHeadsMap) as $headName) {
                    $subFundBreakdown[$headName] = 0;
                }

                $data[$groupId]['sub_groups'][$subGroupId] = [
                    'sub_group_id' => $subGroupId,
                    'sub_group_name' => $subGroupName,
                    'total_students' => 0,
                    'total_paid' => 0,
                    'total_unpaid' => 0,
                    'total_paid_amount' => 0,
                    'total_amount' => 0,
                    'paid_student_ids' => [],
                    'unpaid_student_ids' => [],
                    'category_wise_breakdown_count' => $categoriesBreakdown,
                    'fee_fund_head_wise_breakdown' => $subFundBreakdown
                ];

                if (!in_array($subGroupId, $data[$groupId]['_sub_group_keys'])) {
                    $data[$groupId]['_sub_group_keys'][] = $subGroupId;
                    $data[$groupId]['overall_stats']['total_sub_groups'] = count($data[$groupId]['_sub_group_keys']);
                }
            }

            $snapshot = json_decode($challan->challan_snapshot, true);
            $feeStructures = $snapshot['fee_structures'] ?? [];

            $challanTotal = 0;
            $fundHeadAmounts = [];
            foreach ($feeStructures as $structure) {
                $headAmounts = $structure['fee_head_amounts'] ?? [];
                foreach ($headAmounts as $headName => $amount) {
                    $amt = (float) $amount;
                    $challanTotal += $amt;
                    if (!isset($fundHeadAmounts[$headName])) {
                        $fundHeadAmounts[$headName] = 0;
                    }
                    $fundHeadAmounts[$headName] += $amt;
                }
            }

            $isPaid = ($challan->status === 'P');
            $categoryName = $challan->category_name ?: 'Uncategorized';
            $studentId = (int) $challan->sis_student_id;

            // Overall Stats Update
            if ($isPaid) {
                $data[$groupId]['overall_stats']['total_students_paid'] += 1;
                $data[$groupId]['overall_stats']['total_paid_amount'] += $challanTotal;
                if ($studentId && !in_array($studentId, $data[$groupId]['overall_stats']['paid_student_ids'])) {
                    $data[$groupId]['overall_stats']['paid_student_ids'][] = $studentId;
                }
            } else {
                $data[$groupId]['overall_stats']['total_students_unpaid'] += 1;
                if ($studentId && !in_array($studentId, $data[$groupId]['overall_stats']['unpaid_student_ids'])) {
                    $data[$groupId]['overall_stats']['unpaid_student_ids'][] = $studentId;
                }
            }

            // Sub Group Stats Update
            if ($subGroupId) {
                $data[$groupId]['sub_groups'][$subGroupId]['total_students'] += 1;
                $data[$groupId]['sub_groups'][$subGroupId]['total_amount'] += $challanTotal;
                if ($isPaid) {
                    $data[$groupId]['sub_groups'][$subGroupId]['total_paid'] += 1;
                    $data[$groupId]['sub_groups'][$subGroupId]['total_paid_amount'] += $challanTotal;
                    if ($studentId && !in_array($studentId, $data[$groupId]['sub_groups'][$subGroupId]['paid_student_ids'])) {
                        $data[$groupId]['sub_groups'][$subGroupId]['paid_student_ids'][] = $studentId;
                    }
                } else {
                    $data[$groupId]['sub_groups'][$subGroupId]['total_unpaid'] += 1;
                    if ($studentId && !in_array($studentId, $data[$groupId]['sub_groups'][$subGroupId]['unpaid_student_ids'])) {
                        $data[$groupId]['sub_groups'][$subGroupId]['unpaid_student_ids'][] = $studentId;
                    }
                }
            }

            // Category breakdown (Overall)
            if (!isset($data[$groupId]['overall_stats']['category_wise_breakdown_count'][$categoryName])) {
                $data[$groupId]['overall_stats']['category_wise_breakdown_count'][$categoryName] = [
                    'total_students' => 0, 'total_paid' => 0, 'total_unpaid' => 0,
                    'total_paid_amount' => 0, 'total_amount' => 0,
                    'paid_student_ids' => [], 'unpaid_student_ids' => []
                ];
            }
            $overCatRef = &$data[$groupId]['overall_stats']['category_wise_breakdown_count'][$categoryName];
            $overCatRef['total_students'] += 1;
            $overCatRef['total_amount'] += $challanTotal;
            if ($isPaid) {
                $overCatRef['total_paid'] += 1;
                $overCatRef['total_paid_amount'] += $challanTotal;
                if ($studentId && !in_array($studentId, $overCatRef['paid_student_ids'])) {
                    $overCatRef['paid_student_ids'][] = $studentId;
                }
            } else {
                $overCatRef['total_unpaid'] += 1;
                if ($studentId && !in_array($studentId, $overCatRef['unpaid_student_ids'])) {
                    $overCatRef['unpaid_student_ids'][] = $studentId;
                }
            }

            // Category breakdown (Sub Group)
            if ($subGroupId) {
                if (!isset($data[$groupId]['sub_groups'][$subGroupId]['category_wise_breakdown_count'][$categoryName])) {
                    $data[$groupId]['sub_groups'][$subGroupId]['category_wise_breakdown_count'][$categoryName] = [
                        'total_students' => 0, 'total_paid' => 0, 'total_unpaid' => 0,
                        'total_paid_amount' => 0, 'total_amount' => 0,
                        'paid_student_ids' => [], 'unpaid_student_ids' => []
                    ];
                }
                $catRef = &$data[$groupId]['sub_groups'][$subGroupId]['category_wise_breakdown_count'][$categoryName];
                $catRef['total_students'] += 1;
                $catRef['total_amount'] += $challanTotal;
                if ($isPaid) {
                    $catRef['total_paid'] += 1;
                    $catRef['total_paid_amount'] += $challanTotal;
                    if ($studentId && !in_array($studentId, $catRef['paid_student_ids'])) {
                        $catRef['paid_student_ids'][] = $studentId;
                    }
                } else {
                    $catRef['total_unpaid'] += 1;
                    if ($studentId && !in_array($studentId, $catRef['unpaid_student_ids'])) {
                        $catRef['unpaid_student_ids'][] = $studentId;
                    }
                }
            }

            // Fund Head Breakdown
            foreach ($fundHeadAmounts as $headName => $amount) {
                if (!isset($data[$groupId]['overall_stats']['fee_fund_head_wise_breakdown'][$headName])) {
                    $data[$groupId]['overall_stats']['fee_fund_head_wise_breakdown'][$headName] = 0;
                }
                if ($isPaid) {
                    $data[$groupId]['overall_stats']['fee_fund_head_wise_breakdown'][$headName] += $amount;
                }

                if ($subGroupId) {
                    if (!isset($data[$groupId]['sub_groups'][$subGroupId]['fee_fund_head_wise_breakdown'][$headName])) {
                        $data[$groupId]['sub_groups'][$subGroupId]['fee_fund_head_wise_breakdown'][$headName] = 0;
                    }
                    if ($isPaid) {
                        $data[$groupId]['sub_groups'][$subGroupId]['fee_fund_head_wise_breakdown'][$headName] += $amount;
                    }
                }
            }
        });

        // Transform into the requested structure
        $resultData = [];
        foreach ($data as $groupId => $groupData) {
            unset($groupData['_sub_group_keys']);

            $subGroupList = [];
            foreach ($groupData['sub_groups'] as $subKey => $subData) {

                // Format category_wise_breakdown_count
                $catArray = [];
                foreach ($subData['category_wise_breakdown_count'] as $catName => $catStats) {
                    $catArray[$catName] = [$catStats];
                }
                $subData['category_wise_breakdown_count'] = count($catArray) > 0 ? [$catArray] : [];

                // Format fee_fund_head_wise_breakdown
                $fundArray = [];
                foreach ($subData['fee_fund_head_wise_breakdown'] as $fundName => $paidAmount) {
                    $fundArray[$fundName] = ['paid_amount' => $paidAmount];
                }
                $subData['fee_fund_head_wise_breakdown'] = count($fundArray) > 0 ? [$fundArray] : [];

                $subGroupList[] = $subData;
            }
            $groupData['sub_groups'] = $subGroupList;

            // Format overall_stats categories
            $overallCatArray = [];
            foreach ($groupData['overall_stats']['category_wise_breakdown_count'] as $catName => $catStats) {
                $overallCatArray[$catName] = [$catStats];
            }
            $groupData['overall_stats']['category_wise_breakdown_count'] = count($overallCatArray) > 0 ? [$overallCatArray] : [];

            // Format overall_stats fund heads
            $overallFundArray = [];
            foreach ($groupData['overall_stats']['fee_fund_head_wise_breakdown'] as $fundName => $paidAmount) {
                $overallFundArray[$fundName] = ['paid_amount' => $paidAmount];
            }
            $groupData['overall_stats']['fee_fund_head_wise_breakdown'] = count($overallFundArray) > 0 ? [$overallFundArray] : [];

            $resultData[] = $groupData;
        }

        return $resultData;
    }
}
