<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Services\ReportService;
use Inertia\Inertia;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'institution_id',
            'school_class_id',
            'section',
            'fee_fund_category_id',
            'month',
            'year',
            'year_session'
        ]);

        $data = $this->reportService->getSummaryAndPaginatedInstitutions($filters);

        return Inertia::render('Admin/Reports/Index', [
            'institutions' => $data['institutions'],
            'summaryTotals' => $data['summaryTotals'],
            'filterOptions' => $this->reportService->getReportFilterOptions(),
            'filters' => $filters
        ]);
    }

    public function showInstitution(Request $request, $id)
    {
        $institution = Institution::findOrFail($id);
        $filters = $request->only([
            'school_class_id',
            'section',
            'fee_fund_category_id',
            'month',
            'year',
            'year_session'
        ]);

        $stats = $this->reportService->getInstitutionStats($id, $filters);

        return Inertia::render('Admin/Reports/InstitutionShow', [
            'institution' => $institution,
            'stats' => $stats,
            'filters' => $filters
        ]);
    }

    public function showStudents(Request $request)
    {
        $filters = $request->all();
        $challans = $this->reportService->getStudentsList($filters);

        return Inertia::render('Admin/Reports/StudentsList', [
            'challans' => $challans,
            'filters' => $filters,
            'institution' => Institution::find($filters['institution_id'] ?? null),
            'schoolClass' => SchoolClass::find($filters['school_class_id'] ?? null)
        ]);
    }
}
