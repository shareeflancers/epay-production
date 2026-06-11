<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Consumer;
use App\Models\ActiveChallan;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestChallanController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/TestChallans');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'institution_ids' => 'nullable|array',
            'institution_ids.*' => 'integer',
            'region_ids' => 'nullable|array',
            'region_ids.*' => 'integer',
        ]);

        $institutionIds = $request->input('institution_ids', []);
        $regionIds = $request->input('region_ids', []);

        if (empty($institutionIds) && empty($regionIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide at least one Institution ID or Region ID.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $generatedCount = 0;

            // Collect all target institutions
            $institutions = collect();

            if (!empty($institutionIds)) {
                $institutions = $institutions->merge(Institution::whereIn('id', $institutionIds)->get());
            }

            if (!empty($regionIds)) {
                $institutions = $institutions->merge(Institution::whereIn('region_id', $regionIds)->get());
            }

            // Ensure unique institutions
            $institutions = $institutions->unique('id');

            foreach ($institutions as $institution) {
                $numChallans = 1;

                for ($i = 0; $i < $numChallans; $i++) {
                    // Create a fake consumer for this institution and its region
                    $consumer = Consumer::factory()->create([
                        'institution_id' => $institution->id,
                        'region_id' => $institution->region_id,
                        'is_active' => true,
                    ]);

                    // Generate exactly 1 active challan for this consumer
                    ActiveChallan::factory()->create([
                        'consumer_id' => $consumer->id,
                        'institution_id' => $institution->id,
                        'region_id' => $institution->region_id,
                        'status' => 'U', // Unpaid
                    ]);

                    $generatedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully generated {$generatedCount} test challans across " . $institutions->count() . " institutions."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generating test challans: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate test challans: ' . $e->getMessage()
            ], 500);
        }
    }
}
