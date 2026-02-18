<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use DB;
use App\Models\FeeFundCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;


class FeeFundCategoryController extends Controller
{
    /**
     * Display a listing of the fee fund categories.
     */
    public function index(Request $request)
    {
        $query = FeeFundCategory::query();

        // Search functionality - search in both title and details
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('category_title', 'like', '%' . $search . '%')
                  ->orWhere('details', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'display_order');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 8);
        $categories = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/FeeFundCategory/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
                'sort' => $sortField,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:fee_fund_category,id',
        ]);

        foreach ($request->ids as $index => $id) {
            FeeFundCategory::where('id', $id)->update(['display_order' => $index + 1]);
        }

        return back()->with('success', 'Categories reordered successfully');
    }

    /**
     * Store a newly created fee fund category.
     */
    public function store(Request $request)
    {
        try{
            DB::beginTransaction();
            $validated = $request->validate([
                'category_title' => 'required|string|max:255',
                'details' => 'required|string',
                'is_active' => 'boolean',
            ]);

            $maxOrder = FeeFundCategory::max('display_order');
            $newOrder = $maxOrder ? $maxOrder + 1 : 1;

            $category = FeeFundCategory::create([
                'category_title' => $validated['category_title'],
                'details' => $validated['details'],
                'is_active' => $validated['is_active'],
                'display_order' => $newOrder,
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'New Fee Category created successfully.');
        }catch(\Exception $e){
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create Fee Category: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified fee fund category.
     */
    public function update(Request $request, FeeFundCategory $feeFundCategory)
    {
        try{
            DB::beginTransaction();
            $validated = $request->validate([
                'category_title' => 'required|string|max:255',
                'details' => 'required|string',
                'is_active' => 'boolean',
            ]);

            $feeFundCategory->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Fee Category updated successfully.');
        }catch(\Exception $e){
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update Fee Category: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified fee fund category.
     */
    public function destroy(FeeFundCategory $feeFundCategory)
    {
        try{
            // Check if category is in use
            if ($feeFundCategory->feeFundStructures()->exists()) {
                return redirect()->back()->with('error', 'Cannot delete category. It is being used by fee structures.');
            }

            DB::beginTransaction();

            // Mark as inactive and soft delete
            $feeFundCategory->update(['is_active' => false]);
            $feeFundCategory->softDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Fee Category deleted successfully.');
        }catch (\Exception $e){
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete Fee Category: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified fee fund category.
     */
    public function updateStatus(Request $request, FeeFundCategory $feeFundCategory)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'is_active' => 'boolean',
            ]);

            $feeFundCategory->update([
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Status updated successfully.');
        }catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
}
