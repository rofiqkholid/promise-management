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
<div class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-slate-50 dark:bg-slate-900" id="spkFormContainer">
    
    {{-- ── LEFT SIDE: Detail Configuration Form ─────────────────────────── --}}
    @if($isEditable)
        <form id="spkForm" action="{{ isset($workOrder) ? route('management.work-order.update', $workOrder->hashed_id) : route('management.work-order.store') }}" method="POST" 
              class="w-1/2 h-full flex flex-col overflow-hidden border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-[width] duration-200 ease-in-out" :style="showPreview ? 'width: 50%' : 'width: 100%'">
            @csrf
            @if(isset($workOrder))
                @method('PUT')
            @else
                <input type="hidden" name="inquiry_id" value="{{ $inquiry->hashed_id }}">
            @endif
            <input type="hidden" name="department_id" :value="department_id">
    @else
        <div class="w-1/2 h-full flex flex-col overflow-hidden border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-[width] duration-200 ease-in-out" :style="showPreview ? 'width: 50%' : 'width: 100%'">
            <input type="hidden" name="department_id" :value="department_id">
    @endif
        
        {{-- Fixed Header --}}
        <div class="p-6 pb-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-10 flex-none">
            <div class="flex items-center gap-3">
                <a href="{{ isset($workOrder) ? route('management.work-order.index') : route('management.inquiry.show', $inquiry->hashed_id) }}"
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
            @if(isset($workOrder) && !$workOrder->is_latest)
                <div class="p-3.5 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 text-amber-800 dark:text-amber-400 text-xs flex items-center justify-between">
                    <span>
                        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                        This is an outdated revision (Rev. {{ sprintf('%02d', $workOrder->revision_no) }}). A newer revision of this SPK exists.
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
                        $rule = \App\Models\ApprovalConfig::activeFor('WO')
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

                    {{-- Approve / Reject Actions (only for authorized user on pending step, on REVIEW page only) --}}
                    @if($pendingStep && $canActOnThis && Route::currentRouteName() === 'management.work-order.review')
                        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2">
                            <span class="text-[10px] text-slate-500 flex-1">Your turn to review as <strong>{{ $pendingStep->approver_position }}</strong>:</span>
                            <form action="{{ route('management.work-order.approve', $workOrder->hashed_id) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <input type="text" name="remarks" placeholder="Optional comments..."
                                       class="px-2.5 py-1.5 text-[10px] border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-xs w-40 focus:outline-none focus:border-blue-400">
                                <button type="submit"
                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] rounded-xs transition-colors cursor-pointer flex items-center gap-1.5">
                                    <i class="fa-solid fa-check text-[9px]"></i> Approve
                                </button>
                            </form>
                            <form action="{{ route('management.work-order.reject', $workOrder->hashed_id) }}" method="POST"
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
        <x-form-card title="1. General Information" icon="fa-circle-info">
            {{-- Link to WO Doc Format master header (header_id) --}}
            <input type="hidden" name="header_id" value="{{ isset($workOrder) ? $workOrder->header_id : ($woHeader->id ?? 1) }}">


            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <x-form-input label="WO Number" name="wo_number" x-model="work_order_no" required :readonly="!$isEditable" />
                    <x-form-input label="WO Publish Date" name="publish_date" type="date" x-model="publish_date" required :disabled="!$isEditable" />
                </div>

                <div class="grid grid-cols-2 gap-3 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <x-form-input label="First Sample Date" name="first_sample_date" type="date" x-model="first_sample_date" :disabled="!$isEditable" />
                    <x-form-input label="Due Date (Plan)" name="due_date_plan" type="date" x-model="due_date_plan" required @change="checkPrioritySuggestions();" :disabled="!$isEditable" />
                </div>
            </div>

            <div class="space-y-2 border-t border-slate-100 dark:border-slate-800 pt-3">
                    <label class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Priority <span class="text-rose-500">*</span></label>
                    <div class="grid grid-cols-3 gap-2.5">
                        <button type="button" @click="priority = 'URGENT'" :disabled="!isEditable"
                                class="flex flex-col items-center justify-center py-2 px-3 border rounded-xs cursor-pointer"
                                :class="priority === 'URGENT' 
                                    ? 'border-rose-500 bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 font-bold' 
                                    : 'border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed'">
                            <span class="text-xs font-bold tracking-wider uppercase">URGENT</span>
                            <span class="text-[10px] opacity-75 mt-0.5">&lt; 10 Working Days</span>
                        </button>
                        <button type="button" @click="priority = 'STANDARD'" :disabled="!isEditable"
                                class="flex flex-col items-center justify-center py-2 px-3 border rounded-xs cursor-pointer"
                                :class="priority === 'STANDARD' 
                                    ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 font-bold' 
                                    : 'border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed'">
                            <span class="text-xs font-bold tracking-wider uppercase">STANDARD</span>
                            <span class="text-[10px] opacity-75 mt-0.5">10 - 14 Working Days</span>
                        </button>
                        <button type="button" @click="priority = 'LOW'" :disabled="!isEditable"
                                class="flex flex-col items-center justify-center py-2 px-3 border rounded-xs cursor-pointer"
                                :class="priority === 'LOW' 
                                    ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400 font-bold' 
                                    : 'border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 disabled:opacity-50 disabled:cursor-not-allowed'">
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
                        class="text-[11px] font-medium text-blue-600 hover:text-blue-700 bg-blue-50 dark:bg-blue-950/40 border border-blue-400 dark:border-blue-700 px-2 py-2 rounded-xs transition-colors">
                    <i class="fa-solid fa-gear mr-1"></i> Master Process
                </button>
            </x-slot>
            
            <div class="space-y-4">
                
                <div class="space-y-3">
                    <div class="space-y-2.5">
                        @foreach($processes as $proc)
                            @php
                                $procDetail = isset($workOrder) ? $workOrder->processes->find($proc->id) : null;
                                $savedDepts = $procDetail ? json_decode($procDetail->pivot->assigned_departments ?? '[]') : null;
                                $defaultDepts = json_decode($proc->default_assigned_departments ?? '[]', true) ?: [];
                            @endphp
                            <div class="flex flex-col p-3.5 border rounded-xs"
                                 :class="isProcessSelected({{ $proc->id }}) 
                                    ? 'border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50' 
                                    : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/50'">
                                
                                {{-- Left Side: Custom Checkbox & Process info --}}
                                <div class="flex items-center gap-3">
                                    <label class="relative flex items-center justify-center cursor-pointer select-none">
                                        <input type="checkbox" name="processes[]" value="{{ $proc->id }}" 
                                               :checked="isProcessSelected({{ $proc->id }})"
                                               @change="toggleProcess({{ $proc->id }})"
                                               class="peer h-4.5 w-4.5 cursor-pointer appearance-none rounded-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 checked:border-blue-600 checked:bg-blue-600 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                               {{ !$isEditable ? 'disabled' : '' }}>
                                        <span class="absolute text-white opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none text-xs">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                        </span>
                                    </label>
                                    <div>
                                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block"
                                              :class="isProcessSelected({{ $proc->id }}) ? 'text-blue-700 dark:text-blue-400 font-extrabold' : ''">
                                            {{ $proc->process_name }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 block mt-0.5">Owner: {{ $proc->getDefaultAssignedDepartments()->pluck('name')->implode(', ') ?: 'N/A' }}</span>
                                    </div>
                                </div>
                                
                                {{-- Assigned Departments checkable badges list --}}
                                <div x-show="isProcessSelected({{ $proc->id }})" class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                                    <div class="text-[10px] text-slate-500 uppercase font-medium mb-1.5">Assigned Departments:</div>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($departments as $dept)
                                            <label class="inline-flex items-center gap-1 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-2 py-0.5 text-[10px] cursor-pointer select-none rounded-xs font-semibold"
                                                   :class="process_departments[{{ $proc->id }}] && process_departments[{{ $proc->id }}].map(Number).includes({{ $dept->id }}) 
                                                        ? 'bg-blue-50 border-blue-300 text-blue-700 dark:bg-blue-950/20 dark:border-blue-800 dark:text-blue-400 font-bold' 
                                                        : 'text-slate-600 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800/50'">
                                                <input type="checkbox" 
                                                       value="{{ $dept->id }}" 
                                                       x-model.number="process_departments[{{ $proc->id }}]"
                                                       @change="syncGlobalSupportDepartments()"
                                                       class="h-3 w-3 rounded-xs border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-0"
                                                       {{ !$isEditable ? 'disabled' : '' }}>
                                                <span>{{ $dept->code }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    
                                    <!-- PIC Selection List -->
                                    <div class="mt-3 space-y-2 border-t border-slate-100 dark:border-slate-800/60 pt-2"
                                         x-show="process_departments[{{ $proc->id }}] && process_departments[{{ $proc->id }}].length > 0">
                                        <div class="text-[10px] text-slate-500 uppercase font-medium">Assign PIC for each department:</div>
                                        <template x-for="deptId in (process_departments[{{ $proc->id }}] || [])" :key="deptId">
                                            <div class="flex items-center justify-between gap-3 bg-slate-100 dark:bg-slate-800 p-2 border border-slate-300 dark:border-slate-700 rounded-xs">
                                                <span class="text-xs font-bold text-slate-700 dark:text-slate-350" x-text="getDeptCodeById(deptId)"></span>
                                                <div class="flex items-center gap-2">
                                                    <label class="text-xs text-slate-700 dark:text-slate-350">PIC:</label>
                                                     <select x-model="process_pics[{{ $proc->id }} + '_' + deptId]"
                                                            :disabled="!isEditable"
                                                            class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs px-2.5 py-1 rounded-xs focus:outline-none focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed w-48">
                                                        <option value="">-- Choose PIC --</option>
                                                        <template x-for="u in getUsersByDept(deptId)" :key="u.id">
                                                            <option :value="u.id" x-text="u.name" :selected="process_pics[{{ $proc->id }} + '_' + deptId] == u.id"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-form-card>



        {{-- Approval Workflow Card --}}
        <x-form-card title="3. Approval Workflow Steps" icon="fa-user-shield">
            <x-slot name="headerActions">
                <button type="button" onclick="document.getElementById('modal-master-approval').classList.remove('hidden')"
                        class="text-[11px] font-medium text-blue-600 hover:text-blue-700 bg-blue-50 dark:bg-blue-950/40 border border-blue-400 dark:border-blue-700 px-2 py-2 rounded-xs transition-colors">
                    <i class="fa-solid fa-gear mr-1"></i> Master Approval Rule
                </button>
            </x-slot>
            <p class="text-[10px] text-slate-500 dark:text-slate-450 mb-2">Select the required approval level. The approval <span class="text-blue-600 font-semibold">marked in blue</span> is recommended based on the department selected in the Process Checklist.</p>
            
            <div class="space-y-2">
                @foreach($approvalRules as $rule)
                    @php $ruleId = $rule->id; $ruleDeptId = $rule->department_id; @endphp
                    <label class="flex items-start gap-3 p-3 border rounded-xs cursor-pointer select-none transition-all duration-150"
                           :class="{
                               'border-blue-400 bg-blue-50/40 dark:border-blue-700 dark:bg-blue-950/20 shadow-sm': selected_approval_rules.map(Number).includes({{ $ruleId }}) && isSuggestedRule({{ $ruleDeptId }}),
                               'border-slate-300 bg-slate-50/60 dark:border-slate-700 dark:bg-slate-800/30': selected_approval_rules.map(Number).includes({{ $ruleId }}) && !isSuggestedRule({{ $ruleDeptId }}),
                               'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900/50 opacity-60': !selected_approval_rules.map(Number).includes({{ $ruleId }})
                           }">
                        <input type="checkbox" name="selected_approval_rules[]" value="{{ $ruleId }}"
                               x-model.number="selected_approval_rules"
                               :disabled="!isEditable"
                               class="h-4 w-4 rounded-xs border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-0 mt-0.5 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    Level {{ $rule->approval_level }}: {{ $rule->position_label }} ({{ $rule->action_label }})
                                </span>
                                {{-- Suggested badge --}}
                                <span x-show="isSuggestedRule({{ $ruleDeptId }})"
                                      class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800 rounded-full">
                                    <i class="fa-solid fa-bolt text-[8px]"></i> Department Suggestion
                                </span>
                                {{-- Not matching badge --}}
                                <span x-show="!isSuggestedRule({{ $ruleDeptId }})"
                                      class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-semibold bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500 border border-slate-200 dark:border-slate-700 rounded-full">
                                    <i class="fa-solid fa-minus text-[8px]"></i> Other Dept
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-500 dark:text-slate-300 block mt-0.5">
                                Dept: {{ $rule->department->name ?? '—' }}
                                @if($rule->approverUsers->isNotEmpty())
                                    · Approver: {{ $rule->approverUsers->pluck('name')->implode(', ') }}
                                @endif
                            </span>
                        </div>
                    </label>
                @endforeach

                @if($approvalRules->isEmpty())
                    <div class="py-6 text-center text-slate-400">
                        <i class="fa-solid fa-triangle-exclamation text-amber-400 mr-1"></i>
                        <span class="text-xs">No active approval rules found. Please configure them in the <a href="{{ route('management.approval-config.index') }}" class="text-blue-500 underline">Approval Matrix</a>.</span>
                    </div>
                @endif
            </div>
        </x-form-card>

        {{-- General Remarks --}}
        <x-form-card title="Additional WO Notes (General Remarks)" icon="fa-file-lines">
            <textarea name="remarks" x-model="remarks" rows="3" placeholder="Enter any extra notes or instructions..."
                      class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 py-2 text-xs text-slate-900 dark:text-slate-100 focus:bg-white focus:border-blue-500 focus:outline-none disabled:bg-slate-200 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed transition-colors duration-150 resize-none" {{ !$isEditable ? 'disabled' : '' }}></textarea>
        </x-form-card>

        {{-- Product Specifications Card (BOM) --}}
        <x-form-card title="3. Product Specifications (BOM)" icon="fa-boxes-stacked">
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-[10px] font-bold text-center text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                            <th class="p-2 min-w-36">Part Number / Name</th>
                            <th class="p-2">EO</th>
                            <th class="p-2">Class ID</th>
                            <th class="p-2">UOM</th>
                            <th class="p-2">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(prod, index) in products" :key="index">
                            <tr class="border-b border-slate-100 dark:border-slate-800/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                                <td class="p-2">
                                    <div class="font-bold text-slate-800 dark:text-slate-200" x-text="prod.customer_part_no"></div>
                                    <div class="text-[10px] text-slate-400" x-text="prod.customer_part_name"></div>
                                </td>
                                <td class="p-2">
                                    <input type="text" x-model="prod.eo" class="py-1 px-1.5 text-xs text-center border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:outline-none" :disabled="!isEditable">
                                </td>
                                <td class="p-2">
                                    <select x-model="prod.class_id" class="p-1 text-xs border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:outline-none" :disabled="!isEditable">
                                        <option value="FG">FG (Finished Good)</option>
                                        <option value="RM">RM (Raw Material)</option>
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select x-model="prod.uom" class="p-1 text-xs border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:outline-none" :disabled="!isEditable">
                                        <option value="Kg">Kg</option>
                                        <option value="Sheet">Sheet</option>
                                        <option value="Pcs">Pcs</option>
                                    </select>
                                </td>
                                <td class="p-2">
                                    <input type="text" x-model="prod.remarks" class="py-1 px-1.5 text-xs border border-slate-200 dark:border-slate-700 focus:border-blue-500 focus:outline-none" placeholder="Remarks..." :disabled="!isEditable">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </x-form-card>

        {{-- Data for submission is sent via JSON AJAX, not hidden fields --}}

        {{-- Hidden inputs for selected approval rules to ensure they submit via AJAX --}}
        <template x-for="ruleId in selected_approval_rules" :key="ruleId">
            <input type="hidden" name="selected_approval_rules[]" :value="ruleId">
        </template>

        </div> {{-- End Scrollable Container --}}

        {{-- Fixed Footer --}}
        <div class="p-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2.5 bg-slate-50 dark:bg-slate-900/60 z-10 flex-none">
            <a href="{{ isset($workOrder) ? route('management.work-order.index') : route('management.inquiry.show', $inquiry->hashed_id) }}"
               class="px-4 py-2 text-xs font-bold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xs transition-colors">
                {{ isset($workOrder) ? 'Back to SPK List' : 'Back to Inquiry' }}
            </a>
            @if(isset($workOrder) && $workOrder->status === 'Approved' && $workOrder->is_latest)
                <form action="{{ route('management.work-order.revise', $workOrder->hashed_id) }}" method="POST" onsubmit="return confirm('Create a new draft revision of this SPK? This will make the current revision read-only.')" class="inline">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xs shadow-xs transition-colors cursor-pointer flex items-center gap-1.5">
                        <i class="fa-solid fa-code-branch"></i> Revise SPK (New Revision)
                    </button>
                </form>
            @endif
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
    @if($isEditable)
        </form>
    @else
        </div>
    @endif

    <div class="w-1/2 h-full overflow-y-auto bg-slate-150 dark:bg-slate-800 transition-[width] duration-200 ease-in-out p-8 flex flex-col items-center border-l border-slate-200 dark:border-slate-700"
         x-show="showPreview" :style="showPreview ? 'width: 50%' : 'width: 0%'">
        @include('management.work-order.preview')
    </div>
    
    @if(isset($workOrder) && $workOrder->status === 'Draft')
        <form id="submitApprovalForm" action="{{ route('management.work-order.submit', $workOrder->hashed_id) }}" method="POST" class="hidden">
            @csrf
        </form>
    @endif
</div>
</div>

{{-- ── MODAL: MASTER PROCESS CHECKLIST ────────────────────────── --}}
<div id="modal-master-process" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-3xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[85vh]">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center flex-none">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-gear text-blue-500"></i> Master Process Checklist
            </h3>
            <button onclick="document.getElementById('modal-master-process').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        
        <div class="p-5 overflow-y-auto flex-1 text-xs space-y-4">
            <div class="flex justify-end">
                <button type="button" onclick="openAddProcessModal()" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] rounded-xs transition-colors flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-plus text-[9px]"></i> Add Process
                </button>
            </div>
            
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xs">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 uppercase">
                            <th class="p-2.5 w-24">Code</th>
                            <th class="p-2.5">Process Name</th>
                            <th class="p-2.5">Default Dept(s)</th>
                            <th class="p-2.5 w-16 text-center">Status</th>
                            <th class="p-2.5 w-24 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850">
                        @foreach($processes as $p)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                                <td class="p-2.5 font-mono text-slate-750 dark:text-slate-300">{{ $p->process_code }}</td>
                                <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200">{{ $p->process_name }}</td>
                                <td class="p-2.5 text-slate-500 dark:text-slate-400">
                                    {{ $p->getDefaultAssignedDepartments()->pluck('code')->implode(', ') ?: '—' }}
                                </td>
                                <td class="p-2.5 text-center">
                                    @if($p->is_active)
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/20 text-emerald-650 dark:text-emerald-450 border border-emerald-250 dark:border-emerald-900 rounded-xs">Active</span>
                                    @else
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700 rounded-xs">Inactive</span>
                                    @endif
                                </td>
                                <td class="p-2.5 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <button type="button" onclick="openEditProcessModal({{ json_encode($p) }})" class="p-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-400 text-slate-600 dark:text-slate-350 hover:text-blue-650 rounded-xs transition-colors flex items-center justify-center w-7 h-7" title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                        </button>
                                        <form action="{{ route('management.process-checklist.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Delete this process?')" class="inline-flex">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-250 dark:border-rose-900/50 text-rose-600 dark:text-rose-450 hover:border-rose-500 rounded-xs transition-colors flex items-center justify-center w-7 h-7" title="Delete">
                                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="p-4 border-t border-slate-100 dark:border-slate-700 flex justify-end flex-none">
            <button onclick="document.getElementById('modal-master-process').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs font-bold text-xs transition-colors cursor-pointer">Close</button>
        </div>
    </div>
