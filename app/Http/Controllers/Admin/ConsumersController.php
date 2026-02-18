<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use DB;
use App\Models\ProfileDetail;
use App\Models\Consumer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ConsumersController extends Controller
{
    /**
     * Display a listing of the consumers.
     */
    public function index(Request $request, $type)
    {
        // Define columns based on profile type
        $consumerColumns = [
            'consumers.id',
            'consumers.consumer_number',
            'consumers.identification_number',
            'consumers.institution_id',
            'consumers.region_id',
            'consumers.consumer_type'
        ];

        $profileColumns = [
            'profile_details.profile_type',
            'profile_details.consumer_id',
            'profile_details.is_active',
            'profile_details.created_at'
        ];

        // Add type-specific columns
        switch ($type) {
            case 'student':
                $profileColumns = array_merge($profileColumns, [
                    'profile_details.name',
                    'profile_details.father_or_guardian_name',
                    'profile_details.region_name',
                    'profile_details.institution_name',
                    'profile_details.institution_level',
                    'profile_details.class',
                    'profile_details.section'
                ]);
                break;

            case 'institution':
                $profileColumns = array_merge($profileColumns, [
                    'profile_details.region_name',
                    'profile_details.institution_name',
                    'profile_details.institution_level'
                ]);
                break;

            case 'inductee':
                $profileColumns = array_merge($profileColumns, [
                    'profile_details.name',
                    'profile_details.father_or_guardian_name'
                ]);
                break;
        }

        // Build the query
        $query = Consumer::query()
            ->join('profile_details', 'consumers.id', '=', 'profile_details.consumer_id')
            ->select(array_merge($consumerColumns, $profileColumns))
            ->where('consumers.consumer_type', $type)
            ->where('profile_details.profile_type', $type);

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->Where('consumers.identification_number', 'like', '%' . $search . '%')
                  ->orWhere('profile_details.name', 'like', '%' . $search . '%')
                  ->orWhere('consumers.consumer_number', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'profile_details.is_active');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 8);
        $consumers = $query->paginate($perPage)->withQueryString();

        // Return view based on type (capitalizing first letter for proper view path)
        return Inertia::render('Admin/Consumers/' . ucfirst($type), [
            'consumers' => $consumers,
            'filters' => [
                'search' => $request->search,
                'sort' => $sortField,
                'direction' => $sortDirection,
                'per_page' => $perPage,
            ],
            'type' => $type
        ]);
    }

    /**
     * Update the specified consumer.
     */
    public function update(Request $request, Consumer $consumer)
    {
        if ($consumer->consumer_type === 'student') {
            $rules['name'] = 'required|string|max:255';
            $rules['father_or_guardian_name'] = 'nullable|string|max:255';
            $rules['class'] = 'nullable|string|max:50';
            $rules['section'] = 'nullable|string|max:50';
        } elseif ($consumer->consumer_type === 'institution') {
            $rules['institution_name'] = 'required|string|max:255';
            $rules['institution_level'] = 'nullable|string|max:50';
            $rules['region_name'] = 'nullable|string|max:255';
        } elseif ($consumer->consumer_type === 'inductee') {
            $rules['name'] = 'required|string|max:255';
            $rules['father_or_guardian_name'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            // Prepare update data for profile details
            $profileData = [];
            if ($consumer->consumer_type === 'student') {
                $profileData = [
                    'name' => $validated['name'],
                    'father_or_guardian_name' => $validated['father_or_guardian_name'] ?? null,
                    'class' => $validated['class'] ?? null,
                    'section' => $validated['section'] ?? null,
                ];
            } elseif ($consumer->consumer_type === 'institution') {
                $profileData = [
                    'institution_name' => $validated['institution_name'],
                    'institution_level' => $validated['institution_level'] ?? null,
                    'region_name' => $validated['region_name'] ?? null,
                ];
            } elseif ($consumer->consumer_type === 'inductee') {
                $profileData = [
                    'name' => $validated['name'],
                    'father_or_guardian_name' => $validated['father_or_guardian_name'] ?? null,
                ];
            }

            // Update profile details
            if (!empty($profileData)) {
                $consumer->profileDetails()
                    ->where('is_active', 1)
                    ->update($profileData);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Consumer updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating consumer: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update consumer.');
        }
    }

    /**
     * Remove the specified consumer.
     */
    public function destroy(Consumer $consumer)
    {
        try {
            DB::beginTransaction();

            // Inactivate profile details and consumer
            $consumer->profileDetails()->update(['is_deleted' => 1, 'is_active' => 0]);
            $consumer->update(['is_active' => 0]);

            // Soft delete consumer
            $consumer->softDelete();

            DB::commit();

            return redirect()->back()->with('success', 'Consumer deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting consumer: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete consumer.');
        }
    }

    /**
     * Update the status of the specified consumer.
     */
    public function updateStatus(Request $request, Consumer $consumer)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $consumer->update(['is_active' => $validated['is_active']]);
            $consumer->profileDetails()->update(['is_active' => $validated['is_active']]);

            DB::commit();
            return redirect()->back()->with('success', 'Consumer status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating consumer status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update status.');
        }
    }
}
