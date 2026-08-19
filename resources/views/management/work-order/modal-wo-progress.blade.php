{{-- Modal Progress Tracking & Checklist --}}
<div id="modal-wo-progress" class="hidden fixed inset-0 z-50 overflow-hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-xs transition-opacity duration-150"
     x-data="woProgressModal()" @keydown.escape.window="closeModal()" @click.self="closeModal()">
    
    <div class="bg-white dark:bg-slate-900 w-full max-w-5xl h-[85vh] flex flex-col shadow-2xl rounded-sm overflow-hidden border border-slate-200 dark:border-slate-800 transition-all duration-150 transform scale-95"
         :class="isOpen ? 'scale-100 opacity-100' : 'scale-95 opacity-0'">
        
        {{-- Modal Header --}}
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50 flex-none">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-sm bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <i class="fa-solid fa-list-check text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                        Work Order Progress Details
                    </h3>
                </div>
            </div>
            
            <button type="button" @click="closeModal()" class="w-7 h-7 flex items-center justify-center rounded-sm bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors cursor-pointer flex-none">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Modal Content --}}
        <div class="flex-1 overflow-y-auto p-6 bg-slate-50/40 dark:bg-slate-950/20 space-y-6">
            <template x-if="!woId">
                <div class="py-24 text-center flex flex-col items-center justify-center text-slate-400">
                    <i class="fa-solid fa-spinner fa-spin text-3xl mb-3 text-indigo-500"></i>
                    <p class="text-xs font-semibold">Loading progress details...</p>
                </div>
            </template>

            <template x-if="woId">
                <div class="space-y-6">
                    
                    {{-- ROW 1: WO Information --}}
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-sm border border-slate-300 dark:border-slate-800 shadow-xs">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Work Order No</span>
                                <h4 class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight" x-text="woNumber"></h4>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Revision</span>
                                <div class="text-xs font-semibold text-slate-800 dark:text-slate-250">
                                    Rev. <span x-text="revisionNo"></span>
                                </div>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Customer &amp; Model</span>
                                <div class="text-xs text-slate-800 dark:text-slate-205 flex items-center gap-1.5">
                                    <span class="font-bold text-slate-900 dark:text-white" x-text="customerCode"></span>
                                    <span class="text-slate-400">•</span>
                                    <span x-text="modelName"></span>
                                </div>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Priority</span>
                                <span class="inline-block px-2.5 py-0.5 text-[10px] font-extrabold border rounded-sm uppercase tracking-wide"
                                      :class="priorityClass" x-text="priority"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ROW 2: Approval Status Stepper --}}
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-sm border border-slate-300 dark:border-slate-800 shadow-xs space-y-4">
                        <h5 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <i class="fa-solid fa-file-shield text-indigo-500"></i> Approval Progress Status
                        </h5>

                        <div class="flex items-start justify-between w-full py-4 overflow-x-auto relative">
                            {{-- Step 0 (Created) --}}
                            <div class="flex flex-col items-center flex-1 min-w-[140px] text-center relative">
                                <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold shadow-xs relative z-10">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </div>
                                <template x-if="approvals.length > 0">
                                    <div class="absolute left-[calc(50%+16px)] w-[calc(100%-32px)] top-4 h-0.5 bg-slate-200 dark:bg-slate-800 z-0"
                                         :class="approvals[0] && approvals[0].status === 'Approved' ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'"></div>
                                </template>
                                <span class="text-xs font-bold mt-2 text-slate-850 dark:text-white">WO Created</span>
                                <span class="text-[10px] text-slate-500 font-semibold mt-0.5" x-text="'By: ' + (createdBy || '—')"></span>
                                <span class="text-[10px] text-slate-400 mt-0.5" x-text="createdAt"></span>
                            </div>

                            {{-- Loop Approvals --}}
                            <template x-for="(step, idx) in approvals" :key="idx">
                                <div class="flex flex-col items-center flex-1 min-w-[140px] text-center relative">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border shadow-xs transition-colors relative z-10"
                                         :class="{
                                             'bg-emerald-500 border-emerald-505 text-white': step.status === 'Approved',
                                             'bg-amber-500 border-amber-505 text-white animate-pulse': step.status === 'Pending',
                                             'bg-rose-500 border-rose-505 text-white': step.status === 'Rejected',
                                             'bg-slate-500 border-slate-505 text-white': step.status === 'Revised',
                                             'bg-slate-50 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700': step.status === 'Waiting'
                                         }">
                                        <i class="fa-solid text-[9px]" :class="{
                                            'fa-check': step.status === 'Approved',
                                            'fa-rotate': step.status === 'Pending',
                                            'fa-xmark': step.status === 'Rejected',
                                            'fa-arrows-rotate': step.status === 'Revised',
                                            'fa-clock': step.status === 'Waiting'
                                        }"></i>
                                    </div>
                                    <template x-if="idx < approvals.length - 1">
                                        <div class="absolute left-[calc(50%+16px)] w-[calc(100%-32px)] top-4 h-0.5 z-0" 
                                             :class="approvals[idx+1].status === 'Approved' ? 'bg-emerald-500' : 'bg-slate-100 dark:bg-slate-800'"></div>
                                    </template>
                                    <span class="text-xs font-bold mt-2 text-slate-850 dark:text-white" x-text="step.approver_position"></span>
                                    <span class="text-[10px] text-slate-400 mt-0.5" x-text="step.status"></span>
                                    <template x-if="step.status === 'Approved' && step.approver_name">
                                        <span class="text-[10px] text-slate-550 font-semibold mt-0.5" x-text="'By: ' + step.approver_name"></span>
                                    </template>
                                    <span class="text-[9px] text-slate-400 font-mono mt-0.5" x-show="step.approved_at" x-text="step.approved_at"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- ROW 3: Progress Task --}}
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-sm border border-slate-300 dark:border-slate-800 shadow-xs space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h5 class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-route text-indigo-500"></i> Department Task Checklist &amp; Progress
                            </h5>
                            <template x-if="status !== 'Approved' && status !== 'Released'">
                                <div class="px-2 py-0.5 text-[9px] bg-amber-50 dark:bg-amber-955/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/30 rounded-sm flex items-center gap-1 font-bold">
                                    <i class="fa-solid fa-lock text-[8px]"></i> Locked (Awaiting Approval)
                                </div>
                            </template>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(proc, pIdx) in processes" :key="pIdx">
                                <div class="bg-slate-50/50 dark:bg-slate-900/20 border border-slate-200 dark:border-slate-800 rounded-sm overflow-hidden">
                                    {{-- Process Title --}}
                                    <div class="bg-slate-100/50 dark:bg-slate-800/40 px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-3 bg-indigo-600 rounded-sm"></div>
                                            <span class="text-xs font-extrabold text-slate-800 dark:text-slate-205 uppercase tracking-wider" x-text="proc.process_name"></span>
                                        </div>
                                    </div>

                                    {{-- Departments list inside Process --}}
                                    <div class="p-4 divide-y divide-slate-100 dark:divide-slate-800/60">
                                        <template x-for="(dept, dIdx) in proc.assigned_departments" :key="dIdx">
                                            <div class="py-4 first:pt-0 last:pb-0 space-y-3">
                                                
                                                {{-- Dept Header info --}}
                                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                                    <div class="flex items-center gap-2.5">
                                                        <div class="px-2 py-0.5 rounded-sm bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 font-extrabold text-[10px] tracking-wider border border-indigo-100/30" x-text="dept.department_code"></div>
                                                        <div class="text-[11px] text-slate-550 dark:text-slate-400 font-medium">
                                                            PIC: <span class="font-bold text-slate-700 dark:text-slate-200" x-text="dept.pic_name"></span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="flex items-center gap-3 w-full sm:w-auto">
                                                        <div class="flex-1 sm:flex-none w-48 bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden border border-slate-200 dark:border-slate-700/50">
                                                            <div class="bg-indigo-600 h-full rounded-full transition-all duration-150" :style="'width: ' + (products.length > 0 ? (dept.checked_product_ids.length / products.length * 100) : 0) + '%'"></div>
                                                        </div>
                                                        <span class="font-mono text-[10px] font-extrabold text-slate-650 dark:text-slate-400 whitespace-nowrap bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-2 py-0.5 rounded-sm" x-text="dept.checked_product_ids.length + ' / ' + products.length + ' Done'"></span>
                                                    </div>
                                                </div>

                                                {{-- Accordion Parts checklist --}}
                                                <div x-data="{ collapsed: true }" class="bg-white dark:bg-slate-900 p-2.5 border border-slate-200 dark:border-slate-800 rounded-sm">
                                                    <button type="button" @click="collapsed = !collapsed" class="flex items-center gap-2 text-[10px] font-extrabold text-slate-400 hover:text-indigo-650 transition-colors uppercase cursor-pointer select-none">
                                                        <i class="fa-solid text-[9px]" :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-down'"></i>
                                                        <span x-text="collapsed ? 'Show Checklist Parts' : 'Hide Checklist Parts'"></span>
                                                    </button>

                                                    <div x-show="!collapsed" class="mt-3.5" x-transition>
                                                        <form @submit.prevent="submitProgressUpdate(proc.process_id, dept.department_id, $el)" class="space-y-4">
                                                            @csrf
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                <template x-for="p in products" :key="p.id">
                                                                    <div class="flex items-center justify-between p-2.5 border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-955/20 rounded-sm hover:border-indigo-300 dark:hover:border-indigo-900/40 transition-all text-xs">
                                                                        
                                                                        {{-- Checklist enabled if user is PIC AND WO is approved --}}
                                                                        <template x-if="(dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (status === 'Approved' || status === 'Released')">
                                                                            <label class="flex items-center gap-2.5 cursor-pointer w-full select-none text-slate-700 dark:text-slate-300">
                                                                                <input type="checkbox" name="checked_product_ids[]" :value="p.id"
                                                                                       :checked="dept.checked_product_ids.includes(Number(p.id))"
                                                                                       class="h-4 w-4 rounded-sm border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-0 cursor-pointer">
                                                                                <div class="min-w-0 flex-1">
                                                                                    <span class="font-bold text-slate-800 dark:text-slate-100 block" x-text="p.customer_part_no"></span>
                                                                                    <span class="text-[10px] text-slate-400 truncate block mt-0.5" x-text="p.customer_part_name"></span>
                                                                                </div>
                                                                            </label>
                                                                        </template>

                                                                        {{-- Read-only checklist --}}
                                                                        <template x-if="!((dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (status === 'Approved' || status === 'Released'))">
                                                                            <div class="flex items-center gap-2.5 w-full min-w-0">
                                                                                <div class="w-4 h-4 rounded-sm flex items-center justify-center flex-none border"
                                                                                     :class="dept.checked_product_ids.includes(Number(p.id)) ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-405 border-slate-200 dark:border-slate-700'">
                                                                                    <i class="fa-solid text-[8px]" :class="dept.checked_product_ids.includes(Number(p.id)) ? 'fa-check' : 'fa-xmark'"></i>
                                                                                </div>
                                                                                <div class="min-w-0 flex-1">
                                                                                    <span class="font-bold text-slate-700 dark:text-slate-300 block" x-text="p.customer_part_no"></span>
                                                                                    <span class="text-[10px] text-slate-450 truncate block mt-0.5" x-text="p.customer_part_name"></span>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            {{-- Actions button --}}
                                                            <template x-if="(dept.is_my_pic_task === true || dept.is_my_pic_task === 1) && (status === 'Approved' || status === 'Released')">
                                                                <div class="flex justify-end pt-1">
                                                                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] uppercase tracking-wider rounded-sm shadow-sm hover:shadow transition-all cursor-pointer flex items-center gap-1.5">
                                                                        <i class="fa-solid fa-floppy-disk text-[9px]"></i> Save Progress
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
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