</div>



{{-- ── MODAL: ADD PROCESS CHECKLIST ───────────────────────────── --}}
<div id="modal-add-process" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Add Master Process</h3>
            <button onclick="document.getElementById('modal-add-process').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="add-process-form" action="{{ route('management.process-checklist.store') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Process Code <span class="text-rose-500">*</span></label>
                <input type="text" name="process_code" required placeholder="e.g. CUTTING" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Process Name <span class="text-rose-500">*</span></label>
                <input type="text" name="process_name" required placeholder="e.g. Cutting Process" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div x-data="approverSelect('add_proc_dept_select', {{ json_encode($departments->map(fn($d) => ['id' => $d->id, 'label' => $d->name . ' (' . $d->code . ')'])) }}, [])" class="relative">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Default Department Owner <span class="text-rose-500">*</span>
                </label>
                {{-- Hidden native select --}}
                <select name="default_assigned_departments[]" id="add_proc_dept_select" multiple class="hidden">
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                {{-- Search input --}}
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
                           placeholder="Search department..."
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 pr-8 rounded-xs focus:outline-none focus:border-blue-500">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </span>
                    <div x-show="open" x-transition
                         class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-lg max-h-44 overflow-y-auto">
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
                <div class="mt-1 flex flex-wrap gap-1 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xs min-h-[30px]">
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
                <div class="mt-3 space-y-2 border-t border-slate-100 dark:border-slate-800/60 pt-2" x-show="selectedIds.length > 0">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Default PIC per Department:</label>
                    <template x-for="deptId in selectedIds" :key="deptId">
                        <div class="flex items-center justify-between gap-2 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xs">
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300" x-text="itemLabel(deptId)"></span>
                            <select :name="'default_pics[' + deptId + ']'"
                                    x-model="defaultPics[deptId]"
                                    class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-[10px] px-2 py-1 rounded-xs focus:outline-none focus:border-blue-500 w-48">
                                <option value="">-- Choose PIC --</option>
                                <template x-for="u in window.allUsersList.filter(u => u.id_dept == deptId)" :key="u.id">
                                    <option :value="u.id" x-text="u.name" :selected="defaultPics[deptId] == u.id"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-750">
                <button type="button" onclick="document.getElementById('modal-add-process').classList.add('hidden')" class="px-3.5 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs font-bold text-xs transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xs text-xs transition-colors cursor-pointer">Save Process</button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: EDIT PROCESS CHECKLIST ──────────────────────────── --}}
