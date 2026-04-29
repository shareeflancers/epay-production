<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\ApiLog;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function index()
    {
        $auditLogs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $apiLogs = ApiLog::orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/SecurityAudit', [
            'auditLogs' => $auditLogs,
            'apiLogs' => $apiLogs,
        ]);
    }

    public function getAuditLogs(Request $request)
    {
        return AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getApiLogs(Request $request)
    {
        return ApiLog::orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getLatestSnapshots()
    {
        // Get the latest snapshot for each step_name
        return \App\Models\ProcedureSnapshot::whereIn('id', function($query) {
            $query->selectRaw('MAX(id)')
                ->from('procedure_snapshots')
                ->groupBy('step_name');
        })
        ->get()
        ->keyBy('step_name');
    }
}
