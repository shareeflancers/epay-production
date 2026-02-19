<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consumer;
use App\Models\ProfileDetail;
use App\Models\FeeFundCategory;
use App\Models\FeeFundStructure;
use App\Models\ActiveChallan;
use App\Models\Region;
use App\Models\Level;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class SettingsController extends Controller
{
    /**
     * Display the category update page.
     */
    public function index()
    {
        return Inertia::render('Admin/Settings/CategoryBind');
    }

    /**
     * Search for a consumer by identification number, consumer number, or name.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['data' => []]);
        }

        $consumers = Consumer::with(['profileDetails'])
            ->where('identification_number', 'LIKE', "%{$query}%")
            ->orWhere('consumer_number', 'LIKE', "%{$query}%")
            ->orWhereHas('profileDetails', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->limit(10) // Limit results for performance
            ->get();

        return response()->json(['data' => $consumers]);
    }

    /**
     * Get all fee fund categories for the multi-select.
     */
    public function getCategories()
    {
        $categories = FeeFundCategory::where('is_active', true)
            ->select('id', 'category_title as label')
            ->orderBy('display_order')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Update consumer and profile details.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'father_name' => 'nullable|string',
            'category_ids' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $profile = ProfileDetail::where('consumer_id', $id)->firstOrFail();
            $profile->update([
                'name' => $request->name,
                'father_or_guardian_name' => $request->father_name,
                'fee_fund_category_ids' => $request->category_ids,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Student details updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status of consumer and profile.
     */
    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $consumer = Consumer::findOrFail($id);
            $newStatus = !$consumer->is_active;

            $consumer->update(['is_active' => $newStatus]);

            ProfileDetail::where('consumer_id', $id)->update(['is_active' => $newStatus]);

            DB::commit();

            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete consumer and profile.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $consumer = Consumer::findOrFail($id);
            // Use custom trait method for soft delete
            if (method_exists($consumer, 'softDelete')) {
                $consumer->softDelete();
            } else {
                $consumer->delete();
            }

            // Soft delete ProfileDetail as well
            $profiles = ProfileDetail::where('consumer_id', $id)->get();
            foreach ($profiles as $profile) {
                if (method_exists($profile, 'softDelete')) {
                    $profile->softDelete();
                } else {
                    $profile->delete();
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Consumer deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Challan Generation
    |--------------------------------------------------------------------------
    */

    /**
     * Generate a unique challan number (20-char alphanumeric).
     * Verified unique against the active_challans table.
     */
    private function generateUniqueChallanNo(): string
    {
        do {
            $challanNo = implode('', array_map(fn() => random_int(0, 9), range(1, 20)));
        } while (ActiveChallan::where('challan_no', $challanNo)->exists());

        return $challanNo;
    }

    /**
     * Generate bulk challans for the current running month.
     * Creates one challan per active consumer based on their fee category & fee structure.
     */
    public function generateBulkChallans()
    {
        $now = Carbon::now();
        $dueDate = $now->copy()->day(20)->format('Y-m-d');
        $billingMonth = $now->format('F Y'); // e.g. "February 2026"

        // region_id is now directly on consumer; level_id comes via institution

        $generated = 0;
        $skipped = 0;
        $skipReasons = [
            'no_profile' => 0,
            'no_categories' => 0,
            'no_institution' => 0,
            'no_fee_structure' => 0,
        ];
        $skippedDetails = [];

        DB::beginTransaction();
        try {
            // Process consumers in chunks for memory efficiency
            Consumer::where('is_active', true)
                ->with(['profileDetails' => function ($q) {
                    $q->where('is_active', true);
                }, 'institution'])
                ->chunk(100, function ($consumers) use (
                    $dueDate, $billingMonth,
                    &$generated, &$skipped, &$skipReasons, &$skippedDetails
                ) {
                    foreach ($consumers as $consumer) {
                        $profile = $consumer->profileDetails->first();

                        // Skip if no active profile
                        if (!$profile) {
                            $skipped++;
                            $skipReasons['no_profile']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No active profile found";
                            continue;
                        }

                        // Skip if no fee categories assigned
                        if (empty($profile->fee_fund_category_ids)) {
                            $skipped++;
                            $skipReasons['no_categories']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No fee categories assigned";
                            continue;
                        }

                        $categoryIds = $profile->fee_fund_category_ids;

                        // region_id directly from consumer, level_id from institution
                        $regionId = $consumer->region_id ?? null;
                        $levelId  = $consumer->institution->level_id ?? null;

                        if (!$consumer->institution) {
                            $skipped++;
                            $skipReasons['no_institution']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No institution linked";
                            continue;
                        }

                        $query = FeeFundStructure::where('is_active', true)
                            ->whereIn('fee_fund_category_id', $categoryIds);

                        if ($regionId) {
                            $query->where('region_id', $regionId);
                        }
                        if ($levelId) {
                            $query->where('level_id', $levelId);
                        }

                        $feeStructures = $query->get();

                        if ($feeStructures->isEmpty()) {
                            $skipped++;
                            $skipReasons['no_fee_structure']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No fee structure found (region_id: {$regionId}, level_id: {$levelId}, categories: " . implode(',', $categoryIds) . ")";
                            continue;
                        }

                        // Calculate amount_base = sum of all matching fee structure totals
                        $amountBase = $feeStructures->sum('total');

                        // Determine fee_type based on consumer_type
                        $feeType = in_array($consumer->consumer_type, ['student', 'inductee']) ? 'fee' : 'voucher';

                        // Get category titles for the remarks
                        $categoryTitles = FeeFundCategory::whereIn('id', $categoryIds)
                            ->pluck('category_title')
                            ->implode(', ');

                        // Build descriptive remarks
                        $reserved = "Bulk Challan | {$billingMonth} | "
                            . "Consumer: {$consumer->consumer_number} | "
                            . "Type: {$consumer->consumer_type} | "
                            . "Name: {$profile->name} | "
                            . "Region: {$profile->region_name} | "
                            . "Level: {$profile->institution_level} | "
                            . "Categories: {$categoryTitles} | "
                            . "Fee Type: {$feeType} | "
                            . "Base Amount: {$amountBase} | "
                            . "Due Date: {$dueDate}";

                        // Generate unique challan number
                        $challanNo = $this->generateUniqueChallanNo();

                        ActiveChallan::create([
                            'consumer_id'          => $consumer->id,
                            'challan_no'           => $challanNo,
                            'status'               => 'U',
                            'tran_auth_id'         => null,
                            'due_date'             => $dueDate,
                            'amount_base'          => $amountBase,
                            'amount_arrears'       => 0.00,
                            'amount_within_dueDate' => $amountBase,
                            'amount_after_dueDate'  => $amountBase,
                            'fee_type'             => $feeType,
                            'reserved'             => $reserved,
                            'is_active'            => true,
                        ]);

                        $generated++;
                    }
                });

            DB::commit();

            Log::info("Bulk Challan Generation completed: {$generated} generated, {$skipped} skipped for {$billingMonth}.");

            // Build contextual message
            if ($generated === 0 && $skipped === 0) {
                $message = "No active consumers found for {$billingMonth}.";
            } elseif ($generated === 0) {
                $message = "No challans generated — {$skipped} consumers skipped (missing fee structure or profile) for {$billingMonth}.";
            } else {
                $message = "{$generated} challans generated, {$skipped} skipped for {$billingMonth}.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'generated' => $generated,
                'skipped'   => $skipped,
                'skip_reasons' => $skipReasons,
                'skipped_details' => array_slice($skippedDetails, 0, 50), // limit to first 50
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Challan Generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk challan generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Challan Update (Search & Edit)
    |--------------------------------------------------------------------------
    */

    /**
     * Display the challan update page.
     */
    public function challanIndex()
    {
        return Inertia::render('Admin/Settings/ChallanUpdate');
    }

    /**
     * Search challans by consumer_id (consumer_number) or challan_no.
     */
    public function challanSearch(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json(['data' => []]);
        }

        $challans = ActiveChallan::with(['consumer.profileDetails'])
            ->where('challan_no', 'LIKE', "%{$query}%")
            ->orWhereHas('consumer', function ($q) use ($query) {
                $q->where('consumer_number', 'LIKE', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json(['data' => $challans]);
    }

    /**
     * Update a single challan's editable fields.
     */
    public function challanUpdateSingle(Request $request, $id)
    {
        $request->validate([
            'amount_base'           => 'nullable|numeric|min:0',
            'amount_within_dueDate' => 'nullable|numeric|min:0',
            'amount_after_dueDate'  => 'nullable|numeric|min:0',
            'amount_arrears'        => 'nullable|numeric|min:0',
            'due_date'              => 'nullable|date',
            'fee_type'              => 'nullable|in:fee,voucher',
            'status'                => 'nullable|in:U,P,B',
            'reserved'              => 'nullable|string|max:400',
        ]);

        DB::beginTransaction();
        try {
            $challan = ActiveChallan::findOrFail($id);
            $challan->update($request->only([
                'amount_base',
                'amount_within_dueDate',
                'amount_after_dueDate',
                'amount_arrears',
                'due_date',
                'fee_type',
                'status',
                'reserved',
            ]));

            DB::commit();
            return redirect()->back()->with('success', 'Challan updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update challan: ' . $e->getMessage());
        }
    }
}
