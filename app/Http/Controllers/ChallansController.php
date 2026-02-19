<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActiveChallan;
use App\Models\Consumer;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChallansController extends Controller
{
    /**
     * Verify API authentication credentials
     */
    private function verifyAuth(Request $request)
    {
        $username = $request->header('username');
        $password = $request->header('password');

        $validUsername = env('API_USERNAME');
        $validPassword = env('API_PASSWORD');

        if (empty($username) || empty($password) ||
            $username !== $validUsername || $password !== $validPassword) {
            return false;
        }

        return true;
    }

    public function billInquiry(Request $request)
    {
        try {
            // Authentication
            if (!$this->verifyAuth($request)) {
                return response()->json([
                    'response_Code' => '401',
                    'message' => 'Invalid authentication credentials'
                ], 401);
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'consumer_number' => 'required|string',
            ]);

            // Find the consumer by consumer number and check if active
            $consumer = Consumer::where('consumer_number', $validated['consumer_number'])
                                        ->where('is_active', true)
                                        ->first();

            // Check if consumer exists and is active
            if (!$consumer) {
                return response()->json([
                    'message' => 'Consumer not found or is inactive'
                ], 404);
            }

            // Search for latest unpaid challan using the consumer ID
            $challan = ActiveChallan::where('consumer_id', $consumer->id)
                                    ->unpaid()
                                    ->orderedForInquiry()
                                    ->first();

            // Check if challan exists
            if (!$challan) {
                // Check if a paid challan exists for this month
                $paidChallan = ActiveChallan::where('consumer_id', $consumer->id)
                    ->where('status', 'P')
                    ->whereMonth('due_date', now()->month)
                    ->whereYear('due_date', now()->year)
                    ->first();

                if ($paidChallan) {
                    return response()->json([
                        'message' => 'Challan is already paid for the month'
                    ], 200);
                }

                return response()->json([
                    'message' => 'No unpaid challan found for this consumer'
                ], 404);
            }

            // Return the challan details as JSON
            return response()->json($challan->toOneLinkInquiryResponse(), 200);
        } catch (ValidationException $e) {
            // Let Laravel's validation format be returned in JSON-friendly way
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            Log::error('billInquiry error', ['exception' => $e, 'request' => $request->all()]);
            return response()->json([
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }

    public function billPayment(Request $request)
    {
        Log::info('billPayment entered', $request->all());
        try {
            // Authentication
            if (!$this->verifyAuth($request)) {
                return response()->json([
                    'response_Code' => '401',
                    'message' => 'Invalid authentication credentials'
                ], 401);
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'consumer_number' => 'required|string',
                'tran_auth_id'      => 'required|string|max:6',
                'transaction_amount'          => 'required|numeric|min:0.01',
                'tran_date'  => 'required|string',
                'tran_time'  => 'required|string',
                'bank_mnemonic'  => 'required|string',
                'reserved'  => 'nullable|string',
            ]);

            // Find the consumer by consumer number and check if active
            $consumer = Consumer::where('consumer_number', $validated['consumer_number'])
                                        ->where('is_active', true)
                                        ->first();

            // Check if consumer exists and is active
            if (!$consumer) {
                return response()->json([
                    'message' => 'Consumer not found or is inactive'
                ], 404);
            }

            // Search for challan using the consumer ID
            // Ideally we should match amount or other details, but for now we pick the latest unpaid
            $challan = ActiveChallan::where('consumer_id', $consumer->id)
                                    ->unpaid()
                                    ->orderedForInquiry()
                                    ->first();

            // Check if challan exists
            if (!$challan) {
                // Check if a paid challan exists for this month
                $paidChallan = ActiveChallan::where('consumer_id', $consumer->id)
                    ->where('status', 'P')
                    ->whereMonth('due_date', now()->month)
                    ->whereYear('due_date', now()->year)
                    ->first();

                if ($paidChallan) {
                    return response()->json([
                        'message' => 'Challan is already paid for the month'
                    ], 200);
                }

                return response()->json([
                    'message' => 'No unpaid challan found for this consumer'
                ], 404);
            }

            // Update challan details
            $challan->status = "P"; // Mark as Paid
            $challan->bank_mnemonic = $validated['bank_mnemonic'];
            $challan->date_paid = date('Y-m-d H:i:s', strtotime($validated['tran_date'] . ' ' . $validated['tran_time']));
            $challan->tran_auth_id = $validated['tran_auth_id'];
            $challan->reserved = $validated['reserved'] ?? $challan->reserved;
            $challan->save();

            // Return a success response
            return response()->json($challan->toOneLinkPaymentResponse(), 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Throwable $e) {
            Log::error('billPayment error', ['exception' => $e, 'request' => $request->all()]);
            return response()->json([
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }
}
