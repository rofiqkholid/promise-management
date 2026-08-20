<?php

namespace App\Http\Controllers;

use App\Models\ProjectInquiry;
use App\Models\WorkOrder;
use App\Models\MngEbdHeader;
use App\Models\ToolingQuotation;
use App\Models\Customer;
use App\Models\ProjectModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ============================================================
        // 1. KPI Summary Metrics
        // ============================================================
        $totalInquiries = ProjectInquiry::count();
        $activeInquiries = ProjectInquiry::whereNotIn('status', ['CLOSED', 'REJECTED', 'CANCELLED'])->count();

        $totalWorkOrders = WorkOrder::where('is_latest', true)->count();
        $activeWorkOrders = WorkOrder::where('is_latest', true)
            ->whereNotIn('status', ['CLOSED', 'COMPLETED', 'REJECTED', 'CANCELLED'])
            ->count();
        $completedWorkOrders = WorkOrder::where('is_latest', true)
            ->whereIn('status', ['CLOSED', 'COMPLETED', 'APPROVED'])
            ->count();

        $totalEbdHeaders = MngEbdHeader::count();
        $totalToolingQuotations = ToolingQuotation::count();

        $urgentCount = WorkOrder::where('is_latest', true)
            ->whereIn('priority', ['URGENT', 'HIGH'])
            ->whereNotIn('status', ['CLOSED', 'COMPLETED', 'REJECTED', 'CANCELLED'])
            ->count();

        // ============================================================
        // 2. Last 6 Months Trend (Inquiries & Work Orders)
        // ============================================================
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push([
                'key' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'year' => $date->year,
                'month' => $date->month,
            ]);
        }

        $trendLabels = $months->pluck('label')->toArray();
        $inquiryTrendData = [];
        $woTrendData = [];

        foreach ($months as $m) {
            $inqCount = ProjectInquiry::whereYear('created_at', $m['year'])
                ->whereMonth('created_at', $m['month'])
                ->count();
            $woCount = WorkOrder::where('is_latest', true)
                ->whereYear('created_at', $m['year'])
                ->whereMonth('created_at', $m['month'])
                ->count();

            $inquiryTrendData[] = $inqCount;
            $woTrendData[] = $woCount;
        }

        // ============================================================
        // 3. Phase / Status Distribution (Work Orders & Inquiries)
        // ============================================================
        $statusCounts = WorkOrder::where('is_latest', true)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        if (empty($statusCounts)) {
            $statusCounts = [
                'Draft' => 0,
                'Process' => 0,
                'Approved' => 0,
                'Completed' => 0,
            ];
        }

        $phaseLabels = array_keys($statusCounts);
        $phaseData = array_values($statusCounts);

        // ============================================================
        // 4. SPK / WO Type Breakdown
        // ============================================================
        $spk1Count = WorkOrder::where('is_latest', true)->where(fn($q) => $q->whereNull('wo_type')->orWhereIn('wo_type', ['wo1', '']))->count();
        $spkTypeData = [
            'SPK 1 Main' => $spk1Count,
            'SPK 2 Fastener' => WorkOrder::where('is_latest', true)->where('wo_type', 'fastener')->count(),
            'SPK 2 Add Process' => WorkOrder::where('is_latest', true)->where('wo_type', 'add_process')->count(),
            'SPK 2 Tooling' => WorkOrder::where('is_latest', true)->where('wo_type', 'tooling')->count(),
            'SPK 2 Material' => WorkOrder::where('is_latest', true)->where('wo_type', 'material')->count(),
        ];

        // ============================================================
        // 5. Recent Inquiries (Latest records)
        // ============================================================
        $recentInquiries = ProjectInquiry::with(['customer', 'projectModel', 'workOrders' => fn($q) => $q->where('is_latest', true)])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // ============================================================
        // 6. Urgent & High Priority SPKs
        // ============================================================
        $urgentWorkOrders = WorkOrder::with(['inquiry.customer', 'inquiry.projectModel'])
            ->where('is_latest', true)
            ->orderByRaw("CASE WHEN priority = 'URGENT' THEN 1 WHEN priority = 'HIGH' THEN 2 ELSE 3 END")
            ->orderBy('due_date_plan', 'asc')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalInquiries',
            'activeInquiries',
            'totalWorkOrders',
            'activeWorkOrders',
            'completedWorkOrders',
            'totalEbdHeaders',
            'totalToolingQuotations',
            'urgentCount',
            'trendLabels',
            'inquiryTrendData',
            'woTrendData',
            'phaseLabels',
            'phaseData',
            'spkTypeData',
            'recentInquiries',
            'urgentWorkOrders'
        ));
    }
}
