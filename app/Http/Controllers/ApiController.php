<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\FeeFundCategory;
use App\Models\Consumer;
use App\Models\ActiveChallan;
use Illuminate\Validation\ValidationException;
use Throwable;

class ApiController extends Controller
{
    /**
     * Verify API authentication credentials
     */
    private function verifyAuth(Request $request)
    {
        $username = $request->header('username');
        $password = $request->header('password');

        $validUsername = config('services.internel.username');
        $validPassword = config('services.internel.password');

        if (empty($username) || empty($password) ||
            $username !== $validUsername || $password !== $validPassword) {
            return false;
        }

        return true;
    }

    public function fetchFeeCategories(Request $request)
    {
        try {
            // Authentication
            if (!$this->verifyAuth($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication credentials'
                ], 401);
            }

            $categories = FeeFundCategory::select([
                'id as category_id',
                'category_title',
                'details as category_description',
            ])
            ->where('is_active', 1)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (Throwable $e) {
            Log::error('API Error in fetchFeeCategories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching categories'
            ], 500);
        }
    }

    public function fetchSingleChallan(Request $request)
    {
        try {
            // Authentication
            if (!$this->verifyAuth($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication credentials'
                ], 401);
            }

            $id = $request->input('identification_number');
            if (!$id) {
                return response()->json(['success' => false, 'message' => 'identification_number is required'], 400);
            }

            $consumer = Consumer::where('identification_number', $id)->first();

            if (!$consumer) {
                return response()->json(['success' => false, 'message' => 'Consumer not found'], 404);
            }

            $challan = ActiveChallan::where('consumer_id', $consumer->id)
                ->orderBy('due_date', 'desc')
                ->first();

            if (!$challan) {
                return response()->json(['success' => false, 'message' => 'No active unpaid challan found'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'challan_no' => $challan->challan_no,
                    'print_url' => route('challan.view', ['challan_no' => $challan->challan_no]),

                ]
            ]);

        } catch (Throwable $e) {
            Log::error('API Error in fetchSingleChallan: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function fetchBulkChallans(Request $request)
    {
        try {
            // Authentication
            if (!$this->verifyAuth($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication credentials'
                ], 401);
            }

            $ids = $request->input('identification_numbers');
            if (!$ids || !is_array($ids)) {
                return response()->json(['success' => false, 'message' => 'identification_numbers must be an array'], 400);
            }

            // We just return a URL that the external system can open
            // The URL will contain the IDs as a query parameter
            $printUrl = route('challans.bulk-print', ['ids' => implode(',', $ids)]);

            return response()->json([
                'success' => true,
                'data' => [
                    'bulk_print_url' => $printUrl
                ]
            ]);

        } catch (Throwable $e) {
            Log::error('API Error in fetchBulkChallans: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function fetchChallanStatus(Request $request)
    {
        try {
            // Authentication
            if (!$this->verifyAuth($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication credentials'
                ], 401);
            }

            $ids = $request->input('identification_numbers');
            if (!$ids || !is_array($ids)) {
                return response()->json(['success' => false, 'message' => 'identification_numbers must be an array'], 400);
            }

            $consumers = Consumer::whereIn('identification_number', $ids)->get(['id', 'identification_number']);
            $consumerMap = $consumers->pluck('identification_number', 'id')->toArray();

            $challans = ActiveChallan::whereIn('consumer_id', array_keys($consumerMap))->get();

            $data = [];
            foreach ($challans as $challan) {
                $idNumber = $consumerMap[$challan->consumer_id] ?? null;
                if ($idNumber) {
                    if (!isset($data[$idNumber])) {
                        $data[$idNumber] = [];
                    }
                    $data[$idNumber][] = [
                        'status' => $challan->status,
                        'amount_within_dueDate' => $challan->amount_within_dueDate,
                        'billing_month' => $challan->reserved ? trim(explode('|', $challan->reserved)[1]) : null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Throwable $e) {
            Log::error('API Error in fetchChallanStatus: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    public function fetchAnalytics(Request $request)
    {
        try {
            // Authentication
            if (!$this->verifyAuth($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid authentication credentials'
                ], 401);
            }

            $type = $request->input('type', 'overall');
            $institutionId = $request->input('institution_id');
            $regionId = $request->input('region_id');
            $classId = $request->input('school_class_id');
            $section = $request->input('section');

            switch ($type) {
                case 'institution':
                    $results = DB::table('active_challans')
                        ->join('institutions', 'active_challans.institution_id', '=', 'institutions.id')
                        ->leftJoin('fee_fund_category', 'active_challans.fee_fund_category_id', '=', 'fee_fund_category.id')
                        ->select([
                            'institutions.id as group_id',
                            'institutions.name as group_name',
                            'fee_fund_category.category_title',
                            DB::raw('count(case when active_challans.status = "P" then 1 end) as paid_count'),
                            DB::raw('count(case when active_challans.status = "U" then 1 end) as unpaid_count'),
                        ]);

                    if ($institutionId) {
                        $results->where('active_challans.institution_id', $institutionId);
                    }

                    $data = $this->formatAnalyticsData($results->groupBy('institutions.id', 'institutions.name', 'fee_fund_category.category_title')->get());
                    break;

                case 'region':
                    $results = DB::table('active_challans')
                        ->join('regions', 'active_challans.region_id', '=', 'regions.id')
                        ->leftJoin('fee_fund_category', 'active_challans.fee_fund_category_id', '=', 'fee_fund_category.id')
                        ->select([
                            'regions.id as group_id',
                            'regions.name as group_name',
                            'fee_fund_category.category_title',
                            DB::raw('count(case when active_challans.status = "P" then 1 end) as paid_count'),
                            DB::raw('count(case when active_challans.status = "U" then 1 end) as unpaid_count'),
                        ]);

                    if ($regionId) {
                        $results->where('active_challans.region_id', $regionId);
                    }

                    $data = $this->formatAnalyticsData($results->groupBy('regions.id', 'regions.name', 'fee_fund_category.category_title')->get());
                    break;

                case 'class_section':
                    $results = DB::table('active_challans')
                        ->join('school_classes', 'active_challans.school_class_id', '=', 'school_classes.id')
                        ->join('consumers', 'active_challans.consumer_id', '=', 'consumers.id')
                        ->join('profile_details', 'consumers.id', '=', 'profile_details.consumer_id')
                        ->leftJoin('fee_fund_category', 'active_challans.fee_fund_category_id', '=', 'fee_fund_category.id')
                        ->select([
                            DB::raw('CONCAT(active_challans.institution_id, "-", active_challans.school_class_id, "-", profile_details.section) as group_id'),
                            DB::raw('CONCAT(school_classes.name, " - ", profile_details.section) as group_name'),
                            'fee_fund_category.category_title',
                            DB::raw('count(case when active_challans.status = "P" then 1 end) as paid_count'),
                            DB::raw('count(case when active_challans.status = "U" then 1 end) as unpaid_count'),
                        ]);

                    if ($institutionId) {
                        $results->where('active_challans.institution_id', $institutionId);
                    }
                    if ($classId) {
                        $results->where('active_challans.school_class_id', $classId);
                    }
                    if ($section) {
                        $results->where('profile_details.section', $section);
                    }

                    $data = $this->formatAnalyticsData($results->groupBy('group_id', 'group_name', 'fee_fund_category.category_title')->get());
                    break;

                default: // overall
                    $results = DB::table('active_challans')
                        ->leftJoin('fee_fund_category', 'active_challans.fee_fund_category_id', '=', 'fee_fund_category.id')
                        ->select([
                            DB::raw('"Overall" as group_id'),
                            DB::raw('"Overall" as group_name'),
                            'fee_fund_category.category_title',
                            DB::raw('count(case when active_challans.status = "P" then 1 end) as paid_count'),
                            DB::raw('count(case when active_challans.status = "U" then 1 end) as unpaid_count'),
                        ])
                        ->groupBy('fee_fund_category.category_title')
                        ->get();
                    $data = $this->formatAnalyticsData($results);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Throwable $e) {
            Log::error('API Error in fetchAnalytics: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Format flat results into a nested structure with category breakdown.
     */
    private function formatAnalyticsData($results)
    {
        $formatted = [];

        foreach ($results as $row) {
            $groupId = $row->group_id;

            if (!isset($formatted[$groupId])) {
                $formatted[$groupId] = [
                    'group_name' => $row->group_name,
                    'total_paid' => 0,
                    'total_unpaid' => 0,
                    'categories' => []
                ];
            }

            $formatted[$groupId]['total_paid'] += $row->paid_count;
            $formatted[$groupId]['total_unpaid'] += $row->unpaid_count;

            if ($row->category_title) {
                $formatted[$groupId]['categories'][] = [
                    'name' => $row->category_title,
                    'paid' => $row->paid_count,
                    'unpaid' => $row->unpaid_count
                ];
            }
        }

        return array_values($formatted);
    }
}
