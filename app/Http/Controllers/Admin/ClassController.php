<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SchoolClass::query();

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
        $classes = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Classes/Index', [
            'classes' => $classes,
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

            // Set default order
            $validated['display_order'] = SchoolClass::max('display_order') + 1;

            SchoolClass::create($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Class created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating class: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create class.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SchoolClass $class)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
            ]);

            $class->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Class updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating class: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update class.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolClass $class)
    {
        DB::beginTransaction();
        try {
            $class->softDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Class deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting class: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete class.');
        }
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, SchoolClass $class)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $class->update(['is_active' => $request->is_active]);

            DB::commit();
            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating class status: ' . $e->getMessage());
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
                'ids.*' => 'exists:school_classes,id',
            ]);

            foreach ($request->ids as $index => $id) {
                SchoolClass::where('id', $id)->update(['display_order' => $index + 1]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Classes reordered successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reordering classes: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to reorder classes.');
        }
    }
}
