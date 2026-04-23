<?php

namespace App\Http\Controllers;

use App\Models\ActiveChallan;
use App\Models\Consumer;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PublicChallanController extends Controller
{
    /**
     * Search for a consumer's active challans.
     * Returns JSON data for the frontend.
     */
    public function search(Request $request)
    {
        $consumerNumber = $request->input('consumer_number');

        if (!$consumerNumber) {
            return response()->json(['success' => false, 'message' => 'Consumer number is required.'], 400);
        }

        $consumer = Consumer::where('consumer_number', $consumerNumber)
            ->with(['activeChallans' => function ($q) {
                $q->where('status', 'U')->orderBy('due_date', 'desc');
            }, 'profileDetails' => function ($q) {
                $q->where('is_active', true);
            }])
            ->first();

        if (!$consumer) {
            return response()->json(['success' => false, 'message' => 'Consumer not found.'], 404);
        }

        $challan = $consumer->activeChallans->first();

        if (!$challan) {
            return response()->json(['success' => false, 'message' => 'No active/unpaid challan found for this consumer.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'challan_no' => $challan->challan_no,
                'name' => $consumer->profileDetails->first()->name ?? 'N/A',
                'amount' => $challan->amount_within_dueDate,
                'due_date' => $challan->due_date->format('Y-m-d'),
                'view_url' => route('challan.view', ['challan_no' => $challan->challan_no]),
            ]
        ]);
    }

    /**
     * Display the printable challan form.
     */
    public function show($challan_no)
    {
        $challan = ActiveChallan::where('challan_no', $challan_no)
            ->with(['consumer.profileDetails' => function ($q) {
                $q->where('is_active', true);
            }])
            ->firstOrFail();

        $profile = $challan->consumer->profileDetails->first();

        // Generate QR Code as SVG
        $qrCode = QrCode::size(100)->generate($challan->consumer->consumer_number);

        return view('challan.print', compact('challan', 'profile', 'qrCode'));
    }
}
