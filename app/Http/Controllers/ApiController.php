<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
}
