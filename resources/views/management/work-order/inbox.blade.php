@extends('layouts.app')

@section('title', 'WO Inbox · Promise Management')

@section('content')
@php
    $mapWo = function($collection) {
        return $collection->map(function($wo) {
            $deptCodes = collect();
            if ($wo->ownerDepartment) {
                $deptCodes->push($wo->ownerDepartment->code);
            }
            foreach ($wo->supportDepartments as $sd) {
                if ($sd->code) {
                    $deptCodes->push($sd->code);
                }
            }
            $targetDepts = $deptCodes->unique()->filter()->implode(' / ') ?: '—';

            return [
                'id' => $wo->id,
                'hashed_id' => $wo->hashed_id,
                'wo_number' => $wo->wo_number,
                'subject' => $wo->subject,
                'status' => $wo->status,
                'priority' => $wo->priority,
                'created_at' => $wo->created_at ? $wo->created_at->toISOString() : null,
                'target_departments' => $targetDepts,
                'customer_code' => $wo->inquiry->customer->code ?? '—',
                'model_names' => $wo->inquiry->projectModel->name ?? '—'
            ];
        })->values();
    };
@endphp

{{-- Main Container: Matches the structural layout pattern in form.blade.php (mt-16 and h-[calc(100vh-64px)]) --}}
<div class="flex h-[calc(100vh-64px)] mt-15 overflow-hidden bg-white" x-data="outlookInbox()" x-init="initInbox()">
    
    {{-- Toast Notification --}}
    <div id="toast" class="fixed bottom-5 right-5 z-50 transform translate-y-20 opacity-0 transition-all duration-300 bg-slate-800 text-white text-xs py-2.5 px-4 font-medium flex items-center gap-2 border-l-4 border-[#0c4da2]">
        <svg class="w-3.5 h-3.5 text-[#0c4da2] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span id="toast-message">Success!</span>
    </div>

    {{-- Split Layout Grid (Spanning full width/height) --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-0 border-t border-slate-200 bg-white min-h-0 overflow-hidden">
        
        {{-- LEFT PANEL: LIST OF SPK --}}
        <div class="lg:col-span-4 border-r border-slate-200 bg-white flex flex-col h-full overflow-hidden"
             :class="selectedHashedId ? 'hidden lg:flex' : 'flex h-full'">
            
            {{-- Filter Row (Microsoft Style matching Admin) --}}
            <div class="min-h-[44px] py-1.5 md:py-0 px-3 border-b border-slate-200 bg-slate-100 flex items-center justify-between gap-1.5 shrink-0 relative z-30 select-none">
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-slate-500 tracking-wider shrink-0 flex items-center gap-1 mr-1">
                        <i class="fa-solid fa-filter text-[9px]"></i> Category:
                    </span>
                    
                    {{-- Dropdown Filter --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" type="button"
                                class="h-7 px-2 border rounded-sm flex items-center justify-center gap-1 transition-colors text-[10px] bg-white border-slate-300 text-gray-600 hover:bg-gray-50 focus:outline-none">
                            <span class="font-semibold" x-text="getFilterLabel()"></span>
                            <i class="fa-solid fa-chevron-down text-[8px] text-slate-400"></i>
                        </button>
                        <div x-show="open" 
                             class="absolute left-0 mt-1 w-44 bg-white border border-slate-300 shadow-md rounded-sm py-1 text-xs z-50"
                             style="display: none;">
                            <button type="button" @click="setFilter('all'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 text-slate-700">All SPK</button>
                            <button type="button" @click="setFilter('pending'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 text-slate-700">Pending Action</button>
                            <button type="button" @click="setFilter('approved'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 text-slate-700">Approved</button>
                            <button type="button" @click="setFilter('rejected'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-gray-50 text-slate-700">Rejected</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Bar Row --}}
            <div class="h-[52px] px-3 border-b border-slate-200 bg-slate-50 flex items-center gap-2 shrink-0">
                <div class="flex-1 flex items-center gap-2 border border-slate-300 bg-white px-3 h-8">
                    <svg class="h-3.5 w-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="searchQuery" placeholder="Search SPK..."
                           class="flex-1 py-1 text-xs outline-none bg-transparent text-gray-700 placeholder-gray-400 border-none focus:ring-0 focus:outline-none">
                </div>
            </div>

            {{-- Count Header --}}
            <div class="px-4 py-1.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center text-[10px] text-gray-500 font-bold tracking-wider select-none shrink-0">
                <span>Document List</span>
                <span class="text-slate-400 font-medium" x-text="filteredList.length + ' SPK'"></span>
            </div>

            {{-- List Items (Outlook style with scrollable area) --}}
            <div class="flex-1 overflow-y-auto divide-y divide-slate-200 bg-white min-h-0">
                <template x-for="item in filteredList" :key="item.id">
                    <div @click="selectWo(item.hashed_id)" 
                         class="px-4 py-3 cursor-pointer transition-all flex flex-col border-l-4 border-y border-transparent"
                         :class="selectedHashedId === item.hashed_id ? 'bg-blue-50 border-l-[#0c4da2] border-y-blue-100 relative z-10' : 'hover:bg-gray-50/50'">
                        
                        <div class="flex justify-between items-start gap-2 select-none">
                            <span class="text-xs font-bold text-slate-800" x-text="item.wo_number"></span>
                            <span class="text-[9px] text-slate-400 font-mono" x-text="formatDate(item.created_at)"></span>
                        </div>
                        
                        <div class="text-[10px] text-slate-500 mt-1 flex flex-wrap gap-x-2 select-none">
                            <span>Cust: <span class="font-bold text-slate-700" x-text="item.customer_code"></span></span>
                            <span>•</span>
                            <span>Model: <span class="font-bold text-slate-700" x-text="item.model_names"></span></span>
                        </div>
                        
                        <div class="flex items-center justify-between mt-2 select-none">
                            <span class="text-[9px] text-slate-400 font-bold" x-text="'To: ' + item.target_departments"></span>
                            <div class="flex gap-1.5">
                                <span class="px-1.5 py-0.5 text-[8.5px] font-bold border rounded-xs uppercase" :class="getPriorityClass(item.priority)" x-text="item.priority"></span>
                                <span class="px-1.5 py-0.5 text-[8.5px] font-bold border rounded-xs uppercase" :class="getStatusClass(item.status)" x-text="item.status"></span>
                            </div>
                        </div>
                    </div>
                </template>
                
                <template x-if="filteredList.length === 0">
                    <div class="py-12 text-center text-slate-450 text-xs italic bg-white">
                        No SPK records found.
                    </div>
                </template>
            </div>
        </div>

        {{-- RIGHT PANEL: PREVIEW & INTERACTION --}}
        <div class="lg:col-span-8 bg-slate-50 flex flex-col h-full overflow-hidden"
             :class="selectedHashedId ? 'flex h-full' : 'hidden lg:flex'">
            
            <template x-if="!selectedHashedId">
                <div class="flex-1 flex flex-col items-center justify-center text-center p-8 text-slate-400 bg-white">
                    <i class="fa-solid fa-envelope-open text-4xl mb-4 text-slate-200"></i>
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest mb-1">WO Review Console</h3>
                    <p class="text-xs text-slate-500 max-w-sm">Select an WO from the left panel to review its details, approve, or update checklist progress.</p>
                </div>
            </template>
            
            <template x-if="selectedHashedId && loadingDetail">
                <div class="flex-1 flex items-center justify-center bg-white">
                    <div class="text-center space-y-2 text-slate-500 text-xs">
                        <i class="fa-solid fa-spinner animate-spin text-xl text-[#0c4da2]"></i>
                        <p>Loading document details...</p>
                    </div>
                </div>
            </template>

            <template x-if="selectedHashedId && !loadingDetail">
                <div class="flex-1 flex flex-col overflow-hidden bg-slate-50">
                    {{-- Header Panel --}}
                    <div class="px-4 py-2 border-b border-slate-200 bg-white flex justify-between items-center flex-none select-none">
                        <div class="flex items-center gap-3">
                            {{-- Mobile back button --}}
                            <button type="button" @click="selectedHashedId = null"
                                    class="lg:hidden w-7 h-7 flex items-center justify-center border border-slate-300 bg-white text-slate-500 hover:text-slate-800 rounded-xs">
                                <i class="fa-solid fa-arrow-left text-xs"></i>
                            </button>
                            <div class="flex gap-4">
                                <button type="button" @click="activeRightTab = 'doc'" 
                                        class="py-2 text-xs font-bold border-b-2 transition-all cursor-pointer"
                                        :class="activeRightTab === 'doc' ? 'border-[#0c4da2] text-[#0c4da2]' : 'border-transparent text-slate-500 hover:text-slate-800'">
                                    SPK Document
                                </button>
                                <button type="button" @click="activeRightTab = 'checklist'" 
                                        class="py-2 text-xs font-bold border-b-2 transition-all cursor-pointer"
                                        :class="activeRightTab === 'checklist' ? 'border-[#0c4da2] text-[#0c4da2]' : 'border-transparent text-slate-500 hover:text-slate-800'">
                                    PIC Task     Progress
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Scrollable preview content --}}
                    <div class="flex-1 overflow-y-auto p-4 bg-slate-100 flex flex-col items-center min-h-0">
                        {{-- Tab 1: SPK Document Preview --}}
                        <div x-show="activeRightTab === 'doc'" class="w-full max-w-[760px]" :key="selectedHashedId" 
                             x-data="{ 
                                 get document_no() { return detailData.document_no; },
                                 get doc_department() { return detailData.doc_department; },
                                 get publish_date() { return detailData.publish_date; },
                                 get doc_publish_date() { return detailData.doc_publish_date; },
                                 get doc_revision_no() { return detailData.doc_revision_no; },
                                 get page_hal() { return detailData.page_hal; },
                                 get work_order_no() { return detailData.wo_number; },
                                 get priority() { return detailData.priority; },
                                 get products() { return detailData.products; },
                                 get first_sample_date() { return detailData.first_sample_date; },
                                 get due_date_plan() { return detailData.due_date_plan; },
                                 get due_dates_closed() { return detailData.due_dates_closed; },
                                 get remarks() { return detailData.remarks; },
                                 get approvals() { return detailData.approvals; },
                                 get released_at() { return detailData.released_at; },
                                 get created_by() { return detailData.created_by; },
                                 get created_at() { return detailData.created_at; },
                                 get target_departments_full() { return detailData.target_departments || '—'; },
                                 getDeptCodeByRuleId(ruleId) {
                                     let rule = this.approvalRulesList.find(r => r.id == ruleId);
                                     return rule ? rule.dept_code : '—';
                                 },
                                 computedDocRevisionNo() { return String(this.doc_revision_no).padStart(2, '0'); }
                             }">
                            @include('management.work-order.preview')
                        </div>
                                    {{-- Tab 2: PIC Checklist --}}
                        <div x-show="activeRightTab === 'checklist'" class="w-full max-w-[760px] space-y-4">
                            {{-- Checklist Product Search --}}
                            <div class="bg-white border border-slate-300 rounded-xs p-2.5 shadow-2xs flex items-center gap-2">
                                <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs ml-1"></i>
                                <input type="text" x-model="checklistSearchQuery" placeholder="Search product by part no or name..."
                                       class="w-full text-xs text-slate-800 bg-transparent focus:outline-none border-none p-0">
                                <button x-show="checklistSearchQuery" @click="checklistSearchQuery = ''" class="text-slate-400 hover:text-slate-655 cursor-pointer">
                                    <i class="fa-solid fa-circle-xmark text-xs"></i>
                                </button>
                            </div>

                            <template x-for="(proc, pIdx) in detailData.processes" :key="pIdx">
                                <div class="bg-white border border-slate-300 rounded-xs overflow-hidden shadow-2xs">
                                    <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-800" x-text="proc.process_name"></span>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <template x-for="(dept, dIdx) in proc.assigned_departments" :key="dIdx">
                                            <div class="border border-slate-200 rounded-xs overflow-hidden">
                                                {{-- Accordion Header --}}
                                                <button type="button" @click="toggleDept(proc.process_id, dept.department_id)"
                                                        class="w-full flex justify-between items-center bg-slate-50 hover:bg-slate-100/70 p-2.5 border-b border-slate-200 text-xs font-semibold cursor-pointer select-none">
                                                    <div class="flex items-center gap-2">
                                                        <span class="px-2 py-0.5 rounded-xs bg-slate-200 text-slate-800 font-bold" x-text="dept.department_code"></span>
                                                        <span class="text-slate-500" x-text="'(PIC: ' + dept.pic_name + ')'"></span>
                                                        <template x-if="dept.is_my_pic_task === true || dept.is_my_pic_task === 1">
                                                            <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 border border-indigo-200 font-extrabold text-[9px] rounded-xs uppercase tracking-wider">My Task</span>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center gap-3 select-none">
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-mono text-slate-600 text-[11px]" x-text="dept.checked_product_ids.length + '/' + detailData.products.length + ' Done'"></span>
                                                            <div class="w-48 bg-slate-200 h-1.5 rounded-full overflow-hidden border border-slate-300/30 flex-shrink-0">
                                                                <div class="bg-[#0c4da2] h-full rounded-full transition-all duration-300" :style="'width: ' + (detailData.products.length > 0 ? (dept.checked_product_ids.length / detailData.products.length * 100) : 0) + '%'"></div>
                                                            </div>
                                                        </div>
                                                        <i class="fa-solid text-[9px] text-slate-400" :class="isDeptExpanded(proc.process_id, dept.department_id) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                                    </div>
                                                </button>
                                                
                                                {{-- Accordion Body (Checkbox checklist) --}}
                                                <div x-show="isDeptExpanded(proc.process_id, dept.department_id)" class="p-3 space-y-2.5 bg-white border-t border-slate-200">
                                                    {{-- Lock Warning if not approved --}}
                                                    <template x-if="detailData.status !== 'Approved' && detailData.status !== 'Released'">
                                                        <div class="px-2.5 py-1.5 text-[10px] bg-amber-50 text-amber-700 border border-amber-200 rounded-xs flex items-center gap-1.5 font-bold mb-2">
                                                            <i class="fa-solid fa-lock"></i> Progress checklist is locked during approval process.
                                                        </div>
                                                    </template>
                                                    
                                                    <div class="bg-slate-50 p-2.5 border border-slate-200 rounded-xs">
                                                        <div class="grid grid-cols-2 gap-2">
                                                            <template x-for="p in detailData.products.filter(p => !checklistSearchQuery || p.customer_part_no.toLowerCase().includes(checklistSearchQuery.toLowerCase()) || p.customer_part_name.toLowerCase().includes(checklistSearchQuery.toLowerCase()))" :key="p.id">
                                                                <div class="flex items-center justify-between p-2 border rounded-xs text-xs transition-colors duration-200 shadow-2xs"
                                                                     :class="dept.checked_product_ids.includes(Number(p.id)) ? 'border-emerald-200 bg-emerald-50/20' : 'border-slate-200 bg-white'">
                                                                    
                                                                    {{-- Checkbox for PIC (Enabled only if approved) --}}
                                                                    <template x-if="(dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (detailData.status === 'Approved' || detailData.status === 'Released')">
                                                                        <label class="flex items-center gap-2.5 cursor-pointer w-full select-none text-slate-700">
                                                                            <input type="checkbox" name="checked_product_ids[]" :value="p.id"
                                                                                   :checked="dept.checked_product_ids.includes(Number(p.id))"
                                                                                   @change="toggleProductChecked(proc.process_id, dept.department_id, p.id, $event.target.checked)"
                                                                                   class="h-3.5 w-3.5 rounded-xs border-slate-300 text-[#0c4da2] focus:ring-0 cursor-pointer">
                                                                            <div class="min-w-0 flex-1">
                                                                                <span class="font-extrabold block text-slate-800" :class="dept.checked_product_ids.includes(Number(p.id)) ? 'text-emerald-800' : 'text-slate-800'" x-text="p.customer_part_no"></span>
                                                                                <span class="text-[10px] text-slate-455 truncate block" x-text="p.customer_part_name"></span>
                                                                            </div>
                                                                        </label>
                                                                    </template>
                                                                    
                                                                    {{-- Read-only or Disabled checklist if not PIC or not approved --}}
                                                                    <template x-if="!((dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (detailData.status === 'Approved' || detailData.status === 'Released'))">
                                                                        <div class="flex items-center gap-2.5 w-full">
                                                                            <div class="w-4 h-4 rounded-xs flex items-center justify-center flex-none border"
                                                                                 :class="dept.checked_product_ids.includes(Number(p.id)) ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'">
                                                                                <i class="fa-solid text-[8px]" :class="dept.checked_product_ids.includes(Number(p.id)) ? 'fa-check' : 'fa-xmark'"></i>
                                                                            </div>
                                                                            <div class="min-w-0 flex-1">
                                                                                <span class="font-bold block" :class="dept.checked_product_ids.includes(Number(p.id)) ? 'text-emerald-800 font-extrabold' : 'text-slate-600'" x-text="p.customer_part_no"></span>
                                                                                <span class="text-[10px] text-slate-455 truncate block" x-text="p.customer_part_name"></span>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                    
                                                    {{-- Save Button for PIC --}}
                                                    <template x-if="(dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (detailData.status === 'Approved' || detailData.status === 'Released')">
                                                        <div class="flex justify-end pt-2">
                                                            <button type="button" @click="saveDeptProgress(proc.process_id, dept.department_id, dept.checked_product_ids)"
                                                                    class="px-4 py-1.5 bg-indigo-650 hover:bg-indigo-750 text-white font-bold text-[10px] uppercase tracking-wider rounded-xs shadow-sm hover:shadow transition-all cursor-pointer flex items-center gap-1.5">
                                                                <i class="fa-solid fa-floppy-disk text-[9px]"></i> Save Progress
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Sticky Footer Actions --}}
                    <div class="p-4 border-t border-slate-200 bg-slate-50 flex-none select-none">
                        <template x-if="detailData.can_approve">
                            <div class="flex items-end gap-2 w-full">
                                    {{-- Remarks --}}
                                    <div class="flex flex-col gap-1 flex-1">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Remarks</span>
                                        <input type="text" x-model="approvalRemarks" placeholder="Add approval comments/remarks here..."
                                               class="w-full px-2.5 py-1.5 text-xs border border-slate-300 bg-white text-slate-800 rounded-xs focus:outline-none focus:border-[#0c4da2]">
                                    </div>
                                    {{-- Due Date Closed --}}
                                    <div class="flex flex-col gap-1 shrink-0">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Due Date Closed</span>
                                        <input type="date" x-model="dueDateClosed"
                                               class="px-2.5 py-1.5 text-xs border border-slate-300 bg-white text-slate-800 rounded-xs focus:outline-none focus:border-[#0c4da2]">
                                    </div>
                                    {{-- Action Buttons --}}
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" @click="submitApproval('approve')"
                                                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xs cursor-pointer flex items-center gap-1.5 transition-colors">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button type="button" @click="submitApproval('reject')"
                                                class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xs cursor-pointer flex items-center gap-1.5 transition-colors">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </div>
                                </div>
                        </template>
                        
                        <template x-if="!detailData.can_approve">
                            <div class="text-xs text-slate-500 italic py-1">
                                <span x-text="getDocProcessedLabel()"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
