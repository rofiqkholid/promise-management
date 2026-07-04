{{-- Modal Progress Tracking & Checklist --}}
<div id="modal-wo-progress" class="hidden fixed inset-0 z-50 overflow-hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-xs transition-opacity duration-150"
     x-data="woProgressModal()" @keydown.escape.window="closeModal()" @click.self="closeModal()">
    
    <div class="bg-white dark:bg-slate-900 w-full max-w-5xl h-[85vh] flex flex-col shadow-xl rounded-xs overflow-hidden border border-slate-300 dark:border-slate-700 transition-all duration-150 transform scale-95"
         :class="isOpen ? 'scale-100 opacity-100' : 'scale-95 opacity-0'">
        
        {{-- Modal Header --}}
        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-850 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 flex-none">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-xs bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-route text-xs"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">
                        SPK Process Tracker
                    </h3>
                </div>
            </div>
            
            <button type="button" @click="closeModal()" class="w-6 h-6 flex items-center justify-center rounded-xs bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors cursor-pointer flex-none">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        {{-- Tab Navigation --}}
        <div class="px-5 border-b border-slate-200 dark:border-slate-750 bg-white dark:bg-slate-900 flex justify-between items-center flex-none">
            <div class="flex gap-4">
                <button type="button" @click="setTab('detail')" 
                        class="py-2.5 text-xs font-semibold border-b-2 transition-all cursor-pointer"
                        :class="activeTab === 'detail' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                    Detail SPK
                </button>
                <button type="button" @click="setTab('global')" 
                        class="py-2.5 text-xs font-semibold border-b-2 transition-all cursor-pointer"
                        :class="activeTab === 'global' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-white'">
                    Progress Global
                </button>
            </div>
        </div>

        {{-- Modal Content --}}
        <div class="flex-1 overflow-y-auto p-5 space-y-5 bg-white dark:bg-slate-900">
            
            {{-- TAB 1: DETAIL VIEW --}}
            <div x-show="activeTab === 'detail'" class="space-y-5" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0">
                <template x-if="!woId">
                    <div class="py-20 text-center flex flex-col items-center justify-center text-slate-400">
                        <i class="fa-solid fa-file-invoice text-3xl mb-3 text-slate-300 dark:text-slate-700"></i>
                        <p class="text-xs font-semibold">Silakan pilih SPK dari tabel utama atau tab Progress Global.</p>
                    </div>
                </template>

                <template x-if="woId">
                    <div class="space-y-5">
                        {{-- SPK Info Header --}}
                        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xs border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row justify-between gap-4">
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-bold text-slate-850 dark:text-white tracking-wide" x-text="woNumber"></span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-xs border uppercase tracking-wider" :class="statusClass" x-text="status"></span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-xs border uppercase tracking-wider" :class="priorityClass" x-text="priority"></span>
                                </div>
                                <h4 class="text-xs font-normal text-slate-650 dark:text-slate-350" x-text="subject"></h4>
                            </div>
                            <div class="flex flex-wrap gap-4 items-center text-[10px] text-slate-500 dark:text-slate-400">
                                <div class="bg-white dark:bg-slate-950 px-3 py-1.5 rounded-xs border border-slate-200 dark:border-slate-800">
                                    <span class="text-slate-400 block text-[9px] uppercase font-bold tracking-wider mb-0.5">Total Parts</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-200" x-text="products.length + ' Item Products'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Timeline --}}
                        <div class="bg-white dark:bg-slate-900/20 border border-slate-200 dark:border-slate-800 p-4 rounded-xs space-y-4">
                            <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                                <i class="fa-solid fa-signature text-blue-500"></i> Approval Progress Steps
                            </div>
                            
                            <div class="relative flex items-center justify-between w-full pt-4 pb-2 px-4 overflow-x-auto">
                                <div class="absolute left-16 right-16 top-1/2 -translate-y-3 h-0.5 bg-slate-200 dark:bg-slate-800 z-0"></div>

                                {{-- Created State --}}
                                <div class="relative z-10 flex flex-col items-center min-w-20">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border bg-emerald-500 border-emerald-500 text-white">
                                        <i class="fa-solid fa-paper-plane text-[9px]"></i>
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 mt-2 text-center whitespace-nowrap">WO Created</span>
                                    <span class="text-[8px] text-slate-450 mt-0.5">Finished</span>
                                </div>

                                {{-- Approvals Loop --}}
                                <template x-for="(step, idx) in approvals" :key="idx">
                                    <div class="relative z-10 flex flex-col items-center min-w-28">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-150 border"
                                             :class="{
                                                 'bg-emerald-500 border-emerald-500 text-white': step.status === 'Approved',
                                                 'bg-amber-500 border-amber-500 text-white animate-pulse': step.status === 'Pending',
                                                 'bg-rose-500 border-rose-500 text-white': step.status === 'Rejected',
                                                 'bg-slate-500 border-slate-500 text-white': step.status === 'Revised',
                                                 'bg-slate-50 dark:bg-slate-800 text-slate-400 border-slate-250 dark:border-slate-750': step.status === 'Waiting'
                                             }">
                                            <i class="fa-solid" :class="{
                                                'fa-check text-[10px]': step.status === 'Approved',
                                                'fa-rotate text-[10px]': step.status === 'Pending',
                                                'fa-xmark text-[10px]': step.status === 'Rejected',
                                                'fa-arrows-rotate text-[10px]': step.status === 'Revised',
                                                'fa-clock text-[10px]': step.status === 'Waiting'
                                            }"></i>
                                        </div>
                                        <span class="text-[10px] font-bold mt-2 text-center whitespace-nowrap text-slate-700 dark:text-slate-355"
                                              x-text="step.approver_position"></span>
                                        <span class="text-[8px] text-slate-450 mt-0.5" x-text="step.status === 'Pending' ? 'Waiting Action' : step.status"></span>
                                        <span class="text-[8px] text-slate-450 dark:text-slate-500 font-mono mt-0.5" x-show="step.approved_at" x-text="step.approved_at"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Department Progress & PIC Checklist --}}
                        <div class="space-y-4">
                            <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                                <i class="fa-solid fa-list-check text-blue-500"></i> Department Progress &amp; PIC Tasks
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <template x-for="(proc, pIdx) in processes" :key="pIdx">
                                    <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xs overflow-hidden">
                                        {{-- Process Group Header --}}
                                        <div class="bg-slate-50 dark:bg-slate-900/80 px-4 py-2 border-b border-slate-300 dark:border-slate-700 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-none bg-blue-600"></div>
                                                <span class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="proc.process_name"></span>
                                            </div>
                                        </div>

                                        {{-- Departments in Process --}}
                                        <div class="p-4 space-y-4 divide-y divide-slate-200 dark:divide-slate-700">
                                            <template x-for="(dept, dIdx) in proc.assigned_departments" :key="dIdx">
                                                <div class="pt-4 first:pt-0 space-y-3">
                                                    {{-- Dept Header & Info --}}
                                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                                        <div class="flex items-center gap-2.5">
                                                            <div class="px-2 py-0.5 rounded-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10px]" x-text="dept.department_code"></div>
                                                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                                                PIC: <span class="font-bold text-slate-700 dark:text-slate-200" x-text="dept.pic_name"></span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-3 w-full sm:w-auto">
                                                            <div class="flex-1 sm:flex-none w-24 bg-slate-100 dark:bg-slate-800 h-1.5 rounded-none overflow-hidden">
                                                                <div class="bg-blue-600 h-full rounded-none transition-all duration-150" :style="'width: ' + (products.length > 0 ? (dept.checked_product_ids.length / products.length * 100) : 0) + '%'"></div>
                                                            </div>
                                                            <span class="font-mono text-[10px] font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap" x-text="dept.checked_product_ids.length + ' / ' + products.length + ' Done'"></span>
                                                        </div>
                                                    </div>

                                                    {{-- Accordion Layout for Parts Checklist --}}
                                                    <div x-data="{ collapsed: true }" class="bg-slate-50/50 dark:bg-slate-900/30 p-2 border border-slate-200 dark:border-slate-800 rounded-xs">
                                                        <button type="button" @click="collapsed = !collapsed" class="flex items-center gap-2 text-[10px] font-bold text-slate-500 hover:text-blue-650 transition-colors uppercase cursor-pointer select-none">
                                                            <i class="fa-solid text-[9px]" :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-down'"></i>
                                                            <span x-text="collapsed ? 'Tampilkan Checklist Parts' : 'Sembunyikan Checklist Parts'"></span>
                                                        </button>

                                                        <div x-show="!collapsed" class="mt-3">
                                                            <form @submit.prevent="submitProgressUpdate(proc.process_id, dept.department_id, $el)" class="space-y-4">
                                                                @csrf
                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                                    <template x-for="p in products" :key="p.id">
                                                                        <div class="flex items-center justify-between p-2 border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 rounded-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all text-xs">
                                                                            
                                                                            {{-- Checklist enabled if user is PIC --}}
                                                                            <template x-if="dept.is_my_pic_task === true || dept.is_my_pic_task === 1">
                                                                                <label class="flex items-center gap-2.5 cursor-pointer w-full select-none text-slate-700 dark:text-slate-300">
                                                                                    <input type="checkbox" name="checked_product_ids[]" :value="p.id"
                                                                                           :checked="dept.checked_product_ids.includes(Number(p.id))"
                                                                                           class="h-4 w-4 rounded-xs border-slate-350 dark:border-slate-700 text-blue-600 focus:ring-0 cursor-pointer">
                                                                                    <div class="min-w-0 flex-1">
                                                                                        <span class="font-bold text-slate-800 dark:text-slate-100 block" x-text="p.customer_part_no"></span>
                                                                                        <span class="text-[10px] text-slate-400 truncate block" x-text="p.customer_part_name"></span>
                                                                                    </div>
                                                                                </label>
                                                                            </template>

                                                                            {{-- Read-only checkbox state if not PIC --}}
                                                                            <template x-if="!(dept.is_my_pic_task === true || dept.is_my_pic_task === 1)">
                                                                                <div class="flex items-center gap-2.5 w-full min-w-0">
                                                                                    <div class="w-4 h-4 rounded-none flex items-center justify-center flex-none"
                                                                                         :class="dept.checked_product_ids.includes(Number(p.id)) ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600' : 'bg-slate-100 dark:bg-slate-850 text-slate-400'">
                                                                                        <i class="fa-solid text-[9px]" :class="dept.checked_product_ids.includes(Number(p.id)) ? 'fa-check' : 'fa-xmark'"></i>
                                                                                    </div>
                                                                                    <div class="min-w-0 flex-1">
                                                                                        <span class="font-semibold text-slate-700 dark:text-slate-300 block" x-text="p.customer_part_no"></span>
                                                                                        <span class="text-[10px] text-slate-450 truncate block" x-text="p.customer_part_name"></span>
                                                                                    </div>
                                                                                </div>
                                                                            </template>
                                                                        </div>
                                                                    </template>
                                                                </div>

                                                                {{-- Actions button --}}
                                                                <template x-if="dept.is_my_pic_task === true || dept.is_my_pic_task === 1">
                                                                    <div class="flex justify-end pt-1">
                                                                        <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] uppercase tracking-wider rounded-xs shadow-sm hover:shadow transition-all cursor-pointer flex items-center gap-1.5">
                                                                            <i class="fa-solid fa-floppy-disk text-[9px]"></i> Simpan Progress
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

            {{-- TAB 2: GLOBAL ACTIVE SPK PROGRESS --}}
            <div x-show="activeTab === 'global'" class="space-y-4" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0">
                {{-- Global Filters --}}
                <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 bg-slate-50 dark:bg-slate-900/50 p-3 rounded-xs border border-slate-200 dark:border-slate-800">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="text" x-model="globalSearch" placeholder="Cari No SPK, Subject, atau Departemen..." 
                               class="w-full pl-8 pr-3 py-1.5 bg-white dark:bg-slate-950 border border-slate-250 dark:border-slate-800 rounded-xs text-xs focus:outline-none focus:border-blue-500 text-slate-700 dark:text-slate-350">
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-650 dark:text-slate-400 cursor-pointer select-none">
                            <input type="checkbox" x-model="globalFilterRunning" class="h-3.5 w-3.5 rounded-xs border-slate-300 dark:border-slate-850 text-blue-600 focus:ring-0">
                            Hanya Tampilkan Yang Berjalan
                        </label>
                        <button type="button" @click="fetchGlobalProgress()" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-xs text-slate-600 dark:text-slate-300 text-xs font-bold transition-all cursor-pointer">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </button>
                    </div>
                </div>

                {{-- Global Table / Cards --}}
                <div class="space-y-2">
                    <template x-for="item in filteredGlobalProgress" :key="item.id">
                        <div class="p-3 bg-white dark:bg-slate-900/30 border border-slate-200 dark:border-slate-850 rounded-xs hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col md:flex-row justify-between gap-4">
                            <div class="space-y-1 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-bold text-slate-800 dark:text-white" x-text="item.wo_number"></span>
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-xs border uppercase" :class="getGlobalStatusClass(item.status)" x-text="item.status"></span>
                                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-xs border uppercase" :class="getGlobalPriorityClass(item.priority)" x-text="item.priority"></span>
                                </div>
                                <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-350" x-text="item.subject"></h4>
                                <div class="text-[9px] text-slate-400 flex items-center gap-2.5">
                                    <span>Dept Owner: <span class="font-bold text-slate-600 dark:text-slate-300" x-text="item.owner_dept"></span></span>
                                    <span>•</span>
                                    <span x-text="'Dibuat: ' + item.created_at"></span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 md:w-3/5">
                                {{-- Approval Progress Indicator --}}
                                <div class="flex-1 min-w-28">
                                    <div class="flex justify-between items-center text-[9px] font-bold text-slate-500 mb-1">
                                        <span>Persetujuan</span>
                                        <span class="font-mono text-slate-700 dark:text-slate-350" x-text="item.approved_approvals + '/' + item.total_approvals"></span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-none overflow-hidden">
                                        <div class="bg-indigo-500 h-full rounded-none transition-all" :style="'width: ' + (item.total_approvals > 0 ? (item.approved_approvals / item.total_approvals * 100) : 0) + '%'"></div>
                                    </div>
                                </div>

                                {{-- Checklist Progress Indicator --}}
                                <div class="flex-1 min-w-28">
                                    <div class="flex justify-between items-center text-[9px] font-bold text-slate-500 mb-1">
                                        <span>Proses Checklist</span>
                                        <span class="font-mono text-slate-700 dark:text-slate-350" x-text="item.completed_tasks + '/' + item.total_tasks"></span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-none overflow-hidden">
                                        <div class="bg-emerald-500 h-full rounded-none transition-all" :style="'width: ' + item.process_percent + '%'"></div>
                                    </div>
                                </div>

                                {{-- Action Button --}}
                                <button type="button" @click="inspectWo(item.hashed_id)" class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-150 dark:bg-blue-950/30 dark:hover:bg-blue-900/40 text-blue-600 dark:text-blue-400 font-bold text-[9px] uppercase rounded-xs border border-blue-200 dark:border-blue-800 transition-colors cursor-pointer whitespace-nowrap">
                                    <i class="fa-solid fa-magnifying-glass-chart"></i> Detail
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="filteredGlobalProgress.length === 0">
                        <div class="py-12 text-center flex flex-col items-center justify-center text-slate-400 bg-slate-50/50 dark:bg-slate-900/10 rounded-xs border border-dashed border-slate-200 dark:border-slate-850">
                            <i class="fa-solid fa-box-archive text-2xl mb-2 text-slate-300 dark:text-slate-750"></i>
                            <p class="text-xs font-semibold">Tidak ada SPK aktif yang cocok dengan kriteria filter.</p>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const WO_BASE_URL = window.WO_BASE_URL || '{{ url('management') }}';
