<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeFundStructure;
use App\Models\FeeFundCategory;
use App\Models\Region;
use App\Models\Level;
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
            ->with(['feeFundCategory:id,category_title', 'region:id,name', 'level:id,level']);

        // Search functionality - search in region, institution_level, and category title
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('region', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('level', function ($q) use ($search) {
                        $q->where('level', 'like', '%' . $search . '%');
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
        } elseif ($sortField === 'level_name' || $sortField === 'institution_level') {
            $query->join('levels', 'fee_fund_structure.level_id', '=', 'levels.id')
                ->orderBy('levels.level', $sortDirection)
                ->select('fee_fund_structure.*');
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 8);
        $structures = $query->paginate($perPage)->withQueryString();

        // Get categories for the form dropdown
        $categories = FeeFundCategory::select('id', 'category_title')->where('is_active', true)->orderBy('display_order')->get();

        // Get regions and levels for dropdowns
        $regions = Region::select('id', 'name')->where('is_active', true)->orderBy('display_order')->get();
        $levels = Level::select('id', 'level')->where('is_active', true)->orderBy('display_order')->get();

        return Inertia::render('Admin/FeeStructure/Index', [
            'structures' => $structures,
            'categories' => $categories,
            'regions' => $regions,
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
                'region_id' => 'required|exists:regions,id',
                'level_id' => 'required|exists:levels,id',
                'fee_fund_category_id' => 'required|exists:fee_fund_category,id',
                'admission_fee' => 'nullable|numeric|min:0',
                'slc' => 'nullable|numeric|min:0',
                'tution_fee' => 'nullable|numeric|min:0',
                'idf' => 'nullable|numeric|min:0',
                'exam_fee' => 'nullable|numeric|min:0',
                'it_fee' => 'nullable|numeric|min:0',
                'csf' => 'nullable|numeric|min:0',
                'rdf' => 'nullable|numeric|min:0',
                'cdf' => 'nullable|numeric|min:0',
                'security_fund' => 'nullable|numeric|min:0',
                'bs_fund' => 'nullable|numeric|min:0',
                'prep_fund' => 'nullable|numeric|min:0',
                'donation_fund' => 'nullable|numeric|min:0',
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
                'level_id' => 'required|exists:levels,id',
                'fee_fund_category_id' => 'required|exists:fee_fund_category,id',
                'admission_fee' => 'nullable|numeric|min:0',
                'slc' => 'nullable|numeric|min:0',
                'tution_fee' => 'nullable|numeric|min:0',
                'idf' => 'nullable|numeric|min:0',
                'exam_fee' => 'nullable|numeric|min:0',
                'it_fee' => 'nullable|numeric|min:0',
                'csf' => 'nullable|numeric|min:0',
                'rdf' => 'nullable|numeric|min:0',
                'cdf' => 'nullable|numeric|min:0',
                'security_fund' => 'nullable|numeric|min:0',
                'bs_fund' => 'nullable|numeric|min:0',
                'prep_fund' => 'nullable|numeric|min:0',
                'donation_fund' => 'nullable|numeric|min:0',
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
