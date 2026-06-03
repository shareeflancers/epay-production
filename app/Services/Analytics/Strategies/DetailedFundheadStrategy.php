<?php

namespace App\Services\Analytics\Strategies;

use App\Services\Analytics\Contracts\AnalyticsStrategyInterface;
use App\Services\FeeCategoryService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DetailedFundheadStrategy implements AnalyticsStrategyInterface
{
    protected FeeCategoryService $feeCategoryService;

    public function __construct(FeeCategoryService $feeCategoryService)
    {
        $this->feeCategoryService = $feeCategoryService;
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
        $yearSession = $filters['year_session'] ?? null;

        // Fetch all active categories to ensure they are listed in the breakdown
        $activeCategories = $this->feeCategoryService->getActiveFeeCategories();

        // Fetch mapped fee heads for initialization
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
        $classFeeHeadsMap = [];
        $overallFeeHeadsMap = [];

        foreach ($feeStructuresDb as $struct) {
            $cId = (int) $struct->school_class_id;
            if (!isset($classFeeHeadsMap[$cId])) {
                $classFeeHeadsMap[$cId] = [];
            }
            if ($struct->feeFundHead && is_array($struct->feeFundHead->fee_head)) {
                foreach ($struct->feeFundHead->fee_head as $headName) {
                    $classFeeHeadsMap[$cId][$headName] = true;
                    $overallFeeHeadsMap[$headName] = true;
                }
            }
        }

        $query->join('institutions', $tableName . '.institution_id', '=', 'institutions.id')
            ->join('school_classes', $tableName . '.school_class_id', '=', 'school_classes.id')
            ->leftJoin('consumers', $tableName . '.consumer_id', '=', 'consumers.id')
            ->leftJoin('fee_fund_category', $tableName . '.fee_fund_category_id', '=', 'fee_fund_category.id');

        // Apply filters
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
        if ($yearSession) {
            $query->join('year_sessions', $tableName . '.year_session_id', '=', 'year_sessions.id')
                  ->where('year_sessions.name', $yearSession);
        }

        $query->select([
            $tableName . '.id',
            $tableName . '.institution_id',
            'institutions.name as institution_name',
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

        $query->lazy()->each(function ($challan) use (&$data, $activeCategories, $classFeeHeadsMap, $overallFeeHeadsMap) {
            $instId = (int) $challan->institution_id;
            $classId = (int) $challan->school_class_id;
            $sectionStr = $challan->section ?? '';
            $classKey = $classId . '-' . $sectionStr;

            if (!isset($data[$instId])) {
                $categoriesBreakdown = [];
                foreach ($activeCategories as $cat) {
                    $categoriesBreakdown[$cat->category_title] = [
                        'total_students' => 0,
                        'total_paid' => 0,
                        'total_unpaid' => 0,
                        'total_paid_amount' => 0,
                        'total_amount' => 0,
                        'paid_student_ids' => [],
                        'unpaid_student_ids' => []
                    ];
                }

                $overallFeeFundHeadWiseBreakdown = [];
                foreach (array_keys($overallFeeHeadsMap) as $headName) {
                    $overallFeeFundHeadWiseBreakdown[$headName] = 0;
                }

                $data[$instId] = [
                    'institution_id' => $instId,
                    'institution_name' => $challan->institution_name,
                    'classes' => [],
                    'overall_stats' => [
                        'total_classes' => 0,
                        'total_students_paid' => 0,
                        'total_students_unpaid' => 0,
                        'total_paid_amount' => 0,
                        'paid_student_ids' => [],
                        'unpaid_student_ids' => [],
                        'category_wise_breakdown_count' => $categoriesBreakdown,
                        'fee_fund_head_wise_breakdown' => $overallFeeFundHeadWiseBreakdown
                    ],
                    '_class_keys' => []
                ];
            }

            if (!isset($data[$instId]['classes'][$classKey])) {
                $categoriesBreakdown = [];
                foreach ($activeCategories as $cat) {
                    $categoriesBreakdown[$cat->category_title] = [
                        'total_students' => 0,
                        'total_paid' => 0,
                        'total_unpaid' => 0,
                        'total_paid_amount' => 0,
                        'total_amount' => 0,
                        'paid_student_ids' => [],
                        'unpaid_student_ids' => []
                    ];
                }

                $classFeeFundHeadWiseBreakdown = [];
                if (isset($classFeeHeadsMap[$classId])) {
                    foreach (array_keys($classFeeHeadsMap[$classId]) as $headName) {
                        $classFeeFundHeadWiseBreakdown[$headName] = 0;
                    }
                }

                $data[$instId]['classes'][$classKey] = [
                    'class_id' => $classId,
                    'class_name' => $challan->class_name,
                    'section' => $sectionStr,
                    'total_students' => 0,
                    'total_paid' => 0,
                    'total_unpaid' => 0,
                    'total_paid_amount' => 0,
                    'total_amount' => 0,
                    'paid_student_ids' => [],
                    'unpaid_student_ids' => [],
                    'category_wise_breakdown_count' => $categoriesBreakdown,
                    'fee_fund_head_wise_breakdown' => $classFeeFundHeadWiseBreakdown
                ];

                if (!in_array($classKey, $data[$instId]['_class_keys'])) {
                    $data[$instId]['_class_keys'][] = $classKey;
                    $data[$instId]['overall_stats']['total_classes'] = count($data[$instId]['_class_keys']);
                }
            }

            // Parse snapshot amounts
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

            // Class level updates
            $data[$instId]['classes'][$classKey]['total_students'] += 1;
            $data[$instId]['classes'][$classKey]['total_amount'] += $challanTotal;

            if ($isPaid) {
                $data[$instId]['classes'][$classKey]['total_paid'] += 1;
                $data[$instId]['classes'][$classKey]['total_paid_amount'] += $challanTotal;
                if ($studentId && !in_array($studentId, $data[$instId]['classes'][$classKey]['paid_student_ids'])) {
                    $data[$instId]['classes'][$classKey]['paid_student_ids'][] = $studentId;
                }

                $data[$instId]['overall_stats']['total_students_paid'] += 1;
                $data[$instId]['overall_stats']['total_paid_amount'] += $challanTotal;
                if ($studentId && !in_array($studentId, $data[$instId]['overall_stats']['paid_student_ids'])) {
                    $data[$instId]['overall_stats']['paid_student_ids'][] = $studentId;
                }
            } else {
                $data[$instId]['classes'][$classKey]['total_unpaid'] += 1;
                if ($studentId && !in_array($studentId, $data[$instId]['classes'][$classKey]['unpaid_student_ids'])) {
                    $data[$instId]['classes'][$classKey]['unpaid_student_ids'][] = $studentId;
                }

                $data[$instId]['overall_stats']['total_students_unpaid'] += 1;
                if ($studentId && !in_array($studentId, $data[$instId]['overall_stats']['unpaid_student_ids'])) {
                    $data[$instId]['overall_stats']['unpaid_student_ids'][] = $studentId;
                }
            }

            // Category breakdown update (Class Level)
            if (!isset($data[$instId]['classes'][$classKey]['category_wise_breakdown_count'][$categoryName])) {
                $data[$instId]['classes'][$classKey]['category_wise_breakdown_count'][$categoryName] = [
                    'total_students' => 0,
                    'total_paid' => 0,
                    'total_unpaid' => 0,
                    'total_paid_amount' => 0,
                    'total_amount' => 0,
                    'paid_student_ids' => [],
                    'unpaid_student_ids' => []
                ];
            }
            $catRef = &$data[$instId]['classes'][$classKey]['category_wise_breakdown_count'][$categoryName];
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

            // Category breakdown update (Overall Level)
            if (!isset($data[$instId]['overall_stats']['category_wise_breakdown_count'][$categoryName])) {
                $data[$instId]['overall_stats']['category_wise_breakdown_count'][$categoryName] = [
                    'total_students' => 0,
                    'total_paid' => 0,
                    'total_unpaid' => 0,
                    'total_paid_amount' => 0,
                    'total_amount' => 0,
                    'paid_student_ids' => [],
                    'unpaid_student_ids' => []
                ];
            }
            $overCatRef = &$data[$instId]['overall_stats']['category_wise_breakdown_count'][$categoryName];
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

            // Fund Head Breakdown Update
            foreach ($fundHeadAmounts as $headName => $amount) {
                // Initialize Class level
                if (!isset($data[$instId]['classes'][$classKey]['fee_fund_head_wise_breakdown'][$headName])) {
                    $data[$instId]['classes'][$classKey]['fee_fund_head_wise_breakdown'][$headName] = 0;
                }

                // Initialize Overall level
                if (!isset($data[$instId]['overall_stats']['fee_fund_head_wise_breakdown'][$headName])) {
                    $data[$instId]['overall_stats']['fee_fund_head_wise_breakdown'][$headName] = 0;
                }

                if ($isPaid) {
                    $data[$instId]['classes'][$classKey]['fee_fund_head_wise_breakdown'][$headName] += $amount;
                    $data[$instId]['overall_stats']['fee_fund_head_wise_breakdown'][$headName] += $amount;
                }
            }
        });

        // Transform into the requested structure
        $resultData = [];
        foreach ($data as $instId => $instData) {
            unset($instData['_class_keys']);

            $classesList = [];
            foreach ($instData['classes'] as $classKey => $classData) {

                // Format category_wise_breakdown_count
                $catArray = [];
                foreach ($classData['category_wise_breakdown_count'] as $catName => $catStats) {
                    $catArray[$catName] = [$catStats];
                }
                $classData['category_wise_breakdown_count'] = count($catArray) > 0 ? [$catArray] : [];

                // Format fee_fund_head_wise_breakdown
                $fundArray = [];
                foreach ($classData['fee_fund_head_wise_breakdown'] as $fundName => $paidAmount) {
                    $fundArray[$fundName] = ['paid_amount' => $paidAmount];
                }
                $classData['fee_fund_head_wise_breakdown'] = count($fundArray) > 0 ? [$fundArray] : [];

                $classesList[] = $classData;
            }
            $instData['classes'] = $classesList;

            // Format overall_stats categories
            $overallCatArray = [];
            foreach ($instData['overall_stats']['category_wise_breakdown_count'] as $catName => $catStats) {
                $overallCatArray[$catName] = [$catStats];
            }
            $instData['overall_stats']['category_wise_breakdown_count'] = count($overallCatArray) > 0 ? [$overallCatArray] : [];

            // Format overall_stats fund heads
            $overallFundArray = [];
            foreach ($instData['overall_stats']['fee_fund_head_wise_breakdown'] as $fundName => $paidAmount) {
                $overallFundArray[$fundName] = ['paid_amount' => $paidAmount];
            }
            $instData['overall_stats']['fee_fund_head_wise_breakdown'] = count($overallFundArray) > 0 ? [$overallFundArray] : [];

            $resultData[] = $instData;
        }

        return $resultData;
    }
}
