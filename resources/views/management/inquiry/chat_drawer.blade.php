<!-- Slide-over Right Drawer for Inquiry Product Discussion with Dynamic Tabbed Detail Panel -->
<div x-data="Object.assign(chatRoomEngine('inquiry_product', null), {
        showChatDrawer: false,
        productDetail: null,
        loadingProduct: false,

        openChat(prodId) {
            this.showChatDrawer = true;
            this.productDetail = null;
            this.loadingProduct = true;
            this.hasDetailPanel = true;
            this.showDetailPanel = false;

            // Fetch Inquiry Product details for the integrated tabbed detail panel
            fetch(`{{ url('management/inquiry-product') }}/${prodId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(data => {
                const prod = data.product || data;
                this.productDetail = prod;
                this.detailPanelData = prod;
                const title = prod.customer_part_no || 'Inquiry Product Chat';
                const subtitle = `${prod.customer_part_name || ''} • ${prod.model_name || ''}`;
                this.initRoom('inquiry_product', prod.id || prodId, title, subtitle);
            })
            .catch(err => {
                console.error(err);
                this.initRoom('inquiry_product', prodId, 'Inquiry Product Chat', '');
            })
            .finally(() => {
                this.loadingProduct = false;
            });
        },

        closeChatDrawer() {
            this.showChatDrawer = false;
            this.leaveRoom();
            this.productDetail = null;
            this.detailPanelData = null;
        }
     })"
     @open-product-chat.window="openChat($event.detail.id)"
     @keydown.escape.window="if (showChatDrawer) closeChatDrawer()"
     class="relative">

    <!-- Dim Backdrop (Click to close) -->
    <div x-show="showChatDrawer" 
         @click="closeChatDrawer()"
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
    <div x-show="showChatDrawer"
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