<div id="modal-edit-process" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Edit Master Process</h3>
            <button onclick="document.getElementById('modal-edit-process').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="edit-process-form" action="" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Process Code <span class="text-rose-500">*</span></label>
                <input type="text" name="process_code" id="edit_proc_code" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Process Name <span class="text-rose-500">*</span></label>
                <input type="text" name="process_name" id="edit_proc_name" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div x-data="approverSelect('edit_proc_dept_select', {{ json_encode($departments->map(fn($d) => ['id' => $d->id, 'label' => $d->name . ' (' . $d->code . ')'])) }}, [])" x-on:set-proc-depts.window="if ($event.detail.target === 'edit_proc_dept_select') setSelected($event.detail.ids)" class="relative">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Default Department Owner <span class="text-rose-500">*</span>
                </label>
                {{-- Hidden native select --}}
                <select name="default_assigned_departments[]" id="edit_proc_dept_select" multiple class="hidden">
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                {{-- Search input --}}
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
                           placeholder="Search department..."
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 pr-8 rounded-xs focus:outline-none focus:border-blue-500">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </span>
                    <div x-show="open" x-transition
                         class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-lg max-h-44 overflow-y-auto">
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
                <div class="mt-1 flex flex-wrap gap-1 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xs min-h-[30px]">
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
                <div class="mt-3 space-y-2 border-t border-slate-100 dark:border-slate-800/60 pt-2" x-show="selectedIds.length > 0">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Default PIC per Department:</label>
                    <template x-for="deptId in selectedIds" :key="deptId">
                        <div class="flex items-center justify-between gap-2 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xs">
                            <span class="text-[10px] font-bold text-slate-700 dark:text-slate-350" x-text="itemLabel(deptId)"></span>
                            <select :name="'default_pics[' + deptId + ']'"
                                    x-model="defaultPics[deptId]"
                                    class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-[10px] px-2 py-1 rounded-xs focus:outline-none focus:border-blue-500 w-48">
                                <option value="">-- Choose PIC --</option>
                                <template x-for="u in window.allUsersList.filter(u => u.id_dept == deptId)" :key="u.id">
                                    <option :value="u.id" x-text="u.name" :selected="defaultPics[deptId] == u.id"></option>
                                </template>
                            </select>
                        </div>
                    </template>
                </div>
            </div>
            <div class="flex items-center gap-2 py-1">
                <input type="checkbox" name="is_active" id="edit_proc_active" value="1" class="rounded-xs text-blue-600">
                <label for="edit_proc_active" class="font-semibold text-slate-700 dark:text-slate-350">Active</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-750">
                <button type="button" onclick="document.getElementById('modal-edit-process').classList.add('hidden')" class="px-3.5 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs font-bold text-xs transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xs text-xs transition-colors cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: MASTER APPROVAL RULE ────────────────────────────── --}}