<script>
const WO_BASE_URL = window.WO_BASE_URL || '{{ url('management') }}';
function woProgressModal() {
    return {
        isOpen: false,
        woId: null,
        hashedId: '',
        woNumber: '',
        subject: '',
        status: '',
        priority: '',
        approvals: [],
        processes: [],
        products: [],
        createdAt: '',
        revisionNo: '',
        customerCode: '',
        modelName: '',
        createdBy: '',
        
        get simplifiedStatus() {
            if (this.status === 'Draft') return 'Draft';
            if (this.status === 'Pending Approval') return 'In Progress';
            if (this.status === 'Approved' || this.status === 'Released') return 'Finish';
            return this.status;
        },

        get statusClass() {
            return {
                'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900': this.simplifiedStatus === 'Draft',
                'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': this.simplifiedStatus === 'In Progress',
                'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900': this.simplifiedStatus === 'Finish',
            }[this.simplifiedStatus] || 'bg-slate-50 text-slate-700 border-slate-200';
        },

        get priorityClass() {
            return {
                'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900': this.priority === 'URGENT',
                'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': this.priority === 'STANDARD',
            }[this.priority] || 'bg-slate-50 text-slate-700 border-slate-200';
        },
        
        openModal(hashedId) {
            this.hashedId = hashedId;
            const modal = document.getElementById('modal-wo-progress');
            modal.classList.remove('hidden');
            
            // Fetch Details via AJAX
            fetch(`${WO_BASE_URL}/work-order/${hashedId}/ajax-details`)
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        const d = json.data;
                        this.woId = d.id;
                        this.woNumber = d.wo_number;
                        this.subject = d.subject;
                        this.status = d.status;
                        this.priority = d.priority;
                        this.approvals = d.approvals;
                        this.processes = d.processes;
                        this.products = d.products;
                        this.createdAt = d.created_at;
                        this.revisionNo = d.revision_no;
                        this.customerCode = d.products[0] ? d.products[0].customer_code : '—';
                        this.modelName = d.products[0] ? d.products[0].model_name : '—';
                        this.createdBy = d.created_by;
                        
                        setTimeout(() => {
                            this.isOpen = true;
                        }, 50);
                    } else {
                        alert(json.message || 'Failed to fetch details');
                        this.closeModal();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Network error while loading data.');
                    this.closeModal();
                });
        },
        
        closeModal() {
            this.isOpen = false;
            setTimeout(() => {
                const modal = document.getElementById('modal-wo-progress');
                modal.classList.add('hidden');
                this.woId = null;
            }, 150);
        },
        
        init() {
            window.addEventListener('open-wo-progress', (e) => {
                if (e.detail && e.detail.hashedId) {
                    this.openModal(e.detail.hashedId);
                }
            });
        },
        
        submitProgressUpdate(processId, deptId, formEl) {
            const fd = new FormData(formEl);
            fd.append('process_id', processId);
            fd.append('department_id', deptId);
            
            fetch(`${WO_BASE_URL}/work-order/${this.hashedId}/progress`, {
                method: 'POST',
                body: fd,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(res => {
                if (res.ok) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                           icon: 'success',
                           title: 'Success',
                           text: 'Task progress updated successfully!',
                           timer: 1500,
                           showConfirmButton: false
                        });
                    } else {
                        alert('Task progress updated successfully!');
                    }
                    this.openModal(this.hashedId);
                } else {
                    alert('Failed to update progress.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error while saving progress.');
            });
        }
    };
}
</script>
