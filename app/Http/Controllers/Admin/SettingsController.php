<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consumer;
use App\Models\ProfileDetail;
use App\Models\FeeFundCategory;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
}
