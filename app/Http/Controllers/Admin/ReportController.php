<?php

 namespace App\Http\Controllers\Admin;

 use App\Http\Controllers\Controller;
 use App\Models\Institution;
 use App\Models\SchoolClass;
 use App\Models\ActiveChallan;
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

         // Calculate totals for all matched institutions (before pagination)
         $totalsQuery = clone $query;
         $totals = $totalsQuery->withCount([
             'activeChallans as total' => function ($q) use ($classId, $section) {
                 if ($classId) $q->where('school_class_id', $classId);
                 if ($section) {
                     $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                         $pq->where('section', $section);
                     });
                 }
             },
             'activeChallans as paid' => function ($q) use ($classId, $section) {
                 $q->where('status', 'P');
                 if ($classId) $q->where('school_class_id', $classId);
                 if ($section) {
                     $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                         $pq->where('section', $section);
                     });
                 }
             },
             'activeChallans as synced' => function ($q) use ($classId, $section) {
                 $q->where('sms_sync', 1);
                 if ($classId) $q->where('school_class_id', $classId);
                 if ($section) {
                     $q->whereHas('consumer.profileDetails', function ($pq) use ($section) {
                         $pq->where('section', $section);
                     });
                 }
             }
         ])->get();

         $summaryTotals = [
             'total' => $totals->sum('total'),
             'paid' => $totals->sum('paid'),
             'synced' => $totals->sum('synced'),
         ];

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
         ->paginate(10)
         ->withQueryString();

         return Inertia::render('Admin/Reports/Index', [
             'institutions' => $institutions,
             'summaryTotals' => $summaryTotals,
             'filterOptions' => [
                 'institutions' => Institution::where('is_active', true)->select('id', 'name as label')->get(),
                 'classes' => SchoolClass::where('is_active', true)->select('id', 'name as label')->get(),
             ],
             'filters' => $request->only(['institution_id', 'school_class_id', 'section'])
         ]);
     }

     public function showInstitution($id)
     {
         $institution = Institution::findOrFail($id);

         $stats = \DB::table('active_challans as ac')
             ->join('consumers as c', 'ac.consumer_id', '=', 'c.id')
             ->join('profile_details as pd', 'c.id', '=', 'pd.consumer_id')
             ->leftJoin('school_classes as sc', 'ac.school_class_id', '=', 'sc.id')
             ->where('ac.institution_id', $id)
             ->where('pd.is_active', true)
             ->select([
                 'ac.school_class_id',
                 'sc.name as class_name',
                 'pd.section',
                 \DB::raw('count(ac.id) as total_count'),
                 \DB::raw('sum(case when ac.status = "P" then 1 else 0 end) as paid_count'),
                 \DB::raw('sum(case when ac.status = "U" then 1 else 0 end) as unpaid_count'),
                 \DB::raw('sum(case when ac.sms_sync = 1 then 1 else 0 end) as synced_count'),
             ])
             ->groupBy('ac.school_class_id', 'sc.name', 'pd.section')
             ->orderBy('sc.name')
             ->orderBy('pd.section')
             ->get();

         return Inertia::render('Admin/Reports/InstitutionShow', [
             'institution' => $institution,
             'stats' => $stats
         ]);
     }

     public function showStudents(\Illuminate\Http\Request $request)
     {
         $institutionId = $request->input('institution_id');
         $classId = $request->input('school_class_id');
         $section = $request->input('section');

         $query = ActiveChallan::with(['consumer.profileDetails', 'schoolClass'])
             ->where('institution_id', $institutionId);

         if ($classId) {
             $query->where('school_class_id', $classId);
         }

         if ($section) {
             $query->whereHas('consumer.profileDetails', function ($q) use ($section) {
                 $q->where('section', $section);
             });
         }

         $challans = $query->latest()->paginate(50)->withQueryString();

         return Inertia::render('Admin/Reports/StudentsList', [
             'challans' => $challans,
             'filters' => $request->all(),
             'institution' => Institution::find($institutionId),
             'schoolClass' => SchoolClass::find($classId)
         ]);
     }
 }