<div id="modal-master-approval" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-4xl border border-slate-200 dark:border-slate-700 flex flex-col max-h-[85vh]">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center flex-none">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-blue-500"></i> Master Approval Rules (SPK Matrix)
            </h3>
            <button onclick="document.getElementById('modal-master-approval').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        
        <div class="p-5 overflow-y-auto flex-1 text-xs space-y-4">
            <div class="flex justify-end">
                <button type="button" onclick="openAddApprovalModal()" class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] rounded-xs transition-colors flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-plus text-[9px]"></i> Add Rule
                </button>
            </div>
            <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xs">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 uppercase">
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
                        @foreach($approvalRules as $rule)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                                <td class="p-2.5 text-center font-bold">{{ $rule->approval_level }}</td>
                                <td class="p-2.5 font-bold text-slate-800 dark:text-slate-200">{{ $rule->position_label }}</td>
                                <td class="p-2.5">
                                    <span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-750 border border-slate-200 dark:border-slate-700 text-slate-650 dark:text-slate-300 font-semibold rounded-xs">{{ $rule->action_label ?? 'Checked' }}</span>
                                </td>
                                <td class="p-2.5 font-semibold text-slate-700 dark:text-slate-350">{{ $rule->department->code ?? '—' }}</td>
                                <td class="p-2.5 text-slate-500 dark:text-slate-400 text-[10px]">
                                    {{ $rule->approverUsers->pluck('name')->implode(', ') ?: 'Any in Department' }}
                                </td>
                                <td class="p-2.5 text-center font-mono">{{ $rule->sort_order }}</td>
                                <td class="p-2.5 text-center">
                                    @if($rule->is_active)
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/20 text-emerald-650 dark:text-emerald-450 border border-emerald-250 dark:border-emerald-900 rounded-xs">Active</span>
                                    @else
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700 rounded-xs">Inactive</span>
                                    @endif
                                </td>
                                <td class="p-2.5 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <button type="button" onclick="openEditApprovalModal({{ json_encode($rule) }})" class="p-1.5 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:border-blue-400 text-slate-600 dark:text-slate-300 hover:text-blue-650 rounded-xs transition-colors flex items-center justify-center w-7 h-7" title="Edit">
                                            <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                        </button>
                                        <form action="{{ route('management.approval-config.destroy', $rule->id) }}" method="POST" onsubmit="return confirm('Delete this approval rule?')" class="inline-flex">
                                            @csrf
                                            <button type="submit" class="p-1.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-250 dark:border-rose-900/50 text-rose-600 dark:text-rose-450 hover:border-rose-500 rounded-xs transition-colors flex items-center justify-center w-7 h-7" title="Delete">
                                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="p-4 border-t border-slate-100 dark:border-slate-700 flex justify-end flex-none">
            <button onclick="document.getElementById('modal-master-approval').classList.add('hidden')" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs font-bold text-xs transition-colors cursor-pointer">Close</button>
        </div>
    </div>
