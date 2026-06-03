<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Analytics\AnalyticsService;
use App\Services\ChallanService;
use App\Services\FeeCategoryService;
use Throwable;

class ApiController extends Controller
{
    protected AnalyticsService $analyticsService;
    protected ChallanService $challanService;
    protected FeeCategoryService $feeCategoryService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        AnalyticsService $analyticsService,
        ChallanService $challanService,
        FeeCategoryService $feeCategoryService
    ) {
        $this->analyticsService = $analyticsService;
        $this->challanService = $challanService;
        $this->feeCategoryService = $feeCategoryService;
    }

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

            $categories = $this->feeCategoryService->getActiveFeeCategories();

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

            try {
                $challan = $this->challanService->getSingleChallan($id);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json(['success' => false, 'message' => 'Consumer not found'], 404);
            }

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

            $printUrl = $this->challanService->getBulkChallansPrintUrl($ids);

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

            $data = $this->challanService->getChallanStatus($ids);

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
            $fee_fund_category_id = $request->input('fee_fund_category_id');
            $month = $request->input('month');
            $year = $request->input('year');
            $yearSession = $request->input('year_session') ?? $request->input('session_year');
            $detailed = $request->input('detailed');

            $filters = [
                'fee_fund_category_id' => $fee_fund_category_id,
                'institution_id' => $institutionId,
                'region_id' => $regionId,
                'school_class_id' => $classId,
                'section' => $section,
                'month' => $month,
                'year' => $year,
                'year_session' => $yearSession,
                'detailed' => (int) $detailed === 1,
            ];

            $responsePayload = $this->analyticsService->getAnalytics($type, $filters);
            $responsePayload['success'] = true;

            return response()->json($responsePayload);

        } catch (Throwable $e) {
            Log::error('API Error in fetchAnalytics: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}

