<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;
use Inertia\Inertia;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Level::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('level', 'like', '%' . $search . '%');
        }

        // Sorting
        $sortField = $request->get('sort', 'display_order');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $levels = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Levels/Index', [
            'levels' => $levels,
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
                'level' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            // Set default order
            $validated['display_order'] = Level::max('display_order') + 1;

            Level::create($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Level created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating level: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create level.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Level $level)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'level' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $level->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Level updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating level: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update level.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Level $level)
    {
        DB::beginTransaction();
        try {
            $level->softDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Level deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting level: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete level.');
        }
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, Level $level)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $level->update(['is_active' => $request->is_active]);

            DB::commit();
            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating level status: ' . $e->getMessage());
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
                'ids.*' => 'exists:levels,id',
            ]);

            foreach ($request->ids as $index => $id) {
                Level::where('id', $id)->update(['display_order' => $index + 1]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Levels reordered successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reordering levels: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reorder levels.');
        }
    }
}