</div>

{{-- ── MODAL: EDIT APPROVAL RULE ──────────────────────────────── --}}
<div id="modal-edit-approval" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Edit Master Approval Rule</h3>
            <button onclick="document.getElementById('modal-edit-approval').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="edit-approval-form" action="" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <input type="hidden" name="document_type" value="WO">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Approval Level <span class="text-rose-500">*</span></label>
                <input type="number" name="approval_level" id="edit_app_level" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Position Label <span class="text-rose-500">*</span></label>
                <input type="text" name="position_label" id="edit_app_position" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sign-off Header <span class="text-rose-500">*</span></label>
                <select name="action_label" id="edit_app_action" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
                    <option value="Checked">Checked</option>
                    <option value="Approved">Approved</option>
                    <option value="Reviewed">Reviewed</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Responsible Dept <span class="text-rose-500">*</span></label>
                <select name="department_id" id="edit_app_dept" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500 select2-department">
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                    @endforeach
                </select>
            </div>
            <div x-data="approverSelect('edit_master_approver_user_ids', {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . $u->nik . ')'])) }}, [])" x-on:set-master-approvers.window="if ($event.detail.target === 'edit_master_approver_user_ids') setSelected($event.detail.ids)" class="relative">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Specific Approver(s)
                    <span class="text-slate-300 text-[9px] normal-case font-normal ml-1">(searchable — select multiple)</span>
                </label>
                {{-- Hidden native select --}}
                <select name="approver_user_ids[]" id="edit_master_approver_user_ids" multiple class="hidden">
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                {{-- Search input --}}
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
                           placeholder="Search approver..."
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 pr-8 rounded-xs focus:outline-none focus:border-blue-500">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </span>
                    {{-- Dropdown --}}
                    <div x-show="open" x-transition
                         class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-lg max-h-40 overflow-y-auto">
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
                <div class="mt-1 flex flex-wrap gap-1 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xs min-h-[30px]">
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sort Order</label>
                <input type="number" name="sort_order" id="edit_app_sort" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-center gap-2 py-1">
                <input type="checkbox" name="is_active" id="edit_app_active" value="1" class="rounded-xs text-blue-600">
                <label for="edit_app_active" class="font-semibold text-slate-700 dark:text-slate-350">Active status</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-750">
                <button type="button" onclick="document.getElementById('modal-edit-approval').classList.add('hidden')" class="px-3.5 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs font-bold text-xs transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xs text-xs transition-colors cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL: ADD APPROVAL RULE ────────────────────────────── --}}
