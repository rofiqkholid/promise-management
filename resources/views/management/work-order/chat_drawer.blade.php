<!-- Slide-over Right Drawer for Work Order Details and Chat -->
<div x-data="Object.assign(chatRoomEngine('work_order', null), {
        showDrawer: false,
        woDetail: null,
        loadingWo: false,

        openWoChat(detail) {
            this.showDrawer = true;
            this.woDetail = null;
            this.loadingWo = true;

            const hashedId = detail.hashedId;
            const woId = detail.woId;

            // Fetch WO details for the left panel
            fetch(`{{ url('management/work-order') }}/${hashedId}/ajax-details`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    this.woDetail = data.data;
                    const title = this.woDetail.wo_number || 'Work Order Discussion';
                    const subtitle = `${this.woDetail.inquiry?.customer?.code || ''} • ${this.woDetail.inquiry?.project_model?.name || ''}`;
                    this.initRoom('work_order', this.woDetail.id || woId, title, subtitle);
                } else {
                    this.initRoom('work_order', woId, 'Work Order Chat', '');
                }
            })
            .catch(err => {
                console.error(err);
                this.initRoom('work_order', woId, 'Work Order Chat', '');
            })
            .finally(() => {
                this.loadingWo = false;
            });
        },

        closeDrawer() {
            this.showDrawer = false;
            this.leaveRoom();
            this.woDetail = null;
        }
     })"
     x-show="showDrawer"
     @open-wo-chat.window="openWoChat($event.detail)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     class="fixed inset-y-0 right-0 z-[9000] w-full max-w-5xl bg-white dark:bg-slate-800 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-700 h-full overflow-hidden"
     style="display: none;"
     x-cloak>

    <!-- Drawer Header -->
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/40 flex-shrink-0">
        <div class="flex items-center gap-3">
            <span class="flex h-8 w-8 items-center justify-center bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-full">
                <i class="fa-solid fa-file-signature"></i>
            </span>
            <div>
                <h3 class="text-sm font-extrabold text-slate-800 dark:text-white" x-text="woDetail?.wo_number || 'SPK Discussion Room'"></h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mt-0.5" 
                   x-text="woDetail ? (woDetail.inquiry?.customer?.code + ' • ' + (woDetail.inquiry?.project_model?.name || '')) : 'Work Order &amp; Discussion'"></p>
            </div>
        </div>
        <button @click="closeDrawer()" 
                class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors text-xl leading-none cursor-pointer">
            &times;
        </button>
    </div>

    <!-- Drawer Body (Split into Left & Right Panels) -->
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden min-h-0">
        
        <!-- Left Panel: WO Specifications & Products -->
        <div class="w-full md:w-[32%] p-4 bg-slate-50/50 dark:bg-slate-900/30 border-r border-slate-200 dark:border-slate-700/80 overflow-y-auto space-y-4 h-full">
            <div class="border-b border-slate-200 dark:border-slate-700 pb-2 flex justify-between items-center">
                <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">SPK Information</h4>
                <span class="px-2 py-0.5 text-[9px] font-extrabold rounded-sm uppercase tracking-wider"
                      :class="woDetail?.priority === 'URGENT' ? 'bg-rose-100 text-rose-700 border border-rose-200' : 'bg-amber-100 text-amber-700 border border-amber-200'"
                      x-text="woDetail?.priority || '—'"></span>
            </div>

            <!-- Loading Skeleton -->
            <template x-if="loadingWo">
                <div class="space-y-4 animate-pulse p-1">
                    <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded-sm w-3/4"></div>
                    <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded-sm w-1/2"></div>
                    <div class="h-16 bg-slate-200 dark:bg-slate-700 rounded-sm w-full"></div>
                </div>
            </template>

            <!-- Loaded SPK Detail -->
            <template x-if="!loadingWo && woDetail">
                <div class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="block text-[10px] font-medium text-slate-500 uppercase">WO Type</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-100" x-text="woDetail.wo_type || 'SPK 1'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-500 uppercase">Revision</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-100" x-text="'Rev ' + (woDetail.doc_revision_no || woDetail.revision_no || '00')"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-500 uppercase">Inquiry No</span>
                            <span class="font-semibold text-blue-600 dark:text-blue-400" x-text="woDetail.inquiry?.inquiry_number || '—'"></span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-medium text-slate-500 uppercase">Status</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100" x-text="woDetail.status || '—'"></span>
                        </div>
                    </div>

                    <!-- Products in this WO -->
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                        <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2" x-text="'Part Items (' + (woDetail.products?.length || 0) + ')'"></span>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <template x-for="(prod, pIdx) in woDetail.products" :key="pIdx">
                                <div class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm">
                                    <div class="flex justify-between items-start">
                                        <span class="font-bold text-slate-800 dark:text-slate-100 text-[11px]" x-text="prod.customer_part_no"></span>
                                        <button type="button" 
                                                @click="chatInputMessage = (chatInputMessage ? chatInputMessage + ' ' : '') + '#' + prod.customer_part_no + ' '"
                                                class="text-[9.5px] text-indigo-600 dark:text-indigo-400 hover:underline font-semibold"
                                                title="Tag this part in chat">
                                            #Tag
                                        </button>
                                    </div>
                                    <span class="block text-[10.5px] text-slate-500 truncate" x-text="prod.customer_part_name"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Remarks if available -->
                    <div x-show="woDetail.remarks" class="p-2.5 bg-slate-100/70 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 text-xs">
                        <span class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-0.5">SPK Remarks</span>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed font-medium" x-text="woDetail.remarks"></p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Right Panel: Universal Chat Room -->
        <div class="w-full md:w-[68%] flex flex-col h-full overflow-hidden min-h-0">
            @include('management.chat.chat-room')
        </div>
    </div>

</div>

@include('management.chat.chat-scripts')
