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

        $validUsername = config('services.onelink.username');
        $validPassword = config('services.onelink.password');

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
                ], 200);
            }

            // Validate the incoming request data
            $validated = $request->validate([
                'consumer_number' => 'required|string',
            ]);

            $consumer = Consumer::where('consumer_number', $validated['consumer_number'])->first();

            // Case 1: Consumer not found
            if (!$consumer) {
                return response()->json([
                    'response_Code' => '01',
                    'message' => 'Consumer does not exist'
                ], 200);
            }

            // Case 2: Consumer exists but inactive
            if (!$consumer->is_active) {
                return response()->json([
                    'response_Code' => '02',
                    'message' => 'Consumer is inactive'
                ], 200);
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
                    ->whereMonth('due_date', now()->month)
                    ->whereYear('due_date', now()->year)
                    ->first();

                if ($paidChallan->status == 'P') {
                    return response()->json([
                        'response_Code' => '03',
                        'message' => 'Challan is already paid for the month of ' . $paidChallan->due_date->format('F Y')
                    ], 200);
                }

                if ($paidChallan->status == 'B') {
                    return response()->json([
                        'response_Code' => '04',
                        'message' => 'Challan is blocked for this Consumer'
                    ], 200);
                }

                return response()->json([
                    'response_Code' => '05',
                    'message' => 'No challan found for this Consumer'
                ], 200);
            }

            // Return the challan details as JSON
            return response()->json(array_merge([
                'response_Code' => '00',
                'message' => 'Successful Bill Inquiry',
            ], $challan->toOneLinkInquiryResponse()), 200);

        } catch (ValidationException $e) {
            // Let Laravel's validation format be returned in JSON-friendly way
            return response()->json([
                'response_Code' => '06',
                'message' => 'Invalid Data (' . json_encode($e->errors()) . ')'
            ], 200);
        } catch (Throwable $e) {
            Log::error('billInquiry error', ['exception' => $e, 'request' => $request->all()]);
            return response()->json([
                'response_Code' => '07',
                'message' => 'An unknow error occurred (' . $e->getMessage() . ')'
            ], 200);
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
                'tran_ref_number'      => 'required|string|max:24',
                'transaction_amount'          => 'required|numeric|min:0.01',
                'tran_date'  => 'required|string',
                'tran_time'  => 'required|string',
                'bank_mnemonic'  => 'required|string',
                'reserved'  => 'nullable|string',
            ]);

             $consumer = Consumer::where('consumer_number', $validated['consumer_number'])->first();

            // Case 1: Consumer not found
            if (!$consumer) {
                return response()->json([
                    'response_Code' => '01',
                    'message' => 'Consumer does not exist'
                ], 200);
            }

            // Case 2: Consumer exists but inactive
            if (!$consumer->is_active) {
                return response()->json([
                    'response_Code' => '02',
                    'message' => 'Consumer is inactive'
                ], 200);
            }

            // Search for challan using the consumer ID
            // Ideally we should match amount or other details, but for now we pick the latest unpaid
            $challan = ActiveChallan::where('consumer_id', $consumer->id)->orderedForInquiry()->first();

            // Case 3: No challan found
            if(!$challan){
                return response()->json([
                    'response_Code' => '05',
                    'message' => 'No challan found for this Consumer'
                ], 200);
            }

            // Case 4: Challan is already paid
            if($challan->status == 'P'){
                return response()->json([
                    'response_Code' => '03',
                    'message' => 'Challan is already paid for the month of ' . $challan->due_date->format('F Y')
                ], 200);
            }

            // Case 5: Challan is blocked
            if($challan->status == 'B'){
                return response()->json([
                    'response_Code' => '04',
                    'message' => 'Challan is blocked for this Consumer'
                ], 200);
            }

            // Case 6: Check the amount
            if($challan->amount_within_dueDate != $validated['transaction_amount']){
                return response()->json([
                    'response_Code' => '10',
                    'message' => 'Transaction Amount Mismatch!'
                ], 200);
            }

            // Case 7: Check the time and Due Date
            // if($challan->tran_date <= Carbon::Now()){
            //     return response()->json([
            //         'response_Code' => '10',
            //         'message' => 'Challan Expired!'
            //     ], 200);
            // }

            /* add a code here to look for history of challan to ideally look for the paid challan of current month
            when we move pass the payment dates.

            1. Make sure to add cases where if 25th date is passed then return user the message the user has already paid this month fee.
            2. also if in history and unpaid and date is passed send the message that payment date has passed and you can pay it next month as arrears for which no extra fee will be charged.*/

            // Case 7: Update challan details
            if($challan->tran_auth_id == $validated['tran_auth_id']){
                $challan->status = "P"; // Mark as Paid
                $challan->tran_ref_number = $validated['tran_ref_number'];
                $challan->bank_mnemonic = $validated['bank_mnemonic'];
                $challan->date_paid = date('Y-m-d H:i:s', strtotime($validated['tran_date'] . ' ' . $validated['tran_time']));
                $challan->tran_auth_id = $validated['tran_auth_id'];
                $challan->reserved = $validated['reserved'] ?? $challan->reserved;
                $challan->save();

                // Sync with external SMS/Fund system
                \App\Services\SmsSyncService::syncPaidChallan($challan);
            }else{
                return response()->json([
                    'response_Code' => '09',
                    'message' => 'Transaction Auth Id Mismatch!'
                ], 200);
            }

            // Return the challan details as JSON
            return response()->json(array_merge([
                'response_Code' => '00',
                'message' => 'Successful Bill Payment',
            ], $challan->toOneLinkPaymentResponse()), 200);

        } catch (ValidationException $e) {
            return response()->json([
                'response_Code' => '06',
                'message' => 'Invalid Data (' . json_encode($e->errors()) . ')'
            ], 200);
        } catch (Throwable $e) {
            Log::error('billPayment error', ['exception' => $e, 'request' => $request->all()]);
            return response()->json([
                'response_Code' => '07',
                'message' => 'An unexpected error occurred (' . $e->getMessage() . ')'
            ], 200);
        }
    }
}