<div id="modal-add-approval" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xs shadow-2xl w-full max-w-md border border-slate-200 dark:border-slate-700">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Add Master Approval Rule</h3>
            <button onclick="document.getElementById('modal-add-approval').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg leading-none cursor-pointer">&times;</button>
        </div>
        <form id="add-approval-form" action="{{ route('management.approval-config.store') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <input type="hidden" name="document_type" value="WO">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Approval Level <span class="text-rose-500">*</span></label>
                <input type="number" name="approval_level" required value="1" min="1" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Position Label <span class="text-rose-500">*</span></label>
                <input type="text" name="position_label" required placeholder="e.g. Marketing GM" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sign-off Header <span class="text-rose-500">*</span></label>
                <select name="action_label" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="Checked">Checked</option>
                    <option value="Approved">Approved</option>
                    <option value="Reviewed">Reviewed</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Responsible Dept <span class="text-rose-500">*</span></label>
                <select name="department_id" required class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500 cursor-pointer select2-department">
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                    @endforeach
                </select>
            </div>
            <div x-data="approverSelect('add_master_approver_user_ids', {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . $u->nik . ')'])) }}, [])" class="relative">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Specific Approver(s)
                    <span class="text-slate-300 text-[9px] normal-case font-normal ml-1">(searchable — select multiple)</span>
                </label>
                {{-- Hidden native select --}}
                <select name="approver_user_ids[]" id="add_master_approver_user_ids" multiple class="hidden">
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                {{-- Search input --}}
                <div class="relative">
                    <input type="text" x-model="search" @focus="open = true" @click.outside="open = false" @keydown.escape="open = false"
                           placeholder="Search approver..."
                           class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 pr-8 rounded-xs focus:outline-none focus:border-blue-500">
                    <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                    </span>
                    <div x-show="open" x-transition
                         class="absolute z-40 w-full mt-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xs shadow-lg max-h-40 overflow-y-auto">
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
                <div class="mt-1 flex flex-wrap gap-1 p-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xs min-h-[30px]">
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
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sort Order</label>
                <input type="number" name="sort_order" value="0" min="0" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-xs px-2.5 py-1.5 rounded-xs focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex items-center gap-2 py-1">
                <input type="checkbox" name="is_active" id="add_app_active" value="1" checked class="rounded-xs text-blue-600">
                <label for="add_app_active" class="font-semibold text-slate-700 dark:text-slate-350">Active status</label>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-750">
                <button type="button" onclick="document.getElementById('modal-add-approval').classList.add('hidden')" class="px-3.5 py-2 border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-350 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xs font-bold text-xs transition-colors cursor-pointer">Cancel</button>
                <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xs text-xs transition-colors cursor-pointer">Save Rule</button>
            </div>
        </form>
    </div>
