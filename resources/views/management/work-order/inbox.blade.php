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
<div class="flex h-[calc(100vh-64px)] mt-15 overflow-hidden bg-white dark:bg-slate-900" x-data="Object.assign(outlookInbox(), chatRoomEngine('work_order', null))" x-init="initInbox()">
    
    <x-sweetalert />

    {{-- Split Layout Grid (Spanning full width/height) --}}
    <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-0 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 min-h-0 overflow-hidden">
        
        {{-- LEFT PANEL: LIST OF WO --}}
        <div class="lg:col-span-4 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col h-full overflow-hidden"
             :class="selectedHashedId ? 'hidden lg:flex' : 'flex h-full'">
            
            {{-- Filter Row (Microsoft Style matching Admin) --}}
            <div class="min-h-[44px] py-1.5 md:py-0 px-3 border-b border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950 flex items-center justify-between gap-1.5 shrink-0 relative z-30 select-none">
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 tracking-wider shrink-0 flex items-center gap-1 mr-1">
                        <i class="fa-solid fa-filter text-[9px]"></i> Category:
                    </span>
                    
                    {{-- Dropdown Filter --}}
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" type="button"
                                class="h-7 px-2 border rounded-sm flex items-center justify-center gap-1 transition-colors text-[10px] bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 focus:outline-none">
                            <span class="font-semibold" x-text="getFilterLabel()"></span>
                            <i class="fa-solid fa-chevron-down text-[8px] text-slate-400 dark:text-slate-550"></i>
                        </button>
                        <div x-show="open" 
                             class="absolute left-0 mt-1 w-44 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 shadow-md rounded-sm py-1 text-xs z-50"
                             style="display: none;">
                            <button type="button" @click="setFilter('all'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-blue-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900">All WO</button>
                            <button type="button" @click="setFilter('pending'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-blue-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900">Pending Action</button>
                            <button type="button" @click="setFilter('approved'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-blue-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900">Approved</button>
                            <button type="button" @click="setFilter('rejected'); open = false" class="w-full text-left px-3 py-1.5 hover:bg-blue-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900">Rejected</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Bar Row --}}
            <div class="h-[52px] px-3 border-b border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 flex items-center gap-2 shrink-0">
                <div class="flex-1 flex items-center gap-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 h-8">
                    <svg class="h-3.5 w-3.5 text-gray-400 dark:text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="searchQuery" placeholder="Search WO..."
                           class="flex-1 py-1 text-xs outline-none bg-transparent text-gray-700 dark:text-slate-200 placeholder-gray-400 dark:placeholder-slate-500 border-none focus:ring-0 focus:outline-none">
                </div>
            </div>

            {{-- Count Header --}}
            <div class="px-4 py-1.5 bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center text-[10px] text-gray-500 dark:text-slate-400 font-bold tracking-wider select-none shrink-0">
                <span>Document List</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold" x-text="filteredList.length + ' WO'"></span>
            </div>

            {{-- List Items (Outlook style with scrollable area) --}}
            <div class="flex-1 overflow-y-auto divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900 min-h-0">
                <template x-for="item in filteredList" :key="item.id">
                    <div @click="selectWo(item.hashed_id)" 
                         class="px-4 py-3 cursor-pointer transition-all flex flex-col border-l-4 border-y border-transparent"
                         :class="(selectedHashedId === item.hashed_id || (selectedWoId && selectedWoId === item.id)) ? 'bg-blue-50/70 dark:bg-blue-950/20 border-l-[#0c4da2] dark:border-l-blue-500 border-y-blue-100 dark:border-y-blue-900/30 relative z-10' : 'hover:bg-gray-50/50 dark:hover:bg-slate-800/40'">
                        
                        <div class="flex justify-between items-start gap-2 select-none">
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200" x-text="item.wo_number"></span>
                            <span class="text-xs text-slate-400 dark:text-slate-500 font-mono" x-text="formatDate(item.created_at)"></span>
                        </div>
                        
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap gap-x-2 select-none">
                            <span>Cust: <span class="font-bold text-slate-700 dark:text-slate-300" x-text="item.customer_code"></span></span>
                            <span>•</span>
                            <span>Model: <span class="font-bold text-slate-700 dark:text-slate-300" x-text="item.model_names"></span></span>
                        </div>
                        
                        <div class="flex items-center justify-between mt-2 select-none">
                            <span class="text-xs text-slate-500 dark:text-slate-555 font-medium" x-text="'To: ' + item.target_departments"></span>
                            <div class="flex gap-1.5">
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-sm uppercase tracking-wider" :class="getPriorityClass(item.priority)" x-text="item.priority"></span>
                                <span class="px-1.5 py-0.5 text-[9px] font-extrabold rounded-sm uppercase tracking-wider" :class="getStatusClass(item.status)" x-text="item.status"></span>
                            </div>
                        </div>
                    </div>
                </template>
                
                <template x-if="filteredList.length === 0">
                    <div class="py-12 text-center text-slate-450 dark:text-slate-500 text-xs italic bg-white dark:bg-slate-900">
                        No WO records found.
                    </div>
                </template>
            </div>
        </div>

        {{-- RIGHT PANEL: PREVIEW & INTERACTION --}}
        <div class="lg:col-span-8 bg-slate-100 dark:bg-slate-900 flex flex-col h-full overflow-hidden"
             :class="selectedHashedId ? 'flex h-full' : 'hidden lg:flex'">
            
            <template x-if="!selectedHashedId">
                <div class="flex-1 flex flex-col items-center justify-center text-center p-8 text-slate-400 bg-white dark:bg-slate-900">
                    <i class="fa-solid fa-envelope-open text-4xl mb-4 text-slate-200 dark:text-slate-700"></i>
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-1">WO Review Console</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm">Select an WO from the left panel to review its details, approve, or update checklist progress.</p>
                </div>
            </template>
            
            <template x-if="selectedHashedId && loadingDetail">
                <div class="flex-1 flex items-center justify-center bg-white dark:bg-slate-900">
                    <div class="text-center space-y-2 text-slate-500 dark:text-slate-400 text-xs">
                        <i class="fa-solid fa-spinner animate-spin text-xl text-[#0c4da2] dark:text-blue-500"></i>
                        <p>Loading document details...</p>
                    </div>
                </div>
            </template>

            <template x-if="selectedHashedId && !loadingDetail">
                <div class="flex-1 flex flex-col overflow-hidden bg-slate-100 dark:bg-slate-900">
                    {{-- Header Panel --}}
                    <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex justify-between items-center flex-none select-none">
                        <div class="flex items-center gap-3">
                            {{-- Mobile back button --}}
                            <button type="button" @click="selectedHashedId = null"
                                    class="lg:hidden w-7 h-7 flex items-center justify-center border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-sm">
                                <i class="fa-solid fa-arrow-left text-xs"></i>
                            </button>
                            <div class="flex gap-4">
                                <button type="button" @click="if (activeRightTab === 'chat') { leaveRoom(); } activeRightTab = 'doc'" 
                                        class="py-2 text-xs font-bold border-b-2 transition-all cursor-pointer"
                                        :class="activeRightTab === 'doc' ? 'border-[#0c4da2] dark:border-blue-500 text-[#0c4da2] dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    WO Document
                                </button>
                                <button type="button" @click="if (activeRightTab === 'chat') { leaveRoom(); } activeRightTab = 'checklist'" 
                                        class="py-2 text-xs font-bold border-b-2 transition-all cursor-pointer"
                                        :class="activeRightTab === 'checklist' ? 'border-[#0c4da2] dark:border-blue-500 text-[#0c4da2] dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    Task Progress Checklist
                                </button>
                                <button type="button" @click="activeRightTab = 'chat'; if (chatId !== detailData.id) { initRoom('work_order', detailData.id, detailData.wo_number, (detailData.inquiry?.customer?.code || '') + ' • ' + (detailData.inquiry?.project_model?.name || '')); }" 
                                        class="py-2 text-xs font-bold border-b-2 transition-all cursor-pointer flex items-center gap-1.5"
                                        :class="activeRightTab === 'chat' ? 'border-[#0c4da2] dark:border-blue-500 text-[#0c4da2] dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'">
                                    <i class="fa-solid fa-comments text-xs"></i>
                                    <span>Discussion Room</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Scrollable preview content for Document & Checklist --}}
                    <div x-show="activeRightTab !== 'chat'" class="flex-1 overflow-auto p-4 bg-slate-100 dark:bg-slate-950 flex flex-col items-center min-h-0">
                        {{-- Tab 1: WO Document Preview --}}
                        <div x-show="activeRightTab === 'doc'" class="w-full max-w-[760px]" :key="selectedHashedId" 
                             x-data="{ 
                                 get wo_type() { return detailData.wo_type; },
                                 get document_no() { return detailData.document_no; },
                                 get doc_department() { return detailData.doc_department; },
                                 get doc_publish_date() { return detailData.doc_publish_date; },
                                 get doc_revision_no() { return detailData.doc_revision_no; },
                                 get page_hal() { return detailData.page_hal; },
                                 get work_order_no() { return detailData.wo_number; },
                                 get priority() { return detailData.priority; },
                                 get urgent_reason() { return detailData.urgent_reason; },
                                 get urgent_confirmed_by() { return detailData.urgent_confirmed_by; },
                                 get urgent_confirmed_at() { return detailData.urgent_confirmed_at; },
                                 get products() { return detailData.products; },
                                 get first_sample_date() { return detailData.first_sample_date; },
                                 get due_date_plan() { return detailData.due_date_plan; },
                                 get due_dates_closed() { return detailData.due_dates_closed; },
                                 get remarks() { return detailData.remarks; },
                                 get approvals() { return detailData.approvals; },
                                 get released_at() { return detailData.released_at; },
                                 get created_by() { return detailData.created_by; },
                                 get created_at() { return detailData.created_at; },
                                 get target_departments_full() { return detailData.target_departments_full || '—'; },
                                 getDeptCodeByRuleId(ruleId) {
                                     let rule = this.approvalRulesList.find(r => r.id == ruleId);
                                     return rule ? rule.dept_code : '—';
                                 },
                                 computedDocRevisionNo() { return String(this.doc_revision_no).padStart(2, '0'); }
                             }">
                            <div x-show="detailData && detailData.wo_type === 'SPK_2_TOOLING'">
                                @include('management.work-order.wo2-tooling.preview')
                            </div>
                            <div x-show="detailData && detailData.wo_type === 'SPK_2_FASTENER'">
                                @include('management.work-order.wo2-fastener.preview')
                            </div>
                            <div x-show="!detailData || (detailData.wo_type !== 'SPK_2_TOOLING' && detailData.wo_type !== 'SPK_2_FASTENER')">
                                @include('management.work-order.wo1.preview')
                            </div>
                        </div>
                                    {{-- Tab 2: PIC Checklist (Planhat / Asana Management Style) --}}
                        <div x-show="activeRightTab === 'checklist'" class="w-full max-w-[780px] space-y-4">
                            
                            {{-- Search Filter Bar --}}
                            <div class="flex items-center justify-between gap-3">
                                <div class="relative flex-1 max-w-sm flex items-center">
                                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs absolute left-3 pointer-events-none"></i>
                                    <input type="text" 
                                           x-model="checklistSearchQuery" 
                                           placeholder="Search product by Part No or Name..."
                                           class="w-full pl-8 pr-7 py-1.5 text-xs text-slate-800 dark:text-slate-100 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg focus:outline-none focus:border-purple-500 focus:ring-1 focus:ring-purple-500/20 transition-all placeholder:text-slate-400 shadow-2xs">
                                    <button x-show="checklistSearchQuery" 
                                            @click="checklistSearchQuery = ''" 
                                            class="absolute right-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                                        <i class="fa-solid fa-circle-xmark text-xs"></i>
                                    </button>
                                </div>
                                <div class="text-xs text-slate-500 font-medium select-none">
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="detailData.products ? detailData.products.length : 0"></span> Products in checklist
                                </div>
                            </div>

                            {{-- Process & Department Task Groups --}}
                            <template x-for="(proc, pIdx) in detailData.processes" :key="pIdx">
                                <div class="space-y-3">
                                    <template x-for="(dept, dIdx) in proc.assigned_departments" :key="dIdx">
                                        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs transition-all">
                                            
                                            {{-- Group Header (Matching Reference Image) --}}
                                            <div @click="toggleDept(proc.process_id, dept.department_id)"
                                                 class="flex items-center justify-between px-4 py-3 bg-slate-50/60 hover:bg-slate-100/60 dark:bg-slate-800/40 dark:hover:bg-slate-800/70 cursor-pointer select-none transition-colors">
                                                
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <i class="fa-solid text-purple-600 dark:text-purple-400 text-xs transition-transform duration-200" 
                                                       :class="isDeptExpanded(proc.process_id, dept.department_id) ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                    
                                                    <span class="font-bold text-sm text-slate-800 dark:text-slate-100 truncate" x-text="proc.process_name"></span>
                                                    
                                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[11px] font-mono border border-slate-200 dark:border-slate-700" x-text="dept.department_code"></span>

                                                    <template x-if="dept.is_my_pic_task === true || dept.is_my_pic_task === 1">
                                                        <span class="px-2 py-0.5 bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800/60 font-semibold text-[10px] rounded-md uppercase tracking-wider">
                                                            My Task
                                                        </span>
                                                    </template>
                                                </div>

                                                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 flex-shrink-0">
                                                    <span class="font-mono text-xs" 
                                                          :class="getDeptProgressPct(dept) === 100 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-600 dark:text-slate-400'"
                                                          x-text="getDeptValidCheckedCount(dept) + '/' + (detailData.products ? detailData.products.length : 0)"></span>
                                                    
                                                    <i class="fa-regular text-sm" 
                                                       :class="getDeptProgressPct(dept) === 100 ? 'fa-circle-check text-emerald-500' : 'fa-circle text-slate-300 dark:text-slate-600'"></i>
                                                </div>
                                            </div>

                                            {{-- Subheader Row (PIC Info & Select All) --}}
                                            <div x-show="isDeptExpanded(proc.process_id, dept.department_id)" 
                                                 class="px-4 py-2 bg-slate-50/30 dark:bg-slate-800/20 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-slate-400">Assigned PIC:</span>
                                                    <span class="font-medium text-slate-700 dark:text-slate-300" x-text="dept.pic_name || '—'"></span>
                                                </div>

                                                {{-- Select All Checkbox for PIC --}}
                                                <template x-if="(dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (detailData.status === 'Approved' || detailData.status === 'Released')">
                                                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-medium text-purple-600 dark:text-purple-400 hover:underline select-none">
                                                        <input type="checkbox" 
                                                               :checked="isAllProductsChecked(proc.process_id, dept.department_id)"
                                                               @change="toggleSelectAllProducts(proc.process_id, dept.department_id, $event.target.checked)"
                                                               class="h-3.5 w-3.5 rounded border-slate-300 dark:border-slate-600 text-purple-600 focus:ring-0 cursor-pointer">
                                                        <span>Select All</span>
                                                    </label>
                                                </template>
                                            </div>

                                            {{-- Lock Warning if not approved --}}
                                            <template x-if="isDeptExpanded(proc.process_id, dept.department_id) && detailData.status !== 'Approved' && detailData.status !== 'Released'">
                                                <div class="px-4 py-2 text-xs bg-amber-500/10 text-amber-700 dark:text-amber-400 border-t border-amber-500/20 flex items-center gap-2 font-medium">
                                                    <i class="fa-solid fa-lock text-amber-500 text-[11px]"></i> Progress checklist is locked during approval process.
                                                </div>
                                            </template>

                                            {{-- Task Item Rows (Clean Table-style List) --}}
                                            <div x-show="isDeptExpanded(proc.process_id, dept.department_id)" class="border-t border-slate-100 dark:border-slate-800">
                                                <template x-for="p in detailData.products.filter(p => !checklistSearchQuery || p.customer_part_no.toLowerCase().includes(checklistSearchQuery.toLowerCase()) || p.customer_part_name.toLowerCase().includes(checklistSearchQuery.toLowerCase()))" :key="p.id">
                                                    <div class="px-4 py-2.5 flex items-center justify-between text-xs transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40 border-b border-slate-100 last:border-b-0 dark:border-slate-800/60"
                                                         :class="dept.checked_product_ids.includes(Number(p.id)) ? 'bg-emerald-50/15 dark:bg-emerald-950/10' : ''">
                                                        
                                                        {{-- Left Column: Checkbox, Part No, Product Name --}}
                                                        <div class="flex items-center gap-3 min-w-0 flex-1 pr-4">
                                                            {{-- Checkbox (Editable for PIC) --}}
                                                            <template x-if="(dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (detailData.status === 'Approved' || detailData.status === 'Released')">
                                                                <input type="checkbox" 
                                                                       name="checked_product_ids[]" 
                                                                       :value="p.id"
                                                                       :checked="dept.checked_product_ids.includes(Number(p.id))"
                                                                       @change="toggleProductChecked(proc.process_id, dept.department_id, p.id, $event.target.checked)"
                                                                       class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-purple-600 focus:ring-0 cursor-pointer flex-shrink-0">
                                                            </template>

                                                            {{-- Static Checkbox indicator (Read-Only) --}}
                                                            <template x-if="!((dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (detailData.status === 'Approved' || detailData.status === 'Released'))">
                                                                <div class="w-4 h-4 rounded border flex items-center justify-center text-[9px] flex-shrink-0"
                                                                     :class="dept.checked_product_ids.includes(Number(p.id)) ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600 text-transparent'">
                                                                    <i class="fa-solid fa-check"></i>
                                                                </div>
                                                            </template>

                                                            {{-- Part No --}}
                                                            <span class="font-medium text-slate-800 dark:text-slate-100 font-mono flex-shrink-0" 
                                                                  :class="dept.checked_product_ids.includes(Number(p.id)) ? 'text-emerald-900 dark:text-emerald-300' : ''"
                                                                  x-text="p.customer_part_no"></span>

                                                            {{-- Product Name --}}
                                                            <span class="truncate text-slate-500 dark:text-slate-400" 
                                                                  x-text="p.customer_part_name"></span>
                                                        </div>

                                                        {{-- Right Column: Priority, Status, PIC Avatar --}}
                                                        <div class="flex items-center gap-3 flex-shrink-0 select-none">
                                                            {{-- Priority Pill (If defined) --}}
                                                            <template x-if="detailData.priority">
                                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold"
                                                                      :class="detailData.priority === 'Urgent' ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-900' : (detailData.priority === 'High' ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700')"
                                                                      x-text="detailData.priority"></span>
                                                            </template>

                                                            {{-- Status Text / Date --}}
                                                            <span class="w-16 text-right text-[11px] font-medium"
                                                                  :class="dept.checked_product_ids.includes(Number(p.id)) ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-slate-400 dark:text-slate-500'"
                                                                  x-text="dept.checked_product_ids.includes(Number(p.id)) ? 'Done' : 'To Do'"></span>

                                                            {{-- PIC Avatar Circle --}}
                                                            <div class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center text-[9px] font-bold uppercase"
                                                                 :title="dept.pic_name"
                                                                 x-text="dept.pic_name ? dept.pic_name.split(' ').map(n => n[0]).slice(0, 2).join('') : 'P'"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Tab 3: Discussion Room (Full height) --}}
                    <div x-show="activeRightTab === 'chat'" class="flex-1 flex flex-col min-h-0 overflow-hidden bg-slate-100 dark:bg-slate-950" :key="'chat-' + selectedHashedId">
                        @include('management.chat.chat-room')
                    </div>

                    {{-- Sticky Footer Actions --}}
                    <div x-show="activeRightTab !== 'chat'" class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 flex-none select-none shadow-lg">
                        {{-- 1. Document / Approval Tab Footer --}}
                        <template x-if="activeRightTab === 'doc' && detailData.can_approve">
                            <div class="flex flex-col gap-2.5 w-full">
                                {{-- Redesigned Premium Urgent Priority Confirmation for Marketing GM --}}
                                <template x-if="detailData.is_marketing_gm_step">
                                    <div class="p-3 bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/80 rounded-sm space-y-2 w-full">
                                        <div class="flex items-center justify-between text-xs">
                                            <label class="flex items-center gap-2 font-bold text-rose-900 dark:text-rose-300 cursor-pointer select-none">
                                                <input type="checkbox" x-model="urgentConfirmed" class="w-4 h-4 text-rose-600 focus:ring-rose-500 rounded-sm cursor-pointer accent-rose-600">
                                                <span>I confirm this Work Order is URGENT</span>
                                            </label>
                                            <span class="text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Marketing GM Confirmation</span>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider">Urgent Reason / Note <span class="text-rose-500">*</span></span>
                                            <input type="text" x-model="urgentReason" placeholder="Enter reason why this SPK is urgent..."
                                                   class="w-full px-2.5 py-1.5 text-xs border border-rose-300 dark:border-rose-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-sm focus:outline-none focus:border-rose-500">
                                        </div>
                                    </div>
                                </template>

                                <div class="flex items-end gap-2 w-full">
                                    {{-- Remarks --}}
                                    <div class="flex flex-col gap-1 flex-1">
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Remarks</span>
                                        <input type="text" x-model="approvalRemarks" placeholder="Add approval comments/remarks here..."
                                               class="w-full px-2.5 py-1.5 text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-sm focus:outline-none focus:border-[#0c4da2] dark:focus:border-blue-500">
                                    </div>
                                    {{-- Due Date Closed --}}
                                    <div class="flex flex-col gap-1 shrink-0" x-show="isLastApprovalLevel()">
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Due Date Closed</span>
                                        <input type="date" x-model="dueDateClosed"
                                               class="px-2.5 py-1.5 text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 rounded-sm focus:outline-none focus:border-[#0c4da2] dark:focus:border-blue-500">
                                    </div>
                                    {{-- Action Buttons --}}
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" @click="submitApproval('approve')"
                                                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-sm cursor-pointer flex items-center gap-1.5 transition-colors">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button type="button" @click="submitApproval('reject')"
                                                class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-sm cursor-pointer flex items-center gap-1.5 transition-colors">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <template x-if="activeRightTab === 'doc' && !detailData.can_approve">
                            <div class="text-xs text-slate-500 dark:text-slate-400 italic py-1">
                                <span x-text="getDocProcessedLabel()"></span>
                            </div>
                        </template>

                        {{-- 2. Checklist Tab Footer --}}
                        <template x-if="activeRightTab === 'checklist' && getMyPicTasks().length > 0">
                            <div class="flex justify-between items-center gap-4 w-full">
                                <div class="flex flex-col gap-1.5 flex-1 max-w-[280px]">
                                    <div class="flex justify-between items-center text-[11px] text-slate-700 dark:text-slate-200 font-bold">
                                        <span>Progress Checklist</span>
                                        <span class="font-mono text-indigo-650 dark:text-indigo-400" x-text="getMyPicProgressSummary().checked + ' / ' + getMyPicProgressSummary().total + ' Done'"></span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden border border-slate-300/30">
                                        <div class="bg-indigo-600 dark:bg-indigo-500 h-full rounded-full transition-all duration-300"
                                             :style="'width: ' + (getMyPicProgressSummary().total > 0 ? (getMyPicProgressSummary().checked / getMyPicProgressSummary().total * 100) : 0) + '%'"></div>
                                    </div>
                                </div>
                                <button type="button" @click="saveAllMyProgress()"
                                        :disabled="!hasUnsavedChanges()"
                                        :class="hasUnsavedChanges() ? 'bg-indigo-600 hover:bg-indigo-750 text-white cursor-pointer shadow-xs hover:shadow' : 'bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 cursor-not-allowed'"
                                        class="px-4 py-2 font-bold text-xs rounded-sm transition-all flex items-center gap-1.5">
                                    <i class="fa-solid fa-floppy-disk"></i> Save Progress
                                </button>
                            </div>
                        </template>
                        
                        <template x-if="activeRightTab === 'checklist' && getMyPicTasks().length === 0">
                            <div class="text-xs text-slate-500 dark:text-slate-400 italic py-1">
                                You do not have any active PIC tasks on this Work Order.
                            </div>
                        </template>

                        {{-- 3. Quotation Tooling Download (Visible in Footer ONLY when Approval is Finished) --}}
                        <template x-if="detailData && detailData.wo_type === 'SPK_2_TOOLING' && (detailData.status === 'Approved' || detailData.status === 'Released' || (detailData.approvals && detailData.approvals.length > 0 && detailData.approvals.every(a => a.status === 'Approved')))">
                            <div class="flex items-center justify-between gap-3 pt-2.5 mt-2.5 border-t border-slate-200 dark:border-slate-700/80 w-full">
                                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-check text-sm"></i>
                                    <span>Approval Completed — Attachment Ready</span>
                                </span>
                                <button type="button"
                                        @click="openQuotationExportModal(WO_BASE_URL + '/work-order-tooling/' + selectedHashedId + '/quotation')"
                                        class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-sm shadow-xs hover:shadow transition-all shrink-0 cursor-pointer"
                                        title="Download Quotation Tooling Attachment">
                                    <i class="fa-solid fa-file-excel text-base"></i>
                                    <span>Download Quotation Tooling</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@include('components.sweetalert')

