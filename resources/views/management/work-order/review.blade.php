@extends('layouts.app')

@section('title', 'Review Work Order (SPK) · Promise Management')

@section('content')
<style>
    .split-container {
        display: flex;
        height: calc(100vh - 64px);
        margin-top: 64px;
        overflow: hidden;
        background-color: #f8fafc;
    }
    .dark .split-container {
        background-color: #0f172a;
    }
    
    .spk-form-panel {
        width: 50%;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-right: 1px solid #e2e8f0;
        background-color: #ffffff;
        transition: width 0.2s ease-in-out;
    }
    .dark .spk-form-panel {
        border-right-color: #1e293b;
        background-color: #0f172a;
    }
    
    .spk-preview-panel {
        width: 50%;
        height: 100%;
        overflow-y: auto;
        background-color: #f1f5f9;
        transition: width 0.2s ease-in-out;
    }
    .dark .spk-preview-panel {
        background-color: #1e293b;
    }

    .form-card {
        background-color: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 2px;
        padding: 20px;
        box-shadow: none;
    }
    .dark .form-card {
        background-color: #1e293b;
        border-color: #334155;
    }
    
    .info-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 2px;
    }
    .dark .info-label {
        color: #94a3b8;
    }

    .info-value {
        font-size: 0.8rem;
        font-weight: 600;
        color: #0f172a;
    }
    .dark .info-value {
        color: #f1f5f9;
    }

    .spk-paper {
        background: #fff;
        border: 1px solid #334155;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        font-family: 'Times New Roman', Times, serif;
        color: #000;
    }

    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background: white !important;
            color: black !important;
        }
        .split-container {
            margin-top: 0 !important;
            height: auto !important;
            overflow: visible !important;
        }
        .spk-preview-panel {
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            background: white !important;
            padding: 0 !important;
        }
        .spk-paper {
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
            padding: 20mm !important;
        }
    }
</style>

<x-sweetalert />

