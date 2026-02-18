<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Region::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%');
        }

        // Sorting
        $sortField = $request->get('sort', 'display_order');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $regions = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Regions/Index', [
            'regions' => $regions,
            'filters' => [
                'search' => $request->search,
                'sort' => $sortField,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            Region::create($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Region created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating region: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create region.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Region $region)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $region->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Region updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating region: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update region.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region)
    {
        DB::beginTransaction();
        try {
            $region->softDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Region deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting region: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete region.');
        }
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, Region $region)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $region->update(['is_active' => $request->is_active]);

            DB::commit();
            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating region status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update status.');
        }
    }

    /**
     * Reorder the resources.
     */
    public function reorder(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:regions,id',
            ]);

            foreach ($request->ids as $index => $id) {
                Region::where('id', $id)->update(['display_order' => $index + 1]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Regions reordered successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reordering regions: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reorder regions.');
        }
    }
}