</div>
</div>

<script>
function openAddProcessModal() {
    // Reset add process form
    const form = document.getElementById('add-process-form');
    if (form) {
        form.reset();
    }
    // Clear select tag widgets inside add form
    window.dispatchEvent(new CustomEvent('set-proc-depts', { detail: { target: 'add_proc_dept_select', ids: [] } }));
    window.dispatchEvent(new CustomEvent('set-proc-pics', { detail: { target: 'add_proc_dept_select', pics: {} } }));
    document.getElementById('modal-add-process').classList.remove('hidden');
}

function openAddApprovalModal() {
    // Reset add approval form
    const form = document.getElementById('add-approval-form');
    if (form) {
        form.reset();
    }
    // Clear select tag widgets inside add form
    window.dispatchEvent(new CustomEvent('set-master-approvers', { detail: { target: 'add_master_approver_user_ids', ids: [] } }));
    document.getElementById('modal-add-approval').classList.remove('hidden');
}

function openEditProcessModal(proc) {
    document.getElementById('edit-process-form').action = '{{ url('management/process-checklist') }}/' + proc.id + '/update';
    document.getElementById('edit_proc_code').value = proc.process_code;
    document.getElementById('edit_proc_name').value = proc.process_name;
    document.getElementById('edit_proc_active').checked = proc.is_active;

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
    window.dispatchEvent(new CustomEvent('set-proc-depts', { detail: { target: 'edit_proc_dept_select', ids: deptIds } }));
    window.dispatchEvent(new CustomEvent('set-proc-pics', { detail: { target: 'edit_proc_dept_select', pics: defaultPics } }));

    document.getElementById('modal-edit-process').classList.remove('hidden');
}

function openEditApprovalModal(rule) {
    document.getElementById('edit-approval-form').action = '{{ url('management/approval-config') }}/' + rule.id + '/update';
    document.getElementById('edit_app_level').value = rule.approval_level;
    document.getElementById('edit_app_position').value = rule.position_label;
    document.getElementById('edit_app_action').value = rule.action_label ?? 'Checked';
    document.getElementById('edit_app_dept').value = rule.department_id;
    document.getElementById('edit_app_sort').value = rule.sort_order;
    document.getElementById('edit_app_active').checked = rule.is_active;

    // Set multi-select approvers by dispatching custom event to Alpine component
    let userIds = (rule.approver_user_ids || []).map(Number);
    window.dispatchEvent(new CustomEvent('set-master-approvers', { detail: { target: 'edit_master_approver_user_ids', ids: userIds } }));

    document.getElementById('modal-edit-approval').classList.remove('hidden');
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
            return [
                'approval_level' => $a->approval_level,
                'approver_position' => $a->approver_position,
                'status' => $a->status,
                'approver_name' => $a->approver_name,
                'remarks' => $a->remarks,
                'department_code' => $a->department->code ?? ($a->department->name ?? '—'),
                'due_date_closed' => $a->due_date_closed ? $a->due_date_closed->format('Y-m-d') : null,
                'approved_at' => $a->approved_at ? \Carbon\Carbon::parse($a->approved_at)->format('d-M-Y H:i') : null
            ];
        })->values()->toArray();
    }
@endphp

