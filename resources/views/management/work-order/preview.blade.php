{{-- Live SPK Document Preview (MS Word Style Component) --}}
<div class="spk-preview-panel p-8 flex flex-col items-center overflow-y-auto border-l border-slate-200 dark:border-slate-800"
     x-show="showPreview" style="width: 50%">
    
    {{-- UNIFIED SPK DOCUMENT --}}
    <div class="spk-paper w-full max-w-[760px] p-8 border shadow-sm font-serif text-slate-900 space-y-5 bg-white">
        
        {{-- Document Header Table --}}
        <table class="w-full border-collapse border border-slate-900 text-xs font-serif text-slate-900">
            <tbody>
                <tr class="divide-x divide-slate-900">
                    <td class="w-[30%] p-2 text-center border-b border-slate-900 align-middle">
                        <img src="{{ asset('assets/image/sai-logo.png') }}" class="h-12 mx-auto object-contain mb-4">
                        <div class="text-[11px] font-bold uppercase tracking-tight leading-none text-center">PT SUMMIT ADYAWINSA INDONESIA</div>
                    </td>
                    <td class="w-[45%] p-3 text-center font-extrabold text-sm uppercase tracking-wide border-b border-slate-900 align-middle">
                        WORK ORDER (SPK)
                    </td>
                    <td class="w-[25%] text-[10px] border-b border-slate-900 p-0">
                        <table class="w-full h-full text-[9px] border-collapse">
                            <tbody>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900 w-[50%]">Document Number</td>
                                    <td class="p-1">: <span x-text="document_no"></span></td>
                                </tr>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900">Department</td>
                                    <td class="p-1">: <span x-text="doc_department"></span></td>
                                </tr>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900">Publish Date</td>
                                    <td class="p-1">: <span>{{ isset($woHeader->doc_publish_date) ? \Carbon\Carbon::parse($woHeader->doc_publish_date)->format('d-M-Y') : '01-Jan-2024' }}</span></td>
                                </tr>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900">Revision</td>
                                    <td class="p-1">: <span x-text="computedDocRevisionNo()"></span></td>
                                </tr>
                                <tr>
                                    <td class="p-1 font-semibold border-r border-slate-900">Page</td>
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
                <div>Publish Date: <span class="font-bold" x-text="formatDateStr(publish_date)"></span></div>
                <div>No. <span class="font-bold" x-text="work_order_no"></span></div>
                <div>To: <span class="font-bold" x-text="selected_processes.length ? (getDepartmentName(department_id) + (support_departments.length ? ' / ' + support_departments.map(d => d.name).join(', ') : '')) : '—'"></span></div>
            </div>
            <div class="text-[10px] w-32">
                <span class="block font-bold mb-1">Priority:</span>
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

        {{-- Dear Sir/Madam and Message --}}
        <div class="text-[10px] text-slate-900 space-y-1">
            <p>Dear Sir/Madam,</p>
            <p>Please prepare the items listed below:</p>
            
            <div class="grid grid-cols-3 gap-y-1 gap-x-2 py-1">
                <template x-for="proc in processesList" :key="proc.id">
                    <template x-if="selected_processes.map(Number).includes(Number(proc.id))">
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
            <p class="font-bold mb-1">Attached data or information:</p>
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
                    <template x-if="products.length === 0">
                        <tr>
                            <td colspan="9" class="p-4 text-center text-slate-400 italic">No products added.</td>
                        </tr>
                    </template>
                    <template x-for="(prod, index) in products" :key="index">
                        <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle">
                            <td class="p-1" x-text="index + 1"></td>
                            <td class="p-1" x-text="prod.customer_code"></td>
                            <td class="p-1" x-text="prod.model_name"></td>
                            <td class="p-1" x-text="prod.eo"></td>
                            <td class="p-1" x-text="prod.customer_part_no"></td>
                            <td class="p-1 text-left" x-text="prod.customer_part_name"></td>
                            <td class="p-1 font-bold text-black" x-text="prod.class_id"></td>
                            <td class="p-1" x-text="prod.uom"></td>
                            <td class="p-1 text-left" x-text="prod.remarks || '—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Table 2: Schedule & Volume --}}
        <div class="text-[10px] text-slate-900">
            <!-- <p class="font-bold mb-1">Schedule &amp; Target Volume:</p> -->
            <table class="w-full text-center border-collapse border border-slate-900 text-[10px]">
                <thead>
                    <tr class="divide-x divide-slate-900 border-b border-slate-900 font-bold bg-slate-50/50">
                        <th class="p-1 w-6">No</th>
                        <th class="p-1">SOP</th>
                        <th class="p-1 w-20">Model Life</th>
                        <th class="p-1">First Sample</th>
                        <th class="p-1">DueDate (Plan)</th>
                        <th class="p-1">DueDate Closed</th>
                        <th class="p-1">Vol/Years</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle">
                        <td class="p-1">1</td>
                        <td class="p-1 text-left">
                            <template x-for="(sched, idx) in getUniqueSchedules()" :key="idx">
                                <div class="leading-relaxed">
                                    <span x-text="sched.variant ? (sched.variant + ' : ') : ''"></span>
                                    <span x-text="formatDateStr(sched.sop_date)"></span>
                                </div>
                            </template>
                        </td>
                        <td class="p-1" x-text="products[0] && products[0].model_life ? (products[0].model_life + ' Years') : '—'"></td>
                        <td class="p-1" x-text="formatDateStr(first_sample_date) || '—'"></td>
                        <td class="p-1 font-bold text-black" x-text="formatDateStr(due_date_approval) || '—'"></td>
                        <td class="p-1 font-bold text-black" x-text="formatDateStr(due_date_closed) || '—'"></td>
                        <td class="p-1 text-left">
                            <template x-for="(sched, idx) in getUniqueSchedules()" :key="idx">
                                <div class="leading-relaxed">
                                    <span x-text="sched.annual_volume ? (Number(sched.annual_volume).toLocaleString() + ' / Year') : '—'"></span>
                                </div>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Remarks section --}}
        <div class="text-[10px] text-slate-900 border border-slate-900 p-2 bg-slate-50/50">
            <span class="font-bold">Remarks:</span>
            <span class="ml-1" x-text="remarks || '—'"></span>
        </div>

        {{-- Signature Table (Approval Workflow) --}}
        @php
            // Filter out rules that don't have any specific users assigned
            $filteredRules = $approvalRules->filter(function($r) {
                return is_array($r->approver_user_ids) && count($r->approver_user_ids) > 0;
            });

            // Build a lookup of approvals by level for quick access
            $approvalsByLevel = isset($workOrder)
                ? $workOrder->approvals->keyBy('approval_level')
                : collect();
            // Determine column width based on number of rules (min 2: Dibuat + at least 1 approver)
            $totalCols = max(2, $filteredRules->count() + 1);
            $colPct = round(100 / $totalCols) . '%';
        @endphp
        <table class="w-full border-collapse border border-slate-900 text-[10px] text-slate-900 text-center">
            <tbody>
                {{-- Header row --}}
                <tr class="divide-x divide-slate-900 font-bold bg-slate-50/50">
                    {{-- Prepared column --}}
                    <td class="p-1 border-b border-slate-900" :style="{ width: (100 / (1 + selected_approval_rules.length)) + '%' }">Prepared</td>
                    {{-- Dynamic approver columns --}}
                    @forelse($filteredRules as $rule)
                        <td class="p-1 border-b border-slate-900" 
                            x-show="selected_approval_rules.map(Number).includes({{ $rule->id }})"
                            :style="{ width: (100 / (1 + selected_approval_rules.length)) + '%' }">
                            {{ $rule->action_label ?? 'Checked' }}
                        </td>
                    @empty
                        {{-- Fallback if no rules configured yet: show 2 placeholder columns --}}
                        <td class="p-1 border-b border-slate-900">Checked</td>
                        <td class="p-1 border-b border-slate-900">Approved</td>
                    @endforelse
                </tr>
                {{-- Signature body row --}}
                <tr class="divide-x divide-slate-900" style="height: 100px">
                    {{-- Dibuat / Creator --}}
                    <td class="p-2 border-b border-slate-900 align-bottom relative" :style="{ width: (100 / (1 + selected_approval_rules.length)) + '%' }">
                        <div class="absolute inset-x-0 top-2 text-center text-[9px] text-emerald-600 font-bold italic">APPROVED</div>
                        <div class="absolute inset-x-0 top-6 text-center text-[8px] text-slate-400">
                            {{ isset($workOrder) ? $workOrder->created_at->format('d-M-Y H:i') : now()->format('d-M-Y H:i') }}
                        </div>
                        <div class="font-bold text-[10px]">{{ isset($workOrder) ? $workOrder->created_by : auth()->user()->name }}</div>
                        <div class="text-[8px] text-slate-400">Staff MKT</div>
                    </td>
                    {{-- Dynamic approver signature cells --}}
                    @forelse($filteredRules as $rule)
                        @php
                            $levelApproval = $approvalsByLevel->get($rule->approval_level);
                            $isApproved    = $levelApproval && $levelApproval->status === 'Approved';
                            $isPending     = $levelApproval && $levelApproval->status === 'Pending';
                            $isRejected    = $levelApproval && $levelApproval->status === 'Rejected';
                        @endphp
                        <td class="p-2 border-b border-slate-900 align-bottom relative"
                            x-show="selected_approval_rules.map(Number).includes({{ $rule->id }})"
                            :style="{ width: (100 / (1 + selected_approval_rules.length)) + '%' }">
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
                            <div class="text-[9px] font-bold text-slate-400 uppercase">— Not Configured —</div>
                        </td>
                        <td class="p-2 border-b border-slate-900 align-bottom">
                            <div class="text-[9px] font-bold text-slate-400 uppercase">— Not Configured —</div>
                        </td>
                    @endforelse
                </tr>
            </tbody>
        </table>

    </div>
</div>
