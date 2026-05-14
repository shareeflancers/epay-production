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
     * Search for a consumer by identification number, or name.
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
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('institution_name', 'LIKE', "%{$query}%");
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
    /**
     * Move all active challans to the history table.
     */
    public function moveToHistory()
    {
        DB::beginTransaction();
        try {
            $activeChallans = ActiveChallan::all();
            $count = $activeChallans->count();

            if ($count === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active challans to move.',
                    'moved_count' => 0
                ]);
            }

            // Take snapshot before moving
            \App\Services\ProcedureService::snapshotArchive();

            foreach ($activeChallans as $challan) {
                // Replicate attributes to history model (excluding ID)
                $history = new \App\Models\ChallanHistory();
                $history->fill($challan->getAttributes());
                unset($history->id);
                $history->save();

                // Delete the active record
                $challan->delete();
            }

            DB::commit();
            Log::info("Archived {$count} challans to history.");

            return response()->json([
                'success' => true,
                'message' => "Successfully archived {$count} challans to history.",
                'moved_count' => $count
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Move to history failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to move challans to history: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateBulkChallans()
    {
        $now = Carbon::now();
        $dueDate = $now->copy()->day(20)->format('Y-m-d');
        $billingMonth = $now->format('F Y'); // e.g. "April 2026"

        // Take snapshot before generation
        \App\Services\ProcedureService::snapshotGenerate();

        $generated = 0;
        $updated   = 0;
        $skipped   = 0;
        $skipReasons = [
            'no_profile'       => 0,
            'no_categories'    => 0,
            'no_institution'   => 0,
            'no_year_session'  => 0,
            'no_fee_structure' => 0,
        ];
        $skippedDetails = [];

        DB::beginTransaction();
        try {
            Consumer::where('is_active', true)
                ->with([
                    'profileDetails' => fn ($q) => $q->where('is_active', true),
                    'institution',
                    'region',
                ])
                ->chunk(100, function ($consumers) use (
                    $dueDate, $billingMonth, $now,
                    &$generated, &$updated, &$skipped, &$skipReasons, &$skippedDetails
                ) {
                    $existingChallans = ActiveChallan::whereIn('consumer_id', $consumers->pluck('id'))
                        ->get()
                        ->keyBy('consumer_id');

                    foreach ($consumers as $consumer) {
                        $profile = $consumer->profileDetails->first();

                        // ── Guard: active profile ─────────────────────────────
                        if (!$profile) {
                            $skipped++;
                            $skipReasons['no_profile']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No active profile";
                            continue;
                        }

                        // ── Guard: fee categories ─────────────────────────────
                        if (empty($profile->fee_fund_category_ids)) {
                            $skipped++;
                            $skipReasons['no_categories']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No fee categories assigned";
                            continue;
                        }

                        // ── Guard: institution ────────────────────────────────
                        if (!$consumer->institution) {
                            $skipped++;
                            $skipReasons['no_institution']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No institution linked";
                            continue;
                        }

                        $categoryIds   = $profile->fee_fund_category_ids;
                        $regionId      = $consumer->region_id ?? null;
                        $schoolClassId = $profile->school_class_id ?? null;
                        $institutionId = $consumer->institution_id;

                        // ── Resolve active YearSession (institution + class) ──
                        $yearSession = \App\Models\YearSession::where('institution_id', $institutionId)
                            ->where('school_class_id', $schoolClassId)
                            ->where('is_active', true)
                            ->first();

                        if (!$yearSession) {
                            $skipped++;
                            $skipReasons['no_year_session']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No active year session for institution #{$institutionId}, class #{$schoolClassId}";
                            continue;
                        }

                        // ── Fee structures ────────────────────────────────────
                        $query = FeeFundStructure::where('is_active', true)
                            ->whereIn('fee_fund_category_id', $categoryIds);

                        if ($regionId)      { $query->where('region_id',       $regionId);      }
                        if ($schoolClassId) { $query->where('school_class_id', $schoolClassId); }

                        $feeStructures = $query->with(['feeFundCategory', 'feeFundHead'])->get();

                        if ($feeStructures->isEmpty()) {
                            $skipped++;
                            $skipReasons['no_fee_structure']++;
                            $skippedDetails[] = "Consumer #{$consumer->id} ({$consumer->consumer_number}): No fee structure (region: {$regionId}, class: {$schoolClassId}, cats: " . implode(',', $categoryIds) . ")";
                            continue;
                        }

                        // ── Arrears Calculation ──────────────────────────────
                        // Only look for the ABSOLUTE LATEST challan in History
                        // If the most recent challan was paid, arrears are zero.
                        // If the most recent challan was unpaid, its total becomes the new arrears.
                        $absoluteLatestHistory = \App\Models\ChallanHistory::where('consumer_id', $consumer->id)
                            ->orderBy('due_date', 'desc')
                            ->first();

                        $amountArrears = 0;
                        $latestUnpaidHistory = null;

                        if ($absoluteLatestHistory && $absoluteLatestHistory->status === 'U') {
                            $latestUnpaidHistory = $absoluteLatestHistory;
                            $amountArrears = $latestUnpaidHistory->amount_within_dueDate;
                        }

                        // Capture breakdown of arrears for the snapshot
                        $arrearsDetails = [];
                        if ($latestUnpaidHistory) {
                            $snap = json_decode($latestUnpaidHistory->challan_snapshot, true);
                            $arrearsDetails[] = [
                                'challan_no' => $latestUnpaidHistory->challan_no,
                                'amount'     => $latestUnpaidHistory->amount_within_dueDate,
                                'breakdown'  => $snap['fee_structures'] ?? [],
                                'source'     => 'history'
                            ];
                        }

                        // ── Amounts ───────────────────────────────────────────
                        $amountBase = $feeStructures->sum('total');
                        $totalAmount = $amountBase + $amountArrears;
                        $feeType    = in_array($consumer->consumer_type, ['student', 'inductee']) ? 'fee' : 'voucher';

                        // ── Category titles for remarks ───────────────────────
                        $categoryTitles = FeeFundCategory::whereIn('id', $categoryIds)
                            ->pluck('category_title')
                            ->implode(', ');

                        // ── Descriptive reserved string ───────────────────────
                        $reserved = "Bulk Challan | {$billingMonth} | "
                            . "Consumer: {$consumer->consumer_number} | "
                            . "Type: {$consumer->consumer_type} | "
                            . "Name: {$profile->name} | "
                            . "Class: {$profile->class} | "
                            . "Session: {$yearSession->name} | "
                            . "Categories: {$categoryTitles} | "
                            . "Fee Type: {$feeType} | "
                            . "Base Amount: {$amountBase} | "
                            . "Arrears: {$amountArrears} | "
                            . "Due Date: {$dueDate}";

                        $firstStructure = $feeStructures->first();

                        // ── Comprehensive snapshot (point-in-time record) ─────
                        $snapshot = [
                            'generated_at'  => $now->toIso8601String(),
                            'billing_month' => $billingMonth,
                            'arrears_calculation' => [
                                'amount_arrears' => $amountArrears,
                                'details'        => $arrearsDetails,
                            ],
                            'consumer' => [
                                'id'              => $consumer->id,
                                'consumer_number' => $consumer->consumer_number,
                                'consumer_type'   => $consumer->consumer_type,
                                'identification_number' => $consumer->identification_number,
                                'region_id'       => $consumer->region_id,
                                'institution_id'  => $consumer->institution_id,
                            ],
                            'profile' => [
                                'id'                      => $profile->id,
                                'name'                    => $profile->name,
                                'father_or_guardian_name' => $profile->father_or_guardian_name,
                                'class'                   => $profile->class,
                                'section'                 => $profile->section,
                                'school_class_id'         => $profile->school_class_id,
                                'level_id'                => $profile->level_id,
                                'region_name'             => $profile->region_name ?? null,
                                'fee_fund_category_ids'   => $profile->fee_fund_category_ids,
                            ],
                            'institution' => $consumer->institution ? [
                                'id'     => $consumer->institution->id,
                                'name'   => $consumer->institution->name,
                                'region_id' => $consumer->institution->region_id,
                                'level_id'  => $consumer->institution->level_id,
                            ] : null,
                            'region' => $consumer->region ? [
                                'id'   => $consumer->region->id,
                                'name' => $consumer->region->name ?? $consumer->region->region ?? null,
                            ] : null,
                            'year_session' => [
                                'id'         => $yearSession->id,
                                'name'       => $yearSession->name,
                                'start_date' => $yearSession->start_date?->toDateString(),
                                'end_date'   => $yearSession->end_date?->toDateString(),
                            ],
                            'fee_structures' => $feeStructures->map(fn ($s) => [
                                'id'                  => $s->id,
                                'fee_fund_category_id'=> $s->fee_fund_category_id,
                                'fee_fund_category'   => $s->feeFundCategory?->category_title,
                                'fee_fund_head_id'    => $s->fee_fund_head_id,
                                'fee_fund_head'       => $s->feeFundHead?->head_identifier ?? null,
                                'fee_head_amounts'    => $s->fee_head_amounts ?? [],
                                'total'               => $s->total,
                                'region_id'           => $s->region_id,
                                'school_class_id'     => $s->school_class_id,
                            ])->values()->toArray(),
                            'fee_categories' => FeeFundCategory::whereIn('id', $categoryIds)
                                ->get(['id', 'category_title'])
                                ->toArray(),
                        ];

                        // ── Challan payload ───────────────────────────────────
                        $challanData = [
                            'due_date'              => $dueDate,
                            'amount_base'           => $amountBase,
                            'amount_arrears'        => $amountArrears,
                            'amount_within_dueDate' => $totalAmount,
                            'amount_after_dueDate'  => $totalAmount,
                            'fee_type'              => $feeType,
                            'reserved'              => $reserved,
                            'institution_id'        => $institutionId,
                            'region_id'             => $regionId,
                            'fee_fund_category_id'  => $firstStructure->fee_fund_category_id ?? null,
                            'fee_fund_head_id'      => $firstStructure->fee_fund_head_id ?? null,
                            'fee_fund_structure_id' => $firstStructure->id ?? null,
                            'school_class_id'       => $schoolClassId,
                            'section'               => $profile->section,
                            'level_id'              => $profile->level_id,
                            'year_session_id'       => $yearSession->id,
                            'challan_snapshot'      => json_encode($snapshot),
                            'is_active'             => true,
                        ];

                        $activeChallan = $existingChallans->get($consumer->id);

                        if ($activeChallan) {
                            $activeChallan->update($challanData);
                            $updated++;
                        } else {
                            $challanNo = $this->generateUniqueChallanNo();
                            ActiveChallan::create(array_merge($challanData, [
                                'consumer_id'  => $consumer->id,
                                'challan_no'   => $challanNo,
                                'status'       => 'U',
                                'tran_auth_id' => 'J8NTDA',
                            ]));
                            $generated++;
                        }
                    }
                });

            DB::commit();

            Log::info("Bulk Challan Generation: {$generated} generated, {$updated} updated, {$skipped} skipped for {$billingMonth}.");

            // ── Response message ──────────────────────────────────────────────
            if ($generated === 0 && $updated === 0 && $skipped === 0) {
                $message = "No active consumers found for {$billingMonth}.";
            } elseif ($generated === 0 && $updated === 0) {
                $message = "No challans generated — {$skipped} consumers skipped for {$billingMonth}.";
            } else {
                $message = "{$generated} challans generated, {$updated} updated, {$skipped} skipped for {$billingMonth}.";
            }

            return response()->json([
                'success'         => true,
                'message'         => $message,
                'generated'       => $generated,
                'updated'         => $updated,
                'skipped'         => $skipped,
                'skip_reasons'    => $skipReasons,
                'skipped_details' => array_slice($skippedDetails, 0, 50),
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

        $challans = ActiveChallan::with(['consumer.profileDetails', 'yearSession'])
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
     * Get active year sessions for the update dropdown.
     */
    public function getYearSessions()
    {
        $sessions = \App\Models\YearSession::where('is_active', true)
            ->with(['schoolClass', 'institution'])
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => "{$s->name} - {$s->schoolClass?->name} ({$s->institution?->name})",
            ]);

        return response()->json(['data' => $sessions]);
    }

    /**
     * Get all metadata needed for challan updates (dropdowns).
     */
    public function getChallanMetadata()
    {
        return response()->json([
            'institutions' => \App\Models\Institution::select('id', 'name as label')->get(),
            'regions'      => \App\Models\Region::select('id', 'name as label')->get(),
            'categories'   => FeeFundCategory::select('id', 'category_title as label')->get(),
            'heads'        => \App\Models\FeeFundHead::select('id', 'head_identifier as label')->get(),
            'classes'      => \App\Models\SchoolClass::select('id', 'name as label')->get(),
            'levels'       => \App\Models\Level::select('id', 'level as label')->get(),
            'sessions'     => \App\Models\YearSession::where('is_active', true)->select('id', 'name as label')->get(),
        ]);
    }

    public function challanHistoryIndex(Request $request)
    {
        $search = $request->input('search');

        $activeQuery = ActiveChallan::with(['consumer.profileDetails', 'yearSession', 'institution', 'region']);
        $archivedQuery = \App\Models\ChallanHistory::with(['yearSession', 'institution', 'region']);

        if ($search) {
            $activeQuery->where(function($q) use ($search) {
                $q->where('challan_no', 'LIKE', "%{$search}%")
                  ->orWhereHas('consumer', function($cq) use ($search) {
                      $cq->where('consumer_number', 'LIKE', "%{$search}%");
                  });
            });
            $archivedQuery->where('challan_no', 'LIKE', "%{$search}%");
            // Note: Archived doesn't have a direct consumer relation in many cases if they were deleted/moved,
            // but we can search by challan_no which is unique.
        }

        return Inertia::render('Admin/Settings/ChallanHistory', [
            'activeChallans'   => $activeQuery->latest()->paginate(10, ['*'], 'active_page')->withQueryString(),
            'archivedChallans' => $archivedQuery->latest()->paginate(10, ['*'], 'archived_page')->withQueryString(),
            'filters'          => $request->all(['search']),
        ]);
    }

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
            'year_session_id'       => 'nullable|exists:year_sessions,id',
            'institution_id'        => 'nullable|exists:institutions,id',
            'region_id'             => 'nullable|exists:regions,id',
            'fee_fund_category_id'  => 'nullable|exists:fee_fund_category,id',
            'fee_fund_head_id'      => 'nullable|exists:fee_fund_heads,id',
            'school_class_id'       => 'nullable|exists:school_classes,id',
            'section'               => 'nullable|string|max:100',
            'level_id'              => 'nullable|exists:levels,id',
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
                'year_session_id',
                'institution_id',
                'region_id',
                'fee_fund_category_id',
                'fee_fund_head_id',
                'school_class_id',
                'section',
                'level_id',
            ]));

            // Regenerate snapshot after update
            $challan->update([
                'challan_snapshot' => json_encode($challan->generateSnapshot())
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Challan updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update challan: ' . $e->getMessage());
        }
    }

    public function retrySmsSync()
    {
        try {
            $activeCount = 0;
            $historyCount = 0;

            // Process Active Challans
            ActiveChallan::where('status', 'P')
                ->where('sms_sync', 0)
                ->chunk(100, function ($challans) use (&$activeCount) {
                    foreach ($challans as $challan) {
                        \App\Jobs\SyncSmsJob::dispatch($challan);
                        $activeCount++;
                    }
                });

            // Process Challan History
            \App\Models\ChallanHistory::where('status', 'P')
                ->where('sms_sync', 0)
                ->chunk(100, function ($challans) use (&$historyCount) {
                    foreach ($challans as $challan) {
                        \App\Jobs\SyncSmsJob::dispatch($challan);
                        $historyCount++;
                    }
                });

            return redirect()->back()->with('success', "Dispatched {$activeCount} active and {$historyCount} history challans for SMS sync retry.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to retry SMS sync: ' . $e->getMessage());
        }
    }
}
