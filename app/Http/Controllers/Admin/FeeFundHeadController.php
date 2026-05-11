<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use DB;
use App\Models\FeeFundHead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class FeeFundHeadController extends Controller
{
    /**
     * Display a listing of the fee fund heads.
     */
    public function index(Request $request)
    {
        $query = FeeFundHead::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('fee_head', 'like', '%' . $search . '%');
        }

        // Sorting
        $sortField = $request->get('sort', 'display_order');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 100);
        $heads = $query->paginate($perPage)->withQueryString();

        // Transform heads for display if needed (e.g., join array with commas)
        // Actually, we'll handle display in the React component.

        return Inertia::render('Admin/FeeFundHeads/Index', [
            'heads' => $heads,
            'filters' => [
                'search' => $request->search,
                'sort' => $sortField,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Store a newly created fee fund head.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'head_identifier' => 'required|string|max:255',
                'fee_head' => 'required',
                'is_active' => 'boolean',
            ]);

            // Convert to array if it's a string, or just use if it's already an array
            $feeHeads = is_array($validated['fee_head'])
                ? $validated['fee_head']
                : array_map('trim', explode(',', $validated['fee_head']));

            // Filter out empty values
            $feeHeads = array_filter($feeHeads);

            $maxOrder = FeeFundHead::max('display_order');
            $newOrder = $maxOrder ? $maxOrder + 1 : 1;

            $head = FeeFundHead::create([
                'head_identifier' => $validated['head_identifier'],
                'fee_head' => array_values($feeHeads),
                'is_active' => $validated['is_active'] ?? true,
                'display_order' => $newOrder,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'New Fee Fund Head created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create Fee Fund Head: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified fee fund head.
     */
    public function update(Request $request, FeeFundHead $feeFundHead)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'head_identifier' => 'required|string|max:255',
                'fee_head' => 'required',
                'is_active' => 'boolean',
            ]);

            // Convert to array if it's a string, or just use if it's already an array
            $feeHeads = is_array($validated['fee_head'])
                ? $validated['fee_head']
                : array_map('trim', explode(',', $validated['fee_head']));

            // Filter out empty values
            $feeHeads = array_filter($feeHeads);

            $feeFundHead->update([
                'head_identifier' => $validated['head_identifier'],
                'fee_head' => array_values($feeHeads),
                'is_active' => $validated['is_active'],
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Fee Fund Head updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update Fee Fund Head: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified fee fund head.
     */
    public function destroy(FeeFundHead $feeFundHead)
    {
        try {
            DB::beginTransaction();

            $feeFundHead->update(['is_active' => false]);
            $feeFundHead->softDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Fee Fund Head deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete Fee Fund Head: ' . $e->getMessage());
        }
    }

    /**
     * Update the status.
     */
    public function updateStatus(Request $request, FeeFundHead $feeFundHead)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'is_active' => 'boolean',
            ]);

            $feeFundHead->update([
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Reorder heads.
     */
    public function reorder(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:fee_fund_heads,id',
            ]);

            foreach ($request->ids as $index => $id) {
                FeeFundHead::where('id', $id)->update(['display_order' => $index + 1]);
            }

            return back()->with('success', 'Heads reordered successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reorder: ' . $e->getMessage());
        }
    }
}
