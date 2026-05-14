<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Consumer;
use App\Models\SchoolFeeStructure;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $institutionId = $request->input('institution_id');
        $classId = $request->input('school_class_id');
        $section = $request->input('section');

        $query = Institution::where('is_active', true)
            ->where('is_deleted', false);

        if ($institutionId) {
            $query->where('id', $institutionId);
        }

        $institutions = $query->withCount([
            'activeChallans as total_count' => function ($q) use ($classId, $section) {
                if ($classId) $q->where('school_class_id', $classId);
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            'activeChallans as paid_count' => function ($q) use ($classId, $section) {
                $q->where('status', 'P');
                if ($classId) $q->where('school_class_id', $classId);
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            'activeChallans as unpaid_count' => function ($q) use ($classId, $section) {
                $q->where('status', 'U');
                if ($classId) $q->where('school_class_id', $classId);
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
            'activeChallans as synced_count' => function ($q) use ($classId, $section) {
                $q->where('sms_sync', 1);
                if ($classId) $q->where('school_class_id', $classId);
                if ($section) {
                    $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                        $pq->where('section', $section);
                    });
                }
            },
        ])
        ->orderBy('display_order')
        ->orderBy('name')
        ->get();

        return Inertia::render('Admin/Reports/Index', [
            'institutions' => $institutions,
            'filterOptions' => [
                'institutions' => Institution::where('is_active', true)->select('id', 'name as label')->get(),
                'classes' => SchoolClass::where('is_active', true)->select('id', 'name as label')->get(),
            ],
            'filters' => $request->only(['institution_id', 'school_class_id', 'section'])
        ]);
    }
}
