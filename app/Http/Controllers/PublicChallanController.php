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
            ->orWhere('identification_number', $consumerNumber)
            ->with(['profileDetails' => function ($q) {
                $q->where('is_active', true);
            }])
            ->first();

        if (!$consumer) {
            return response()->json(['success' => false, 'message' => 'Consumer not found.'], 404);
        }

        // 1. Try to find an unpaid challan first
        $challan = ActiveChallan::where('consumer_id', $consumer->id)
            ->where('status', 'U')
            ->orderBy('due_date', 'desc')
            ->first();

        // 2. If no unpaid challan, look for the most recent paid one
        $isPaid = false;
        if (!$challan) {
            $challan = ActiveChallan::where('consumer_id', $consumer->id)
                ->where('status', 'P')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($challan) {
                $isPaid = true;
            }
        }

        if (!$challan) {
            return response()->json(['success' => false, 'message' => 'No challan history found for this consumer.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'challan_no' => $challan->challan_no,
                'name' => $consumer->profileDetails->first()->name ?? 'N/A',
                'class' => $challan->schoolClass->name ?? $consumer->profileDetails->first()->class ?? 'N/A',
                'section' => $consumer->profileDetails->first()->section ?? '-',
                'amount' => $challan->amount_within_dueDate,
                'due_date' => $challan->due_date->format('Y-m-d'),
                'status' => $challan->status,
                'is_paid' => $isPaid,
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
            ->with([
                'consumer.profileDetails' => function ($q) {
                    $q->where('is_active', true);
                },
                'institution',
                'region',
                'schoolClass',
                'level',
                'yearSession'
            ])
            ->firstOrFail();

        $profile = $challan->consumer->profileDetails->first();

        // Generate QR Code as SVG pointing to verification URL
        $qrCode = QrCode::size(100)->generate(route('challan.verify', ['consumer_number' => $challan->consumer->consumer_number]));

        return view('challan.print', compact('challan', 'profile', 'qrCode'));
    }

    /**
     * Verify a challan's status.
     */
    public function verify($consumerNumber)
    {
        $consumer = Consumer::where(function($q) use ($consumerNumber) {
                $q->where('consumer_number', $consumerNumber)
                  ->orWhere('identification_number', $consumerNumber);
            })
            ->with(['profileDetails' => function ($q) {
                $q->where('is_active', true);
            }])
            ->firstOrFail();

        // Find the most recent challan
        $challan = ActiveChallan::where('consumer_id', $consumer->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$challan) {
            abort(404, 'No challan found for this consumer.');
        }

        $profile = $consumer->profileDetails->first();

        return view('challan.verify', compact('challan', 'profile'));
    }
}
