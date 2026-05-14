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

        // Calculate total collection
        $totalCollection = ActiveChallan::where('status', 'P')->sum('amount_base');

        // Collection Trend (Whole Current Month)
        $startOfMonth = now()->startOfMonth();
        $endOfToday = now()->endOfDay();

        $collectionTrendData = ActiveChallan::where('status', 'P')
            ->whereNotNull('date_paid')
            ->whereBetween('date_paid', [$startOfMonth, $endOfToday])
            ->selectRaw('DATE(date_paid) as date, SUM(amount_base) as amount')
            ->groupBy('date')
            ->get()
            ->pluck('amount', 'date');

        $collectionTrend = collect();
        for ($date = $startOfMonth->copy(); $date->lte(now()); $date->addDay()) {
            $dateString = $date->toDateString();
            $collectionTrend->push([
                'date' => $date->format('M d'),
                'amount' => (float) ($collectionTrendData[$dateString] ?? 0)
            ]);
        }

        // Top Institutions breakdown (Still fetched but not used in dashboard for now)
        $institutionStats = \App\Models\Institution::withCount([
            'activeChallans as paid' => function ($q) { $q->where('status', 'P'); },
            'activeChallans as unpaid' => function ($q) { $q->where('status', 'U'); }
        ])
        ->orderByDesc('paid')
        ->take(6)
        ->get()
        ->map(function ($inst) {
            return [
                'name' => mb_substr($inst->name, 0, 15) . (strlen($inst->name) > 15 ? '...' : ''),
                'paid' => $inst->paid,
                'unpaid' => $inst->unpaid
            ];
        });

        $stats = [
            'total_consumers' => $totalConsumers,
            'active_challans' => $totalActiveChallans,
            'paid_challans' => $paidChallans,
            'unpaid_challans' => $unpaidChallans,
            'blocked_challans' => $blockedChallans,
            'total_collection' => $totalCollection,
            'charts' => [
                'collection_trend' => $collectionTrend,
                'institution_stats' => $institutionStats,
                'status_distribution' => [
                    ['name' => 'Paid', 'value' => $paidChallans, 'color' => 'teal.6'],
                    ['name' => 'Unpaid', 'value' => $unpaidChallans, 'color' => 'orange.6'],
                    ['name' => 'Blocked', 'value' => $blockedChallans, 'color' => 'red.6'],
                ]
            ]
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