<script>
window.allUsersList = @json($users);
document.addEventListener('alpine:init', () => {
    Alpine.data('spkForm', () => ({
        isEditable: {{ $isEditable ? 'true' : 'false' }},
        work_order_no: @json(isset($workOrder) ? $workOrder->work_order_no : $defaultSpkNo),
        subject: @json(isset($workOrder) ? $workOrder->subject : "Pekerjaan New 5P45"),
        department_id: '{{ isset($workOrder) ? $workOrder->department_id : ($departments->first()->id ?? 1) }}',
        priority: '{{ isset($workOrder) ? ($workOrder->priority ?: "STANDARD") : "STANDARD" }}',
        selected_processes: @json(isset($workOrder) ? $workOrder->processes->pluck('id') : []),
        process_departments: @json($procDeptsData),
        process_pics: @json((object)$procPicsData),
        support_departments: @json(isset($workOrder) ? $workOrder->supportDepartments->map(fn($d) => ['id' => $d->id, 'name' => $d->name . ($d->code ? " ({$d->code})" : "")]) : []),
        remarks: @json(isset($workOrder) ? $workOrder->remarks : ""),
        document_no: @json(isset($workOrder) ? ($workOrder->docFormat->document_no ?? 'FO-13-02') : ($woHeader->document_no ?? 'FO-13-02')),
        doc_department: @json(isset($workOrder) ? ($workOrder->docFormat->doc_department ?? 'Sales') : ($woHeader->doc_department ?? 'Sales')),
        doc_publish_date: @json(isset($workOrder) ? ($workOrder->docFormat->doc_publish_date ? \Carbon\Carbon::parse($workOrder->docFormat->doc_publish_date)->format('Y-m-d') : '2024-01-01') : ($woHeader->doc_publish_date ? \Carbon\Carbon::parse($woHeader->doc_publish_date)->format('Y-m-d') : '2024-01-01')),
        released_at: @json(isset($workOrder) ? ($workOrder->released_at ? $workOrder->released_at->format('Y-m-d') : '') : ''),
        page_hal: @json(isset($workOrder) ? ($workOrder->docFormat->page_hal ?? '1') : ($woHeader->page_hal ?? '1')),
        revision_no: {{ isset($workOrder) ? $workOrder->revision_no : 0 }},
        doc_revision_no: @json(isset($workOrder) ? ($workOrder->docFormat->revision_no ?? 0) : ($woHeader->revision_no ?? 0)),
        publish_date: @json(isset($workOrder) ? ($workOrder->publish_date ? (is_string($workOrder->publish_date) ? substr($workOrder->publish_date, 0, 10) : $workOrder->publish_date->format('Y-m-d')) : now()->format('Y-m-d')) : now()->format('Y-m-d')),
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
                    list.push({
                        approval_level: rule.approval_level,
                        approver_position: rule.dept_code + ' Leader',
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
        selected_approval_rules: @json(isset($workOrder) ? ($workOrder->selected_approval_rule_ids ?: $approvalRules->pluck('id')) : $approvalRules->pluck('id')),
        
        // All approval rules with their department_id for filtering
        approvalRulesList: @json($approvalRules->map(fn($r) => ['id' => $r->id, 'department_id' => $r->department_id, 'dept_code' => $r->department->code ?? $r->department->name ?? ''])->values()),
        
        // Departments list lookup
        departmentsList: @json($departments),
        processesList: @json($processes),
        usersList: @json($users),
        
        // Pre-populated selected products
        products: @json(isset($workOrder) ? $workOrder->products : $inquiry->products).map(p => ({
            work_order_product_id: p.id ?? null, // WO product PK (null for new WO)
            inquiry_product_id: p.inquiry_product_id ?? p.id, // FK to mng_inquiry_products
            customer_code: '{{ isset($workOrder) ? ($workOrder->inquiry->customer->code ?? "") : ($inquiry->customer->code ?? "") }}',
            model_name: '{{ isset($workOrder) ? ($workOrder->inquiry->projectModel->name ?? "") : ($inquiry->projectModel->name ?? "") }}',
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
            has_2d_data: p.has_2d_data ?? (p.inquiry_product ? p.inquiry_product.has_2d_data : false),
            has_3d_data: p.has_3d_data ?? (p.inquiry_product ? p.inquiry_product.has_3d_data : false),
            has_tech_doc: p.has_tech_doc ?? (p.inquiry_product ? p.inquiry_product.has_tech_doc : false),
            remarks: p.remarks ?? ''
        })),

        computedRevisionNo() {
            return String(this.revision_no).padStart(2, '0');
        },
        computedDocRevisionNo() {
            return String(this.doc_revision_no).padStart(2, '0');
        },

        async init() {
            this.updateDepartmentIdFromProcesses();
            this.syncGlobalSupportDepartments(); // also triggers updateApprovalSuggestions
            try {
                let response = await fetch('{{ route('management.calendar.holidays') }}');
                if (response.ok) {
                    this.holidays = await response.json();
                }
            } catch (e) {
                console.error('Failed to load holidays list', e);
            }
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
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    let y = current.getFullYear();
                    let m = String(current.getMonth() + 1).padStart(2, '0');
                    let d = String(current.getDate()).padStart(2, '0');
                    let dateStr = `${y}-${m}-${d}`;
                    
                    if (!this.holidays.includes(dateStr)) {
                        workingDays++;
                    }
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
            let newSelection = [];
            this.approvalRulesList.forEach(rule => {
                if (selectedDeptIds.includes(parseInt(rule.department_id))) {
                    newSelection.push(rule.id);
                }
            });
            // Keep manually added rules (those not in approvalRulesList dept scope) intact
            // Only auto-manage: add suggested ones, remove non-suggested ones that were auto-added
            this.approvalRulesList.forEach(rule => {
                let isNowSuggested = selectedDeptIds.includes(parseInt(rule.department_id));
                let currentlySelected = this.selected_approval_rules.map(Number).includes(rule.id);
                if (isNowSuggested && !currentlySelected) {
                    this.selected_approval_rules.push(rule.id);
                } else if (!isNowSuggested && currentlySelected) {
                    this.selected_approval_rules = this.selected_approval_rules.filter(id => Number(id) !== rule.id);
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
    $(document).ready(function() {
        $('.select2-department').select2({
            placeholder: "-- Choose Department --",
            width: '100%'
        });

        // Initialize select2 for each process department selection
        $('.select2-process-depts').select2({
            placeholder: "Assign Departments...",
            containerCssClass: "select2-process-select-container",
            selectionCssClass: "select2-process-select"
        }).on('change', function() {
            let procId = $(this).data('process-id');
            let selectedValues = $(this).val() || [];
            
            let formEl = document.getElementById('spkFormContainer');
            if (formEl) {
                let alpineData = Alpine.$data(formEl);
                alpineData.process_departments[procId] = selectedValues.map(id => parseInt(id));
                alpineData.syncGlobalSupportDepartments();
            }
        });

        // Initialize state mapping on load (especially for edit/revision screen)
        setTimeout(() => {
            let formEl = document.getElementById('spkFormContainer');
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
            let formEl = document.getElementById('spkFormContainer');
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
                department_id: alpine.department_id || '',
                wo_number: alpine.work_order_no || '',
                publish_date: alpine.publish_date || '',
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
                        inquiry_product_id: p.inquiry_product_id,
                        eo: p.eo || '',
                        class_id: p.class_id || '',
                        uom: p.uom || '',
                        remarks: p.remarks || ''
                    };
                }),
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
                        window.location.href = response.redirect_url;
                    } else {
                        alert(response.message || 'Successfully saved SPK!');
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
                    alert(msg);
                }
            });
        });
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
            },

            get filtered() {
                const q = this.search.toLowerCase();
                return this.allItems.filter(i => i.label.toLowerCase().includes(q));
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
                Array.from(sel.options).forEach(opt => {
                    opt.selected = this.selectedIds.includes(Number(opt.value));
                });
            }
        };
    }
</script>
@endpush

