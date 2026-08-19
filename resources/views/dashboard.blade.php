@extends('layouts.app')

@section('title', 'Dashboard · Feasibility Study Overview')

@section('content')
<div class="flex-1 p-3 pt-17 flex flex-col gap-2.5 h-screen overflow-hidden transition-colors duration-200">
    
    {{-- Top Minimal Header Bar --}}
    <div class="bg-white dark:bg-slate-800 rounded-sm px-3.5 py-2 border border-slate-300 dark:border-slate-700 flex items-center justify-between shadow-2xs shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-sm bg-slate-100 dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                <i class="fa-solid fa-chart-line text-sm"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-bold tracking-tight text-slate-800 dark:text-white leading-tight">Feasibility Study Executive Dashboard</h1>
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-sm text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live System
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Comprehensive overview of Inquiries, Work Orders (SPK 1 & 2), EBD Breakdowns & Tooling Analysis</p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('management.inquiry.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-sm shadow-2xs transition-colors">
                <i class="fa-solid fa-plus text-[10px]"></i>
                <span>New Inquiry</span>
            </a>
            <a href="{{ route('management.work-order.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/60 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-sm border border-slate-300 dark:border-slate-600 transition-colors">
                <i class="fa-solid fa-file-signature text-[10px] text-blue-600 dark:text-blue-400"></i>
                <span>New SPK 1</span>
            </a>
        </div>
    </div>

    {{-- Executive 5-KPI Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 shrink-0">
        
        {{-- KPI 1: Inquiries --}}
        <div class="bg-white dark:bg-slate-800 rounded-sm p-2.5 border border-slate-300 dark:border-slate-700 flex items-center justify-between shadow-2xs hover:border-blue-400 transition-colors">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Total Inquiries</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalInquiries) }}</span>
                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">{{ $inquiriesByStatus['APPROVED'] }} approved</span>
                </div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400 flex gap-2">
                    <span>Draft: <b>{{ $inquiriesByStatus['DRAFT'] }}</b></span>
                    <span>Review: <b>{{ $inquiriesByStatus['IN_REVIEW'] }}</b></span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-sm bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-folder-open text-xs"></i>
            </div>
        </div>

        {{-- KPI 2: Total Work Orders / SPK --}}
        <div class="bg-white dark:bg-slate-800 rounded-sm p-2.5 border border-slate-300 dark:border-slate-700 flex items-center justify-between shadow-2xs hover:border-emerald-400 transition-colors">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Work Orders (SPK)</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalWorkOrders) }}</span>
                    <span class="text-[10px] font-semibold text-blue-600 dark:text-blue-400">{{ $activeWorkOrders }} active</span>
                </div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">
                    <span>Completed / Approved: <b>{{ $completedWorkOrders }}</b></span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-sm bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-file-signature text-xs"></i>
            </div>
        </div>

        {{-- KPI 3: EBD Headers --}}
        <div class="bg-white dark:bg-slate-800 rounded-sm p-2.5 border border-slate-300 dark:border-slate-700 flex items-center justify-between shadow-2xs hover:border-indigo-400 transition-colors">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">EBD Breakdown</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalEbdHeaders) }}</span>
                    <span class="text-[10px] text-slate-400">headers</span>
                </div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">
                    <span>Engineering data</span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-sm bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-layer-group text-xs"></i>
            </div>
        </div>

        {{-- KPI 4: Tooling Quotation --}}
        <div class="bg-white dark:bg-slate-800 rounded-sm p-2.5 border border-slate-300 dark:border-slate-700 flex items-center justify-between shadow-2xs hover:border-violet-400 transition-colors">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Tooling Quotation</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalToolingQuotations) }}</span>
                    <span class="text-[10px] text-slate-400">quotes</span>
                </div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">
                    <span>Supplier evaluation</span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-sm bg-violet-50 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-800/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-scale-balanced text-xs"></i>
            </div>
        </div>

        {{-- KPI 5: Priority Attention --}}
        <div class="bg-white dark:bg-slate-800 rounded-sm p-2.5 border border-slate-300 dark:border-slate-700 flex items-center justify-between shadow-2xs hover:border-rose-400 transition-colors col-span-2 md:col-span-1">
            <div class="space-y-0.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Urgent / High SPK</span>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-black text-rose-600 dark:text-rose-400 leading-none">{{ number_format($urgentPriorityCount) }}</span>
                    <span class="text-[10px] text-slate-400">needs action</span>
                </div>
                <div class="text-[10px] text-slate-500 dark:text-slate-400">
                    <span>Tight schedule</span>
                </div>
            </div>
            <div class="w-8 h-8 rounded-sm bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-triangle-exclamation text-xs"></i>
            </div>
        </div>

    </div>

    {{-- Main Workspace Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 flex-1 min-h-0">
        
        {{-- Left 7 Columns: Recent Feasibility Projects Table --}}
        <div class="lg:col-span-7 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex flex-col min-h-0 overflow-hidden shadow-2xs">
            <div class="flex items-center justify-between px-3.5 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-blue-600 dark:text-blue-400 text-xs"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">Recent Projects & Inquiries</h2>
                </div>
                <a href="{{ route('management.inquiry.index') }}" class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                    View All <i class="fa-solid fa-arrow-right text-[9px] ml-0.5"></i>
                </a>
            </div>

            <div class="flex-1 overflow-y-auto min-h-0">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-10">
                        <tr>
                            <th class="py-2 px-3">Inquiry & Project</th>
                            <th class="py-2 px-2.5">Customer</th>
                            <th class="py-2 px-2.5">Model Part</th>
                            <th class="py-2 px-2 text-center">Related WO</th>
                            <th class="py-2 px-2.5 text-center">Status</th>
                            <th class="py-2 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @forelse($recentInquiries as $inq)
                            @php
                                $statusBadge = match(strtoupper($inq->status ?? 'DRAFT')) {
                                    'APPROVED', 'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
                                    'IN REVIEW', 'IN_REVIEW', 'PROCESS' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
                                    'DRAFT' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors">
                                <td class="py-2 px-3">
                                    <div class="font-bold text-slate-800 dark:text-white text-[11px]">{{ $inq->inquiry_no }}</div>
                                    <div class="text-[10px] text-slate-500 truncate max-w-[180px]">{{ $inq->project_name }}</div>
                                </td>
                                <td class="py-2 px-2.5">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300 text-[11px]">{{ $inq->customer->code ?? '-' }}</span>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[90px]">{{ $inq->customer->name ?? '' }}</div>
                                </td>
                                <td class="py-2 px-2.5">
                                    <span class="inline-block px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-sm text-[10px] font-medium truncate max-w-[100px]">
                                        {{ $inq->projectModel->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-2 px-2 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 font-bold rounded-sm text-[10px] border border-blue-200 dark:border-blue-800">
                                        {{ $inq->workOrders->count() }} WO
                                    </span>
                                </td>
                                <td class="py-2 px-2.5 text-center">
                                    <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-sm border {{ $statusBadge }}">
                                        {{ $inq->status }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right">
                                    <a href="{{ route('management.inquiry.show', $inq->hashed_id) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700">
                                        Details <i class="fa-solid fa-chevron-right text-[8px]"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">
                                    <i class="fa-solid fa-folder-open text-xl mb-1 text-slate-300 dark:text-slate-600"></i>
                                    <p class="text-xs font-medium">No inquiry records found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Right 5 Columns: Feasibility Pipeline, SPK Types & Urgent Alerts --}}
        <div class="lg:col-span-5 flex flex-col gap-2.5 min-h-0">
            
            {{-- Stage Funnel Summary --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-2.5 shadow-2xs shrink-0">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Feasibility Study Workflow</span>
                    <span class="text-[10px] text-slate-400 font-medium">5 Core Stages</span>
                </div>
                <div class="grid grid-cols-5 gap-1.5 text-center">
                    @foreach($pipelineStages as $stage)
                        <div class="p-1.5 rounded-sm border border-slate-200 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-900/30 flex flex-col items-center justify-center">
                            <span class="text-xs font-black text-slate-800 dark:text-white leading-none">{{ number_format($stage['count']) }}</span>
                            <span class="text-[9px] font-medium text-slate-500 dark:text-slate-400 mt-1 leading-tight truncate w-full">{{ $stage['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- SPK Type Distribution --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-3 shadow-2xs shrink-0 space-y-1.5">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-1">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-800 dark:text-white">SPK / Work Order Categories</h3>
                    <i class="fa-solid fa-diagram-project text-slate-400 text-xs"></i>
                </div>

                <div class="space-y-1.5">
                    @foreach($spkDistribution as $typeName => $info)
                        @php
                            $pct = $totalWorkOrders > 0 ? round(($info['count'] / $totalWorkOrders) * 100) : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-[10px] font-medium text-slate-700 dark:text-slate-300 mb-0.5">
                                <span>{{ $typeName }}</span>
                                <span class="font-bold">{{ $info['count'] }} ({{ $pct }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="{{ $info['color'] }} h-full rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Urgent & High Priority SPK Alert Box --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex flex-col min-h-0 overflow-hidden shadow-2xs flex-1">
                <div class="flex items-center justify-between px-3 py-1.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 shrink-0">
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-xs"></i>
                        <h2 class="text-[11px] font-bold uppercase tracking-wider text-slate-800 dark:text-white">Urgent / High Priority SPK</h2>
                    </div>
                    <a href="{{ route('management.work-order.index') }}" class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                        View All <i class="fa-solid fa-arrow-right text-[8px] ml-0.5"></i>
                    </a>
                </div>

                <div class="p-2 overflow-y-auto flex-1 space-y-1.5 min-h-0">
                    @forelse($priorityWorkOrders as $wo)
                        @php
                            $badgeCls = $wo->priority === 'URGENT' 
                                ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
                                : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
                        @endphp
                        <div class="p-2 bg-slate-50/70 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80 rounded-sm flex items-center justify-between gap-2 text-xs">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-[11px] text-slate-800 dark:text-white truncate">{{ $wo->wo_number }}</span>
                                    <span class="px-1 py-0.2 text-[8px] font-extrabold uppercase rounded-sm border shrink-0 {{ $badgeCls }}">{{ $wo->priority }}</span>
                                </div>
                                <div class="text-[10px] text-slate-500 truncate mt-0.5">Target: {{ $wo->due_date_plan ? $wo->due_date_plan->format('d M Y') : '-' }} | Status: {{ $wo->status }}</div>
                            </div>
                            <a href="{{ route('management.work-order.show', $wo->hashed_id) }}" class="w-6 h-6 rounded-sm bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-blue-600 shrink-0 shadow-2xs">
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </a>
                        </div>
                    @empty
                        <div class="py-3 text-center text-slate-400">
                            <i class="fa-solid fa-circle-check text-base text-emerald-500 mb-0.5"></i>
                            <p class="text-[10px] font-medium">No urgent priority work orders pending.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