const WO_BASE_URL = '{{ url('management') }}';
function outlookInbox() {
    return {
        approvalRulesList: @json($approvalRules->map(fn($r) => ['id' => $r->id, 'dept_code' => $r->department->code ?? $r->department->name ?? ''])->values()),
        activeFilter: 'all',
        searchQuery: '',
        selectedHashedId: null,
        loadingDetail: false,
        activeRightTab: 'doc',
        approvalRemarks: '',
        dueDateClosed: '',
        expandedDepts: {},
        checklistSearchQuery: '',
        
        detailData: {
            id: null,
            hashed_id: '',
            wo_number: '',
            subject: '',
            status: '',
            priority: '',
            approvals: [],
            processes: [],
            products: [],
            document_no: '',
            doc_department: '',
            doc_publish_date: '',
            doc_revision_no: 0,
            page_hal: '',
            revision_no: 0,
            department_id: null,
            department_name: '',
            first_sample_date: '',
            due_date_plan: '',
            due_dates_closed: {},
            remarks: '',
            selected_approval_rule_ids: [],
            created_by: '',
            created_at: '',
            can_approve: false
        },
        
        allList: @json($mapWo($all)),
        recentList: @json($mapWo($recent)),
        approvedList: @json($mapWo($approved)),
        rejectedList: @json($mapWo($rejected)),
        myTasksList: @json($mapWo($myTasks)),
        
        get filteredList() {
            let list = [];
            if (this.activeFilter === 'all') {
                list = this.allList;
            } else if (this.activeFilter === 'pending') {
                const map = new Map();
                this.recentList.forEach(item => map.set(item.id, item));
                this.myTasksList.forEach(item => map.set(item.id, item));
                list = Array.from(map.values());
            } else if (this.activeFilter === 'approved') {
                list = this.approvedList;
            } else if (this.activeFilter === 'rejected') {
                list = this.rejectedList;
            }
            
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                list = list.filter(item => 
                    item.wo_number.toLowerCase().includes(query) ||
                    (item.subject && item.subject.toLowerCase().includes(query))
                );
            }
            return list;
        },
        
        getFilterLabel() {
            return {
                'all': 'All SPK',
                'pending': 'Pending Action',
                'approved': 'Approved',
                'rejected': 'Rejected'
            }[this.activeFilter];
        },
        
        setFilter(filter) {
            this.activeFilter = filter;
            if (this.filteredList.length > 0) {
                this.selectWo(this.filteredList[0].hashed_id);
            } else {
                this.selectedHashedId = null;
            }
        },
        
        showToast(message) {
            const toast = document.getElementById('toast');
            const msgSpan = document.getElementById('toast-message');
            msgSpan.textContent = message;
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
        },
        
        getPriorityClass(priority) {
            return {
                'URGENT': 'bg-rose-50 text-rose-700 border-rose-200',
                'STANDARD': 'bg-amber-50 text-amber-700 border-amber-300',
                'LOW': 'bg-slate-50 text-slate-500 border-slate-200'
            }[priority] || 'bg-slate-50 text-slate-655';
        },
        
        getStatusClass(status) {
            return {
                'Draft': 'bg-blue-50 text-blue-700 border-blue-200',
                'Pending Approval': 'bg-amber-50 text-amber-700 border-amber-300',
                'Approved': 'bg-emerald-50 text-emerald-700 border-emerald-350',
                'Released': 'bg-sky-50 text-sky-700 border-sky-200'
            }[status] || 'bg-slate-50 text-slate-700';
        },
        
        toggleDept(procId, deptId) {
            const key = procId + '_' + deptId;
            this.expandedDepts[key] = !this.expandedDepts[key];
        },
        
        isDeptExpanded(procId, deptId) {
            const key = procId + '_' + deptId;
            return !!this.expandedDepts[key];
        },

        selectWo(hashedId) {
            this.selectedHashedId = hashedId;
            this.loadingDetail = true;
            this.approvalRemarks = '';
            this.dueDateClosed = '';
            
            fetch(`${WO_BASE_URL}/work-order/${hashedId}/api-details`)
                .then(res => res.json())
                .then(json => {
                    this.loadingDetail = false;
                    if (json.success) {
                        this.detailData = json.data;
                        this.activeRightTab = 'doc'; // Default tab always display SPK document
                        
                        // Sort departments (my task first) and set default expanded state
                        this.expandedDepts = {};
                        this.detailData.processes.forEach(proc => {
                            proc.assigned_departments.sort((a, b) => {
                                const aMy = a.is_my_pic_task ? 1 : 0;
                                const bMy = b.is_my_pic_task ? 1 : 0;
                                return bMy - aMy;
                            });
                            
                            proc.assigned_departments.forEach(dept => {
                                const key = proc.process_id + '_' + dept.department_id;
                                this.expandedDepts[key] = !!dept.is_my_pic_task;
                            });
                        });
                    } else {
                        this.showToast('Failed to load SPK details.');
                    }
                })
                .catch(err => {
                    this.loadingDetail = false;
                    console.error(err);
                });
        },
        
        getUniqueSchedules() {
            let uniqueList = [];
            let seen = new Set();
            if (this.detailData && this.detailData.products) {
                this.detailData.products.forEach(p => {
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
            }
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
        
        getDocProcessedLabel() {
            if (this.detailData.status === 'Released') {
                return 'This SPK document has been fully released.';
            }
            const activeStep = this.detailData.approvals.find(a => a.status === 'Pending');
            if (activeStep) {
                return `Waiting for approval from: ${activeStep.approver_position} (${activeStep.approver_name || 'Staff'})`;
            }
            return 'The document has been fully processed.';
        },
        
        submitApproval(action) {
            const url = `${WO_BASE_URL}/work-order/${this.selectedHashedId}/${action}`;
            const fd = new FormData();
            fd.append('remarks', this.approvalRemarks);
            if (action === 'approve') {
                fd.append('due_date_closed', this.dueDateClosed);
            }
            
            fetch(url, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(res => {
                if (res.ok) {
                    this.showToast(`Document successfully ${action === 'approve' ? 'approved' : 'rejected'}.`);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    this.showToast('Failed to process document.');
                }
            })
            .catch(err => {
                console.error(err);
                this.showToast('Network error occurred.');
            });
        },
        
        toggleProductChecked(processId, departmentId, productId, isChecked) {
            const proc = this.detailData.processes.find(p => p.process_id === processId);
            if (!proc) return;
            const dept = proc.assigned_departments.find(d => d.department_id === departmentId);
            if (!dept) return;

            productId = Number(productId);
            if (isChecked) {
                if (!dept.checked_product_ids.includes(productId)) {
                    dept.checked_product_ids.push(productId);
                }
            } else {
                dept.checked_product_ids = dept.checked_product_ids.filter(id => id !== productId);
            }
        },

        saveDeptProgress(processId, departmentId, checkedProductIds) {
            const fd = new FormData();
            fd.append('process_id', processId);
            fd.append('department_id', departmentId);
            checkedProductIds.forEach(id => {
                fd.append('checked_product_ids[]', id);
            });

            fetch(`${WO_BASE_URL}/work-order/${this.selectedHashedId}/progress`, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(res => {
                if (res.ok) {
                    this.showToast('Progress saved successfully!');
                } else {
                    this.showToast('Failed to save progress.');
                }
            })
            .catch(err => {
                console.error(err);
                this.showToast('Network error while saving progress.');
            });
        },
        
        initInbox() {
            const urlParams = new URLSearchParams(window.location.search);
            const selectId = urlParams.get('select');
            if (selectId) {
                this.selectWo(selectId);
            } else if (this.filteredList.length > 0) {
                this.selectWo(this.filteredList[0].hashed_id);
            }
        }
    };
}
</script>
@endpush
@endsection
