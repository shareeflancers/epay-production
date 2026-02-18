<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActiveChallan;
use App\Models\Consumer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Fetch stats
        $totalConsumers = Consumer::count();
        $totalActiveChallans = ActiveChallan::count();
        $paidChallans = ActiveChallan::where('status', 'P')->count();
        $unpaidChallans = ActiveChallan::where('status', 'U')->count();
        $blockedChallans = ActiveChallan::where('status', 'B')->count();

        // Calculate total collection (sum of amount_base for paid challans)
        $totalCollection = ActiveChallan::where('status', 'P')->sum('amount_base');

        $stats = [
            'total_consumers' => $totalConsumers,
            'active_challans' => $totalActiveChallans,
            'paid_challans' => $paidChallans,
            'unpaid_challans' => $unpaidChallans,
            'blocked_challans' => $blockedChallans,
            'total_collection' => $totalCollection,
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
