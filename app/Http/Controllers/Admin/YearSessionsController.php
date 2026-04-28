<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\YearSession;
use App\Models\SchoolClass;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YearSessionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = YearSession::with(['schoolClass', 'institution']);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhereHas('schoolClass', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('institution', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $sessions = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/YearSessions/Index', [
            'sessions' => $sessions,
            'classes' => SchoolClass::where('is_active', true)->orderBy('display_order')->get(),
            'institutions' => Institution::where('is_active', true)->orderBy('name')->get(),
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
                'name'            => 'required|string|max:255',
                'start_date'      => 'nullable|date',
                'end_date'        => 'nullable|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'institution_id'  => 'required|exists:institutions,id',
                'is_active'       => 'boolean',
            ]);

            YearSession::create($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Year Session created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating year session: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create year session.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, YearSession $yearSession)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name'            => 'required|string|max:255',
                'start_date'      => 'nullable|date',
                'end_date'        => 'nullable|date|after_or_equal:start_date',
                'school_class_id' => 'required|exists:school_classes,id',
                'institution_id'  => 'required|exists:institutions,id',
                'is_active'       => 'boolean',
            ]);

            $yearSession->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Year Session updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating year session: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update year session.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(YearSession $yearSession)
    {
        DB::beginTransaction();
        try {
            // Check if there are any challans associated with this session
            if ($yearSession->activeChallans()->count() > 0) {
                return redirect()->back()->with('error', 'Cannot delete a session that has associated challans.');
            }

            $yearSession->softDelete();

            DB::commit();
            return redirect()->back()->with('success', 'Year Session deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting year session: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete year session.');
        }
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, YearSession $yearSession)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $yearSession->update(['is_active' => $request->is_active]);

            DB::commit();
            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating session status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update status.');
        }
    }
}
