@extends('layouts.app')

@section('title', isset($workOrder) ? 'Work Order Document · Promise Management' : 'Create Work Order (SPK) · Promise Management')

@section('content')
@php
    $isEditable = !isset($workOrder) || ($workOrder->status === 'Draft' && ($workOrder->is_latest ?? true));
@endphp
<style>
    @media print {
        header, sidebar, nav, button, .no-print, .action-toolbar, .wo-form-panel {
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
        .wo-preview-panel {
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .wo-paper {
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

<div x-data="spkForm" id="spkFormWrapper">
<div class="split-container flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-slate-50 dark:bg-slate-900" id="spkFormContainer">
    
    {{-- ── LEFT SIDE: Detail Configuration Form ─────────────────────────── --}}
    @if($isEditable)
        <form id="spkForm" action="{{ isset($workOrder) ? route('management.work-order-fastener.update', $workOrder->hashed_id) : route('management.work-order-fastener.store') }}" method="POST" 
              class="wo-form-panel w-1/2 h-full flex flex-col overflow-hidden border-r border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 transition-[width] duration-200 ease-in-out" :style="showPreview ? 'width: 50%' : 'width: 100%'">
            @csrf
            @if(isset($workOrder))
                @method('PUT')
            @else
                <input type="hidden" name="ebd_header_id" value="{{ $ebdHeader->hashed_id ?? '' }}">
                <input type="hidden" name="header_id" value="{{ $woHeader->id ?? 1 }}">
            @endif
            <input type="hidden" name="department_id" :value="department_id">
    @else
        <div class="wo-form-panel w-1/2 h-full flex flex-col overflow-hidden border-r border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900 transition-[width] duration-200 ease-in-out" :style="showPreview ? 'width: 50%' : 'width: 100%'">
            <input type="hidden" name="department_id" :value="department_id">
    @endif
        
        {{-- Fixed Header --}}
        <div class="p-6 pb-4 border-b border-slate-300 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-10 flex-none">
            <div class="flex items-center gap-3">
                <a href="{{ route('management.work-order-fastener.index') }}"
                   class="flex items-center justify-center w-7 h-7 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-500 hover:text-blue-600 hover:border-blue-500 transition-colors text-xs rounded-sm"
                   title="Back to SPK 2 Fastener List">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="text-md font-black tracking-tight text-slate-800 dark:text-white flex items-center gap-1.5">
                        <span>{{ isset($workOrder) ? 'Work Order Detail' : 'Create Work Order (SPK)' }}</span>
                        @if(isset($workOrder))
                            <span class="text-xs bg-slate-100 text-slate-800 dark:bg-slate-900/60 dark:text-slate-300 px-2 py-0.5 font-bold uppercase">{{ $workOrder->status }}</span>
                            <span class="text-xs bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50 px-2 py-0.5 font-bold rounded-sm">Rev. {{ sprintf('%02d', $workOrder->revision_no ?? 0) }}</span>
                        @endif
                    </h2>
                    <p class="text-[10px] text-slate-400">{{ isset($workOrder) ? 'View WO specifications, process details, and BOM parts.' : 'Configure WO details, assign departments, and manage BOM components.' }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 no-print">
                <button type="button" @click="showPreview = !showPreview"
                        class="flex items-center justify-center gap-1.5 px-2.5 h-7 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 transition-colors text-xs font-semibold rounded-sm"
                        :title="showPreview ? 'Hide Preview' : 'Show Preview'">
                    <i class="fa-solid" :class="showPreview ? 'fa-eye-slash' : 'fa-eye'"></i>
                    <span x-text="showPreview ? 'Hide' : 'Preview'"></span>
                </button>
                @if(isset($workOrder))
                    <button type="button" onclick="window.print()"
                            class="w-7 h-7 bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center text-xs shadow-xs transition-colors cursor-pointer rounded-sm"
                            title="Print SPK">
                        <i class="fa-solid fa-print"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Scrollable Container --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            @if(isset($workOrder) && !$workOrder->is_latest)
                <div class="p-3.5 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 text-amber-800 dark:text-amber-400 text-xs flex items-center justify-between">
                    <span>
                        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                        This is an outdated revision (Rev. {{ sprintf('%02d', $workOrder->revision_no) }}). A newer revision of this WO exists.
                    </span>
                    <a href="{{ route('management.work-order.show', \App\Models\WorkOrder::where('wo_number', $workOrder->wo_number)->where('is_latest', true)->first()->hashed_id ?? $workOrder->hashed_id) }}" 
                       class="font-bold underline hover:text-amber-600 dark:hover:text-amber-300 ml-3">
                        View Latest Revision &rarr;
                    </a>
                </div>
            @endif

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
                        $rule = \App\Models\ApprovalConfig::activeFor('SPK')
                            ->where('approval_level', $pendingStep->approval_level)
                            ->first();
                        $canActOnThis = $rule ? $rule->canBeApprovedBy($user) : true;
                    }
                @endphp
                <div class="border border-slate-300 dark:border-slate-700 rounded-sm overflow-hidden">
                    {{-- Header --}}
                    <div class="px-4 py-2.5 bg-{{ $workOrder->status === 'Approved' ? 'emerald' : 'amber' }}-50 dark:bg-{{ $workOrder->status === 'Approved' ? 'emerald' : 'amber' }}-950/20 border-b border-slate-300 dark:border-slate-700 flex items-center gap-2">
                        <i class="fa-solid fa-{{ $workOrder->status === 'Approved' ? 'circle-check text-emerald-500' : 'clock-rotate-left text-amber-500' }} text-xs"></i>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Approval Progress</span>
                        <span class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-sm border
                            {{ $workOrder->status === 'Approved' ? 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900' : 'bg-amber-100 text-amber-700 border-amber-300 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800' }}">
                            {{ $workOrder->status }}
                        </span>
                    </div>

                    {{-- Sequential Steps --}}
                    <div class="p-3 space-y-1.5 relative bg-white dark:bg-slate-900">
                        {{-- Continuous Timeline Line --}}
                        <div class="absolute left-[25px] top-6 bottom-8 w-[1.5px] bg-slate-200 dark:bg-slate-700 z-0"></div>

                        @foreach($approvals as $step)
                            @php
                                $stepColor = match($step->status) {
                                    'Approved' => ['dot' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-400', 'badge' => 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/30 dark:border-emerald-900 dark:text-emerald-400'],
                                    'Pending'  => ['dot' => 'bg-amber-500 animate-pulse', 'text' => 'text-amber-700 dark:text-amber-400', 'badge' => 'bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-400'],
                                    'Rejected' => ['dot' => 'bg-rose-500', 'text' => 'text-rose-600 dark:text-rose-400', 'badge' => 'bg-rose-50 border-rose-200 text-rose-600 dark:bg-rose-950/30 dark:border-rose-900 dark:text-rose-400'],
                                    default    => ['dot' => 'bg-slate-300 dark:bg-slate-600', 'text' => 'text-slate-400', 'badge' => 'bg-slate-100 border-slate-300 text-slate-400 dark:bg-slate-800 dark:border-slate-700'],
                                };
                            @endphp
                            <div class="flex items-start gap-2.5 relative z-10">
                                <div class="flex flex-col items-center pt-2.5 flex-shrink-0" style="width: 24px;">
                                    <span class="w-2 h-2 rounded-full flex-shrink-0 z-10 {{ $stepColor['dot'] }} ring-4 ring-white dark:ring-slate-900"></span>
                                </div>
                                <div class="flex-1 min-w-0 p-1.5 px-2.5 rounded-sm border transition-all duration-200
                                    {{ $step->status === 'Pending' 
                                       ? 'bg-amber-50/40 border-amber-200/60 dark:bg-amber-950/20 dark:border-amber-900/50 shadow-2xs' 
                                       : 'bg-transparent border-transparent' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <span class="inline-block px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-sm bg-slate-100 dark:bg-slate-800 text-slate-650 dark:text-slate-400 border border-slate-200 dark:border-slate-700 mr-1.5">L{{ $step->approval_level }}</span>
                                            <span class="text-xs font-bold text-slate-750 dark:text-slate-200">{{ $step->approver_position }}</span>
                                            <span class="text-[10px] text-slate-400 ml-1.5">({{ $step->department->name ?? '—' }})</span>
                                        </div>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-sm border {{ $stepColor['badge'] }}">
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
                                        <div class="text-[10px] italic text-slate-400 mt-0.5 font-medium">"{{ $step->remarks }}"</div>
                                    @endif

                                    {{-- Approver Emails & Resend Button --}}
                                    @php
                                        $rule = \App\Models\ApprovalConfig::activeFor('SPK')
                                            ->where('approval_level', $step->approval_level)
                                            ->where('department_id', $step->department_id)
                                            ->first();
                                        $stepApprovers = [];
                                        if ($rule) {
                                            $approverUsers = $rule->approver_users;
                                            if ($approverUsers->isEmpty()) {
                                                $approverUsers = \App\Models\User::where('id_dept', $rule->department_id)->where('is_active', true)->get();
                                            }
                                            foreach ($approverUsers as $ap) {
                                                if (!empty($ap->email) && $ap->is_active) {
                                                    $stepApprovers[] = [
                                                        'email' => $ap->email,
                                                        'name' => $ap->name
                                                    ];
                                                }
                                            }
                                        }
                                    @endphp
                                    @if(!empty($stepApprovers))
                                        <div class="mt-1.5 flex flex-wrap gap-1.5 select-none pt-1">
                                            @foreach($stepApprovers as $apData)
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-750 dark:text-slate-200 text-[10px]" title="{{ $apData['email'] }}">
                                                    <span class="font-semibold">{{ $apData['name'] }}</span>
                                                    <span class="text-slate-500 dark:text-slate-450 font-mono text-[9px]">({{ $apData['email'] }})</span>
                                                    <button type="button" 
                                                            onclick="resendEmail('{{ $workOrder->hashed_id }}', '{{ $apData['email'] }}', '{{ $apData['name'] }}', 'approver', this)"
                                                            class="ml-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-sm bg-blue-600 hover:bg-blue-700 text-white font-bold transition-all cursor-pointer text-[9px] shadow-xs"
                                                            title="Resend email">
                                                        <i class="fa-solid fa-paper-plane text-[8px]"></i>
                                                        <span>Resend</span>
                                                    </button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Approve / Reject Actions (only for authorized user on pending step, on REVIEW page only) --}}
                    @if($pendingStep && $canActOnThis && Route::currentRouteName() === 'management.work-order.review')
                        @php
                            $isMarketingGMStep = $workOrder->priority === 'URGENT' && 
                                (str_contains(strtolower($pendingStep->approver_position), 'gm') || 
                                 str_contains(strtolower($pendingStep->approver_position), 'general manager') ||
                                 ($pendingStep->department->code ?? '') === 'MKT');
                        @endphp
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-300 dark:border-slate-700 space-y-2.5">
                            @if($isMarketingGMStep)
                                <div class="p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800 rounded-sm space-y-1.5">
                                    <label class="block text-[11px] font-bold text-amber-900 dark:text-amber-300 flex items-center gap-1.5">
                                        <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                                        Marketing GM Urgent Confirmation & Reason (Required):
                                    </label>
                                    <textarea name="urgent_reason" required rows="2" form="approve-spk-form" placeholder="Tuliskan alasan / catatan konfirmasi mengapa SPK ini URGENT..."
                                              x-model="urgent_reason"
                                              class="w-full p-2 text-xs border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-sm focus:outline-none focus:border-amber-500">{{ old('urgent_reason', $workOrder->urgent_reason) }}</textarea>
                                </div>
                            @endif

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[10px] text-slate-500 flex-1">Your turn to review as <strong>{{ $pendingStep->approver_position }}</strong>:</span>
                                <form id="approve-spk-form" action="{{ route('management.work-order.approve', $workOrder->hashed_id) }}" method="POST" class="flex items-center gap-2">
                                    @csrf
                                    <input type="text" name="remarks" placeholder="Optional general comments..."
                                           class="px-2.5 py-1.5 text-[10px] border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-sm w-48 focus:outline-none focus:border-blue-400">
                                    <button type="submit"
                                            class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-sm transition-colors cursor-pointer flex items-center gap-1.5">
                                        <i class="fa-solid fa-check text-[9px]"></i> Approve
                                    </button>
                                </form>
                                <form action="{{ route('management.work-order.reject', $workOrder->hashed_id) }}" method="POST"
                                      onsubmit="return confirm('Reject this SPK? It will be returned to Draft.')">
                                    @csrf
                                    <button type="submit"
                                            class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] rounded-sm transition-colors cursor-pointer flex items-center gap-1.5">
                                        <i class="fa-solid fa-xmark text-[9px]"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    @elseif($pendingStep && !$canActOnThis)
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-300 dark:border-slate-700">
                            <p class="text-[10px] text-slate-400 italic flex items-center gap-1.5">
                                <i class="fa-solid fa-lock text-[9px]"></i>
                                Waiting for <strong class="text-slate-500">{{ $pendingStep->approver_position }}</strong> ({{ $pendingStep->department->name ?? '' }}) to approve.
                            </p>
                        </div>
                    @endif
                </div>
            @endif


        {{-- General Configuration Card --}}
        <x-form-card title="1. General Information" icon="fa-circle-info">
            {{-- Link to WO Doc Format master header (header_id) --}}
            <input type="hidden" name="header_id" value="{{ isset($workOrder) ? $workOrder->header_id : ($woHeader->id ?? 1) }}">


            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <x-form-input label="WO Number" name="wo_number" x-model="work_order_no" required :readonly="!$isEditable" />
                    <x-form-input label="WO Release Date" name="released_at" type="date" x-model="released_at" required :disabled="!$isEditable" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <x-form-input label="First Sample Date" name="first_sample_date" type="date" x-model="first_sample_date" :disabled="!$isEditable" />
                    <x-form-input label="Due Date (Plan)" name="due_date_plan" type="date" x-model="due_date_plan" required @change="checkPrioritySuggestions();" :disabled="!$isEditable" />
                </div>
            </div>

            <div class="space-y-2 pt-3">
                    <label class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Priority <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-3 gap-2.5">
                        <button type="button" @click="priority = 'URGENT'" :disabled="!isEditable"
                                class="flex flex-col items-center justify-center py-2 px-3 border rounded-sm cursor-pointer"
                                :class="priority === 'URGENT' 
                                    ? 'border-rose-500 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 font-bold' 
                                    : 'border-slate-300 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed'">
                            <span class="text-xs font-bold tracking-wider uppercase">URGENT</span>
                            <span class="text-[10px] opacity-75 mt-0.5">&lt; 10 Working Days</span>
                        </button>
                        <button type="button" @click="priority = 'STANDARD'" :disabled="!isEditable"
                                class="flex flex-col items-center justify-center py-2 px-3 border rounded-sm cursor-pointer"
                                :class="priority === 'STANDARD' 
                                    ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 font-bold' 
                                    : 'border-slate-300 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed'">
                            <span class="text-xs font-bold tracking-wider uppercase">STANDARD</span>
                            <span class="text-[10px] opacity-75 mt-0.5">10 - 14 Working Days</span>
                        </button>
                        <button type="button" @click="priority = 'LOW'" :disabled="!isEditable"
                                class="flex flex-col items-center justify-center py-2 px-3 border rounded-sm cursor-pointer"
                                :class="priority === 'LOW' 
                                    ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 font-bold' 
                                    : 'border-slate-300 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed'">
                            <span class="text-xs font-bold tracking-wider uppercase">LOW</span>
                            <span class="text-[10px] opacity-75 mt-0.5">&gt; 14 Working Days</span>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-2 flex items-start gap-1">
                        <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                        <span>* Priority is automatically selected based on the Due Date (Plan) and calculated using effective working days only (Monday-Friday, excluding holidays).</span>
                    </p>
                    <input type="hidden" name="priority" :value="priority">
                </div>
        </x-form-card>

        {{-- Request Type Checklist --}}
        <x-form-card title="2. Request Process Checklist" icon="fa-list-check">
            <x-slot name="headerActions">
                <button type="button" onclick="document.getElementById('modal-master-process').classList.remove('hidden')"
                        class="text-[11px] font-medium text-blue-600 hover:text-blue-700 bg-blue-50 dark:bg-blue-950/40 border border-blue-400 dark:border-blue-700 px-2 py-2 rounded-sm transition-colors">
                    <i class="fa-solid fa-gear mr-1"></i> Master Process
                </button>
            </x-slot>
            
            <div class="space-y-4">
                
                <div class="space-y-3">
                    <div class="space-y-2.5">
                        <template x-for="proc in processesList" :key="proc.id">
                            <div class="flex flex-col p-3.5 border rounded-sm animate-fadeIn"
                                 :class="isProcessSelected(proc.id) 
                                    ? 'border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50' 
                                    : 'border-slate-300 bg-white dark:border-slate-800 dark:bg-slate-900/50'">
                                
                                {{-- Left Side: Custom Checkbox & Process info --}}
                                <div class="flex items-center gap-3">
                                    <label class="relative flex items-center justify-center cursor-pointer select-none">
                                        <input type="checkbox" name="processes[]" :value="proc.id" 
                                               :checked="isProcessSelected(proc.id)"
                                               @change="toggleProcess(proc.id)"
                                               class="peer h-4.5 w-4.5 cursor-pointer appearance-none rounded-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 checked:border-blue-600 checked:bg-blue-600 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                               :disabled="!isEditable">
                                        <span class="absolute text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none text-xs">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                        </span>
                                    </label>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block"
                                              :class="isProcessSelected(proc.id) ? 'text-blue-700 dark:text-blue-400 font-extrabold' : ''"
                                              x-text="proc.process_name">
                                        </span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5" x-text="'Owner: ' + getProcessDefaultDeptsLabel(proc)"></span>
                                    </div>
                                </div>
                                
                                {{-- Assigned Departments checkable badges list --}}
                                <div x-show="isProcessSelected(proc.id)" class="mt-2.5 pt-2 border-t border-slate-300 dark:border-slate-800">
                                    <div class="text-[10px] text-slate-500 uppercase font-medium mb-1.5">Assigned Departments:</div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="dept in departmentsList" :key="dept.id">
                                            <label class="inline-flex items-center gap-1 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 px-2 py-0.5 text-[10px] cursor-pointer select-none rounded-sm font-semibold"
                                                   :class="process_departments[proc.id] && process_departments[proc.id].map(Number).includes(dept.id) 
                                                        ? 'bg-blue-50 border-blue-300 text-blue-700 dark:bg-blue-950/20 dark:border-blue-800 dark:text-blue-400 font-bold' 
                                                        : 'text-slate-600 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800/50'">
                                                <input type="checkbox" 
                                                       :value="dept.id" 
                                                       x-model.number="process_departments[proc.id]"
                                                       @change="syncGlobalSupportDepartments()"
                                                       class="h-3 w-3 rounded-sm border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-0"
                                                       :disabled="!isEditable">
                                                <span x-text="dept.code"></span>
                                            </label>
                                        </template>
                                    </div>
                                    
                                    <!-- PIC Selection List -->
                                    <div class="mt-3 space-y-2 border-t border-slate-300 dark:border-slate-800/60 pt-2"
                                         x-show="process_departments[proc.id] && process_departments[proc.id].length > 0">
                                        <div class="text-[10px] text-slate-500 uppercase font-medium">Assign PIC for each department:</div>
                                        <template x-for="deptId in (process_departments[proc.id] || [])" :key="deptId">
                                            <div class="flex items-center justify-between gap-3 bg-slate-100 dark:bg-slate-800 p-2 border border-slate-300 dark:border-slate-700 rounded-sm">
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-350" x-text="getDeptCodeById(deptId)"></span>
                                                <div class="flex items-center gap-2">
                                                    <label class="text-xs text-slate-700 dark:text-slate-350">PIC:</label>
                                                    <select x-init="setTimeout(() => window.initPicSelect2($el, deptId, process_pics[proc.id + '_' + deptId], val => { process_pics[proc.id + '_' + deptId] = val; }, !isEditable), 50)"
                                                            class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs w-48">
                                                    </select>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </x-form-card>



        {{-- Approval Workflow Card --}}
        <x-form-card title="3. Approval Workflow Steps" icon="fa-user-shield">
            <x-slot name="headerActions">
                <button type="button" onclick="document.getElementById('modal-master-approval').classList.remove('hidden')"
                        class="text-[11px] font-medium text-blue-600 hover:text-blue-700 bg-blue-50 dark:bg-blue-950/40 border border-blue-400 dark:border-blue-700 px-2 py-2 rounded-sm transition-colors">
                    <i class="fa-solid fa-gear mr-1"></i> Master Approval Rule
                </button>
            </x-slot>
            <p class="text-[10px] text-slate-500 dark:text-slate-450 mb-2">Select the required approval level. The approval <span class="text-blue-600 font-semibold">marked in blue</span> is recommended based on the department selected in the Process Checklist.</p>
            
            <div class="space-y-2">
                <template x-for="rule in approvalRulesListFull.filter(r => r.is_active)" :key="rule.id">
                    <label class="flex items-start gap-3 p-3 border rounded-sm cursor-pointer select-none transition-all duration-150 animate-fadeIn"
                           :class="{
                               'border-blue-400 bg-blue-50/40 dark:border-blue-700 dark:bg-blue-950/20 shadow-sm': selected_approval_rules.includes(Number(rule.id)) && isSuggestedRule(rule.department_id),
                               'border-slate-300 bg-slate-50/60 dark:border-slate-700 dark:bg-slate-800/30': selected_approval_rules.includes(Number(rule.id)) && !isSuggestedRule(rule.department_id),
                               'border-slate-300 bg-white dark:border-slate-800 dark:bg-slate-900/50 opacity-60': !selected_approval_rules.includes(Number(rule.id))
                           }">
                        <input type="checkbox" name="selected_approval_rules[]" :value="rule.id"
                               x-model.number="selected_approval_rules"
                               :disabled="!isEditable"
                               class="h-4 w-4 rounded-sm border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-0 mt-0.5 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    Level <span x-text="rule.approval_level"></span>: <span x-text="rule.position_label"></span> (<span x-text="rule.action_label"></span>)
                                </span>
                                {{-- Suggested badge --}}
                                <span x-show="isSuggestedRule(rule.department_id)"
                                      class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800 rounded-full">
                                    <i class="fa-solid fa-bolt text-[8px]"></i> Department Suggestion
                                </span>
                                {{-- Not matching badge --}}
                                <span x-show="!isSuggestedRule(rule.department_id)"
                                      class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-semibold bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 border border-slate-300 dark:border-slate-700 rounded-full">
                                    <i class="fa-solid fa-minus text-[8px]"></i> Other Dept
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[10px] font-medium bg-slate-100/80 dark:bg-slate-800/85 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600">
                                    <span class="font-bold text-slate-400 dark:text-slate-500 mr-1">Dept:</span>
                                    <span x-text="rule.department_name" class="font-bold text-slate-800 dark:text-slate-100"></span>
                                </span>
                                <template x-if="rule.approver_users_list_names">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[10px] font-medium bg-blue-50/50 dark:bg-blue-950/20 text-blue-800 dark:text-blue-200 border border-blue-300 dark:border-blue-700">
                                        <span class="font-bold text-slate-400 dark:text-slate-500 mr-1">Approver:</span>
                                        <span x-text="rule.approver_users_list_names" class="font-bold text-blue-900 dark:text-blue-100"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </label>
                </template>

                <template x-if="approvalRulesListFull.length === 0">
                    <div class="py-6 text-center text-slate-400">
                        <i class="fa-solid fa-triangle-exclamation text-amber-400 mr-1"></i>
                        <span class="text-xs">No active approval rules found. Please configure them in the <a href="{{ route('management.approval-config.index') }}" class="text-blue-500 underline">Approval Matrix</a>.</span>
                    </div>
                </template>
            </div>
        </x-form-card>

        {{-- General Remarks --}}
        <x-form-card title="Additional WO Notes (General Remarks)" icon="fa-file-lines">
            <textarea name="remarks" x-model="remarks" rows="3" placeholder="Enter any extra notes or instructions..."
                      class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white focus:border-blue-500 focus:outline-none disabled:bg-slate-200 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed transition-colors duration-150 resize-none" {{ !$isEditable ? 'disabled' : '' }}></textarea>
        </x-form-card>

        {{-- Product Specifications Card (BOM) --}}
        <x-form-card title="3. Product Specifications (BOM)" icon="fa-boxes-stacked">
            <x-slot name="headerActions">
                @if($isEditable)
                    <button type="button" @click="openAddBom()"
                            class="text-[11px] font-medium text-blue-600 hover:text-blue-700 bg-blue-50 dark:bg-blue-950/40 border border-blue-400 dark:border-blue-700 px-2 py-2 rounded-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-plus mr-1"></i> Add BOM Item
                    </button>
                @endif
            </x-slot>
            
            <div class="overflow-x-auto border border-slate-300 dark:border-slate-800 rounded-sm">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-semibold text-center text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                            @if($isEditable)
                                <th class="p-2 w-10 text-center">Drag</th>
                            @endif
                            <th class="p-2 min-w-48 text-left">Part Number / Name</th>
                            <th class="p-2">Remarks</th>
                            <th class="p-2 w-20 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(prod, index) in products" :key="prod.tempId">
                            <tr :draggable="isEditable ? 'true' : 'false'"
                                @dragstart="draggedIndex = index; $event.dataTransfer.effectAllowed = 'move';"
                                @dragover.prevent=""
                                @drop="
                                    if (draggedIndex !== null && draggedIndex !== index) {
                                        let item = products[draggedIndex];
                                        products.splice(draggedIndex, 1);
                                        products.splice(index, 0, item);
                                        products = [...products];
                                    }
                                    draggedIndex = null;
                                "
                                class="border-b border-slate-300 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/40 align-middle"
                                :class="isEditable ? 'cursor-grab active:cursor-grabbing' : ''">
                                @if($isEditable)
                                    <td class="p-2 text-center text-slate-400 select-none">
                                        <i class="fa-solid fa-grip-vertical text-xs"></i>
                                    </td>
                                @endif
                                <!-- Part info with depth indentation -->
                                <td class="p-2" :style="'padding-left: ' + (getBomDepth(prod) * 1.5 + 0.5) + 'rem'">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="getBomDepth(prod) > 0">
                                            <span class="text-slate-400 dark:text-slate-650 font-mono select-none">└─</span>
                                        </template>
                                        <div class="flex-1">
                                            <div class="font-bold text-slate-800 dark:text-slate-200" x-text="prod.customer_part_no || '—'"></div>
                                            <div class="text-[10px] text-slate-400" x-text="prod.customer_part_name || '—'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2" x-text="prod.remarks || '—'"></td>
                                <td class="p-2 text-center">
                                    <div class="flex justify-center gap-1">
                                        @if($isEditable)
                                            <button type="button" @click="openEditBom(index)" class="p-1 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 rounded-sm cursor-pointer" title="Edit Item">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <button type="button" @click="removeBomItem(index)" class="p-1 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-sm cursor-pointer" title="Remove Item">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        @else
                                            <span class="text-[10px] text-slate-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-form-card>

        {{-- Data for submission is sent via JSON AJAX, not hidden fields --}}

        </div> {{-- End Scrollable Container --}}

        {{-- Fixed Footer --}}
        <div class="p-6 pt-4 border-t border-slate-300 dark:border-slate-800 flex justify-end gap-2.5 bg-slate-50 dark:bg-slate-900/60 z-10 flex-none">
            <a href="{{ route('management.work-order-fastener.index') }}"
               class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-sm transition-colors">
                Back to WO List
            </a>
            @if(isset($workOrder) && in_array($workOrder->status, ['Draft', 'Pending Approval']) && ($workOrder->is_latest ?? true))
                <button type="button" onclick="confirmDeleteWO()"
                        class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-trash"></i> Delete WO
                </button>
            @endif
            @if(isset($workOrder) && $workOrder->status === 'Approved' && $workOrder->is_latest)
                <button type="button" onclick="confirmReviseWO()"
                        class="px-4 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-code-branch"></i> Revise WO (New Revision)
                </button>
            @endif
            @if($isEditable)
                <button type="submit"
                        class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer">
                    {{ isset($workOrder) ? 'Save Changes' : 'Save Work Order' }}
                </button>
                @if(isset($workOrder) && $workOrder->status === 'Draft')
                    <button type="button" onclick="confirmSubmitApproval()"
                            class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-sm shadow-xs transition-colors cursor-pointer">
                        Submit for Approval
                    </button>
                @endif
            @endif
        </div>
    @if($isEditable)
        </form>
    @else
        </div>
    @endif

    <div class="wo-preview-panel w-1/2 h-full overflow-auto bg-slate-150 dark:bg-slate-800 transition-[width] duration-200 ease-in-out p-8 flex flex-col items-center border-l border-slate-300 dark:border-slate-700"
         x-show="showPreview" :style="showPreview ? 'width: 50%' : 'width: 0%'">
        @include('management.work-order.wo2-fastener.preview')
    </div>
    
    @if(isset($workOrder))
        @if(in_array($workOrder->status, ['Draft', 'Pending Approval']) && ($workOrder->is_latest ?? true))
            <form id="deleteWoForm" action="{{ route('management.work-order-fastener.destroy', $workOrder->hashed_id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
        @if($workOrder->status === 'Approved' && $workOrder->is_latest)
            <form id="reviseWoForm" action="{{ route('management.work-order.revise', $workOrder->hashed_id) }}" method="POST" class="hidden">
                @csrf
            </form>
        @endif
        @if($workOrder->status === 'Draft')
            <form id="submitApprovalForm" action="{{ route('management.work-order.submit', $workOrder->hashed_id) }}" method="POST" class="hidden">
                @csrf
            </form>
        @endif
    @endif
</div>

{{-- ── MODAL: MASTER PROCESS CHECKLIST ────────────────────────── --}}
<div id="modal-master-process" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-3xl border border-slate-300 dark:border-slate-700 flex flex-col max-h-[85vh]">
        <div class="p-4 border-b border-slate-300 dark:border-slate-700 flex justify-between items-center flex-none">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-gear text-blue-500"></i> Master Process Checklist
            </h3>
            <button onclick="document.getElementById('modal-master-process').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        
        <div class="p-5 overflow-y-auto flex-1 text-xs space-y-4">
            <div class="flex justify-end">
                <button type="button" onclick="openAddProcessModal()" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] rounded-sm transition-colors flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-plus text-[9px]"></i> Add Process
                </button>
            </div>
            
            <div class="overflow-x-auto border border-slate-300 dark:border-slate-700 rounded-sm">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700 text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase">
                            <th class="p-2.5 w-24">Code</th>
                            <th class="p-2.5">Process Name</th>
                            <th class="p-2.5">Default Dept(s)</th>
                            <th class="p-2.5 w-16 text-center">Status</th>
                            <th class="p-2.5 w-24 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                        <template x-for="p in processesList" :key="p.id">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                                <td class="p-2.5 font-mono text-slate-750 dark:text-slate-300" x-text="p.process_code"></td>
                                <td class="p-2.5 font-medium text-slate-800 dark:text-slate-200" x-text="p.process_name"></td>
                                <td class="p-2.5 text-slate-500 dark:text-slate-400" x-text="getProcessDefaultDeptsLabel(p)"></td>
                                <td class="p-2.5 text-center">
                                    <template x-if="p.is_active">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-450 border border-emerald-200 dark:border-emerald-800/50 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    </template>
                                    <template x-if="!p.is_active">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-450 border border-slate-300 dark:border-slate-700 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                                            Inactive
                                        </span>
                                    </template>
                                </td>
                                <td class="p-2.5 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <button type="button" @click="openEditProcessModal(p)" class="p-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 text-slate-600 dark:text-slate-350 hover:text-blue-650 rounded-sm transition-colors flex items-center justify-center w-7 h-7" title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                        </button>
                                        <button type="button" @click="deleteProcessAjax(p.id)" class="p-1.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-250 dark:border-rose-900/50 text-rose-600 dark:text-rose-450 hover:border-rose-500 rounded-sm transition-colors flex items-center justify-center w-7 h-7" title="Delete">
                                            <i class="fa-solid fa-trash-can text-[11px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="p-4 border-t border-slate-300 dark:border-slate-700 flex justify-end flex-none">
            <button onclick="document.getElementById('modal-master-process').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm font-bold text-xs transition-colors cursor-pointer">Close</button>
        </div>
    </div>
</div>

{{-- ── MODAL: PROCESS CHECKLIST CONFIGURATION (ADD/EDIT) ─────────────── --}}
<div id="modal-process-config" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-md border border-slate-300 dark:border-slate-700">
        <div class="p-4 border-b border-slate-300 dark:border-slate-700 flex justify-between items-center">
            <h3 id="modal-process-title" class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Add Master Process</h3>
            <button onclick="document.getElementById('modal-process-config').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="process-config-form" action="" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Process Code <span class="text-rose-500">*</span></label>
                <input type="text" name="process_code" id="proc_code" required placeholder="e.g. CUTTING" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Process Name <span class="text-rose-500">*</span></label>
                <input type="text" name="process_name" id="proc_name" required placeholder="e.g. Cutting Process" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div x-data="approverSelect('proc_dept_select', {{ json_encode($departments->map(fn($d) => ['id' => $d->id, 'label' => $d->name . ' (' . $d->code . ')'])) }}, [])" x-on:set-proc-depts.window="if ($event.detail.target === 'proc_dept_select') setSelected($event.detail.ids)" class="relative">
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Default Department Owner <span class="text-rose-500">*</span>
                </label>
                {{-- Hidden native select --}}
                <select name="default_assigned_departments[]" id="proc_dept_select" multiple class="hidden">
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                {{-- Search input --}}
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
                           placeholder="Search department..."
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 pr-8 rounded-sm focus:outline-none focus:border-blue-500">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </span>
                    <div x-show="open" x-transition
                         class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm shadow-lg max-h-44 overflow-y-auto">
                        <template x-for="item in filtered" :key="item.id">
                            <div @click="toggle(item)"
                                 :class="selectedIds.includes(item.id) ? 'bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                                 class="flex items-center justify-between px-3 py-1.5 text-xs cursor-pointer">
                                <span x-text="item.label"></span>
                                <i x-show="selectedIds.includes(item.id)" class="fa-solid fa-check text-[9px] text-blue-500"></i>
                            </div>
                        </template>
                    </div>
                </div>
                {{-- Tags list --}}
                <div class="mt-1 flex flex-wrap gap-1 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm min-h-[30px]">
                    <template x-if="selectedIds.length === 0">
                        <span class="text-[9px] text-slate-400 italic self-center">No department selected</span>
                    </template>
                    <template x-for="item in selectedItems" :key="item.id">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 text-[9px] font-semibold rounded-full border border-blue-200 dark:border-blue-800">
                            <span x-text="item.label"></span>
                            <button type="button" @click="remove(item.id)" class="hover:text-blue-950 dark:hover:text-white">&times;</button>
                        </span>
                    </template>
                </div>
                
                {{-- PIC Selection list for default departments --}}
                <div class="mt-3 space-y-2 border-t border-slate-300 dark:border-slate-800/60 pt-2" x-show="selectedIds.length > 0">
                    <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Default PIC per Department:</label>
                    <template x-for="deptId in selectedIds" :key="deptId">
                        <div class="flex items-center justify-between gap-2 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm">
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300" x-text="itemLabel(deptId)"></span>
                            <select :name="'default_pics[' + deptId + ']'"
                                    x-init="setTimeout(() => window.initPicSelect2($el, deptId, defaultPics[deptId], val => { defaultPics[deptId] = val; }), 50)"
                                    class="bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-[10px] w-48">
                            </select>
                        </div>
                    </template>
                </div>
            </div>
            <div id="proc_active_wrapper" class="flex items-center gap-2 py-1">
                <input type="checkbox" name="is_active" id="proc_active" value="1" checked class="rounded-sm text-blue-600">
                <label for="proc_active" class="font-semibold text-slate-700 dark:text-slate-350">Active</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-300 dark:border-slate-750">
                <button type="button" onclick="document.getElementById('modal-process-config').classList.add('hidden')" class="px-3.5 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm font-bold text-xs transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-sm text-xs transition-colors cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: MASTER APPROVAL RULE ────────────────────────────── --}}
<div id="modal-master-approval" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-4xl border border-slate-300 dark:border-slate-700 flex flex-col max-h-[85vh]">
        <div class="p-4 border-b border-slate-300 dark:border-slate-700 flex justify-between items-center flex-none">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-blue-500"></i> Master Approval Rules (WO Matrix)
            </h3>
            <button onclick="document.getElementById('modal-master-approval').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        
        <div class="p-5 overflow-y-auto flex-1 text-xs space-y-4">
            <div class="flex justify-end">
                <button type="button" onclick="openAddApprovalModal()" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] rounded-sm transition-colors flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-plus text-[9px]"></i> Add Rule
                </button>
            </div>
            <div class="overflow-x-auto border border-slate-300 dark:border-slate-700 rounded-sm">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-700 text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase">
                            <th class="p-2.5 w-16 text-center">Level</th>
                            <th class="p-2.5">Position Label</th>
                            <th class="p-2.5">Action Header</th>
                            <th class="p-2.5">Department</th>
                            <th class="p-2.5">Specific Approvers</th>
                            <th class="p-2.5 w-16 text-center">Order</th>
                            <th class="p-2.5 w-16 text-center">Status</th>
                            <th class="p-2.5 w-24 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                        <template x-for="rule in approvalRulesListFull" :key="rule.id">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                                <td class="p-2.5 text-center font-medium" x-text="rule.approval_level"></td>
                                <td class="p-2.5 font-medium text-slate-800 dark:text-slate-200" x-text="rule.position_label"></td>
                                <td class="p-2.5">
                                    <span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-750 border border-slate-300 dark:border-slate-700 text-slate-650 dark:text-slate-300 font-medium rounded-sm" x-text="rule.action_label || 'Checked'"></span>
                                </td>
                                <td class="p-2.5 font-medium text-slate-700 dark:text-slate-350" x-text="rule.department_code || '—'"></td>
                                <td class="p-2.5 text-slate-500 dark:text-slate-400 text-[10px]" x-text="rule.approver_users_list_names || 'Any in Department'"></td>
                                <td class="p-2.5 text-center font-mono" x-text="rule.sort_order"></td>
                                <td class="p-2.5 text-center">
                                    <template x-if="rule.is_active">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-450 border border-emerald-200 dark:border-emerald-800/50 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    </template>
                                    <template x-if="!rule.is_active">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-450 border border-slate-300 dark:border-slate-700 rounded-full">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                                            Inactive
                                        </span>
                                    </template>
                                </td>
                                <td class="p-2.5 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <button type="button" @click="openEditApprovalModal(rule)" class="p-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-blue-400 text-slate-600 dark:text-slate-300 hover:text-blue-650 rounded-sm transition-colors flex items-center justify-center w-7 h-7" title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                        </button>
                                        <button type="button" @click="deleteApprovalAjax(rule.id)" class="p-1.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-250 dark:bg-rose-900/50 text-rose-600 dark:text-rose-450 hover:border-rose-500 rounded-sm transition-colors flex items-center justify-center w-7 h-7" title="Delete">
                                            <i class="fa-solid fa-trash-can text-[11px]"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="p-4 border-t border-slate-300 dark:border-slate-700 flex justify-end flex-none">
            <button onclick="document.getElementById('modal-master-approval').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm font-bold text-xs transition-colors cursor-pointer">Close</button>
        </div>
    </div>
</div>

{{-- ── MODAL: APPROVAL RULE CONFIGURATION (ADD/EDIT) ────────────────── --}}
<div id="modal-approval-config" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-md border border-slate-300 dark:border-slate-700">
        <div class="p-4 border-b border-slate-300 dark:border-slate-700 flex justify-between items-center">
            <h3 id="modal-approval-title" class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Add Master Approval Rule</h3>
            <button onclick="document.getElementById('modal-approval-config').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="approval-config-form" action="" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <input type="hidden" name="document_type" value="SPK">
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Approval Level <span class="text-rose-500">*</span></label>
                <input type="number" name="approval_level" id="app_level" required min="1" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Position Label <span class="text-rose-500">*</span></label>
                <input type="text" name="position_label" id="app_position" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Sign-off Header <span class="text-rose-500">*</span></label>
                <select name="action_label" id="app_action" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="Checked">Checked</option>
                    <option value="Approved">Approved</option>
                    <option value="Reviewed">Reviewed</option>
                    <option value="Received">Received</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Responsible Dept <span class="text-rose-500">*</span></label>
                <select name="department_id" id="app_dept" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500 cursor-pointer select2-department">
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                    @endforeach
                </select>
            </div>
            <div x-data="approverSelect('master_approver_user_ids', [], [])" x-on:set-approvers.window="if ($event.detail.target === 'master_approver_user_ids') setSelected($event.detail.ids)" class="relative">
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">
                    Specific Approver(s)
                    <span class="text-slate-300 text-[9px] normal-case font-normal ml-1">(searchable — select multiple)</span>
                </label>
                {{-- Hidden select that actually submits the array of IDs --}}
                <select name="approver_user_ids[]" id="master_approver_user_ids" multiple class="hidden">
                </select>
                {{-- Search input --}}
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
                           placeholder="Search approver..."
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 pr-8 rounded-sm focus:outline-none focus:border-blue-500">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </span>
                    <div x-show="open" x-transition
                         class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm shadow-lg max-h-44 overflow-y-auto">
                        <template x-for="item in filtered" :key="item.id">
                            <div @click="toggle(item)"
                                 :class="selectedIds.includes(item.id) ? 'bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-400' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50'"
                                 class="flex items-center justify-between px-3 py-1.5 text-xs cursor-pointer">
                                <span x-text="item.label"></span>
                                <i x-show="selectedIds.includes(item.id)" class="fa-solid fa-check text-[9px] text-blue-500"></i>
                            </div>
                        </template>
                        <template x-if="filtered.length === 0">
                            <div class="px-3 py-2 text-slate-400 italic text-[11px]">No users found for this department</div>
                        </template>
                    </div>
                </div>
                {{-- Tags list --}}
                <div class="mt-1 flex flex-wrap gap-1 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-sm min-h-[30px]">
                    <template x-if="selectedIds.length === 0">
                        <span class="text-[9px] text-slate-400 italic self-center">No approver selected</span>
                    </template>
                    <template x-for="item in selectedItems" :key="item.id">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-100 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 text-[9px] font-semibold rounded-full border border-blue-200 dark:border-blue-800">
                            <span x-text="item.label"></span>
                            <button type="button" @click="remove(item.id)" class="hover:text-blue-950 dark:hover:text-white">&times;</button>
                        </span>
                    </template>
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Sort Order</label>
                <input type="number" name="sort_order" id="app_sort" value="0" min="0" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div id="app_active_wrapper" class="flex items-center gap-2 py-1">
                <input type="checkbox" name="is_active" id="app_active" value="1" checked class="rounded-sm text-blue-600">
                <label for="app_active" class="font-semibold text-slate-700 dark:text-slate-350">Active status</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-300 dark:border-slate-750">
                <button type="button" onclick="document.getElementById('modal-approval-config').classList.add('hidden')" class="px-3.5 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-sm font-bold text-xs transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-sm text-xs transition-colors cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: BOM ITEM CONFIGURATION (ADD/EDIT) ────────────────── --}}
<div id="modal-bom-item" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-sm shadow-2xl w-full max-w-md border border-slate-300 dark:border-slate-700">
        <div class="p-4 border-b border-slate-300 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                <span x-text="activeBom.index === null ? 'Add' : 'Edit'"></span> BOM Item
            </h3>
            <button onclick="document.getElementById('modal-bom-item').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form @submit.prevent="saveBomItem()" class="p-5 space-y-4 text-xs">
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Part Number <span class="text-rose-500">*</span></label>
                <input type="text" x-model="activeBom.customer_part_no" required :disabled="!!activeBom.inquiry_product_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Part Name <span class="text-rose-500">*</span></label>
                <input type="text" x-model="activeBom.customer_part_name" required :disabled="!!activeBom.inquiry_product_id" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Parent Item</label>
                <select id="bom_parent_select" class="w-full">
                    {{-- Dynamically populated via Select2 --}}
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Remarks</label>
                <textarea x-model="activeBom.remarks" rows="2" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-sm focus:outline-none focus:border-blue-500" placeholder="Remarks..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-300 dark:border-slate-750">
                <button type="button" onclick="document.getElementById('modal-bom-item').classList.add('hidden')" class="px-3.5 py-1.5 border border-slate-300 dark:border-slate-750 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-900 rounded-sm font-bold transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-sm transition-colors cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>
</div> <!-- closes spkFormContainer -->
</div> <!-- closes spkFormWrapper -->

<script>
function openAddProcessModal() {
    document.getElementById('modal-process-title').innerText = 'Add Master Process';
    document.getElementById('process-config-form').action = '{{ route('management.process-checklist.store') }}';
    document.getElementById('proc_code').value = '';
    document.getElementById('proc_name').value = '';
    document.getElementById('proc_active').checked = true;
    document.getElementById('proc_active_wrapper').classList.add('hidden'); // hide on add

    window.dispatchEvent(new CustomEvent('set-proc-depts', { detail: { target: 'proc_dept_select', ids: [] } }));
    window.dispatchEvent(new CustomEvent('set-proc-pics', { detail: { target: 'proc_dept_select', pics: {} } }));
    document.getElementById('modal-process-config').classList.remove('hidden');
}

function openEditProcessModal(proc) {
    document.getElementById('modal-process-title').innerText = 'Edit Master Process';
    document.getElementById('process-config-form').action = '{{ url('management/process-checklist') }}/' + proc.id + '/update';
    document.getElementById('proc_code').value = proc.process_code;
    document.getElementById('proc_name').value = proc.process_name;
    document.getElementById('proc_active').checked = proc.is_active;
    document.getElementById('proc_active_wrapper').classList.remove('hidden'); // show on edit

    // Dispatch event to Alpine custom widget to set selected departments
    let deptIds = [];
    let defaultPics = {};
    try {
        let parsed = JSON.parse(proc.default_assigned_departments || '[]');
        deptIds = parsed.map(d => typeof d === 'object' ? d.department_id : d).map(Number);
        parsed.forEach(d => {
            if (typeof d === 'object' && d.default_pic_user_id) {
                defaultPics[d.department_id] = d.default_pic_user_id;
            }
        });
    } catch(e) {}
    window.dispatchEvent(new CustomEvent('set-proc-depts', { detail: { target: 'proc_dept_select', ids: deptIds } }));
    window.dispatchEvent(new CustomEvent('set-proc-pics', { detail: { target: 'proc_dept_select', pics: defaultPics } }));

    document.getElementById('modal-process-config').classList.remove('hidden');
}

function openAddApprovalModal() {
    document.getElementById('modal-approval-title').innerText = 'Add Master Approval Rule';
    document.getElementById('approval-config-form').action = '{{ route('management.approval-config.store') }}';
    document.getElementById('app_level').value = '1';
    document.getElementById('app_position').value = '';
    document.getElementById('app_action').value = 'Checked';
    $('#app_dept').val($('#app_dept option:first').val()).trigger('change');
    document.getElementById('app_sort').value = '0';
    document.getElementById('app_active').checked = true;
    document.getElementById('app_active_wrapper').classList.add('hidden'); // hide active check on add

    // Reset selected approvers list in Alpine custom component
    window.dispatchEvent(new CustomEvent('update-selected-approvers', {
        detail: {
            target: 'master_approver_user_ids',
            ids: []
        }
    }));
    
    document.getElementById('modal-approval-config').classList.remove('hidden');
}

function openEditApprovalModal(rule) {
    document.getElementById('modal-approval-title').innerText = 'Edit Master Approval Rule';
    document.getElementById('approval-config-form').action = '{{ url('management/approval-config') }}/' + rule.id + '/update';
    document.getElementById('app_level').value = rule.approval_level;
    document.getElementById('app_position').value = rule.position_label;
    document.getElementById('app_action').value = rule.action_label ?? 'Checked';
    $('#app_dept').val(rule.department_id).trigger('change');
    document.getElementById('app_sort').value = rule.sort_order;
    document.getElementById('app_active').checked = rule.is_active;
    document.getElementById('app_active_wrapper').classList.remove('hidden'); // show active check on edit

    // Populate selected approvers list in Alpine custom component
    let userIds = [];
    if (rule.approver_users && rule.approver_users.length > 0) {
        userIds = rule.approver_users.map(u => u.id);
    } else {
        userIds = (rule.approver_user_ids || []).map(Number);
    }
    window.dispatchEvent(new CustomEvent('update-selected-approvers', {
        detail: {
            target: 'master_approver_user_ids',
            ids: userIds
        }
    }));

    document.getElementById('modal-approval-config').classList.remove('hidden');
}
</script>
@php
    $procDeptsData = [];
    $procPicsData = [];
    $itemsSource = isset($workOrder) ? $workOrder->processes : $processes;
    foreach ($itemsSource as $proc) {
        $depts = json_decode(isset($workOrder) ? ($proc->pivot->assigned_departments ?? '[]') : ($proc->default_assigned_departments ?? '[]'), true) ?: [];
        $deptIds = [];
        foreach ($depts as $d) {
            $deptId = is_array($d) ? ($d['department_id'] ?? $d) : $d;
            if ($deptId) {
                $deptIds[] = (int)$deptId;
                // If it is in form edit mode, map the saved pic_user_id
                if (isset($workOrder) && is_array($d) && isset($d['pic_user_id'])) {
                    $procPicsData[$proc->id . '_' . $deptId] = $d['pic_user_id'];
                }
                // If it is in form create mode, map the default_pic_user_id
                if (!isset($workOrder) && is_array($d) && isset($d['default_pic_user_id'])) {
                    $procPicsData[$proc->id . '_' . $deptId] = $d['default_pic_user_id'];
                }
            }
        }
        $procDeptsData[$proc->id] = $deptIds;
    }

    // Query only the names of the user IDs present in $procPicsData (assigned PICs)
    $assignedUserIds = array_unique(array_filter(array_values($procPicsData)));
    $assignedUsersMap = \App\Models\User::whereIn('id', $assignedUserIds)->get()->mapWithKeys(fn($u) => [$u->id => ['name' => $u->name, 'email' => $u->email]]);

    $dueDatesClosedData = [];
    if (isset($workOrder)) {
        $merged = is_array($workOrder->due_dates_closed) ? $workOrder->due_dates_closed : [];
        foreach ($workOrder->approvals as $app) {
            $rule = \App\Models\ApprovalConfig::where('approval_level', $app->approval_level)
                ->where('department_id', $app->department_id)
                ->first();
            if ($rule && $app->due_date_closed) {
                $dateVal = is_string($app->due_date_closed) ? substr($app->due_date_closed, 0, 10) : $app->due_date_closed->format('Y-m-d');
                $merged[$rule->id] = $dateVal;
            }
        }
        $dueDatesClosedData = $merged;
    }

    $approvalsData = [];
    if (isset($workOrder)) {
        $approvalsData = $workOrder->approvals->sortBy('approval_level')->map(function($a) {
            $rule = \App\Models\ApprovalConfig::activeFor('SPK')
                ->where('approval_level', $a->approval_level)
                ->where('department_id', $a->department_id)
                ->first();
            return [
                'approval_level' => $a->approval_level,
                'approver_position' => $a->approver_position,
                'action_label' => $rule->action_label ?? 'Checked',
                'status' => $a->status,
                'approver_name' => $a->approver_name,
                'remarks' => $a->remarks,
                'department_code' => $a->department->code ?? ($a->department->name ?? '—'),
                'due_date_closed' => $a->due_date_closed ? $a->due_date_closed->format('Y-m-d') : null,
                'approved_at' => $a->approved_at ? \Carbon\Carbon::parse($a->approved_at)->format('d-M-Y H:i') : null
            ];
        })->values()->toArray();
    }

    // Prepare approvalRulesList and approvalRulesListFull to avoid nested Blade parser brackets conflicts
    $approvalRulesListMapped = $approvalRules->map(fn($r) => [
        'id' => $r->id,
        'department_id' => $r->department_id,
        'dept_code' => $r->department->code ?? $r->department->name ?? '',
        'approval_level' => $r->approval_level,
        'position_label' => $r->position_label
    ])->values()->toArray();

    $approvalRulesListFullMapped = $approvalRules->map(fn($r) => [
        'id' => $r->id,
        'rule_id' => $r->rule_id,
        'approval_level' => $r->approval_level,
        'position_label' => $r->position_label,
        'action_label' => $r->action_label ?? 'Checked',
        'department_id' => $r->department_id,
        'department_name' => $r->department->name ?? '—',
        'department_code' => $r->department->code ?? '',
        'approver_user_ids' => $r->approver_user_ids ?? [],
        'sort_order' => $r->sort_order,
        'is_active' => $r->is_active,
        'approver_users_list_names' => $r->approver_users->pluck('name')->implode(', '),
        'approver_users_list_emails' => $r->approver_users->pluck('email')->filter()->implode(', ')
    ])->values()->toArray();
@endphp

<script>
window.allUsersList = [];
window.assignedUsersMap = @json($assignedUsersMap);
document.addEventListener('alpine:init', () => {
    Alpine.data('spkForm', () => ({
        isEditable: {{ $isEditable ? 'true' : 'false' }},
        work_order_no: @json(isset($workOrder) ? $workOrder->work_order_no : $defaultSpkNo),
        subject: @json(isset($workOrder) ? $workOrder->subject : "Pekerjaan New 5P45"),
        department_id: '{{ isset($workOrder) ? $workOrder->department_id : ($departments->first()->id ?? 1) }}',
        priority: '{{ isset($workOrder) ? ($workOrder->priority ?: "STANDARD") : "STANDARD" }}',
        urgent_reason: @json(isset($workOrder) ? $workOrder->urgent_reason : ''),
        urgent_confirmed_by: @json(isset($workOrder) ? $workOrder->urgent_confirmed_by : ''),
        urgent_confirmed_at: @json(isset($workOrder) ? ($workOrder->urgent_confirmed_at ? (is_string($workOrder->urgent_confirmed_at) ? $workOrder->urgent_confirmed_at : $workOrder->urgent_confirmed_at->format('Y-m-d H:i')) : '') : ''),
        selected_processes: @json(isset($workOrder) ? $workOrder->processes->pluck('id') : []),
        process_departments: @json($procDeptsData),
        process_pics: @json((object)$procPicsData),
        support_departments: @json(isset($workOrder) ? $workOrder->supportDepartments->map(fn($d) => ['id' => $d->id, 'name' => $d->name . ($d->code ? " ({$d->code})" : "")]) : []),
        remarks: @json(isset($workOrder) ? $workOrder->remarks : ""),
        document_no: @json(isset($workOrder) ? ($workOrder->docFormat->document_no ?? 'FO-13-02') : ($woHeader->document_no ?? 'FO-13-02')),
        doc_department: @json(isset($workOrder) ? ($workOrder->docFormat->doc_department ?? 'Sales') : ($woHeader->doc_department ?? 'Sales')),
        doc_publish_date: @json(isset($workOrder) ? ($workOrder->docFormat->doc_publish_date ? \Carbon\Carbon::parse($workOrder->docFormat->doc_publish_date)->format('Y-m-d') : '2024-01-01') : ($woHeader->doc_publish_date ? \Carbon\Carbon::parse($woHeader->doc_publish_date)->format('Y-m-d') : '2024-01-01')),
        released_at: @json(isset($workOrder) ? ($workOrder->released_at ? (is_string($workOrder->released_at) ? substr($workOrder->released_at, 0, 10) : $workOrder->released_at->format('Y-m-d')) : '') : now()->format('Y-m-d')),
        page_hal: @json(isset($workOrder) ? ($workOrder->docFormat->page_hal ?? '1') : ($woHeader->page_hal ?? '1')),
        revision_no: {{ isset($workOrder) ? $workOrder->revision_no : 0 }},
        doc_revision_no: @json(isset($workOrder) ? ($workOrder->docFormat->revision_no ?? 0) : ($woHeader->revision_no ?? 0)),
        first_sample_date: @json(isset($workOrder) ? ($workOrder->first_sample_date ? (is_string($workOrder->first_sample_date) ? substr($workOrder->first_sample_date, 0, 10) : $workOrder->first_sample_date->format('Y-m-d')) : '') : ''),
        due_date_plan: @json(isset($workOrder) ? ($workOrder->due_date_plan ? (is_string($workOrder->due_date_plan) ? substr($workOrder->due_date_plan, 0, 10) : $workOrder->due_date_plan->format('Y-m-d')) : '') : ''),
        due_dates_closed: @json((object)$dueDatesClosedData),
        showPreview: true,
        loaded_approvals: @json($approvalsData),
        created_by: '{{ isset($workOrder) ? $workOrder->created_by : auth()->user()->name }}',
        created_at: '{{ isset($workOrder) ? $workOrder->created_at->format('Y-m-d') : now()->format('Y-m-d') }}',
        get approvals() {
            if (this.isEditable) {
                let list = [];
                let sortedSelectedRules = [...this.selected_approval_rules]
                    .map(id => this.approvalRulesList.find(r => r.id == id))
                    .filter(Boolean)
                    .sort((a, b) => a.approval_level - b.approval_level);

                sortedSelectedRules.forEach((rule, idx) => {
                    let ruleFull = this.approvalRulesListFull.find(r => r.id == rule.id);
                    list.push({
                        approval_level: rule.approval_level,
                        approver_position: rule.position_label,
                        action_label: ruleFull ? ruleFull.action_label : 'Checked',
                        status: 'Waiting',
                        approver_name: '',
                        remarks: '',
                        department_code: rule.dept_code,
                        due_date_closed: this.due_dates_closed[rule.id] || null,
                        approved_at: null
                    });
                });
                return list;
            } else {
                return this.loaded_approvals || [];
            }
        },
        holidays: [],
        effective_working_days: [],
        draggedIndex: null,
        activeBom: {
            index: null,
            tempId: null,
            parentTempId: '',
            customer_part_no: '',
            customer_part_name: '',
            eo: '',
            class_id: 'RM',
            uom: '',
            remarks: '',
            inquiry_product_id: null
        },
        selected_approval_rules: (@json(isset($workOrder) ? ($workOrder->selected_approval_rule_ids ?: $approvalRules->pluck('id')) : $approvalRules->pluck('id')) || []).map(Number),
        
        // All approval rules with their department_id for filtering
        approvalRulesList: @json($approvalRulesListMapped),
        approvalRulesListFull: @json($approvalRulesListFullMapped),
        
        // Departments list lookup
        departmentsList: @json($departments),
        processesList: @json($processes),
        usersList: [],
        
        // Pre-populated selected products
        products: @json(isset($workOrder) ? $workOrder->products->sortBy('sort_order')->values() : (isset($itemsData) ? $itemsData : ($inquiry->products ?? []))).map(p => ({
            id: p.id ?? null,
            ebd_item_id: p.ebd_item_id ?? null,
            tempId: 'prod_' + Math.random().toString(36).substr(2, 9),
            parent_id: p.parent_id ?? null,
            parentTempId: null,
            work_order_product_id: p.id ?? null,
            inquiry_product_id: p.inquiry_product_id ?? null,
            customer_code: '{{ isset($workOrder) ? ($workOrder->ebdHeader->customer->code ?? $workOrder->inquiry->customer->code ?? "—") : ($ebdHeader->customer->code ?? "—") }}',
            model_name: '{{ isset($workOrder) ? ($workOrder->ebdHeader->projectModel->name ?? $workOrder->inquiry->projectModel->name ?? "—") : ($ebdHeader->projectModel->name ?? "—") }}',
            customer_part_no: p.std_part_no ?? p.customer_part_no ?? '',
            customer_part_name: p.std_part_name ?? p.customer_part_name ?? '',
            std_part_no: p.std_part_no ?? p.customer_part_no ?? '',
            std_part_name: p.std_part_name ?? p.customer_part_name ?? '',
            std_qty: (p.std_qty !== null && p.std_qty !== undefined && p.std_qty !== '') ? p.std_qty : '',
            std_uom: p.std_uom ?? '',
            destination: p.destination ?? '',
            sop_date: p.sop_date ? (typeof p.sop_date === 'string' ? p.sop_date.substring(0, 10) : p.sop_date) : '',
            eol_date: p.eol_date ? (typeof p.eol_date === 'string' ? p.eol_date.substring(0, 10) : p.eol_date) : '',
            model_life: p.model_life ?? '',
            annual_volume: p.annual_volume ?? '',
            eo: p.eo ?? '',
            class_id: p.class_id ?? null,
            uom: p.std_uom ?? p.uom ?? null,
            variant: p.variant ?? '',
            has_2d_data: Boolean(p.has_2d_data),
            has_3d_data: Boolean(p.has_3d_data),
            has_tech_doc: Boolean(p.has_tech_doc),
            remarks: p.remarks ?? ''
        })),

        computedRevisionNo() {
            return String(this.revision_no).padStart(2, '0');
        },
        computedDocRevisionNo() {
            return String(this.doc_revision_no).padStart(2, '0');
        },

        async init() {
            // Resolve parentTempId relationships on page load
            this.products.forEach(p => {
                if (p.parent_id) {
                    let parent = this.products.find(x => x.id == p.parent_id);
                    if (parent) {
                        p.parentTempId = parent.tempId;
                    }
                }
            });

            this.updateDepartmentIdFromProcesses();
            this.syncGlobalSupportDepartments(); // also triggers updateApprovalSuggestions
            
            // Load users list dynamically
            try {
                let r = await fetch('{{ route('management.api.users') }}');
                if (r.ok) {
                    let data = await r.json();
                    window.allUsersList = data;
                    this.usersList = data;
                    
                    // Update names for any placeholder select2 options that were rendered before user list loaded
                    $('select').each(function() {
                        let selectEl = $(this);
                        let val = selectEl.val();
                        if (val) {
                            let values = Array.isArray(val) ? val : [val];
                            let updated = false;
                            values.forEach(v => {
                                let option = selectEl.find('option[value="' + v + '"]');
                                if (option.length > 0 && option.text().startsWith('User ID ')) {
                                    let u = data.find(user => user.id == v);
                                    if (u) {
                                        option.text(u.name);
                                        updated = true;
                                    }
                                }
                            });
                            if (updated) {
                                selectEl.trigger('change.select2');
                            }
                        }
                    });

                    // Dispatch to master approver custom select list
                    window.dispatchEvent(new CustomEvent('update-all-items', {
                        detail: {
                            target: 'master_approver_user_ids',
                            items: data.map(u => ({ id: u.id, label: u.name, id_dept: u.id_dept }))
                        }
                    }));
                }
            } catch (e) {
                console.error('Failed to load users list dynamically', e);
            }

            try {
                let response = await fetch('{{ route('management.calendar.holidays') }}');
                if (response.ok) {
                    let data = await response.json();
                    this.holidays = data.holidays || [];
                    this.effective_working_days = data.effective_working_days || [];
                }
            } catch (e) {
                console.error('Failed to load holidays list', e);
            }
            this.sortProductsHierarchically();
            this.checkPrioritySuggestions();
        },

        checkPrioritySuggestions() {
            if (!this.isEditable) return;
            if (!this.due_date_plan) {
                this.priority = 'STANDARD';
                return;
            }

            let targetDate = new Date(this.due_date_plan);
            if (isNaN(targetDate.getTime())) {
                this.priority = 'STANDARD';
                return;
            }

            let today = new Date();
            today.setHours(0,0,0,0);
            targetDate.setHours(0,0,0,0);

            // Calculate working days between today (exclusive) and targetDate (inclusive)
            let workingDays = 0;
            let current = new Date(today);
            current.setDate(current.getDate() + 1);

            while (current <= targetDate) {
                let dayOfWeek = current.getDay(); // 0 = Sunday, 6 = Saturday
                let y = current.getFullYear();
                let m = String(current.getMonth() + 1).padStart(2, '0');
                let d = String(current.getDate()).padStart(2, '0');
                let dateStr = `${y}-${m}-${d}`;
                
                let isEffectiveWorking = this.effective_working_days.includes(dateStr);
                let isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
                let isHoliday = this.holidays.includes(dateStr);

                if (isEffectiveWorking || (!isWeekend && !isHoliday)) {
                    workingDays++;
                }
                current.setDate(current.getDate() + 1);
            }

            if (workingDays < 10) {
                this.priority = 'URGENT';
            } else if (workingDays <= 14) {
                this.priority = 'STANDARD';
            } else {
                this.priority = 'LOW';
            }
        },

        getDepartmentName(id) {
            let dept = this.departmentsList.find(d => d.id == id);
            return dept ? (dept.name + (dept.code ? ` (${dept.code})` : '')) : '';
        },

        getDeptCodeByRuleId(ruleId) {
            let rule = this.approvalRulesList.find(r => String(r.id) === String(ruleId));
            return rule ? rule.dept_code : '—';
        },

        getDeptCodeById(deptId) {
            let dept = this.departmentsList.find(d => d.id == deptId);
            return dept ? (dept.code || dept.name) : '';
        },

        getUsersByDept(deptId) {
            return this.usersList.filter(u => u.id_dept == deptId);
        },

        isProcessSelected(id) {
            return this.selected_processes.map(Number).includes(Number(id));
        },

        toggleProcess(id) {
            if (!this.isEditable) return;
            const numId = Number(id);
            if (this.isProcessSelected(numId)) {
                this.selected_processes = this.selected_processes.filter(v => Number(v) !== numId);
            } else {
                this.selected_processes = [...this.selected_processes, numId];
                // Set default owner department if not already set
                if (!this.process_departments[numId] || this.process_departments[numId].length === 0) {
                    let proc = this.processesList.find(p => p.id == numId);
                    if (proc && proc.default_assigned_departments) {
                        try {
                            let parsed = JSON.parse(proc.default_assigned_departments) || [];
                            let deptIds = parsed.map(d => typeof d === 'object' ? d.department_id : d).map(Number);
                            this.process_departments[numId] = deptIds;
                            
                            // Pre-fill PICs if default PIC exists in object
                            parsed.forEach(d => {
                                if (typeof d === 'object' && d.default_pic_user_id) {
                                    this.process_pics[numId + '_' + d.department_id] = d.default_pic_user_id;
                                }
                            });
                        } catch (e) {
                            console.error(e);
                        }
                    }
                }
            }
            this.updateDepartmentIdFromProcesses();
            this.syncGlobalSupportDepartments();
        },

        syncGlobalSupportDepartments() {
            let allIds = [];
            this.selected_processes.forEach(procId => {
                let depts = this.process_departments[procId] || [];
                depts.forEach(id => {
                    let parsed = parseInt(id);
                    if (!isNaN(parsed) && !allIds.includes(parsed) && parsed != this.department_id) {
                        allIds.push(parsed);
                    }
                });
            });

            // Map IDs to departments list objects
            let list = allIds.map(id => {
                let dept = this.departmentsList.find(d => d.id == id);
                return dept ? { id: dept.id, name: dept.name + (dept.code ? ` (${dept.code})` : '') } : null;
            }).filter(Boolean);

            this.support_departments = list;
            this.updateApprovalSuggestions();
        },

        /**
         * Returns all department IDs currently selected across all process checklists.
         */
        getSelectedDepartmentIds() {
            let ids = [];
            this.selected_processes.forEach(procId => {
                let depts = this.process_departments[procId] || [];
                depts.forEach(id => {
                    let parsed = parseInt(id);
                    if (!isNaN(parsed) && !ids.includes(parsed)) {
                        ids.push(parsed);
                    }
                });
            });
            return ids;
        },

        /**
         * Returns true if the given department_id is among the departments
         * selected in the Process Checklist.
         */
        isSuggestedRule(deptId) {
            return this.getSelectedDepartmentIds().includes(parseInt(deptId));
        },

        /**
         * Auto-select approval rules whose department matches selected process departments,
         * and deselect rules whose department no longer matches (only if editable).
         */
        updateApprovalSuggestions() {
            if (!this.isEditable) return;
            let selectedDeptIds = this.getSelectedDepartmentIds();
            
            // Only auto-select suggested rules; do not automatically deselect manually checked ones
            this.approvalRulesList.forEach(rule => {
                let isNowSuggested = selectedDeptIds.includes(parseInt(rule.department_id));
                let currentlySelected = this.selected_approval_rules.map(Number).includes(rule.id);
                if (isNowSuggested && !currentlySelected) {
                    this.selected_approval_rules.push(rule.id);
                }
            });
        },

        updateDepartmentIdFromProcesses() {
            if (this.selected_processes.length > 0) {
                let firstProc = this.processesList.find(p => p.id == this.selected_processes[0]);
                if (firstProc && firstProc.default_assigned_departments) {
                    try {
                        let parsed = JSON.parse(firstProc.default_assigned_departments) || [];
                        if (parsed.length > 0) {
                            let firstDept = parsed[0];
                            let deptId = typeof firstDept === 'object' ? firstDept.department_id : firstDept;
                            this.department_id = Number(deptId);
                        }
                    } catch (e) {
                        console.error(e);
                    }
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

        getUniqueSchedules() {
            let uniqueMap = {};
            this.products.forEach(p => {
                if (p.parentTempId || p.parent_id) return;
                let label = p.variant || p.destination || '';
                let key = `${label}-${p.sop_date}`;
                if (!uniqueMap[key]) {
                    uniqueMap[key] = {
                        variant: label,
                        sop_date: p.sop_date,
                        annual_volume: p.annual_volume,
                        remarks: p.remarks || ''
                    };
                }
            });
            return Object.values(uniqueMap);
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
        },

        async refreshProcesses() {
            try {
                let r = await fetch('{{ route("management.api.processes") }}');
                if (r.ok) {
                    let oldProcesses = JSON.parse(JSON.stringify(this.processesList));
                    this.processesList = await r.json();
                    
                    // For any process that is currently selected:
                    // If its default assigned departments changed in the master data,
                    // we should update its active assigned departments in our form.
                    this.selected_processes.forEach(numId => {
                        let oldProc = oldProcesses.find(p => p.id == numId);
                        let newProc = this.processesList.find(p => p.id == numId);
                        if (newProc && (!oldProc || oldProc.default_assigned_departments !== newProc.default_assigned_departments)) {
                            try {
                                let parsed = JSON.parse(newProc.default_assigned_departments) || [];
                                let deptIds = parsed.map(d => typeof d === 'object' ? d.department_id : d).map(Number);
                                this.process_departments[numId] = deptIds;
                                
                                // Also update PICs
                                parsed.forEach(d => {
                                    if (typeof d === 'object' && d.default_pic_user_id) {
                                        this.process_pics[numId + '_' + d.department_id] = d.default_pic_user_id;
                                    }
                                });
                            } catch (e) {
                                console.error(e);
                            }
                        }
                    });
                    
                    this.syncGlobalSupportDepartments();
                }
            } catch (e) {
                console.error(e);
            }
        },

        async refreshApprovalRules() {
            try {
                let r = await fetch('{{ route("management.api.approval-rules") }}');
                if (r.ok) {
                    let data = await r.json();
                    this.approvalRulesListFull = data;
                    this.approvalRulesList = data.map(r => ({
                        id: r.id,
                        department_id: r.department_id,
                        dept_code: r.department_code
                    }));
                }
            } catch (e) {
                console.error(e);
            }
        },

        getProcessDefaultDeptsLabel(p) {
            try {
                let parsed = JSON.parse(p.default_assigned_departments || '[]');
                return parsed.map(d => {
                    let deptId = typeof d === 'object' ? d.department_id : d;
                    let dept = this.departmentsList.find(x => x.id == deptId);
                    return dept ? dept.code : '';
                }).filter(Boolean).join(', ') || '—';
            } catch (e) {
                return '—';
            }
        },

        initParentSelect2() {
            this.$nextTick(() => {
                const $select = $('#bom_parent_select');
                $select.empty();
                $select.append('<option value="">-- None (Top Level) --</option>');
                
                this.products.forEach(parent => {
                    if (parent.tempId !== this.activeBom.tempId) {
                        const isSelected = parent.tempId === this.activeBom.parentTempId;
                        const optionText = `${parent.customer_part_no || 'New Item'} - ${parent.customer_part_name || ''}`;
                        const option = new Option(optionText, parent.tempId, isSelected, isSelected);
                        $select.append(option);
                    }
                });

                if ($select.hasClass("select2-hidden-accessible")) {
                    $select.select2('destroy');
                }
                
                $select.select2({
                    dropdownParent: $('#modal-bom-item'),
                    width: '100%'
                }).off('change').on('change', (e) => {
                    this.activeBom.parentTempId = $(e.target).val() || '';
                });
            });
        },

        openAddBom() {
            this.activeBom = {
                index: null,
                tempId: 'prod_' + Math.random().toString(36).substr(2, 9),
                parentTempId: '',
                customer_part_no: '',
                customer_part_name: '',
                eo: '',
                class_id: 'RM',
                uom: 'Pcs',
                remarks: '',
                inquiry_product_id: null
            };
            this.initParentSelect2();
            document.getElementById('modal-bom-item').classList.remove('hidden');
        },

        openEditBom(index) {
            let p = this.products[index];
            this.activeBom = {
                index: index,
                tempId: p.tempId,
                parentTempId: p.parentTempId || '',
                customer_part_no: p.customer_part_no,
                customer_part_name: p.customer_part_name,
                eo: p.eo || '',
                class_id: p.class_id || 'FG',
                uom: p.uom || 'Kg',
                remarks: p.remarks || '',
                inquiry_product_id: p.inquiry_product_id
            };
            this.initParentSelect2();
            document.getElementById('modal-bom-item').classList.remove('hidden');
        },

        saveBomItem() {
            if (this.activeBom.index === null) {
                // Add new
                this.products.push({
                    id: null,
                    tempId: this.activeBom.tempId,
                    parent_id: null,
                    parentTempId: this.activeBom.parentTempId,
                    work_order_product_id: null,
                    inquiry_product_id: null,
                    customer_code: '{{ isset($workOrder) ? ($workOrder->inquiry->customer->code ?? "") : ($inquiry->customer->code ?? "") }}',
                    model_name: '{{ isset($workOrder) ? ($workOrder->inquiry->projectModel->name ?? "") : ($inquiry->projectModel->name ?? "") }}',
                    customer_part_no: this.activeBom.customer_part_no,
                    customer_part_name: this.activeBom.customer_part_name,
                    destination: '',
                    sop_date: '',
                    eol_date: '',
                    model_life: '',
                    annual_volume: '',
                    eo: this.activeBom.eo,
                    class_id: this.activeBom.class_id,
                    uom: this.activeBom.uom,
                    variant: '',
                    has_2d_data: false,
                    has_3d_data: false,
                    has_tech_doc: false,
                    remarks: this.activeBom.remarks
                });
            } else {
                // Edit existing
                let p = this.products[this.activeBom.index];
                p.parentTempId = this.activeBom.parentTempId;
                p.customer_part_no = this.activeBom.customer_part_no;
                p.customer_part_name = this.activeBom.customer_part_name;
                p.eo = this.activeBom.eo;
                p.class_id = this.activeBom.class_id;
                p.uom = this.activeBom.uom;
                p.remarks = this.activeBom.remarks;
            }
            this.sortProductsHierarchically();
            document.getElementById('modal-bom-item').classList.add('hidden');
        },

        removeBomItem(index) {
            let target = this.products[index];
            this.products.forEach(p => {
                if (p.parentTempId === target.tempId) {
                    p.parentTempId = '';
                    p.parent_id = null;
                }
            });
            this.products.splice(index, 1);
        },

        getBomDepth(prod) {
            let depth = 0;
            let current = prod;
            while (current && current.parentTempId) {
                depth++;
                current = this.products.find(p => p.tempId === current.parentTempId);
                if (depth > 10) break;
            }
            return depth;
        },

        sortProductsHierarchically() {
            let sorted = [];
            const addChildren = (parentTempId) => {
                let children = this.products.filter(p => p.parentTempId === parentTempId);
                children.forEach(child => {
                    sorted.push(child);
                    addChildren(child.tempId);
                });
            };

            let roots = this.products.filter(p => !p.parentTempId);
            roots.forEach(root => {
                sorted.push(root);
                addChildren(root.tempId);
            });

            // Add orphans
            this.products.forEach(p => {
                if (!sorted.includes(p)) {
                    sorted.push(p);
                }
            });

            this.products = sorted;
        }
    }));
});
</script>
@endsection


@push('styles')
<style>
    /* Compact override for process-dept multi-select inside BOM rows */
    .select2-container--default .select2-selection--multiple.select2-process-select {
        min-height: 28px !important;
        padding: 1px 4px !important;
    }
    .select2-container--default .select2-selection--multiple.select2-process-select .select2-selection__choice {
        font-size: 10px !important;
        font-weight: 700 !important;
        padding: 1px 6px !important;
        margin: 2px 3px 2px 0 !important;
    }
    .select2-container--default .select2-selection--multiple.select2-process-select .select2-search__field {
        margin-top: 2px !important;
        font-size: 10px !important;
        height: 20px !important;
        line-height: 20px !important;
    }
</style>
@endpush

@push('scripts')
<script>
    window.confirmDeleteWO = function() {
        confirmDialog({
            title: 'Delete Work Order',
            text: 'Are you sure you want to delete this Work Order? This action cannot be undone!',
            icon: 'warning',
            confirmButtonText: 'Yes, Delete',
            confirmButtonColor: '#ef4444',
            onConfirm: () => {
                document.getElementById('deleteWoForm').submit();
            }
        });
    };

    window.confirmReviseWO = function() {
        confirmDialog({
            title: 'Revise Work Order',
            text: 'Create a new draft revision of this WO? This will make the current revision read-only.',
            icon: 'question',
            confirmButtonText: 'Yes, Revise',
            confirmButtonColor: '#4f46e5',
            onConfirm: () => {
                document.getElementById('reviseWoForm').submit();
            }
        });
    };

    window.confirmSubmitApproval = function() {
        // Access Alpine component data
        const formEl = document.getElementById('spkForm');
        const alpine = formEl ? Alpine.$data(formEl) : null;

        let approverHtml = '';
        let picHtml = '';

        if (alpine) {
            // 1. Build approver list from selected rules
            const selectedRules = alpine.selected_approval_rules || [];
            const activeApprovers = alpine.approvalRulesListFull
                .filter(r => r.is_active && selectedRules.includes(r.id))
                .sort((a, b) => a.approval_level - b.approval_level);

            if (activeApprovers.length > 0) {
                approverHtml = '<div style="text-align:left;margin-bottom:12px;">' +
                    '<div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;border-bottom:1px solid #e2e8f0;padding-bottom:4px;">Approval Signatories</div>';
                activeApprovers.forEach(rule => {
                    const names = rule.approver_users_list_names || 'All users in dept';
                    const emails = rule.approver_users_list_emails || '—';
                    approverHtml += '<div style="padding:6px 0;border-bottom:1px dashed #f1f5f9;">' +
                        '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                        '<div><span style="font-size:12px;font-weight:700;color:#0f172a;">' + (rule.position_label || rule.action_label) + '</span>' +
                        '<span style="font-size:10px;color:#94a3b8;margin-left:6px;">Lv.' + rule.approval_level + '</span></div>' +
                        '<span style="font-size:10px;font-weight:600;color:#475569;">' + (rule.department_name || '—') + '</span></div>' +
                        '<div style="margin-top:2px;"><span style="font-size:10px;color:#64748b;"><i class="fa-solid fa-user" style="margin-right:3px;"></i>' + names + '</span></div>' +
                        '<div style="margin-top:1px;"><span style="font-size:10px;color:#3b82f6;"><i class="fa-solid fa-envelope" style="margin-right:3px;"></i>' + emails + '</span></div>' +
                        '</div>';
                });
                approverHtml += '</div>';
            }

            // 2. Build PIC list from process_pics
            const processPics = alpine.process_pics || {};
            const picEntries = Object.entries(processPics).filter(([k, v]) => v);
            if (picEntries.length > 0) {
                // Group by user
                const userProcessMap = {};
                picEntries.forEach(([key, userId]) => {
                    const [procId] = key.split('_');
                    const proc = alpine.processesList.find(p => p.id == procId);
                    const procName = proc ? proc.process_name : ('Process #' + procId);
                    if (!userProcessMap[userId]) userProcessMap[userId] = { name: null, processes: [] };
                    userProcessMap[userId].processes.push(procName);
                });
                // Resolve names
                const usersMap = window.assignedUsersMap || {};
                Object.keys(userProcessMap).forEach(uid => {
                    if (usersMap[uid]) {
                        userProcessMap[uid].name = usersMap[uid].name || usersMap[uid];
                        userProcessMap[uid].email = usersMap[uid].email || '—';
                    } else {
                        const allU = window.allUsersList || [];
                        const found = allU.find(u => u.id == uid);
                        userProcessMap[uid].name = found ? found.text : ('User #' + uid);
                        userProcessMap[uid].email = '—';
                    }
                });

                picHtml = '<div style="text-align:left;margin-bottom:4px;">' +
                    '<div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;border-bottom:1px solid #e2e8f0;padding-bottom:4px;">Process Checklist PICs</div>';
                Object.entries(userProcessMap).forEach(([uid, info]) => {
                    picHtml += '<div style="padding:6px 0;border-bottom:1px dashed #f1f5f9;">' +
                        '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                        '<span style="font-size:12px;font-weight:700;color:#0f172a;">' + (info.name || '—') + '</span>' +
                        '<span style="font-size:10px;color:#64748b;text-align:right;">' + [...new Set(info.processes)].join(', ') + '</span></div>' +
                        '<div style="margin-top:1px;"><span style="font-size:10px;color:#3b82f6;"><i class="fa-solid fa-envelope" style="margin-right:3px;"></i>' + (info.email || '—') + '</span></div>' +
                        '</div>';
                });
                picHtml += '</div>';
            }
        }

        const summaryHtml = (approverHtml || picHtml) ?
            '<p style="font-size:13px;color:#475569;margin-bottom:14px;">Submitting this Work Order will send <strong>email notifications</strong> to the following people:</p>' +
            '<div style="max-height:280px;overflow-y:auto;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:8px;">' +
            approverHtml + picHtml + '</div>' +
            '<p style="font-size:11px;color:#94a3b8;margin-top:6px;">Are you sure you want to proceed?</p>'
            : '<p style="font-size:13px;color:#475569;">Are you sure you want to submit this Work Order for approval?</p>';

        Swal.fire({
            title: 'Submit for Approval',
            html: summaryHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            width: '520px',
            customClass: { popup: 'text-sm' }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('submitApprovalForm').submit();
            }
        });
    };

    window.initPicSelect2 = function(el, deptId, initialValue, onChangeCallback, isDisabled = false) {
        $(el).select2({
            placeholder: '-- Choose PIC --',
            width: '192px',
            disabled: isDisabled,
            ajax: {
                url: '{{ route("management.api.users") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        select2: 1,
                        q: params.term,
                        id_dept: deptId
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        }).on('change', function() {
            onChangeCallback($(this).val());
        });

        // Set initial value
        if (initialValue) {
            let name = 'User ID ' + initialValue;
            if (window.assignedUsersMap && window.assignedUsersMap[initialValue]) {
                name = window.assignedUsersMap[initialValue].name || window.assignedUsersMap[initialValue];
            } else if (window.allUsersList && window.allUsersList.length > 0) {
                let u = window.allUsersList.find(user => user.id == initialValue);
                if (u) name = u.name;
            }
            let option = new Option(name, initialValue, true, true);
            $(el).append(option).trigger('change.select2');
        }
    };

    $(document).ready(function() {
        $('.select2-department').select2({
            placeholder: "-- Choose Department --",
            width: '100%'
        }).on('change', function() {
            window.dispatchEvent(new CustomEvent('update-approver-dept', {
                detail: {
                    deptId: $(this).val()
                }
            }));
        });



        // Initialize select2 for each process department selection
        $('.select2-process-depts').select2({
            placeholder: "Assign Departments...",
            containerCssClass: "select2-process-select-container",
            selectionCssClass: "select2-process-select"
        }).on('change', function() {
            let procId = $(this).data('process-id');
            let selectedValues = $(this).val() || [];
            
            let formEl = document.getElementById('spkFormWrapper');
            if (formEl) {
                let alpineData = Alpine.$data(formEl);
                alpineData.process_departments[procId] = selectedValues.map(id => parseInt(id));
                alpineData.syncGlobalSupportDepartments();
            }
        });

        // Initialize state mapping on load (especially for edit/revision screen)
        setTimeout(() => {
            let formEl = document.getElementById('spkFormWrapper');
            if (formEl) {
                let alpineData = Alpine.$data(formEl);
                $('.select2-process-depts').each(function() {
                    let procId = $(this).data('process-id');
                    let selectedValues = $(this).val() || [];
                    alpineData.process_departments[procId] = selectedValues.map(id => parseInt(id));
                });
                alpineData.syncGlobalSupportDepartments();
            }
        }, 300);

        // Intercept form submission: collect all data from Alpine state and post as JSON
        // This avoids sending nested [] arrays in the body which can trigger Nginx/WAF blocks
        $('#spkForm').on('submit', function(e) {
            e.preventDefault();
            let $form = $(this);
            let url = $form.attr('action');
            let formEl = document.getElementById('spkFormWrapper');
            let alpine = formEl ? Alpine.$data(formEl) : {};

            // Collect selected processes from checked checkboxes
            let selectedProcesses = [];
            $form.find('input[name="processes[]"]:checked').each(function() {
                selectedProcesses.push($(this).val());
            });

            // Build payload as a plain JSON object
            let payload = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                _method: $form.find('input[name="_method"]').val() || 'POST',
                inquiry_id: $form.find('input[name="inquiry_id"]').val() || null,
                ebd_header_id: $form.find('input[name="ebd_header_id"]').val() || null,
                header_id: $form.find('input[name="header_id"]').val() || 1,
                department_id: alpine.department_id || '',
                wo_number: alpine.work_order_no || '',
                released_at: alpine.released_at || '',
                first_sample_date: alpine.first_sample_date || '',
                due_date_plan: alpine.due_date_plan || '',
                priority: alpine.priority || 'STANDARD',
                remarks: alpine.remarks || '',
                processes: selectedProcesses,
                selected_approval_rules: alpine.selected_approval_rules || [],
                due_dates_closed: alpine.due_dates_closed || {},
                products: (alpine.products || []).map(function(p) {
                    return {
                        work_order_product_id: p.work_order_product_id || null,
                        inquiry_product_id: p.inquiry_product_id || null,
                        ebd_item_id: p.ebd_item_id || null,
                        tempId: p.tempId || '',
                        parentTempId: p.parentTempId || '',
                        customer_part_no: p.customer_part_no || '',
                        customer_part_name: p.customer_part_name || '',
                        eo: p.eo || '',
                        class_id: p.class_id || '',
                        uom: p.uom || '',
                        remarks: p.remarks || '',
                        destination: p.destination || '',
                        sop_date: p.sop_date || '',
                        eol_date: p.eol_date || '',
                        model_life: p.model_life || '',
                        annual_volume: p.annual_volume || '',
                        variant: p.variant || '',
                        has_2d_data: p.has_2d_data || false,
                        has_3d_data: p.has_3d_data || false,
                        has_tech_doc: p.has_tech_doc || false
                    };
                }),
                process_departments: alpine.process_departments || {},
                process_pics: alpine.process_pics || {}
            };

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-HTTP-Method-Override': payload._method
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(response) {
                    if (response.success && response.redirect_url) {
                        showToast(response.message || 'Successfully saved SPK!', 'success');
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1500);
                    } else {
                        showToast(response.message || 'Successfully saved SPK!', 'success');
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to save SPK';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            let errorDetails = Object.values(xhr.responseJSON.errors).flat().join('\n- ');
                            msg += ':\n- ' + errorDetails;
                        } else if (xhr.responseJSON.message) {
                            msg += ': ' + xhr.responseJSON.message;
                        }
                    }
                    showToast(msg, 'error');
                }
            });
        });

        // Intercept Process Config Form via AJAX
        $('#process-config-form').on('submit', function(e) {
            e.preventDefault();
            let $form = $(this);
            let url = $form.attr('action');
            let formData = new FormData(this);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    showToast(response.message || 'Successfully saved process!', 'success');
                    document.getElementById('modal-process-config').classList.add('hidden');
                    
                    let formEl = document.getElementById('spkFormWrapper');
                    if (formEl) {
                        Alpine.$data(formEl).refreshProcesses();
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to save process';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ': ' + xhr.responseJSON.message;
                    }
                    showToast(msg, 'error');
                }
            });
        });

        // Intercept Approval Config Form via AJAX
        $('#approval-config-form').on('submit', function(e) {
            e.preventDefault();
            let $form = $(this);
            let url = $form.attr('action');
            let formData = new FormData(this);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    showToast(response.message || 'Successfully saved approval rule!', 'success');
                    document.getElementById('modal-approval-config').classList.add('hidden');
                    
                    let formEl = document.getElementById('spkFormWrapper');
                    if (formEl) {
                        Alpine.$data(formEl).refreshApprovalRules();
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to save approval rule';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ': ' + xhr.responseJSON.message;
                    }
                    showToast(msg, 'error');
                }
            });
        });

        // Global functions to delete process or approval rule via AJAX
        window.deleteProcessAjax = function(id) {
            if (!confirm('Are you sure you want to delete this process?')) return;
            $.ajax({
                url: '{{ url("management/process-checklist") }}/' + id + '/delete',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showToast(response.message || 'Deleted successfully!', 'success');
                    let formEl = document.getElementById('spkFormWrapper');
                    if (formEl) {
                        Alpine.$data(formEl).refreshProcesses();
                    }
                },
                error: function(xhr) {
                    showToast('Failed to delete process', 'error');
                }
            });
        };

        window.deleteApprovalAjax = function(id) {
            if (!confirm('Are you sure you want to delete this approval rule?')) return;
            $.ajax({
                url: '{{ url("management/approval-config") }}/' + id + '/delete',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showToast(response.message || 'Deleted successfully!', 'success');
                    let formEl = document.getElementById('spkFormWrapper');
                    if (formEl) {
                        Alpine.$data(formEl).refreshApprovalRules();
                    }
                },
                error: function(xhr) {
                    showToast('Failed to delete approval rule', 'error');
                }
            });
        };

        window.resendEmail = function(hashedId, email, name, role, btnEl) {
            let $btn = $(btnEl);
            let originalHtml = $btn.html();
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin text-[8px]"></i> Sending...');
            
            $.ajax({
                url: '{{ url("management/work-order") }}/' + hashedId + '/resend-email',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    email: email,
                    name: name,
                    role: role
                },
                success: function(response) {
                    showToast(response.message || 'Email successfully resent!', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                },
                error: function(xhr) {
                    let msg = 'Failed to resend email';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ': ' + xhr.responseJSON.message;
                    }
                    showToast(msg, 'error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        };
    });

    // Alpine.js component: custom multi-select with searchable dropdown + tag list
    function approverSelect(nativeId, allItems, initialIds) {
        return {
            allItems,
            selectedIds: initialIds.map(Number),
            defaultPics: {},
            search: '',
            open: false,

            init() {
                window.addEventListener('set-proc-pics', (e) => {
                    if (e.detail.target === nativeId) {
                        this.defaultPics = e.detail.pics || {};
                    }
                });
                window.addEventListener('update-all-items', (e) => {
                    if (e.detail.target === nativeId) {
                        this.allItems = e.detail.items || [];
                        this.syncNativeSelect();
                    }
                });
                window.addEventListener('update-selected-approvers', (e) => {
                    if (e.detail.target === nativeId) {
                        this.setSelected(e.detail.ids || []);
                    }
                });
                window.addEventListener('update-approver-dept', (e) => {
                    if (nativeId === 'master_approver_user_ids') {
                        this.deptId = e.detail.deptId;
                    }
                });
            },

            get filtered() {
                const q = this.search.toLowerCase();
                let items = this.allItems;
                if (nativeId === 'master_approver_user_ids') {
                    let deptId = this.deptId || document.getElementById('app_dept')?.value;
                    if (deptId) {
                        items = this.allItems.filter(i => i.id_dept == deptId);
                    }
                }
                return items.filter(i => i.label.toLowerCase().includes(q));
            },

            get selectedItems() {
                return this.allItems.filter(i => this.selectedIds.includes(i.id));
            },

            itemLabel(id) {
                let item = this.allItems.find(i => i.id == id);
                return item ? item.label : '';
            },

            toggle(item) {
                const idx = this.selectedIds.indexOf(item.id);
                if (idx === -1) {
                    this.selectedIds.push(item.id);
                } else {
                    this.selectedIds.splice(idx, 1);
                    delete this.defaultPics[item.id];
                }
                this.syncNativeSelect();
            },

            remove(id) {
                this.selectedIds = this.selectedIds.filter(x => x !== id);
                delete this.defaultPics[id];
                this.syncNativeSelect();
            },

            setSelected(ids) {
                this.selectedIds = ids.map(Number);
                this.syncNativeSelect();
            },

            syncNativeSelect() {
                const sel = document.getElementById(nativeId);
                if (!sel) return;
                sel.innerHTML = '';
                this.selectedIds.forEach(id => {
                    let opt = document.createElement('option');
                    opt.value = id;
                    opt.selected = true;
                    sel.appendChild(opt);
                });
            }
        };
    }
</script>
@endpush