<div class="split-container" x-data="spkReview" id="spkReviewContainer">
    
    {{-- ── LEFT SIDE: Read-Only Detail & Approvals ────────────────────── --}}
    <div class="spk-form-panel no-print" :style="showPreview ? 'width: 50%' : 'width: 100%'">
        
        {{-- Fixed Header --}}
        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-10 flex-none">
            <div class="flex items-center gap-3">
                <a href="{{ route('management.work-order.approval-inbox') }}"
                   class="flex items-center justify-center w-7 h-7 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-500 hover:text-blue-600 hover:border-blue-500 transition-colors text-xs rounded-xs"
                   title="Back to Inbox">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-md font-black tracking-tight text-slate-800 dark:text-white flex items-center gap-1.5">
                        <span>Work Order Review</span>
                        <span class="text-xs bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 px-2 py-0.5 font-bold uppercase">{{ $workOrder->status }}</span>
                    </h2>
                    <p class="text-[10px] text-slate-400">Review SPK specifications and process details to submit your approval.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" @click="showPreview = !showPreview"
                        class="flex items-center justify-center gap-1.5 px-2.5 h-7 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 transition-colors text-xs font-semibold rounded-xs"
                        :title="showPreview ? 'Hide Preview' : 'Show Preview'">
                    <i class="fa-solid" :class="showPreview ? 'fa-eye-slash' : 'fa-eye'"></i>
                    <span x-text="showPreview ? 'Hide' : 'Preview'"></span>
                </button>
            </div>
        </div>

        {{-- Scrollable Content --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            
            {{-- Approval Progress Section --}}
            @php
                $approvals    = $workOrder->approvals->sortBy('approval_level');
                $pendingStep  = $approvals->firstWhere('status', 'Pending');
                $user         = auth()->user();
                $canActOnThis = false;
                if ($pendingStep) {
                    $rule = \App\Models\ApprovalRule::activeFor('SPK')
                        ->where('approval_level', $pendingStep->approval_level)
                        ->first();
                    $canActOnThis = $rule ? $rule->canBeApprovedBy($user) : true;
                }
            @endphp
            <div class="border border-slate-200 dark:border-slate-700 rounded-xs overflow-hidden">
                <div class="px-4 py-2.5 bg-amber-50 dark:bg-amber-950/20 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-500 text-xs"></i>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Approval Steps</span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @foreach($approvals as $step)
                        @php
                            $stepColor = match($step->status) {
                                'Approved' => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-750 dark:text-emerald-400', 'badge' => 'bg-emerald-50 border-emerald-250 text-emerald-750 dark:bg-emerald-950/30 dark:border-emerald-900/60 dark:text-emerald-400'],
                                'Pending'  => ['dot' => 'bg-amber-500 animate-pulse', 'text' => 'text-amber-700 dark:text-amber-400', 'badge' => 'bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-400'],
                                'Rejected' => ['dot' => 'bg-rose-500', 'text' => 'text-rose-600 dark:text-rose-400', 'badge' => 'bg-rose-50 border-rose-250 text-rose-600 dark:bg-rose-950/30 dark:border-rose-900/60 dark:text-rose-400'],
                                default    => ['dot' => 'bg-slate-300 dark:bg-slate-600', 'text' => 'text-slate-400', 'badge' => 'bg-slate-100 border-slate-200 text-slate-400 dark:bg-slate-800 dark:border-slate-700'],
                            };
                        @endphp
                        <div class="px-4 py-3 flex items-start gap-3 {{ $step->status === 'Pending' ? 'bg-amber-50/40 dark:bg-amber-950/10' : '' }}">
                            <div class="flex flex-col items-center gap-1 pt-0.5">
                                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $stepColor['dot'] }}"></span>
                                <span class="text-[9px] font-black text-slate-400">L{{ $step->approval_level }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $step->approver_position }}</span>
                                        <span class="text-[10px] text-slate-400 ml-1.5">({{ $step->department->name ?? '—' }})</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-xs border {{ $stepColor['badge'] }}">
                                        {{ $step->status }}
                                    </span>
                                </div>
                                @if($step->approver_name)
                                    <div class="text-[10px] text-slate-500 mt-0.5">
                                        <i class="fa-solid fa-user text-[8px] mr-1"></i>{{ $step->approver_name }}
                                        @if($step->approved_at)
                                            · <span class="text-slate-455">{{ $step->approved_at->format('d M Y, H:i') }}</span>
                                        @endif
                                    </div>
                                @endif
                                @if($step->remarks)
                                    <div class="text-[10px] italic text-slate-400 mt-0.5">"{{ $step->remarks }}"</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 1. General Information Card --}}
            <div class="form-card space-y-4">
                <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-800">
                    <i class="fa-solid fa-circle-info text-blue-500 mr-1.5"></i> 1. General Information
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="info-label">SPK Number</div>
                        <div class="info-value font-mono text-blue-600 dark:text-blue-400">{{ $workOrder->wo_number }}</div>
                    </div>
                    <div>
                        <div class="info-label">Publish Date</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($workOrder->publish_date)->format('d M Y') }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <div>
                        <div class="info-label">First Sample Date</div>
                        <div class="info-value">{{ $workOrder->first_sample_date ? \Carbon\Carbon::parse($workOrder->first_sample_date)->format('d M Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Due Date (Plan)</div>
                        <div class="info-value">{{ $workOrder->due_date_approval ? \Carbon\Carbon::parse($workOrder->due_date_approval)->format('d M Y') : '—' }}</div>
                    </div>
                    <div>
                        <div class="info-label">Due Date Closed</div>
                        <div class="info-value">{{ $workOrder->due_date_closed ? \Carbon\Carbon::parse($workOrder->due_date_closed)->format('d M Y') : '—' }}</div>
                    </div>
                </div>
            </div>

            {{-- 2. Priority & Processes --}}
            <div class="form-card space-y-4">
                <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-800">
                    <i class="fa-solid fa-list-check text-blue-500 mr-1.5"></i> 2. Priority &amp; Processes
                </h3>
                <div>
                    <div class="info-label">Priority</div>
                    <span class="inline-block mt-1 px-3 py-1 text-xs font-bold border rounded-xs 
                        {{ $workOrder->priority === 'URGENT' ? 'bg-rose-50 border-rose-200 text-rose-700 dark:bg-rose-950/30' : 'bg-slate-50 border-slate-200 text-slate-700 dark:bg-slate-800' }}">
                        {{ $workOrder->priority }}
                    </span>
                </div>
                <div class="border-t border-slate-100 dark:border-slate-800 pt-3">
                    <div class="info-label mb-2">Process Checklist</div>
                    <div class="space-y-2">
                        @foreach($workOrder->processes as $proc)
                            @php
                                $assignedDepts = json_decode($proc->pivot->assigned_departments ?? '[]') ?: [];
                                $deptNames = \App\Models\Department::whereIn('id', $assignedDepts)->pluck('name')->implode(', ');
                            @endphp
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">&check; {{ $proc->process_name }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">Depts: {{ $deptNames ?: '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 3. Product Specifications (BOM) --}}
            <div class="form-card space-y-4">
                <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-800">
                    <i class="fa-solid fa-boxes-stacked text-blue-500 mr-1.5"></i> 3. Product Specifications (BOM)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                <th class="p-2">Part Number / Name</th>
                                <th class="p-2 w-16 text-center">EO</th>
                                <th class="p-2 w-20 text-center">Class ID</th>
                                <th class="p-2 w-16 text-center">UOM</th>
                                <th class="p-2">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($workOrder->products as $prod)
                                <tr class="border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                    <td class="p-2">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $prod->inquiryProduct->customer_part_no ?? '—' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $prod->inquiryProduct->customer_part_name ?? '—' }}</div>
                                    </td>
                                    <td class="p-2 text-center font-mono font-bold text-slate-700 dark:text-slate-350">{{ $prod->eo ?: '-' }}</td>
                                    <td class="p-2 text-center">
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-blue-50 dark:bg-blue-950/20 text-blue-650 rounded-xs border border-blue-200 dark:border-blue-900">
                                            {{ $prod->class_id }}
                                        </span>
                                    </td>
                                    <td class="p-2 text-center text-slate-700 dark:text-slate-350">{{ $prod->uom }}</td>
                                    <td class="p-2 text-slate-650 dark:text-slate-400">{{ $prod->remarks ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($workOrder->remarks)
                <div class="form-card space-y-2">
                    <div class="info-label">Additional SPK Notes</div>
                    <p class="text-xs italic text-slate-600 dark:text-slate-400">"{{ $workOrder->remarks }}"</p>
                </div>
            @endif

        </div>

        {{-- Fixed Footer for Actions --}}
        <div class="p-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-900/60 z-10 flex-none">
            <a href="{{ route('management.work-order.approval-inbox') }}"
               class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xs transition-colors">
                Back to Inbox
            </a>
            
            {{-- Review / Action Form --}}
            @if($pendingStep && $canActOnThis)
                <div class="flex items-center gap-3">
                    <form action="{{ route('management.work-order.approve', $workOrder->id) }}" method="POST" id="approveForm" class="flex items-center gap-2">
                        @csrf
                        <input type="text" name="remarks" placeholder="Add comments..."
                               class="px-2.5 py-1.5 text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-xs w-48 focus:outline-none focus:border-blue-500">
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xs transition-colors cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-check"></i> Approve
                        </button>
                    </form>
                    <form action="{{ route('management.work-order.reject', $workOrder->id) }}" method="POST"
                          onsubmit="return confirm('Reject this SPK? It will be returned to Draft.')">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xs transition-colors cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-xmark"></i> Reject
                        </button>
                    </form>
                </div>
            @else
                <div class="text-xs text-slate-450 italic">
                    @if($pendingStep)
                        Waiting for <strong>{{ $pendingStep->approver_position }}</strong> approval
                    @else
                        This document has been fully processed
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ── RIGHT SIDE: Document Preview ──────────────────────────────── --}}
    @include('management.work-order.preview')
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('spkReview', () => ({
        showPreview: true,
        document_no: @json($workOrder->document_no ?? ($woHeader->document_no ?? 'FO-13-02')),
        doc_department: @json($workOrder->doc_department ?? ($woHeader->doc_department ?? 'Sales')),
        publish_date: @json($workOrder->publish_date ? (is_string($workOrder->publish_date) ? substr($workOrder->publish_date, 0, 10) : $workOrder->publish_date->format('Y-m-d')) : now()->format('Y-m-d')),
        work_order_no: @json($workOrder->wo_number),
        department_id: @json($workOrder->department_id),
        priority: @json($workOrder->priority),
        selected_processes: @json($workOrder->processes->pluck('id')),
        page_hal: @json($workOrder->page_hal ?? ($woHeader->page_hal ?? '1')),
        revision_no: {{ $workOrder->revision_no ?? 0 }},
        doc_revision_no: @json($woHeader->revision_no ?? 0),
        first_sample_date: @json($workOrder->first_sample_date ? (is_string($workOrder->first_sample_date) ? substr($workOrder->first_sample_date, 0, 10) : $workOrder->first_sample_date->format('Y-m-d')) : ''),
        due_date_approval: @json($workOrder->due_date_approval ? (is_string($workOrder->due_date_approval) ? substr($workOrder->due_date_approval, 0, 10) : $workOrder->due_date_approval->format('Y-m-d')) : ''),
        due_date_closed: @json($workOrder->due_date_closed ? (is_string($workOrder->due_date_closed) ? substr($workOrder->due_date_closed, 0, 10) : $workOrder->due_date_closed->format('Y-m-d')) : ''),
        remarks: @json($workOrder->remarks ?? ""),
        selected_approval_rules: @json($workOrder->selected_approval_rule_ids ?: $approvalRules->filter(fn($r) => is_array($r->approver_user_ids) && count($r->approver_user_ids) > 0)->pluck('id')),
        products: @json($workOrder->products).map(p => ({
            work_order_product_id: p.id ?? null,
            inquiry_product_id: p.inquiry_product_id,
            customer_code: '{{ $workOrder->inquiry->customer->code ?? "" }}',
            model_name: p.model_name ?? '',
            customer_part_no: p.customer_part_no ?? '',
            customer_part_name: p.customer_part_name ?? '',
            destination: p.destination ?? '',
            sop_date: p.sop_date ? (typeof p.sop_date === 'string' ? p.sop_date.substring(0, 10) : p.sop_date) : '',
            eol_date: p.eol_date ? (typeof p.eol_date === 'string' ? p.eol_date.substring(0, 10) : p.eol_date) : '',
            model_life: p.model_life ?? '',
            annual_volume: p.annual_volume ?? '',
            eo: p.eo ?? '-',
            class_id: p.class_id ?? 'FG',
            uom: p.uom ?? 'Kg',
            variant: p.variant ?? '',
            remarks: p.remarks ?? ''
        })),
        computedDocRevisionNo() {
            return String(this.doc_revision_no).padStart(2, '0');
        },
        getUniqueSchedules() {
            let uniqueList = [];
            let seen = new Set();
            this.products.forEach(p => {
                let key = (p.sop_date || '') + '_' + (p.variant || '') + '_' + (p.annual_volume || '');
                if (!seen.has(key)) {
                    seen.add(key);
                    uniqueList.push({
                        sop_date: p.sop_date,
                        variant: p.variant,
                        annual_volume: p.annual_volume
                    });
                }
            });
            return uniqueList;
        },
        formatDateStr(d) {
            if (!d) return '—';
            let parts = d.split('-');
            if (parts.length !== 3) return d;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            let y = parts[0];
            let m = months[parseInt(parts[1], 10) - 1] || parts[1];
            let day = parts[2];
            return `${day}-${m}-${y}`;
        },
        getDepartmentName(id) {
            const depts = {
                @foreach($departments as $d)
                    '{{ $d->id }}': '{{ $d->name }}',
                @endforeach
            };
            return depts[id] || '—';
        },
        processesList: @json($processes),
        support_departments: @json($workOrder->ownerDepartment ? [$workOrder->ownerDepartment] : []),
    }));
});
</script>
@endsection
