{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- TOOLING DETAIL MODAL & GLOBAL FLOATING POPOVER COMPONENT           --}}
{{-- Dedicated partial to prevent DataTable clipping and improve UX   --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}

{{-- 1. Full Tooling Detail Modal --}}
<x-modal id="tooling-detail-modal" maxWidth="2xl" showClose="true" closeButtonId="btn-close-tooling-detail">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full pr-6">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-sm bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-black text-xs flex items-center justify-center border border-purple-200 dark:border-purple-800 shrink-0">
                    TOOL
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 id="modal-tool-proc-name" class="text-sm font-black text-slate-900 dark:text-white tracking-tight"></h2>
                        <span id="modal-tool-cat-badge" class="px-1.5 py-0.5 text-[10px] font-black rounded-xs"></span>
                    </div>
                    <p id="modal-tool-part-info" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4 text-xs">
        {{-- Section 1: Tooling & Machine Specifications --}}
        <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 rounded-sm border border-slate-200 dark:border-slate-700">
            <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2.5 flex items-center justify-between">
                <span>Machine & Process Technical Specs</span>
                <span id="modal-tool-rank" class="text-[10px] text-slate-400 font-normal"></span>
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Machine Type</span>
                    <span id="modal-tool-machine" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">—</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Machine Tonnage</span>
                    <span id="modal-tool-tonnage" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">—</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Tooling Quantity</span>
                    <span id="modal-tool-qty" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">1 pcs</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Production Homeline</span>
                    <span id="modal-tool-homeline" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">—</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Die Height</span>
                    <span id="modal-tool-die-height" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">—</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Stroke / Output</span>
                    <span id="modal-tool-stroke" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">—</span>
                </div>
            </div>
        </div>

        {{-- Section 2: Commercial Cost Comparison (Multi-Source) --}}
        <div>
            <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Tooling Cost Comparison (IDR)
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
                <div class="p-2.5 rounded-xs border border-blue-200 dark:border-blue-900/60 bg-blue-50/40 dark:bg-blue-950/20">
                    <span class="text-[9px] font-bold text-blue-800 dark:text-blue-300 uppercase block">Engineering</span>
                    <div id="modal-tool-cost-eng" class="text-xs font-black text-blue-900 dark:text-blue-100 mt-1">Rp 0</div>
                </div>
                <div class="p-2.5 rounded-xs border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/40 dark:bg-indigo-950/20">
                    <span class="text-[9px] font-bold text-indigo-800 dark:text-indigo-300 uppercase block">Sales</span>
                    <div id="modal-tool-cost-sales" class="text-xs font-black text-indigo-900 dark:text-indigo-100 mt-1">Rp 0</div>
                </div>
                <div class="p-2.5 rounded-xs border border-violet-200 dark:border-violet-900/60 bg-violet-50/40 dark:bg-violet-950/20">
                    <span class="text-[9px] font-bold text-violet-800 dark:text-violet-300 uppercase block">Sales Adj.</span>
                    <div id="modal-tool-cost-sales-rev" class="text-xs font-black text-violet-900 dark:text-violet-100 mt-1">—</div>
                </div>
                <div class="p-2.5 rounded-xs border border-amber-200 dark:border-amber-900/60 bg-amber-50/40 dark:bg-amber-950/20">
                    <span class="text-[9px] font-bold text-amber-800 dark:text-amber-300 uppercase block">Customer</span>
                    <div id="modal-tool-cost-cust" class="text-xs font-black text-amber-900 dark:text-amber-100 mt-1">—</div>
                </div>
                <div class="p-2.5 rounded-xs border border-purple-200 dark:border-purple-900/60 bg-purple-50/40 dark:bg-purple-950/20">
                    <span class="text-[9px] font-bold text-purple-800 dark:text-purple-300 uppercase block">Supplier</span>
                    <div id="modal-tool-cost-supp" class="text-xs font-black text-purple-900 dark:text-purple-100 mt-1">—</div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <button type="button" onclick="document.getElementById('tooling-detail-modal').classList.add('hidden'); document.getElementById('tooling-detail-modal').classList.remove('flex')"
                class="px-4 py-1.5 rounded-sm bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 font-bold text-xs text-slate-700 dark:text-slate-200 transition-colors cursor-pointer">
            Close
        </button>
    </x-slot>
</x-modal>

{{-- 2. Global Floating Popover for Tooling Process (Guaranteed Unclipped) --}}
<div id="tooling-global-popover"
     class="hidden fixed z-[99999] p-3 bg-slate-900 text-white rounded-sm shadow-2xl border border-slate-700 w-72 pointer-events-none transition-opacity duration-150 text-left font-normal">
    <div class="text-[11px] font-bold text-purple-300 pb-1.5 border-b border-slate-700 flex items-center justify-between gap-2">
        <span id="popover-tool-proc" class="truncate"></span>
        <span id="popover-tool-cat" class="text-[8px] px-1.5 py-0.2 rounded-xs font-bold"></span>
    </div>
    <div id="popover-tool-part" class="text-[10px] text-slate-300 py-1 font-medium truncate"></div>

    <div class="mt-1 py-1.5 border-t border-slate-800 space-y-1 text-[10px]">
        <div class="flex justify-between items-center">
            <span class="text-slate-400">Machine:</span>
            <span id="popover-tool-machine" class="text-slate-200 font-semibold truncate max-w-[150px]"></span>
        </div>
        <div class="flex justify-between items-center" id="popover-tool-tons-row">
            <span class="text-slate-400">Tonnage:</span>
            <span id="popover-tool-tons" class="text-slate-200 font-mono font-semibold"></span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-slate-400">Quantity:</span>
            <span id="popover-tool-qty" class="text-slate-200 font-bold"></span>
        </div>
        <div class="flex justify-between items-center" id="popover-tool-homeline-row">
            <span class="text-slate-400">Homeline:</span>
            <span id="popover-tool-homeline" class="text-slate-200 font-semibold"></span>
        </div>
    </div>

    <div class="mt-2 pt-1 border-t border-slate-800/80 text-[9px] text-purple-400 italic text-right">
        Click process to view full tooling specs →
    </div>
</div>

<script>
    // Global array of tooling items
    window.currentToolingItems = window.currentToolingItems || [];

    // Open Tooling Detail Modal
    window.openToolingDetailModal = function (idx) {
        const item = window.currentToolingItems[idx];
        if (!item) return;

        const setTxt = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        const tCat = (item.category || 'DIE').toUpperCase();
        const tOp = (item.op && item.op !== '-') ? (tCat === 'JIG' ? `ST ${item.op}` : `OP ${item.op}`) : '';
        const procTitle = tOp ? `${tOp}: ${item.process_name || '-'}` : (item.process_name || '-');

        setTxt('modal-tool-proc-name', procTitle);
        setTxt('modal-tool-part-info', `${item.part_no || '-'} — ${item.part_name || '-'}`);

        const catBadge = document.getElementById('modal-tool-cat-badge');
        if (catBadge) {
            catBadge.textContent = tCat;
            if (tCat === 'JIG') {
                catBadge.className = 'px-1.5 py-0.5 text-[10px] font-black rounded-xs bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
            } else if (tCat === 'CF') {
                catBadge.className = 'px-1.5 py-0.5 text-[10px] font-black rounded-xs bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
            } else {
                catBadge.className = 'px-1.5 py-0.5 text-[10px] font-black rounded-xs bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300';
            }
        }

        setTxt('modal-tool-rank', item.tool_rank && item.tool_rank !== '-' ? `Rank ${item.tool_rank}` : 'Tooling Item');
        setTxt('modal-tool-machine', item.machine_type || 'Standard Press Machine');
        setTxt('modal-tool-tonnage', item.tonnage ? `${item.tonnage} Ton` : '—');
        setTxt('modal-tool-qty', `${item.qty || 1} pcs`);
        setTxt('modal-tool-homeline', item.prod_homeline || '—');
        setTxt('modal-tool-die-height', item.die_height ? `${item.die_height} mm` : '—');
        setTxt('modal-tool-stroke', `${item.stroke || 1} S / ${item.output || 1} O`);

        const formatRupiah = (num) => num ? 'Rp ' + Number(num).toLocaleString('id-ID') : '—';

        setTxt('modal-tool-cost-eng', formatRupiah(item.total_cost_eng));
        setTxt('modal-tool-cost-sales', formatRupiah(item.total_cost_sales));
        setTxt('modal-tool-cost-sales-rev', formatRupiah(item.total_cost_sales_rev));
        setTxt('modal-tool-cost-cust', formatRupiah(item.total_cost_customer));
        setTxt('modal-tool-cost-supp', formatRupiah(item.total_cost_supplier));

        const modal = document.getElementById('tooling-detail-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    // Close button event listener
    document.addEventListener('click', function (e) {
        if (!e.target) return;
        if (e.target.id === 'btn-close-tooling-detail' || e.target.closest('#btn-close-tooling-detail')) {
            const modal = document.getElementById('tooling-detail-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
    });

    // Global Floating Popover Engine for Tooling (Guaranteed Unclipped!)
    window.showGlobalToolingPopover = function (triggerEl, idx) {
        const item = window.currentToolingItems[idx];
        if (!item) return;

        const popover = document.getElementById('tooling-global-popover');
        if (!popover) return;

        const setTxt = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        const tCat = (item.category || 'DIE').toUpperCase();
        const tOp = (item.op && item.op !== '-') ? (tCat === 'JIG' ? `ST ${item.op}` : `OP ${item.op}`) : '';
        const procTitle = tOp ? `${tOp}: ${item.process_name || '-'}` : (item.process_name || '-');

        setTxt('popover-tool-proc', procTitle);
        setTxt('popover-tool-part', `${item.part_no || '-'} (${item.part_name || '-'})`);

        const catBadge = document.getElementById('popover-tool-cat');
        if (catBadge) {
            catBadge.textContent = tCat;
            catBadge.className = 'text-[8px] px-1.5 py-0.2 rounded-xs font-bold ' + 
                (tCat === 'JIG' ? 'bg-emerald-950 text-emerald-300 border border-emerald-800' :
                (tCat === 'CF' ? 'bg-amber-950 text-amber-300 border border-amber-800' : 'bg-purple-950 text-purple-300 border border-purple-800'));
        }

        setTxt('popover-tool-machine', item.machine_type || 'Standard Machine');

        const tonsRow = document.getElementById('popover-tool-tons-row');
        if (tonsRow) {
            tonsRow.style.display = item.tonnage ? '' : 'none';
            if (item.tonnage) setTxt('popover-tool-tons', `${item.tonnage} Ton`);
        }

        setTxt('popover-tool-qty', `${item.qty || 1} pcs`);

        const hlRow = document.getElementById('popover-tool-homeline-row');
        if (hlRow) {
            hlRow.style.display = (item.prod_homeline && item.prod_homeline !== '-') ? '' : 'none';
            if (item.prod_homeline) setTxt('popover-tool-homeline', item.prod_homeline);
        }

        // Calculate smart position relative to viewport (fixed positioning)
        popover.classList.remove('hidden');
        popover.style.opacity = '0';

        const rect = triggerEl.getBoundingClientRect();
        const popoverHeight = popover.offsetHeight || 160;
        const popoverWidth = popover.offsetWidth || 288;

        let left = rect.left;
        if (left + popoverWidth > window.innerWidth - 12) {
            left = window.innerWidth - popoverWidth - 12;
        }
        if (left < 12) left = 12;

        let top = rect.top - popoverHeight - 8;
        if (top < 12) {
            top = rect.bottom + 8;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
        popover.style.opacity = '1';
    };

    window.hideGlobalToolingPopover = function () {
        const popover = document.getElementById('tooling-global-popover');
        if (popover) {
            popover.classList.add('hidden');
        }
    };
</script>
