<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeFundStructure;
use App\Models\FeeFundCategory;
use App\Models\Region;
use App\Models\SchoolClass;
use App\Models\FeeFundHead;
use Illuminate\Http\Request;
use Inertia\Inertia;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class FeeStructureController extends Controller
{
    /**
     * Display a listing of the fee fund structures.
     */
    public function index(Request $request)
    {
        $query = FeeFundStructure::query()
            ->with(['feeFundCategory:id,category_title', 'region:id,name', 'schoolClass:id,name', 'feeFundHead']);

        // Search functionality - search in region, institution_level, and category title
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('region', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('schoolClass', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('feeFundCategory', function ($q) use ($search) {
                        $q->where('category_title', 'like', '%' . $search . '%');
                    });
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'region_name');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortField === 'category_title') {
            $query->join('fee_fund_category', 'fee_fund_structure.fee_fund_category_id', '=', 'fee_fund_category.id')
                ->orderBy('fee_fund_category.category_title', $sortDirection)
                ->select('fee_fund_structure.*');
        } elseif ($sortField === 'region_name' || $sortField === 'region') {
             // Handle both region and region_name for backward compat or UI
            $query->join('regions', 'fee_fund_structure.region_id', '=', 'regions.id')
                ->orderBy('regions.name', $sortDirection)
                ->select('fee_fund_structure.*');
        } elseif ($sortField === 'class_name' || $sortField === 'class') {
            $query->join('school_classes', 'fee_fund_structure.school_class_id', '=', 'school_classes.id')
                ->orderBy('school_classes.name', $sortDirection)
                ->select('fee_fund_structure.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 100);
        $structures = $query->paginate($perPage)->withQueryString();

        // Get categories for the form dropdown
        $categories = FeeFundCategory::select('id', 'category_title')->where('is_active', true)->orderBy('display_order')->get();

        // Get regions and classes for dropdowns
        $regions = Region::select('id', 'name')->where('is_active', true)->orderBy('display_order')->get();
        $classes = SchoolClass::select('id', 'name')->where('is_active', true)->orderBy('display_order')->get();
        $headGroups = FeeFundHead::select('id', 'head_identifier', 'fee_head')->where('is_active', true)->orderBy('display_order')->get();

        return Inertia::render('Admin/FeeStructure/Index', [
            'structures' => $structures,
            'categories' => $categories,
            'regions' => $regions,
            'classes' => $classes,
            'headGroups' => $headGroups,
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
                'region_id' => 'required|exists:regions,id',
                'school_class_id' => 'required|exists:school_classes,id',
                'fee_fund_category_id' => 'required|exists:fee_fund_category,id',
                'fee_fund_head_id' => 'required|exists:fee_fund_heads,id',
                'fee_head_amounts' => 'required|array',
                'total' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            FeeFundStructure::create($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Fee structure created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating fee structure: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create fee structure.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeeFundStructure $feeStructure)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'region_id' => 'required|exists:regions,id',
                'school_class_id' => 'required|exists:school_classes,id',
                'fee_fund_category_id' => 'required|exists:fee_fund_category,id',
                'fee_fund_head_id' => 'required|exists:fee_fund_heads,id',
                'fee_head_amounts' => 'required|array',
                'total' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            $feeStructure->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Fee structure updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating fee structure: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update fee structure.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeeFundStructure $feeStructure)
    {
        DB::beginTransaction();
        try {
            $feeStructure->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Fee structure deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting fee structure: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete fee structure.');
        }
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, FeeFundStructure $feeStructure)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $feeStructure->update(['is_active' => $request->is_active]);

            DB::commit();
            return redirect()->back()->with('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating fee structure status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update status.');
        }
    }
}
