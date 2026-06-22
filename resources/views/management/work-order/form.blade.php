@extends('layouts.app')

@section('title', isset($workOrder) ? 'Work Order Document · Promise Management' : 'Create Work Order (SPK) · Promise Management')

@section('content')
@php
    $isEditable = !isset($workOrder) || ($workOrder->status === 'Draft');
@endphp
<style>
    /* Premium layout styles for side-by-side creation */
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

    /* Premium inputs and components styling */
    .form-card {
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 2px;
        padding: 20px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease-in-out;
    }
    .dark .form-card {
        background-color: #1e293b;
        border-color: #334155;
        box-shadow: none;
    }
    .form-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    
    .form-input-premium {
        width: 100%;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 2px;
        padding: 8px 12px;
        font-size: 0.75rem;
        color: #0f172a;
        transition: all 0.2s ease;
    }
    .dark .form-input-premium {
        background-color: #0f172a;
        border-color: #475569;
        color: #f1f5f9;
    }
    .form-input-premium:focus {
        background-color: #ffffff;
        border-color: #64748b;
        outline: none;
        box-shadow: 0 0 0 1px #64748b;
    }
    .dark .form-input-premium:focus {
        background-color: #0f172a;
        border-color: #94a3b8;
        box-shadow: 0 0 0 1px #94a3b8;
    }
    .form-input-premium:disabled {
        background-color: #e2e8f0;
        color: #64748b;
        cursor: not-allowed;
    }
    .dark .form-input-premium:disabled {
        background-color: #1e293b;
        color: #475569;
    }

    .form-label-premium {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
        margin-bottom: 6px;
    }
    .dark .form-label-premium {
        color: #94a3b8;
    }

    /* Print/Paper document preview style */
    .spk-paper {
        background: #fff;
        border: 1px solid #334155;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        font-family: 'Times New Roman', Times, serif;
        color: #000;
        height: auto;
    }
    .dark .spk-paper {
        background: #fff;
        border-color: #475569;
        color: #000;
    }
    
    @media print {
        header, sidebar, nav, button, .no-print, .action-toolbar, .spk-form-panel {
            display: none !important;
        }
        body {
            background: white !important;
            color: black !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .split-container {
            margin-top: 0 !important;
            height: auto !important;
            overflow: visible !important;
            background: white !important;
        }
        .spk-preview-panel {
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .spk-paper {
            border: none !important;
            box-shadow: none !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 20mm !important;
            margin: 0 !important;
            height: auto !important;
            min-height: 297mm !important;
            page-break-after: avoid !important;
            break-after: auto !important;
        }
        .print-container {
            padding: 0 !important;
            margin: 0 !important;
        }
        table, tr {
            page-break-inside: avoid !important;
        }
    }
</style>

<div class="split-container" x-data="spkForm" id="spkFormContainer">
    
    {{-- ── LEFT SIDE: Detail Configuration Form ─────────────────────────── --}}
    <form action="{{ isset($workOrder) ? route('management.work-order.update', $workOrder->work_order_id) : route('management.work-order.store') }}" method="POST" 
          class="spk-form-panel" :style="showPreview ? 'width: 50%' : 'width: 100%'">
        @csrf
        @if(isset($workOrder))
            @method('PUT')
        @else
            <input type="hidden" name="inquiry_id" value="{{ $inquiry->inquiry_id }}">
        @endif
        <input type="hidden" name="department_id" :value="department_id">
        
        {{-- Fixed Header --}}
        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-10 flex-none">
            <div class="flex items-center gap-3">
                <a href="{{ isset($workOrder) ? route('management.work-order.index') : route('management.inquiry.show', $inquiry->inquiry_id) }}"
                   class="flex items-center justify-center w-7 h-7 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-500 hover:text-blue-600 hover:border-blue-500 transition-colors text-xs rounded-xs"
                   title="{{ isset($workOrder) ? 'Back to SPK List' : 'Back to Inquiry' }}">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-md font-black tracking-tight text-slate-800 dark:text-white flex items-center gap-1.5">
                        <span>{{ isset($workOrder) ? 'Work Order Detail' : 'Create Work Order (SPK)' }}</span>
                        @if(isset($workOrder))
                            <span class="text-xs bg-slate-100 text-slate-800 dark:bg-slate-900/60 dark:text-slate-300 px-2 py-0.5 font-bold uppercase">{{ $workOrder->status }}</span>
                        @endif
                    </h2>
                    <p class="text-[10px] text-slate-400">{{ isset($workOrder) ? 'View SPK specifications, process details, and BOM parts.' : 'Configure SPK details, assign departments, and manage BOM components.' }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 no-print">
                <button type="button" @click="showPreview = !showPreview"
                        class="flex items-center justify-center gap-1.5 px-2.5 h-7 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 transition-colors text-xs font-semibold rounded-xs"
                        :title="showPreview ? 'Hide Preview' : 'Show Preview'">
                    <i class="fa-solid" :class="showPreview ? 'fa-eye-slash' : 'fa-eye'"></i>
                    <span x-text="showPreview ? 'Hide' : 'Preview'"></span>
                </button>
                @if(isset($workOrder))
                    <button type="button" onclick="window.print()"
                            class="w-7 h-7 bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center text-xs shadow-xs transition-colors cursor-pointer rounded-xs"
                            title="Print SPK">
                        <i class="fa-solid fa-print"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Scrollable Container --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            @if(session('success'))
                <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-800 dark:text-emerald-400 text-xs border-l-4 border-emerald-500 rounded-r-xs">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-3.5 bg-rose-50 dark:bg-rose-950/20 text-rose-600 dark:text-rose-400 text-xs border-l-4 border-rose-500 rounded-r-xs">
                    {{ session('error') }}
                </div>
            @endif

            @if(isset($workOrder) && in_array($workOrder->status, ['Pending Approval', 'Approved']))
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
                    {{-- Header --}}
                    <div class="px-4 py-2.5 bg-{{ $workOrder->status === 'Approved' ? 'emerald' : 'amber' }}-50 dark:bg-{{ $workOrder->status === 'Approved' ? 'emerald' : 'amber' }}-950/20 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                        <i class="fa-solid fa-{{ $workOrder->status === 'Approved' ? 'circle-check text-emerald-500' : 'clock-rotate-left text-amber-500' }} text-xs"></i>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Approval Progress</span>
                        <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-xs border
                            {{ $workOrder->status === 'Approved' ? 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900' : 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800' }}">
                            {{ $workOrder->status }}
                        </span>
                    </div>

                    {{-- Sequential Steps --}}
                    <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @foreach($approvals as $step)
                            @php
                                $stepColor = match($step->status) {
                                    'Approved' => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-400', 'badge' => 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-900 dark:text-emerald-400'],
                                    'Pending'  => ['dot' => 'bg-amber-500 animate-pulse', 'text' => 'text-amber-700 dark:text-amber-400', 'badge' => 'bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-400'],
                                    'Rejected' => ['dot' => 'bg-rose-500', 'text' => 'text-rose-600 dark:text-rose-400', 'badge' => 'bg-rose-50 border-rose-200 text-rose-600 dark:bg-rose-950/30 dark:border-rose-900 dark:text-rose-400'],
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
                                                · <span class="text-slate-400">{{ $step->approved_at->format('d M Y, H:i') }}</span>
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

                    {{-- Approve / Reject Actions (only for authorized user on pending step) --}}
                    @if($pendingStep && $canActOnThis)
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2">
                            <span class="text-[10px] text-slate-500 flex-1">Your turn to review as <strong>{{ $pendingStep->approver_position }}</strong>:</span>
                            <form action="{{ route('management.work-order.approve', $workOrder->work_order_id) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <input type="text" name="remarks" placeholder="Optional comments..."
                                       class="px-2.5 py-1.5 text-[10px] border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-xs w-40 focus:outline-none focus:border-blue-400">
                                <button type="submit"
                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-xs transition-colors cursor-pointer flex items-center gap-1.5">
                                    <i class="fa-solid fa-check text-[9px]"></i> Approve
                                </button>
                            </form>
                            <form action="{{ route('management.work-order.reject', $workOrder->work_order_id) }}" method="POST"
                                  onsubmit="return confirm('Reject this SPK? It will be returned to Draft.')">
                                @csrf
                                <button type="submit"
                                        class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] rounded-xs transition-colors cursor-pointer flex items-center gap-1.5">
                                    <i class="fa-solid fa-xmark text-[9px]"></i> Reject
                                </button>
                            </form>
                        </div>
                    @elseif($pendingStep && !$canActOnThis)
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-700">
                            <p class="text-[10px] text-slate-400 italic flex items-center gap-1.5">
                                <i class="fa-solid fa-lock text-[9px]"></i>
                                Waiting for <strong class="text-slate-500">{{ $pendingStep->approver_position }}</strong> ({{ $pendingStep->department->name ?? '' }}) to approve.
                            </p>
                        </div>
                    @endif
                </div>
            @endif


        {{-- General Configuration Card --}}
        <div class="form-card space-y-4">
            <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> 1. General Information
            </h3>
            
            <div class="space-y-4">
                {{-- Document Header Configuration (Moved to Top) --}}
                <div class="pb-4 border-b border-slate-100 dark:border-slate-800 space-y-4">
                    <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Document Header Configuration</h4>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label-premium">Nomor Dokumen</label>
                            <input type="text" name="document_no" x-model="document_no" class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                        </div>
                        <div>
                            <label class="form-label-premium">Departemen (Header)</label>
                            <input type="text" name="doc_department" value="Sales" readonly class="form-input-premium bg-slate-100 dark:bg-slate-800 text-slate-500 cursor-not-allowed">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="form-label-premium">Tanggal Terbit</label>
                            <input type="date" name="publish_date" x-model="publish_date" class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                        </div>
                        <div>
                            <label class="form-label-premium">Nomor Revisi</label>
                            <input type="text" disabled :value="computedRevisionNo()" class="form-input-premium bg-slate-100 dark:bg-slate-800 text-slate-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="form-label-premium">Hal</label>
                            <input type="text" name="page_hal" x-model="page_hal" class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="form-label-premium">SPK Number <span class="text-rose-500">*</span></label>
                    <input type="text" name="work_order_no" x-model="work_order_no" required class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                </div>

                <div>
                    <label class="form-label-premium">Subject / Deskripsi SPK <span class="text-rose-500">*</span></label>
                    <input type="text" name="subject" x-model="subject" required class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                </div>
            </div>
        </div>

        {{-- Request Type Checklist & Priority Card --}}
        <div class="form-card space-y-4">
            <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> 2. Priority &amp; Request Type (Proses)
            </h3>
            
            <div class="space-y-4">
                <div class="space-y-2">
                    <label class="form-label-premium">Priority <span class="text-rose-500">*</span></label>
                    <select name="priority" x-model="priority" required class="form-input-premium cursor-pointer" {{ !$isEditable ? 'disabled' : '' }}>
                        <option value="URGENT">URGENT</option>
                        <option value="STANDARD">STANDARD</option>
                        <option value="LOW">LOW</option>
                    </select>
                </div>
                
                <div class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <label class="form-label-premium">Request Type / Process Checklist</label>
                    <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 text-xs text-slate-700 dark:text-slate-300">
                        @foreach($processes as $proc)
                            <label class="flex items-center gap-2.5 cursor-pointer p-1 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-xs transition-colors">
                                <input type="checkbox" name="processes[]" value="{{ $proc->process_id }}" 
                                       :checked="selected_processes.includes({{ $proc->process_id }})"
                                       @change="toggleProcess({{ $proc->process_id }})"
                                       class="rounded-xs border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-500"
                                       {{ !$isEditable ? 'disabled' : '' }}>
                                <span>{{ $proc->process_name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Support Departments --}}
        <div class="form-card space-y-4">
            <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> 3. Assignments
            </h3>
            
            <div class="space-y-4">
                {{-- Support Departments --}}
                <div>
                    <label class="form-label-premium">Support Departments (Departemen Pendukung)</label>
                    <select name="support_departments[]" multiple class="select2-support-depts w-full" {{ !$isEditable ? 'disabled' : '' }}>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}"
                                    {{ isset($workOrder) && $workOrder->supportDepartments->contains($dept->id) ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Products Schedule & Parts Configuration --}}
        <div class="space-y-4">
            <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-widest">4. Products &amp; Parts Details</h3>
            
            @php
                $formProducts = isset($workOrder) ? $workOrder->products : $inquiry->products;
            @endphp
            @foreach($formProducts as $index => $prod)
                <div class="form-card space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">
                            {{ $prod->model_name }} &middot; {{ $prod->customer_part_name }}
                        </span>
                        @if(isset($workOrder))
                            <input type="hidden" name="products[{{ $index }}][work_order_product_id]" value="{{ $prod->work_order_product_id }}">
                        @else
                            <input type="hidden" name="products[{{ $index }}][inquiry_product_id]" value="{{ $prod->inquiry_product_id }}">
                        @endif
                    </div>

                    {{-- Product schedule details --}}
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="form-label-premium text-[9px] mb-1">First Sample</label>
                            <input type="date" name="products[{{ $index }}][first_sample_date]"
                                   value="{{ $prod->first_sample_date ? (is_string($prod->first_sample_date) ? substr($prod->first_sample_date, 0, 10) : $prod->first_sample_date->format('Y-m-d')) : '' }}"
                                   @change="updateProductDate({{ $prod->inquiry_product_id }}, 'first_sample_date', $event.target.value)"
                                   class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                        </div>
                        <div>
                            <label class="form-label-premium text-[9px] mb-1">Due Date Approval</label>
                            <input type="date" name="products[{{ $index }}][due_date_approval]"
                                   value="{{ $prod->due_date_approval ? (is_string($prod->due_date_approval) ? substr($prod->due_date_approval, 0, 10) : $prod->due_date_approval->format('Y-m-d')) : '' }}"
                                   @change="updateProductDate({{ $prod->inquiry_product_id }}, 'due_date_approval', $event.target.value)"
                                   class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                        </div>
                        <div>
                            <label class="form-label-premium text-[9px] mb-1">Due Date Closed</label>
                            <input type="date" name="products[{{ $index }}][due_date_closed]"
                                   value="{{ $prod->due_date_closed ? (is_string($prod->due_date_closed) ? substr($prod->due_date_closed, 0, 10) : $prod->due_date_closed->format('Y-m-d')) : '' }}"
                                   @change="updateProductDate({{ $prod->inquiry_product_id }}, 'due_date_closed', $event.target.value)"
                                   class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                        </div>
                    </div>

                    <div>
                        <label class="form-label-premium text-[9px] mb-1">Product Remarks</label>
                        <input type="text" name="products[{{ $index }}][remarks]"
                               value="{{ $prod->remarks }}"
                               @input="updateProductRemarks({{ $prod->inquiry_product_id }}, $event.target.value)"
                               placeholder="Specific requirements/remarks for schedule"
                               class="form-input-premium" {{ !$isEditable ? 'disabled' : '' }}>
                    </div>

                    {{-- Parts configuration --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="form-label-premium text-[9px] mb-0">Parts List (BOM Components)</label>
                            @if($isEditable)
                                <button type="button" @click="addPartRow({{ $prod->inquiry_product_id }})"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xs text-[9px] font-bold transition-colors cursor-pointer">
                                    <i class="fa-solid fa-plus text-[8px]"></i> Add Part
                                </button>
                            @endif
                        </div>

                        <div class="border border-slate-100 dark:border-slate-800 rounded-xs overflow-x-auto">
                            <table class="w-full text-left text-[10px] divide-y divide-slate-100 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-900/60 font-bold text-slate-400">
                                    <tr>
                                        <th class="p-2 w-12">EO</th>
                                        <th class="p-2 w-28">Part Number</th>
                                        <th class="p-2">Part Name</th>
                                        <th class="p-2 w-14">Class</th>
                                        <th class="p-2 w-14">UOM</th>
                                        <th class="p-2">Remarks</th>
                                        @if($isEditable)
                                            <th class="p-2 w-6 text-center"></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="(part, idx) in parts[{{ $prod->inquiry_product_id }}]" :key="idx">
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30">
                                            <td class="p-1">
                                                <input type="text" :name="'parts[' + {{ $prod->inquiry_product_id }} + '][' + idx + '][eo]'" x-model="part.eo"
                                                       class="w-full bg-transparent border-0 p-1 text-[10px] text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-300 rounded-xs"
                                                       {{ !$isEditable ? 'disabled' : '' }}>
                                            </td>
                                            <td class="p-1">
                                                <input type="text" :name="'parts[' + {{ $prod->inquiry_product_id }} + '][' + idx + '][part_no]'" x-model="part.part_no" required
                                                       class="w-full bg-transparent border border-transparent hover:border-slate-200 dark:hover:border-slate-700 focus:border-slate-300 dark:focus:border-slate-600 p-1 text-[10px] text-slate-800 dark:text-slate-100 focus:outline-none rounded-xs"
                                                       {{ !$isEditable ? 'disabled' : '' }}>
                                            </td>
                                            <td class="p-1">
                                                <input type="text" :name="'parts[' + {{ $prod->inquiry_product_id }} + '][' + idx + '][part_name]'" x-model="part.part_name" required
                                                       class="w-full bg-transparent border border-transparent hover:border-slate-200 dark:hover:border-slate-700 focus:border-slate-300 dark:focus:border-slate-600 p-1 text-[10px] text-slate-800 dark:text-slate-100 focus:outline-none rounded-xs"
                                                       {{ !$isEditable ? 'disabled' : '' }}>
                                            </td>
                                            <td class="p-1">
                                                <select :name="'parts[' + {{ $prod->inquiry_product_id }} + '][' + idx + '][class_id]'" x-model="part.class_id"
                                                        class="w-full bg-transparent border border-transparent hover:border-slate-200 dark:hover:border-slate-700 focus:border-slate-300 dark:focus:border-slate-600 p-1 text-[10px] text-slate-800 dark:text-slate-100 focus:outline-none cursor-pointer rounded-xs"
                                                        {{ !$isEditable ? 'disabled' : '' }}>
                                                    <option value="FG">FG</option>
                                                    <option value="RM">RM</option>
                                                    <option value="SF">SF</option>
                                                </select>
                                            </td>
                                            <td class="p-1">
                                                <select :name="'parts[' + {{ $prod->inquiry_product_id }} + '][' + idx + '][uom]'" x-model="part.uom"
                                                        class="w-full bg-transparent border border-transparent hover:border-slate-200 dark:hover:border-slate-700 focus:border-slate-300 dark:focus:border-slate-600 p-1 text-[10px] text-slate-800 dark:text-slate-100 focus:outline-none cursor-pointer rounded-xs"
                                                        {{ !$isEditable ? 'disabled' : '' }}>
                                                    <option value="Kg">Kg</option>
                                                    <option value="Sheet">Sheet</option>
                                                    <option value="Pcs">Pcs</option>
                                                </select>
                                            </td>
                                            <td class="p-1">
                                                <input type="text" :name="'parts[' + {{ $prod->inquiry_product_id }} + '][' + idx + '][remarks]'" x-model="part.remarks"
                                                       class="w-full bg-transparent border border-transparent hover:border-slate-200 dark:hover:border-slate-700 focus:border-slate-300 dark:focus:border-slate-600 p-1 text-[10px] text-slate-800 dark:text-slate-100 focus:outline-none rounded-xs"
                                                       {{ !$isEditable ? 'disabled' : '' }}>
                                            </td>
                                            @if($isEditable)
                                                <td class="p-1 text-center">
                                                    <button type="button" @click="removePartRow({{ $prod->inquiry_product_id }}, idx)" class="text-rose-500 hover:text-rose-700">
                                                        <i class="fa-solid fa-trash-can text-[9px]"></i>
                                                    </button>
                                                </td>
                                            @endif
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- General Remarks --}}
        <div class="form-card space-y-3">
            <label class="form-label-premium">Catatan Tambahan SPK (General Remarks)</label>
            <textarea name="remarks" x-model="remarks" rows="3" placeholder="Enter any extra notes or instructions..."
                      class="form-input-premium resize-none" {{ !$isEditable ? 'disabled' : '' }}></textarea>
        </div>

        </div> {{-- End Scrollable Container --}}

        {{-- Fixed Footer --}}
        <div class="p-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2.5 bg-slate-50 dark:bg-slate-900/60 z-10 flex-none">
            <a href="{{ isset($workOrder) ? route('management.work-order.index') : route('management.inquiry.show', $inquiry->inquiry_id) }}"
               class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xs transition-colors">
                {{ isset($workOrder) ? 'Back to SPK List' : 'Back to Inquiry' }}
            </a>
            @if($isEditable)
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xs shadow-xs transition-colors cursor-pointer">
                    {{ isset($workOrder) ? 'Save Changes' : 'Save Work Order' }}
                </button>
                @if(isset($workOrder) && $workOrder->status === 'Draft')
                    <button type="submit" form="submitApprovalForm"
                            class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xs shadow-xs transition-colors cursor-pointer">
                        Submit for Approval
                    </button>
                @endif
            @endif
        </div>
    </form>

    @if(isset($workOrder) && $workOrder->status === 'Draft')
        <form id="submitApprovalForm" action="{{ route('management.work-order.submit', $workOrder->work_order_id) }}" method="POST" class="hidden">
            @csrf
        </form>
    @endif
    
    {{-- ── RIGHT SIDE: Live SPK Document Preview (MS Word Style) ────────── --}}
    <div class="spk-preview-panel p-8 flex flex-col items-center overflow-y-auto border-l border-slate-200 dark:border-slate-800 space-y-6"
         x-show="showPreview" style="width: 50%">
        
        {{-- UNIFIED SPK DOCUMENT --}}
        <div class="spk-paper w-full max-w-[760px] p-8 border shadow-lg flex flex-col justify-between flex-none min-h-[1075px]">
            <div class="space-y-6">
                {{-- Document Header Table --}}
                <table class="w-full border-collapse border border-slate-900 text-xs font-serif text-slate-900">
                    <tbody>
                        <tr class="divide-x divide-slate-900">
                            <td class="w-[30%] p-2 text-center border-b border-slate-900 align-middle">
                                <img src="{{ asset('assets/image/sai-logo.png') }}" class="h-12 mx-auto object-contain mb-1">
                                <div class="text-[9px] font-bold uppercase tracking-tight leading-none text-center">PT SUMMIT ADYAWINSA INDONESIA</div>
                            </td>
                            <td class="w-[45%] p-3 text-center font-extrabold text-sm uppercase tracking-wide border-b border-slate-900 align-middle">
                                SURAT PERINTAH KERJA
                            </td>
                            <td class="w-[25%] text-[10px] border-b border-slate-900 p-0">
                                <table class="w-full h-full text-[9px] border-collapse">
                                    <tbody>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900 w-[50%]">Nomor Dokumen</td>
                                            <td class="p-1">: <span x-text="document_no"></span></td>
                                        </tr>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900">Departemen</td>
                                            <td class="p-1">: <span x-text="doc_department"></span></td>
                                        </tr>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900">Tanggal Terbit</td>
                                            <td class="p-1">: <span x-text="formatDateStr(publish_date)"></span></td>
                                        </tr>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900">Nomor Revisi</td>
                                            <td class="p-1">: <span x-text="computedRevisionNo()"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="p-1 font-semibold border-r border-slate-900">Hal</td>
                                            <td class="p-1">: <span x-text="page_hal"></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
 
                {{-- SPK Info & Priority Block --}}
                <div class="flex justify-between items-start text-xs text-slate-900">
                    <div class="space-y-1">
                        <div>No. <span class="font-bold" x-text="work_order_no"></span></div>
                        <div>Kepada : <span class="font-bold" x-text="getDepartmentName(department_id) + (support_departments.length ? ' / ' + support_departments.map(d => d.name).join(', ') : '')"></span></div>
                    </div>
                    <div class="text-[10px] w-32">
                        <span class="block font-bold mb-1">Prioritas :</span>
                        <div class="space-y-0.5">
                            <label class="flex items-center gap-1.5 cursor-default">
                                <input type="checkbox" disabled :checked="priority === 'URGENT'" class="w-3.5 h-3.5 text-slate-900 border-slate-900 rounded-none">
                                <span class="font-semibold text-[10px]">URGENT</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-default">
                                <input type="checkbox" disabled :checked="priority === 'STANDARD'" class="w-3.5 h-3.5 text-slate-900 border-slate-900 rounded-none">
                                <span class="font-semibold text-[10px]">STANDARD</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-default">
                                <input type="checkbox" disabled :checked="priority === 'LOW'" class="w-3.5 h-3.5 text-slate-900 border-slate-900 rounded-none">
                                <span class="font-semibold text-[10px]">LOW</span>
                            </label>
                        </div>
                    </div>
                </div>
 
                {{-- Dengan hormat... --}}
                <div class="text-[10px] text-slate-900 space-y-1">
                    <p>Dengan hormat,</p>
                    <p>Mohon dibuatkan item dibawah ini :</p>
                    
                    <div class="grid grid-cols-3 gap-y-1 gap-x-2 py-1">
                        <template x-for="proc in processesList" :key="proc.process_id">
                            <template x-if="selected_processes.includes(proc.process_id)">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-black text-[10px]">&check;</span>
                                    <span class="text-[10px] text-black font-semibold" x-text="proc.process_name"></span>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>
 
                {{-- Attachments Block --}}
                <div class="p-2 border border-dashed border-slate-400 text-[10px] text-slate-600">
                    <span class="font-bold">Attachments:</span>
                    <ul class="list-disc list-inside ml-2">
                        <li>None</li>
                    </ul>
                </div>
 
                {{-- Table 1: Parts List (BOM) --}}
                <div class="text-[10px] text-slate-900">
                    <p class="font-bold mb-1">Data atau informasi yang bisa dilampirkan :</p>
                    <table class="w-full text-center border-collapse border border-slate-900 text-[10px]">
                        <thead>
                            <tr class="divide-x divide-slate-900 border-b border-slate-900 font-bold bg-slate-50/50">
                                <th class="p-1 w-6">No</th>
                                <th class="p-1 w-20">Customer</th>
                                <th class="p-1 w-16">Model</th>
                                <th class="p-1 w-8">EO</th>
                                <th class="p-1">Part Number</th>
                                <th class="p-1">Part Name</th>
                                <th class="p-1 w-10">Class ID</th>
                                <th class="p-1 w-10">UOM</th>
                                <th class="p-1">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="getAllParts().length === 0">
                                <tr>
                                    <td colspan="9" class="p-4 text-center text-slate-400 italic">No parts added. Add rows on the left side form.</td>
                                </tr>
                            </template>
                            <template x-for="(part, index) in getAllParts()" :key="index">
                                <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle">
                                    <td class="p-1" x-text="index + 1"></td>
                                    <td class="p-1" x-text="part.customer_name"></td>
                                    <td class="p-1" x-text="part.model_name"></td>
                                    <td class="p-1" x-text="part.eo"></td>
                                    <td class="p-1" x-text="part.part_no"></td>
                                    <td class="p-1 text-left" x-text="part.part_name"></td>
                                    <td class="p-1 font-bold text-black" x-text="part.class_id"></td>
                                    <td class="p-1" x-text="part.uom"></td>
                                    <td class="p-1 text-left" x-text="part.remarks || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Table 2: Schedule & Volume --}}
                <div class="text-[10px] text-slate-900 pt-2">
                    <p class="font-bold mb-1">Schedule &amp; Volume Target :</p>
                    <table class="w-full text-center border-collapse border border-slate-900 text-[10px]">
                        <thead>
                            <tr class="divide-x divide-slate-900 border-b border-slate-900 font-bold bg-slate-50/50">
                                <th class="p-1 w-6">No</th>
                                <th class="p-1">SOP</th>
                                <th class="p-1 w-20">Model Life</th>
                                <th class="p-1">First Sample</th>
                                <th class="p-1">DueDate Approval</th>
                                <th class="p-1">DueDate Closed</th>
                                <th class="p-1">Vol/Years</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(prod, index) in products" :key="index">
                                <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle">
                                    <td class="p-1" x-text="index + 1"></td>
                                    <td class="p-1 text-left">
                                        <span x-text="prod.destination ? (prod.destination + ' : ') : ''"></span>
                                        <span x-text="formatDateStr(prod.sop_date)"></span>
                                    </td>
                                    <td class="p-1" x-text="prod.model_life ? (prod.model_life + ' Years') : '—'"></td>
                                    <td class="p-1" x-text="formatDateStr(prod.first_sample_date) || '—'"></td>
                                    <td class="p-1 font-bold text-black" x-text="formatDateStr(prod.due_date_approval) || '—'"></td>
                                    <td class="p-1 font-bold text-black" x-text="formatDateStr(prod.due_date_closed) || '—'"></td>
                                    <td class="p-1" x-text="prod.annual_volume ? (Number(prod.annual_volume).toLocaleString() + ' /') : '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
 
                {{-- Remarks section --}}
                <div class="text-[10px] text-slate-900 border border-slate-900 p-2 bg-slate-50/50">
                    <span class="font-bold">Remarks:</span>
                    <span class="ml-1" x-text="remarks || '—'"></span>
                </div>
            </div>
 
            {{-- Signature Table (Approval Workflow) --}}
            @php
                // Build a lookup of approvals by level for quick access
                $approvalsByLevel = isset($workOrder)
                    ? $workOrder->approvals->keyBy('approval_level')
                    : collect();
                // Determine column width based on number of rules (min 2: Dibuat + at least 1 approver)
                $totalCols = max(2, $approvalRules->count() + 1);
                $colPct = round(100 / $totalCols) . '%';
            @endphp
            <div class="pt-6">
                <table class="w-full border-collapse border border-slate-900 text-[10px] text-slate-900 text-center">
                    <tbody>
                        {{-- Header row --}}
                        <tr class="divide-x divide-slate-900 font-bold bg-slate-50/50">
                            {{-- Dibuat column --}}
                            <td class="p-1 border-b border-slate-900" style="width: {{ $colPct }}">Dibuat</td>
                            {{-- Dynamic approver columns --}}
                            @forelse($approvalRules as $rule)
                                <td class="p-1 border-b border-slate-900" style="width: {{ $colPct }}">
                                    {{ $rule->approval_level === $approvalRules->last()->approval_level ? 'Disetujui' : 'Diketahui' }}
                                </td>
                            @empty
                                {{-- Fallback if no rules configured yet: show 2 placeholder columns --}}
                                <td class="p-1 border-b border-slate-900">Diketahui</td>
                                <td class="p-1 border-b border-slate-900">Disetujui</td>
                            @endforelse
                        </tr>
                        {{-- Signature body row --}}
                        <tr class="divide-x divide-slate-900" style="height: 100px">
                            {{-- Dibuat / Creator --}}
                            <td class="p-2 border-b border-slate-900 align-bottom relative">
                                <div class="absolute inset-x-0 top-2 text-center text-[9px] text-emerald-600 font-bold italic">APPROVED</div>
                                <div class="absolute inset-x-0 top-6 text-center text-[8px] text-slate-400">
                                    {{ isset($workOrder) ? $workOrder->created_at->format('d-M-Y H:i') : now()->format('d-M-Y H:i') }}
                                </div>
                                <div class="font-bold text-[10px]">{{ isset($workOrder) ? $workOrder->created_by : auth()->user()->name }}</div>
                                <div class="text-[8px] text-slate-400">Staff MKT</div>
                            </td>
                            {{-- Dynamic approver signature cells --}}
                            @forelse($approvalRules as $rule)
                                @php
                                    $levelApproval = $approvalsByLevel->get($rule->approval_level);
                                    $isApproved    = $levelApproval && $levelApproval->status === 'Approved';
                                    $isPending     = $levelApproval && $levelApproval->status === 'Pending';
                                    $isRejected    = $levelApproval && $levelApproval->status === 'Rejected';
                                @endphp
                                <td class="p-2 border-b border-slate-900 align-bottom relative">
                                    @if($isApproved)
                                        <div class="absolute inset-x-0 top-2 text-center text-[9px] text-emerald-600 font-bold italic">APPROVED</div>
                                        <div class="absolute inset-x-0 top-6 text-center text-[8px] text-slate-400">
                                            {{ $levelApproval->approved_at ? $levelApproval->approved_at->format('d-M-Y H:i') : '' }}
                                        </div>
                                        <div class="font-bold text-[10px] text-slate-900">{{ $levelApproval->approver_name }}</div>
                                        <div class="text-[8px] text-slate-400">{{ $rule->position_label }}</div>
                                    @elseif($isRejected)
                                        <div class="absolute inset-x-0 top-2 text-center text-[9px] text-rose-500 font-bold italic">REJECTED</div>
                                        <div class="absolute inset-x-0 top-6 text-center text-[8px] text-slate-400">
                                            {{ $levelApproval->approved_at ? $levelApproval->approved_at->format('d-M-Y H:i') : '' }}
                                        </div>
                                        <div class="font-bold text-[10px] text-slate-900">{{ $levelApproval->approver_name }}</div>
                                        <div class="text-[8px] text-slate-400">{{ $rule->position_label }}</div>
                                    @elseif($isPending)
                                        <div class="absolute inset-x-0 top-2 text-center text-[9px] text-amber-500 font-bold italic">PENDING</div>
                                        <div class="text-[9px] font-bold text-slate-700 uppercase block mb-1">{{ $rule->position_label }}</div>
                                        <div class="text-[8px] text-slate-400">{{ $rule->department->name ?? '' }}</div>
                                    @else
                                        {{-- Waiting / not yet submitted --}}
                                        <div class="text-[9px] font-bold text-slate-700 uppercase block mb-1">{{ $rule->position_label }}</div>
                                        <div class="text-[8px] text-slate-400">{{ $rule->department->name ?? '' }}</div>
                                    @endif
                                </td>
                            @empty
                                {{-- Fallback placeholder cells --}}
                                <td class="p-2 border-b border-slate-900 align-bottom">
                                    <div class="text-[9px] font-bold text-slate-400 uppercase">— Belum dikonfigurasi —</div>
                                </td>
                                <td class="p-2 border-b border-slate-900 align-bottom">
                                    <div class="text-[9px] font-bold text-slate-400 uppercase">— Belum dikonfigurasi —</div>
                                </td>
                            @endforelse
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
 
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('spkForm', () => ({
        isEditable: {{ $isEditable ? 'true' : 'false' }},
        work_order_no: @json(isset($workOrder) ? $workOrder->work_order_no : $defaultSpkNo),
        subject: @json(isset($workOrder) ? $workOrder->subject : "Pekerjaan New 5P45"),
        department_id: '{{ isset($workOrder) ? $workOrder->department_id : ($departments->first()->id ?? 1) }}',
        priority: '{{ isset($workOrder) ? $workOrder->priority : "URGENT" }}',
        selected_processes: @json(isset($workOrder) ? $workOrder->processes->pluck('process_id') : []),
        support_departments: @json(isset($workOrder) ? $workOrder->supportDepartments->map(fn($d) => ['id' => $d->id, 'name' => "{$d->name} ({$d->code})"]) : []),
        remarks: @json(isset($workOrder) ? $workOrder->remarks : ""),
        document_no: @json(isset($workOrder) ? $workOrder->document_no : 'FO-13-02'),
        doc_department: 'Sales',
        publish_date: @json(isset($workOrder) ? ($workOrder->publish_date ? $workOrder->publish_date->format('Y-m-d') : ($workOrder->created_at ? $workOrder->created_at->format('Y-m-d') : now()->format('Y-m-d'))) : now()->format('Y-m-d')),
        page_hal: @json(isset($workOrder) ? $workOrder->page_hal : '1'),
        showPreview: true,
        
        // Departments list lookup
        departmentsList: @json($departments),
        processesList: @json($processes),
        
        // Pre-populated selected products
        products: @json(isset($workOrder) ? $workOrder->products : $inquiry->products).map(p => ({
            inquiry_product_id: p.inquiry_product_id,
            customer_name: '{{ $inquiry->customer_name }}',
            model_name: p.model_name,
            customer_part_no: p.customer_part_no,
            customer_part_name: p.customer_part_name,
            destination: p.destination,
            sop_date: p.sop_date ? (typeof p.sop_date === 'string' ? p.sop_date.substring(0, 10) : p.sop_date) : '',
            eol_date: p.eol_date ? (typeof p.eol_date === 'string' ? p.eol_date.substring(0, 10) : p.eol_date) : '',
            model_life: p.model_life,
            annual_volume: p.annual_volume,
            first_sample_date: p.first_sample_date ? (typeof p.first_sample_date === 'string' ? p.first_sample_date.substring(0, 10) : p.first_sample_date) : '',
            due_date_approval: p.due_date_approval ? (typeof p.due_date_approval === 'string' ? p.due_date_approval.substring(0, 10) : p.due_date_approval) : '',
            due_date_closed: p.due_date_closed ? (typeof p.due_date_closed === 'string' ? p.due_date_closed.substring(0, 10) : p.due_date_closed) : '',
            remarks: p.remarks || ''
        })),

        // Keyed by inquiry_product_id
        parts: {
            @if(isset($workOrder))
                @foreach($workOrder->products as $woProd)
                    '{{ $woProd->inquiry_product_id }}': [
                        @foreach($woProd->parts as $part)
                            {
                                eo: @json($part->eo),
                                part_no: @json($part->part_no),
                                part_name: @json($part->part_name),
                                class_id: @json($part->class_id),
                                uom: @json($part->uom),
                                remarks: @json($part->remarks)
                            },
                        @endforeach
                    ],
                @endforeach
            @else
                @foreach($inquiry->products as $prod)
                    '{{ $prod->inquiry_product_id }}': [
                        {
                            eo: '-',
                            part_no: @json($prod->customer_part_no),
                            part_name: @json($prod->customer_part_name),
                            class_id: 'FG',
                            uom: 'Pcs',
                            remarks: 'Main Finished Good'
                        }
                    ],
                @endforeach
            @endif
        },

        computedRevisionNo() {
            if (!this.work_order_no) return '00';
            let parts = this.work_order_no.split('/');
            let firstPart = parts[0] || '';
            let num = parseInt(firstPart, 10);
            if (isNaN(num)) return '00';
            return String(num).padStart(2, '0');
        },

        init() {
            this.updateDepartmentIdFromProcesses();
        },

        getDepartmentName(id) {
            let dept = this.departmentsList.find(d => d.id == id);
            return dept ? `${dept.name} (${dept.code})` : '';
        },

        toggleProcess(id) {
            if (!this.isEditable) return;
            if (this.selected_processes.includes(id)) {
                this.selected_processes = this.selected_processes.filter(v => v !== id);
            } else {
                this.selected_processes.push(id);
            }
            this.updateDepartmentIdFromProcesses();
        },

        updateDepartmentIdFromProcesses() {
            if (this.selected_processes.length > 0) {
                let firstProc = this.processesList.find(p => p.process_id == this.selected_processes[0]);
                if (firstProc) {
                    this.department_id = firstProc.owner_department_id;
                }
            } else {
                this.department_id = '{{ isset($workOrder) ? $workOrder->department_id : ($departments->first()->id ?? 1) }}';
            }
        },

        toggleSupportDept(id, name) {
            if (!this.isEditable) return;
            let idx = this.support_departments.findIndex(d => d.id == id);
            if (idx > -1) {
                this.support_departments.splice(idx, 1);
            } else {
                this.support_departments.push({ id, name });
            }
        },

        updateProductDate(prodId, key, val) {
            if (!this.isEditable) return;
            let prod = this.products.find(p => p.inquiry_product_id == prodId);
            if (prod) {
                prod[key] = val;
            }
        },

        updateProductRemarks(prodId, val) {
            if (!this.isEditable) return;
            let prod = this.products.find(p => p.inquiry_product_id == prodId);
            if (prod) {
                prod.remarks = val;
            }
        },

        addPartRow(prodId) {
            if (!this.isEditable) return;
            if (!this.parts[prodId]) {
                this.parts[prodId] = [];
            }
            this.parts[prodId].push({
                eo: '-',
                part_no: '',
                part_name: '',
                class_id: 'RM',
                uom: 'Kg',
                remarks: ''
            });
        },

        removePartRow(prodId, index) {
            if (!this.isEditable) return;
            this.parts[prodId].splice(index, 1);
        },

        getAllParts() {
            let all = [];
            this.products.forEach(p => {
                let partList = this.parts[p.inquiry_product_id] || [];
                partList.forEach(part => {
                    all.push({
                        customer_name: p.customer_name,
                        model_name: p.model_name,
                        eo: part.eo,
                        part_no: part.part_no,
                        part_name: part.part_name,
                        class_id: part.class_id,
                        uom: part.uom,
                        remarks: part.remarks
                    });
                });
            });
            return all;
        },

        formatDateStr(dateStr) {
            if (!dateStr) return '';
            let date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            let day = String(date.getDate()).padStart(2, '0');
            let months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            let month = months[date.getMonth()];
            let year = date.getFullYear();
            return `${day}-${month}-${year}`;
        }
    }));
});
</script>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Modern styling overrides for Select2 to match premium UI */
    .select2-container--default .select2-selection--multiple {
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 4px !important;
        min-height: 40px !important;
        padding: 4px 8px !important;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
    }
    .dark .select2-container--default .select2-selection--multiple {
        background-color: #0f172a !important;
        border-color: #475569 !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 1px #3b82f6 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #3b82f6 !important;
        border: none !important;
        color: #ffffff !important;
        border-radius: 4px !important;
        font-size: 11px !important;
        font-weight: 600;
        padding: 4px 8px !important;
        margin-top: 3px !important;
        display: inline-flex;
        align-items: center;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #ffffff !important;
        margin-right: 6px !important;
        border-right: 1px solid rgba(255,255,255,0.2);
        padding-right: 6px;
        font-weight: bold;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        background-color: transparent !important;
        color: #ef4444 !important;
    }
    .select2-dropdown {
        background-color: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        font-size: 12px;
        z-index: 10000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .dark .select2-dropdown {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #3b82f6 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-support-depts').select2({
            placeholder: "Select Support Departments..."
        }).on('change', function() {
            let selectedValues = $(this).val() || [];
            let list = selectedValues.map(id => {
                let optionText = $('.select2-support-depts option[value="'+id+'"]').text();
                return { id: parseInt(id), name: optionText.trim() };
            });
            let formEl = document.getElementById('spkFormContainer');
            if (formEl) {
                Alpine.$data(formEl).support_departments = list;
            }
        });
    });
</script>
@endpush

