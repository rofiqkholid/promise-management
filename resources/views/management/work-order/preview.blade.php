<div class="bg-white border border-slate-400 dark:border-slate-600 shadow-xl text-black w-full max-w-[760px] p-8 space-y-5" style="font-family: 'Times New Roman', Times, serif;">
    
    {{-- Document Header Table --}}
    <table class="w-full border-collapse border border-slate-900 text-xs text-slate-900" style="font-family: 'Times New Roman', Times, serif;">
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
                                <td class="p-1">: <span x-text="formatDateStr(doc_publish_date)"></span></td>
                            </tr>
                            <tr class="border-b border-slate-900">
                                <td class="p-1 font-semibold border-r border-slate-900">Revision</td>
                                <td class="p-1">: <span x-text="String(doc_revision_no).padStart(2, '0')"></span></td>
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
            <div>Publish Date: <span class="font-bold" x-text="formatDateStr(publish_date) || '—'"></span></div>
            <div>No. <span class="font-bold" x-text="work_order_no"></span></div>
            <div>To: <span class="font-bold" x-text="typeof isEditable !== 'undefined' ? (selected_processes.length ? (getDepartmentName(department_id) + (support_departments.length ? ' / ' + support_departments.map(d => d.name).join(', ') : '')) : '—') : (target_departments_full || '—')"></span></div>
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
            <template x-for="proc in (typeof processesList !== 'undefined' ? processesList : (detailData ? detailData.processes : []))" :key="proc.id || proc.process_id">
                <template x-if="typeof isEditable !== 'undefined' ? selected_processes.map(Number).includes(Number(proc.id)) : true">
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
                    <th class="p-1 w-8">2D</th>
                    <th class="p-1 w-8">3D</th>
                    <th class="p-1 w-8">Tech</th>
                    <th class="p-1">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="products.length === 0">
                    <tr>
                        <td colspan="12" class="p-4 text-center text-slate-400 italic">No products added.</td>
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
                        <td class="p-1 font-semibold" x-text="prod.has_2d_data ? '✓' : '✗'" :class="prod.has_2d_data ? 'text-emerald-600 font-bold' : 'text-slate-455'"></td>
                        <td class="p-1 font-semibold" x-text="prod.has_3d_data ? '✓' : '✗'" :class="prod.has_3d_data ? 'text-emerald-600 font-bold' : 'text-slate-455'"></td>
                        <td class="p-1 font-semibold" x-text="prod.has_tech_doc ? '✓' : '✗'" :class="prod.has_tech_doc ? 'text-emerald-600 font-bold' : 'text-slate-455'"></td>
                        <td class="p-1 text-left" x-text="prod.remarks || '—'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Table 2: Schedule & Volume --}}
    <div class="text-[10px] text-slate-900">
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
                    <td class="p-1 font-bold text-black" x-text="formatDateStr(due_date_plan) || '—'"></td>
                    <td class="p-1 text-center font-bold text-rose-600">
                        <template x-for="(dateStr, ruleId) in due_dates_closed" :key="ruleId">
                            <div class="leading-relaxed" x-show="dateStr">
                                <span x-text="formatDateStr(dateStr)"></span>
                                <span x-text="' (' + getDeptCodeByRuleId(ruleId) + ')'" class="text-[8px] text-slate-500 font-semibold"></span>
                            </div>
                        </template>
                        <template x-if="Object.values(due_dates_closed).filter(Boolean).length === 0">
                            <span>—</span>
                        </template>
                    </td>
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

    {{-- Remarks Block --}}
    <div class="text-[10px] text-slate-900 border border-slate-900 p-2 bg-slate-50/50 space-y-1">
        <div>
            <span class="font-bold">Remarks (Marketing):</span>
            <span class="ml-1" x-text="remarks || '—'"></span>
        </div>
        <template x-for="(app, idx) in approvals" :key="idx">
            <div class="pl-3 text-slate-600" x-show="app.remarks">
                <span class="font-bold" x-text="app.approver_position + ' (' + app.department_code + '):'"></span>
                <span class="italic" x-text="'&ldquo;' + app.remarks + '&rdquo;'"></span>
            </div>
        </template>
    </div>

    {{-- Signature Table (Approval Workflow) using Div Table to bypass browser template hoisting bugs --}}
    <div class="table w-full border border-slate-900 text-[9.5px] text-slate-900 text-center">
        <div class="table-row-group">
            <div class="table-row font-bold bg-slate-50 border-b border-slate-900">
                <div class="table-cell p-1 border-r border-slate-900 last:border-r-0" :style="{ width: (100 / (1 + approvals.length)) + '%' }">Prepared</div>
                <template x-for="(step, idx) in approvals" :key="idx">
                    <div class="table-cell p-1 border-r border-slate-900 last:border-r-0" x-text="step.action_label" :style="{ width: (100 / (1 + approvals.length)) + '%' }"></div>
                </template>
            </div>
            <div class="table-row">
                {{-- Dibuat / Creator --}}
                <div class="table-cell p-1.5 border-r border-slate-900 last:border-r-0 align-top" :style="{ width: (100 / (1 + approvals.length)) + '%' }">
                    <div class="flex flex-col justify-between items-center h-20 w-full">
                        <div class="flex flex-col items-center">
                            <div class="inline-block border-2 border-emerald-600 text-emerald-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-xs transform -rotate-3 select-none origin-center">APPROVED</div>
                            <div class="text-[8.5px] text-slate-500 mt-0.5" x-text="formatDateStr(created_at)"></div>
                        </div>
                        <div class="mt-auto">
                            <div class="font-bold text-[9.5px]" x-text="created_by"></div>
                            <div class="text-[8px] text-slate-400">Staff MKT</div>
                        </div>
                    </div>
                </div>
                {{-- Approvers --}}
                <template x-for="(step, idx) in approvals" :key="idx">
                    <div class="table-cell p-1.5 border-r border-slate-900 last:border-r-0 align-top" :style="{ width: (100 / (1 + approvals.length)) + '%' }">
                        <div class="flex flex-col justify-between items-center h-20 w-full">
                            <div class="flex flex-col items-center w-full">
                                <template x-if="step.status === 'Approved'">
                                    <div class="flex flex-col items-center">
                                        <div class="inline-block border-2 border-emerald-600 text-emerald-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-xs transform -rotate-3 select-none origin-center">APPROVED</div>
                                        <div class="text-[8.5px] text-slate-500 mt-0.5" x-text="step.approved_at"></div>
                                        <div x-show="step.due_date_closed" class="text-[8.5px] text-rose-600 font-bold mt-0.5">
                                            Due Close: <span x-text="formatDateStr(step.due_date_closed)"></span>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="step.status === 'Rejected'">
                                    <div class="flex flex-col items-center">
                                        <div class="inline-block border-2 border-rose-600 text-rose-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-xs transform -rotate-3 select-none origin-center">REJECTED</div>
                                        <div class="text-[8.5px] text-slate-500 mt-0.5" x-text="step.approved_at"></div>
                                    </div>
                                </template>
                                <template x-if="step.status === 'Pending'">
                                    <div class="text-[9px] text-amber-500 font-bold italic animate-pulse py-1">PENDING</div>
                                </template>
                                <template x-if="step.status === 'Waiting'">
                                    <div class="text-[8.5px] text-slate-400 py-1">WAITING</div>
                                </template>
                            </div>
                            <div class="mt-auto w-full text-center">
                                <div class="font-bold text-[9.5px] text-slate-900" x-text="step.approver_name || '—'"></div>
                                <div class="text-[8px] text-slate-400" x-text="step.approver_position"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