@push('scripts')
<script>
const WO_BASE_URL = '{{ url('management') }}';
function outlookInbox() {
    return {
        approvalRulesList: @json($approvalRules->map(fn($r) => ['id' => $r->id, 'dept_code' => $r->department->code ?? $r->department->name ?? ''])->values()),
        activeFilter: 'all',
        searchQuery: '',
        selectedHashedId: null,
        selectedWoId: @json($selectedId),
        loadingDetail: false,
        activeRightTab: 'doc',
        approvalRemarks: '',
        urgentConfirmed: false,
        urgentReason: '',
        dueDateClosed: '',
        expandedDepts: {},
        checklistSearchQuery: '',
        originalCheckedStateKey: '',
        
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
        
        isLastApprovalLevel() {
            if (!this.detailData.approvals || this.detailData.approvals.length === 0) return false;
            let approvals = this.detailData.approvals;
            let maxLevel = Math.max(...approvals.map(a => Number(a.approval_level)));
            let activeStep = approvals.find(a => a.status === 'Pending');
            if (!activeStep) return false;
            return Number(activeStep.approval_level) === maxLevel;
        },

        getFilterLabel() {
            return {
                'all': 'All WO',
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
        
        showToast(message, type = 'success') {
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                alert(message);
            }
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
                'URGENT': 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-450 border border-rose-200 dark:border-rose-800/80',
                'STANDARD': 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-450 border border-amber-200 dark:border-amber-800/80',
                'LOW': 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
            }[priority] || 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-455 border border-slate-200 dark:border-slate-700';
        },
        
        getStatusClass(status) {
            return {
                'Draft': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-450 border border-blue-200 dark:border-blue-800/80',
                'Pending Approval': 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-450 border border-amber-200 dark:border-amber-800/80',
                'Approved': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-450 border border-emerald-200 dark:border-emerald-800/80',
                'Released': 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-450 border border-sky-200 dark:border-sky-800/80',
                'Rejected': 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-450 border border-rose-200 dark:border-rose-800/80'
            }[status] || 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-455 border border-slate-200 dark:border-slate-700';
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
            const found = this.allList.find(x => x.hashed_id === hashedId)
                       || this.recentList.find(x => x.hashed_id === hashedId)
                       || this.approvedList.find(x => x.hashed_id === hashedId)
                       || this.rejectedList.find(x => x.hashed_id === hashedId)
                       || this.myTasksList.find(x => x.hashed_id === hashedId);
            if (found) {
                this.selectedWoId = found.id;
            }
            this.loadingDetail = true;
            this.approvalRemarks = '';
            this.dueDateClosed = '';
            
            fetch(`${WO_BASE_URL}/work-order/${hashedId}/ajax-details`)
                .then(res => res.json())
                .then(json => {
                    this.loadingDetail = false;
                    if (json.success) {
                        this.detailData = json.data;
                        this.urgentReason = this.detailData.urgent_reason || '';
                        this.urgentConfirmed = !!(this.detailData.urgent_reason || this.detailData.urgent_confirmed_by);
                        this.activeRightTab = 'doc'; // Default tab always display WO document
                        this.leaveRoom(); // Free up chat connection and don't load chats until user switches to chat tab
                        
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
                        
                        this.$nextTick(() => {
                            this.originalCheckedStateKey = this.getMyPicCheckedStateKey();
                        });
                    } else {
                        this.showToast('Failed to load WO details.', 'error');
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
                    if (p.parent_id || p.parentTempId) return;
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
                return 'This WO document has been fully released.';
            }
            const activeStep = this.detailData.approvals.find(a => a.status === 'Pending');
            if (activeStep) {
                return `Waiting for approval from: ${activeStep.approver_position} (${activeStep.approver_name || 'Staff'})`;
            }
            return 'The document has been fully processed.';
        },
        
        submitApproval(action) {
            if (action === 'approve' && this.detailData.is_marketing_gm_step) {
                if (!this.urgentConfirmed) {
                    this.showToast('Please check "Confirm Priority" before approving.', 'error');
                    return;
                }
                if (!this.urgentReason.trim()) {
                    this.showToast('Please enter the urgent confirmation note.', 'error');
                    return;
                }
            }
            const actionText = action === 'approve' ? 'approve' : 'reject';
            const actionCapitalized = action === 'approve' ? 'Approve' : 'Reject';
            const confirmColor = action === 'approve' ? '#10b981' : '#ef4444'; // Emerald for approve, Rose for reject

            window.confirmDialog({
                title: `${actionCapitalized} SPK?`,
                text: `Are you sure you want to ${actionText} this WO document?`,
                icon: action === 'approve' ? 'success' : 'warning',
                confirmButtonText: `Yes, ${actionCapitalized}`,
                cancelButtonText: 'Cancel',
                confirmButtonColor: confirmColor,
                onConfirm: () => {
                    const url = `${WO_BASE_URL}/work-order/${this.selectedHashedId}/${action}`;
                    const fd = new FormData();
                    fd.append('remarks', this.approvalRemarks);
                    if (action === 'approve') {
                        fd.append('due_date_closed', this.dueDateClosed);
                        if (this.urgentReason) {
                            fd.append('urgent_reason', this.urgentReason);
                        }
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
                            this.showToast('Failed to process document.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToast('Network error occurred.', 'error');
                    });
                }
            });
        },

        getDeptValidCheckedCount(dept) {
            if (!dept || !dept.checked_product_ids || !this.detailData || !this.detailData.products) return 0;
            const prodIds = new Set(this.detailData.products.map(p => Number(p.id)));
            const valid = dept.checked_product_ids.filter(id => prodIds.has(Number(id)));
            return Math.min(new Set(valid).size, this.detailData.products.length);
        },

        getDeptProgressPct(dept) {
            if (!this.detailData || !this.detailData.products || this.detailData.products.length === 0) return 0;
            const count = this.getDeptValidCheckedCount(dept);
            return Math.round((count / this.detailData.products.length) * 100);
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

        isAllProductsChecked(processId, departmentId) {
            const proc = this.detailData.processes.find(p => p.process_id === processId);
            if (!proc) return false;
            const dept = proc.assigned_departments.find(d => d.department_id === departmentId);
            if (!dept) return false;
            
            return this.detailData.products.length > 0 && 
                   this.detailData.products.every(p => dept.checked_product_ids.includes(Number(p.id)));
        },

        toggleSelectAllProducts(processId, departmentId, isChecked) {
            const proc = this.detailData.processes.find(p => p.process_id === processId);
            if (!proc) return;
            const dept = proc.assigned_departments.find(d => d.department_id === departmentId);
            if (!dept) return;

            if (isChecked) {
                dept.checked_product_ids = this.detailData.products.map(p => Number(p.id));
            } else {
                dept.checked_product_ids = [];
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
                    this.showToast('Failed to save progress.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                this.showToast('Network error while saving progress.', 'error');
            });
        },

        getMyPicTasks() {
            if (!this.detailData || !this.detailData.processes) return [];
            let tasks = [];
            this.detailData.processes.forEach(proc => {
                if (proc.assigned_departments) {
                    proc.assigned_departments.forEach(dept => {
                        if (dept.is_my_pic_task === true || dept.is_my_pic_task === 1) {
                            tasks.push({
                                process_id: proc.process_id,
                                process_name: proc.process_name,
                                department_id: dept.department_id,
                                department_code: dept.department_code,
                                checked_product_ids: dept.checked_product_ids
                            });
                        }
                    });
                }
            });
            return tasks;
        },

        getMyPicProgressSummary() {
            const tasks = this.getMyPicTasks();
            let checked = 0;
            let total = 0;
            const totalProducts = (this.detailData && this.detailData.products) ? this.detailData.products.length : 0;
            tasks.forEach(t => {
                checked += t.checked_product_ids.length;
                total += totalProducts;
            });
            return { checked, total };
        },

        getMyPicCheckedStateKey() {
            const tasks = this.getMyPicTasks();
            const state = tasks.map(t => {
                const sortedIds = [...t.checked_product_ids].map(Number).sort((a,b) => a-b);
                return `${t.process_id}-${t.department_id}:${sortedIds.join(',')}`;
            });
            state.sort();
            return state.join('|');
        },

        hasUnsavedChanges() {
            return this.originalCheckedStateKey !== this.getMyPicCheckedStateKey();
        },

        saveAllMyProgress() {
            const tasks = this.getMyPicTasks();
            if (tasks.length === 0) return;

            this.loadingDetail = true;
            const promises = tasks.map(task => {
                const fd = new FormData();
                fd.append('process_id', task.process_id);
                fd.append('department_id', task.department_id);
                task.checked_product_ids.forEach(id => {
                    fd.append('checked_product_ids[]', id);
                });

                return fetch(`${WO_BASE_URL}/work-order/${this.selectedHashedId}/progress`, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });
            });

            Promise.all(promises)
            .then(results => {
                this.loadingDetail = false;
                const okCount = results.filter(r => r.ok).length;
                if (okCount === results.length) {
                    this.showToast('All progress saved successfully!');
                    this.selectWo(this.selectedHashedId);
                } else {
                    this.showToast('Some progress failed to save.', 'error');
                }
            })
            .catch(err => {
                this.loadingDetail = false;
                console.error(err);
                this.showToast('Network error while saving progress.', 'error');
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
@include('management.chat.chat-scripts')
@endpush
@endsection
