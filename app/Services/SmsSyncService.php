<?php

namespace App\Services;

use App\Models\ActiveChallan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsSyncService
{
    /**
     * Sync a paid challan's head-wise amounts to the external SMS/Fund system.
     */
    public static function syncPaidChallan($challan)
    {
        try {
            // Refresh model to ensure we have the latest status from DB
            $challan->refresh();

            // Idempotency check: Skip if already synced
            if ($challan->sms_sync) {
                Log::info("SmsSync: Challan #{$challan->challan_no} is already synced. Skipping.");
                return true;
            }

            // Ensure challan is paid
            if ($challan->status !== 'P') {
                Log::warning("SmsSync: Attempted to sync unpaid challan #{$challan->challan_no}");
                return false;
            }

            // Get the fee structure for head-wise breakdown
            $structure = $challan->feeFundStructure;
            if (!$structure) {
                Log::warning("SmsSync: No fee fund structure found for challan #{$challan->challan_no}");
                return false;
            }

            $heads = [];
            $amounts = $structure->fee_head_amounts;
            $verifyUrl = route('challan.verify', ['consumer_number' => $challan->challan_no]);

            // Map the breakdown from the structure
            if (is_array($amounts)) {
                foreach ($amounts as $name => $amount) {
                    if ($amount > 0) {
                        $heads[] = [
                            'name' => $name,
                            'amount' => (float) $amount,
                            'description' => "Paid via Challan #{$challan->challan_no} on " . $challan->date_paid->format('Y-m-d') . ". Verify: " . $verifyUrl,
                        ];
                    }
                }
            }

            // Include Arrears if present as a separate head
            if ($challan->amount_arrears > 0) {
                $heads[] = [
                    'name' => 'Arrears',
                    'amount' => (float) $challan->amount_arrears,
                    'description' => "Arrears for Challan #{$challan->challan_no}. Verify: " . $verifyUrl,
                ];
            }

            // Final check: if no heads found but there is a base amount, use a generic head
            if (empty($heads) && $challan->amount_base > 0) {
                $heads[] = [
                    'name' => 'General Fee',
                    'amount' => (float) $challan->amount_base,
                    'description' => "Base fee for Challan #{$challan->challan_no}. Verify: " . $verifyUrl,
                ];
            }

            $payload = [
                'institution_id' => $challan->institution_id,
                'heads' => $heads
            ];

            // Send request to external SMS system using config
            $apiUrl = config('services.sms.api_url');
            $apiKey = config('services.sms.api_key');

            $response = Http::withHeaders([
                'token' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->post($apiUrl . '/api/funds/transaction-in', $payload);

            if ($response->successful()) {
                $challan->update(['sms_sync' => 1]);
                Log::info("SmsSync: Successfully synced challan #{$challan->challan_no} to SMS system.");
                return true;
            } else {
                Log::error("SmsSync: API failure for challan #{$challan->challan_no}. Status: " . $response->status() . " Response: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
            Log::error("SmsSync: Exception while syncing challan #{$challan->challan_no}: " . $e->getMessage());
            return false;
        }
    }
}
