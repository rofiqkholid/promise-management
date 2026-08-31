<!-- Slide-over Right Drawer for Work Order Discussion with Dynamic Tabbed Detail Panel -->
<div x-data="Object.assign(chatRoomEngine('work_order', null), {
        showDrawer: false,
        woDetail: null,
        loadingWo: false,

        openWoChat(detail) {
            this.showDrawer = true;
            this.woDetail = null;
            this.loadingWo = true;
            this.hasDetailPanel = true;
            this.showDetailPanel = false;

            const hashedId = detail.hashedId;
            const woId = detail.woId;

            // Fetch WO details for the integrated tabbed detail panel
            fetch(`{{ url('management/work-order') }}/${hashedId}/ajax-details`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    this.woDetail = data.data;
                    this.detailPanelData = data.data;
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
            this.detailPanelData = null;
        }
     })"
     @open-wo-chat.window="openWoChat($event.detail)"
     @keydown.escape.window="if (showDrawer) closeDrawer()"
     class="relative">

    <!-- Dim Backdrop (Click to close) -->
    <div x-show="showDrawer" 
         @click="closeDrawer()"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 z-[8999]"
         style="display: none;"
         x-cloak></div>

    <!-- Slide-over Right Drawer Container -->
    <div x-show="showDrawer"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed inset-y-0 right-0 z-[9000] w-full max-w-2xl bg-white dark:bg-slate-900 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800 h-full overflow-hidden"
         style="display: none;"
         x-cloak>

        <!-- Universal Chat Room with Integrated Tabbed Detail Panel -->
        <div class="flex-1 flex flex-col h-full overflow-hidden min-h-0 relative">
            @include('management.chat.chat-room')
        </div>

    </div>

</div>

@include('management.chat.chat-scripts')
