@extends('layouts.app')

@section('title', 'Dashboard · Feasibility Study Overview')

@section('content')
{{-- Main Dashboard Container (1 Screen Fit, Clean & Intuitive Layout) --}}
<div class="flex-1 flex flex-col p-4 pt-17 gap-3 overflow-hidden transition-colors duration-200" style="height: 100vh;">

    {{-- ── 1. TOP ROW: Clean Title (No Card) + 5 Compact KPI Cards ── --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 shrink-0">
        
        {{-- Plain Text Title (No Card Box) --}}
        <div class="shrink-0 lg:pr-2">
            <h1 class="text-base font-extrabold text-slate-800 dark:text-white tracking-tight leading-tight">Overview Feasibility</h1>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">A quick glimpse of your project metrics.</p>
        </div>

        {{-- 5 KPI Stat Cards (High Contrast & Clear Typography) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 flex-1">
            
            {{-- KPI 1: Inquiries --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-2.5 flex items-center gap-2.5 shadow-2xs hover:border-blue-400 transition-colors">
                <div class="w-8 h-8 rounded-sm bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-folder-open text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 block truncate">Total Inquiries</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-base font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalInquiries) }}</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate font-medium">{{ $activeInquiries }} active</span>
                    </div>
                </div>
            </div>

            {{-- KPI 2: Work Orders --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-2.5 flex items-center gap-2.5 shadow-2xs hover:border-emerald-400 transition-colors">
                <div class="w-8 h-8 rounded-sm bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-signature text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 block truncate">Work Orders</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-base font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalWorkOrders) }}</span>
                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold truncate">{{ $activeWorkOrders }} running</span>
                    </div>
                </div>
            </div>

            {{-- KPI 3: EBD Breakdown --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-2.5 flex items-center gap-2.5 shadow-2xs hover:border-indigo-400 transition-colors">
                <div class="w-8 h-8 rounded-sm bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-layer-group text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 block truncate">EBD Breakdown</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-base font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalEbdHeaders) }}</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate font-medium">Doc</span>
                    </div>
                </div>
            </div>

            {{-- KPI 4: Tooling Quotes --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-2.5 flex items-center gap-2.5 shadow-2xs hover:border-violet-400 transition-colors">
                <div class="w-8 h-8 rounded-sm bg-violet-50 dark:bg-violet-950/50 border border-violet-200 dark:border-violet-800/60 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-scale-balanced text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 block truncate">Tooling Quotes</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-base font-black text-slate-800 dark:text-white leading-none">{{ number_format($totalToolingQuotations) }}</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate font-medium">Quotes</span>
                    </div>
                </div>
            </div>

            {{-- KPI 5: Urgent SPK --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-2.5 flex items-center gap-2.5 shadow-2xs hover:border-rose-400 transition-colors col-span-2 sm:col-span-1">
                <div class="w-8 h-8 rounded-sm bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 block truncate">Urgent SPK</span>
                    <div class="flex items-baseline gap-1.5 mt-0.5">
                        <span class="text-base font-black text-rose-600 dark:text-rose-400 leading-none">{{ number_format($urgentCount) }}</span>
                        <span class="text-[10px] text-rose-600 dark:text-rose-400 truncate font-bold">Action</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- ── 2. MAIN WORKSPACE (Focused on Core Feasibility Monitoring) ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 flex-1 min-h-0">

        {{-- LEFT (8 cols): Primary Table of Feasibility Projects & Inquiries --}}
        <div class="lg:col-span-8 bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex flex-col min-h-0 shadow-2xs overflow-hidden">
            <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-blue-600 dark:text-blue-400 text-xs"></i>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">Feasibility Study Projects Monitoring</h2>
                </div>
                <a href="{{ route('management.inquiry.index') }}" class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                    View All Inquiries <i class="fa-solid fa-arrow-right text-[9px] ml-0.5"></i>
                </a>
            </div>

            <div class="flex-1 overflow-y-auto min-h-0">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-10">
                        <tr>
                            <th class="py-2.5 px-3.5">Inquiry & Project</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3">Model</th>
                            <th class="py-2.5 px-2.5 text-center">SPK</th>
                            <th class="py-2.5 px-2.5 text-center">Status</th>
                            <th class="py-2.5 px-3.5 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @forelse($recentInquiries as $inq)
                            @php
                                $badge = match(strtoupper($inq->status ?? 'DRAFT')) {
                                    'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
                                    'COMPLETED' => 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:border-teal-800',
                                    'IN REVIEW', 'IN_REVIEW', 'PROCESS' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800',
                                    'DRAFT' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
                                    'REJECTED', 'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800'
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="py-2.5 px-3.5">
                                    <a href="{{ route('management.inquiry.show', $inq->hashed_id) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline text-[11px] block">{{ $inq->inquiry_no }}</a>
                                    <div class="text-[10px] text-slate-500 truncate max-w-[220px]">{{ $inq->project_name }}</div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="font-semibold text-slate-700 dark:text-slate-300 text-[11px]">{{ $inq->customer->code ?? '-' }}</span>
                                    <div class="text-[9px] text-slate-400 truncate max-w-[110px]">{{ $inq->customer->name ?? '' }}</div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-sm text-[10px] font-medium truncate inline-block max-w-[110px]">
                                        {{ $inq->projectModel->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-2.5 text-center">
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 font-bold rounded-sm text-[10px] border border-blue-200 dark:border-blue-800">
                                        {{ $inq->workOrders->count() }} WO
                                    </span>
                                </td>
                                <td class="py-2.5 px-2.5 text-center">
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold uppercase rounded-sm border {{ $badge }}">
                                        {{ $inq->status ?? 'DRAFT' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3.5 text-right text-[10px] text-slate-400">
                                    {{ $inq->inquiry_date ? $inq->inquiry_date->format('d M Y') : $inq->created_at->format('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <i class="fa-solid fa-folder-open text-2xl mb-1 text-slate-300 dark:text-slate-600"></i>
                                    <p class="text-xs font-medium">No project inquiries found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- RIGHT (4 cols): Work Breakdown & Urgent Action Items --}}
        <div class="lg:col-span-4 flex flex-col gap-3 min-h-0">

            {{-- 1. SPK / Work Order Type Distribution (Direct & Clear Info) --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 p-3 shadow-2xs shrink-0 space-y-2">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-2">
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-diagram-project text-blue-600 dark:text-blue-400 text-xs"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">SPK / Work Order Categories</h3>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-400">{{ $totalWorkOrders }} Total</span>
                </div>

                <div class="space-y-2 pt-0.5">
                    @foreach($spkTypeData as $typeName => $count)
                        @php
                            $pct = $totalWorkOrders > 0 ? round(($count / $totalWorkOrders) * 100) : 0;
                            $barColor = match($typeName) {
                                'SPK 1 Main' => 'bg-blue-600',
                                'SPK 2 Fastener' => 'bg-emerald-500',
                                'SPK 2 Add Process' => 'bg-amber-500',
                                'SPK 2 Tooling' => 'bg-indigo-500',
                                default => 'bg-cyan-500'
                            };
                        @endphp
                        <div>
                            <div class="flex justify-between text-[11px] font-medium text-slate-700 dark:text-slate-300 mb-1">
                                <span class="truncate">{{ $typeName }}</span>
                                <span class="font-bold shrink-0">{{ $count }} <span class="text-[10px] text-slate-400 font-normal">({{ $pct }}%)</span></span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="{{ $barColor }} h-full rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 2. Urgent & High Priority Work Orders (Actionable List) --}}
            <div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 flex flex-col min-h-0 flex-1 shadow-2xs overflow-hidden">
                <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 shrink-0">
                    <div class="flex items-center gap-1.5">
                        <i class="fa-solid fa-triangle-exclamation text-rose-500 text-xs"></i>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-white">Urgent & High Priority SPK</h3>
                    </div>
                    <a href="{{ route('management.work-order.index') }}" class="text-[10px] font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                        View All <i class="fa-solid fa-arrow-right text-[8px] ml-0.5"></i>
                    </a>
                </div>

                <div class="p-2.5 space-y-2 overflow-y-auto flex-1 min-h-0">
                    @forelse($urgentWorkOrders as $wo)
                        @php
                            $pcls = $wo->priority === 'URGENT'
                                ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'
                                : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
                        @endphp
                        <div class="p-2 rounded-sm bg-slate-50/80 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700/80 text-xs flex items-center justify-between gap-2 hover:border-slate-400 transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('management.work-order.show', $wo->hashed_id) }}" class="font-bold text-[11px] text-slate-800 dark:text-white hover:text-blue-600 truncate">{{ $wo->wo_number }}</a>
                                    <span class="px-1 text-[8px] font-extrabold uppercase rounded-sm border shrink-0 {{ $pcls }}">{{ $wo->priority }}</span>
                                </div>
                                <div class="text-[10px] text-slate-500 truncate mt-0.5">
                                    {{ $wo->inquiry->customer->code ?? '-' }} · Target: <strong class="text-slate-700 dark:text-slate-300">{{ $wo->due_date_plan ? $wo->due_date_plan->format('d M Y') : 'No Target' }}</strong>
                                </div>
                            </div>
                            <span class="text-[9px] font-semibold px-1.5 py-0.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-600 dark:text-slate-300 shrink-0">
                                {{ $wo->status }}
                            </span>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-lg mb-1"></i>
                            <p class="text-[10px] font-medium">No urgent priority work orders.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