function woProgressModal() {
    return {
        isOpen: false,
        activeTab: 'detail',
        globalData: [],
        globalSearch: '',
        globalFilterRunning: true,

        woId: null,
        hashedId: '',
        woNumber: '',
        subject: '',
        status: '',
        priority: '',
        approvals: [],
        processes: [],
        products: [],
        
        get statusClass() {
            return {
                'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900': this.status === 'Draft',
                'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': this.status === 'Pending Approval',
                'bg-emerald-50 text-emerald-750 border-emerald-300 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900': this.status === 'Approved',
                'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/20 dark:text-sky-400 dark:border-sky-900': this.status === 'Released',
            };
        },

        get priorityClass() {
            return {
                'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900': this.priority === 'URGENT',
                'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': this.priority === 'STANDARD',
            };
        },

        getGlobalStatusClass(status) {
            return {
                'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/20 dark:text-blue-400 dark:border-blue-900': status === 'Draft',
                'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': status === 'Pending Approval',
                'bg-emerald-50 text-emerald-750 border-emerald-300 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900': status === 'Approved',
                'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/20 dark:text-sky-400 dark:border-sky-900': status === 'Released',
            }[status] || 'bg-slate-50 text-slate-700 border-slate-200';
        },

        getGlobalPriorityClass(priority) {
            return {
                'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900': priority === 'URGENT',
                'bg-amber-50 text-amber-700 border-amber-350 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900': priority === 'STANDARD',
            }[priority] || 'bg-slate-50 text-slate-700 border-slate-200';
        },

        get filteredGlobalProgress() {
            return this.globalData.filter(item => {
                const searchLower = this.globalSearch.toLowerCase();
                const matchesSearch = !searchLower || 
                                     (item.wo_number && item.wo_number.toLowerCase().includes(searchLower)) ||
                                     (item.subject && item.subject.toLowerCase().includes(searchLower)) ||
                                     (item.owner_dept && item.owner_dept.toLowerCase().includes(searchLower));
                const matchesRunning = !this.globalFilterRunning || !item.is_completed;
                return matchesSearch && matchesRunning;
            });
        },
        
        setTab(tab) {
            this.activeTab = tab;
            if (tab === 'global') {
                this.fetchGlobalProgress();
            }
        },

        fetchGlobalProgress() {
            fetch('/management/work-order-global-progress')
                .then(res => res.json())
                .then(json => {
                    if (json.success) {
                        this.globalData = json.data;
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        },

        inspectWo(hashedId) {
            this.activeTab = 'detail';
            this.openModal(hashedId);
        },
        
        openModal(hashedId) {
            this.hashedId = hashedId;
            const modal = document.getElementById('modal-wo-progress');
            modal.classList.remove('hidden');
            
            // Fetch Details via AJAX
            fetch(`${WO_BASE_URL}/work-order/${hashedId}/api-details`)
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
            }, 150);
        },
        
        init() {
            window.addEventListener('open-wo-progress', (e) => {
                if (e.detail && e.detail.hashedId) {
                    this.activeTab = 'detail';
                    this.openModal(e.detail.hashedId);
                } else {
                    this.activeTab = 'global';
                    this.hashedId = '';
                    this.woId = null;
                    this.openModalWithoutId();
                }
            });
        },

        openModalWithoutId() {
            const modal = document.getElementById('modal-wo-progress');
            modal.classList.remove('hidden');
            this.fetchGlobalProgress();
            setTimeout(() => {
                this.isOpen = true;
            }, 50);
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
