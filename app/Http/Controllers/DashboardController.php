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

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Executive Summary KPIs
        $totalInquiries = ProjectInquiry::count();
        $inquiriesByStatus = [
            'DRAFT' => ProjectInquiry::where('status', 'DRAFT')->count(),
            'IN_REVIEW' => ProjectInquiry::whereIn('status', ['IN REVIEW', 'IN_REVIEW', 'REVIEW'])->count(),
            'APPROVED' => ProjectInquiry::whereIn('status', ['APPROVED', 'COMPLETED'])->count(),
        ];

        $totalWorkOrders = WorkOrder::where('is_latest', true)->count();
        $activeWorkOrders = WorkOrder::where('is_latest', true)
            ->whereNotIn('status', ['CLOSED', 'COMPLETED', 'REJECTED', 'CANCELLED'])
            ->count();
        $completedWorkOrders = WorkOrder::where('is_latest', true)
            ->whereIn('status', ['CLOSED', 'COMPLETED', 'APPROVED'])
            ->count();

        $totalEbdHeaders = MngEbdHeader::count();
        $totalToolingQuotations = ToolingQuotation::count();

        $urgentPriorityCount = WorkOrder::where('is_latest', true)
            ->whereIn('priority', ['URGENT', 'HIGH'])
            ->whereNotIn('status', ['CLOSED', 'COMPLETED', 'REJECTED', 'CANCELLED'])
            ->count();

        // 2. Recent Feasibility Study Projects & Work Orders (Latest 7 records)
        $recentInquiries = ProjectInquiry::with(['customer', 'projectModel', 'workOrders' => function($q) {
                $q->where('is_latest', true);
            }])
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get();

        // 3. SPK Workflow Distribution by Type
        $spkDistribution = [
            'SPK 1 (Main Process)' => [
                'count' => WorkOrder::where('is_latest', true)->where(fn($q) => $q->whereNull('wo_type')->orWhere('wo_type', 'wo1')->orWhere('wo_type', ''))->count(),
                'color' => 'bg-blue-600',
                'icon' => 'fa-industry',
            ],
            'SPK 2 (Fastener)' => [
                'count' => WorkOrder::where('is_latest', true)->where('wo_type', 'fastener')->count(),
                'color' => 'bg-emerald-600',
                'icon' => 'fa-bolt',
            ],
            'SPK 2 (Add Process)' => [
                'count' => WorkOrder::where('is_latest', true)->where('wo_type', 'add_process')->count(),
                'color' => 'bg-amber-500',
                'icon' => 'fa-screwdriver-wrench',
            ],
            'SPK 2 (Tooling)' => [
                'count' => WorkOrder::where('is_latest', true)->where('wo_type', 'tooling')->count(),
                'color' => 'bg-indigo-600',
                'icon' => 'fa-hammer',
            ],
            'SPK 2 (Material)' => [
                'count' => WorkOrder::where('is_latest', true)->where('wo_type', 'material')->count(),
                'color' => 'bg-cyan-600',
                'icon' => 'fa-cubes-stacked',
            ],
        ];

        // 4. Critical & High Priority Attention List
        $priorityWorkOrders = WorkOrder::with(['inquiry.customer', 'inquiry.projectModel'])
            ->where('is_latest', true)
            ->whereIn('priority', ['URGENT', 'HIGH'])
            ->whereNotIn('status', ['CLOSED', 'COMPLETED', 'REJECTED', 'CANCELLED'])
            ->orderBy('due_date_plan', 'asc')
            ->take(4)
            ->get();

        // 5. Stage Funnel Counts for Feasibility Study
        $pipelineStages = [
            ['name' => '1. Inquiries', 'count' => $totalInquiries, 'icon' => 'fa-folder-open', 'color' => 'text-blue-600 bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800'],
            ['name' => '2. SPK 1 Main', 'count' => $spkDistribution['SPK 1 (Main Process)']['count'], 'icon' => 'fa-file-signature', 'color' => 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800'],
            ['name' => '3. EBD Breakdown', 'count' => $totalEbdHeaders, 'icon' => 'fa-layer-group', 'color' => 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/40 border-indigo-200 dark:border-indigo-800'],
            ['name' => '4. SPK 2 Sub-Processes', 'count' => ($totalWorkOrders - $spkDistribution['SPK 1 (Main Process)']['count']), 'icon' => 'fa-diagram-project', 'color' => 'text-amber-600 bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800'],
            ['name' => '5. Tooling Quote', 'count' => $totalToolingQuotations, 'icon' => 'fa-scale-balanced', 'color' => 'text-violet-600 bg-violet-50 dark:bg-violet-950/40 border-violet-200 dark:border-violet-800'],
        ];

        return view('dashboard', compact(
            'totalInquiries',
            'inquiriesByStatus',
            'totalWorkOrders',
            'activeWorkOrders',
            'completedWorkOrders',
            'totalEbdHeaders',
            'totalToolingQuotations',
            'urgentPriorityCount',
            'recentInquiries',
            'spkDistribution',
            'priorityWorkOrders',
            'pipelineStages'
        ));
    }
}
