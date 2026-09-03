{{-- ══════════════════════════════════════════════════════════════════ --}}
{{-- PART DETAIL MODAL & GLOBAL FLOATING POPOVER COMPONENT              --}}
{{-- Separated into its own partial to prevent DataTable clipping     --}}
{{-- ══════════════════════════════════════════════════════════════════ --}}

{{-- 1. Full Part Specification Modal --}}
<x-modal id="part-detail-modal" maxWidth="3xl" showClose="true" closeButtonId="btn-close-part-detail">
    <x-slot name="header">
        <div class="flex items-center justify-between w-full pr-6">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-sm bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-black text-xs flex items-center justify-center border border-blue-200 dark:border-blue-800 shrink-0">
                    BOM
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 id="modal-part-no" class="text-sm font-black text-slate-900 dark:text-white tracking-tight"></h2>
                        <span id="modal-part-badge" class="px-1.5 py-0.5 text-[10px] font-black rounded-xs bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600"></span>
                    </div>
                    <p id="modal-part-name" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-4 text-xs">
        {{-- Section 1: Material & Physical Specs Card --}}
        <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 rounded-sm border border-slate-200 dark:border-slate-700">
            <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2.5 flex items-center justify-between">
                <span>Part & Material Specifications</span>
                <span id="modal-part-level" class="text-[10px] text-slate-400 font-normal"></span>
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Material Spec</span>
                    <span id="modal-mat-spec" class="font-bold text-slate-800 dark:text-slate-100 break-words mt-0.5 block">—</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Thickness</span>
                    <span id="modal-mat-thick" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">—</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Total Operations</span>
                    <span id="modal-total-ops" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">0 Ops</span>
                </div>
                <div class="p-2 bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700">
                    <span class="text-[10px] text-slate-400 block font-medium">Part Structure</span>
                    <span id="modal-part-structure" class="font-bold text-slate-800 dark:text-slate-100 mt-0.5 block">—</span>
                </div>
            </div>
        </div>

        {{-- Section 2: Process Routing Table --}}
        <div>
            <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2 flex items-center justify-between">
                <span>Process Routing & Machine Allocations</span>
                <span id="modal-ops-count-badge" class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300"></span>
            </h3>
            <div class="overflow-x-auto rounded-xs border border-slate-200 dark:border-slate-700">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-[10px] uppercase border-b border-slate-200 dark:border-slate-700">
                            <th class="p-2 text-center w-12 border-r border-slate-200 dark:border-slate-700">No.</th>
                            <th class="p-2 text-center w-16 border-r border-slate-200 dark:border-slate-700">Stage</th>
                            <th class="p-2 border-r border-slate-200 dark:border-slate-700">Process Name</th>
                            <th class="p-2 text-left">Machine / Tonnage</th>
                        </tr>
                    </thead>
                    <tbody id="modal-process-table-body" class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-200">
                        {{-- Populated dynamically via JavaScript --}}
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 3: Cost Comparison for this Item --}}
        <div>
            <h3 class="text-[11px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Cost Comparison Summary (IDR)
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                <div class="p-2.5 rounded-xs border border-blue-200 dark:border-blue-900/60 bg-blue-50/40 dark:bg-blue-950/20">
                    <span class="text-[10px] font-bold text-blue-800 dark:text-blue-300 uppercase block">Engineering</span>
                    <div id="modal-cost-eng" class="text-xs font-black text-blue-900 dark:text-blue-100 mt-1">Rp 0</div>
                    <div id="modal-cost-eng-sub" class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"></div>
                </div>
                <div class="p-2.5 rounded-xs border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50/40 dark:bg-indigo-950/20">
                    <span class="text-[10px] font-bold text-indigo-800 dark:text-indigo-300 uppercase block">Sales</span>
                    <div id="modal-cost-sales" class="text-xs font-black text-indigo-900 dark:text-indigo-100 mt-1">Rp 0</div>
                    <div id="modal-cost-sales-sub" class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"></div>
                </div>
                <div class="p-2.5 rounded-xs border border-violet-200 dark:border-violet-900/60 bg-violet-50/40 dark:bg-violet-950/20">
                    <span class="text-[10px] font-bold text-violet-800 dark:text-violet-300 uppercase block">Sales Adjustment</span>
                    <div id="modal-cost-sales-rev" class="text-xs font-black text-violet-900 dark:text-violet-100 mt-1">—</div>
                    <div id="modal-cost-sales-rev-sub" class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"></div>
                </div>
                <div class="p-2.5 rounded-xs border border-amber-200 dark:border-amber-900/60 bg-amber-50/40 dark:bg-amber-950/20">
                    <span class="text-[10px] font-bold text-amber-800 dark:text-amber-300 uppercase block">Customer Quotation</span>
                    <div id="modal-cost-cust" class="text-xs font-black text-amber-900 dark:text-amber-100 mt-1">—</div>
                    <div id="modal-cost-cust-sub" class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"></div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <button type="button" onclick="$('#part-detail-modal').addClass('hidden').removeClass('flex')"
                class="px-4 py-1.5 rounded-sm bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 font-bold text-xs text-slate-700 dark:text-slate-200 transition-colors cursor-pointer">
            Close
        </button>
    </x-slot>
</x-modal>

{{-- 2. Global Floating Popover (Appended outside the DataTable to guarantee NO clipping) --}}
<div id="part-global-popover"
     class="hidden fixed z-[99999] p-3 bg-slate-900 text-white rounded-sm shadow-2xl border border-slate-700 w-72 pointer-events-none transition-opacity duration-150 text-left font-normal">
    <div class="text-[11px] font-bold text-blue-300 pb-1.5 border-b border-slate-700 flex items-center justify-between gap-2">
        <span id="popover-part-no" class="truncate"></span>
        <span id="popover-part-badge" class="text-[8px] px-1.5 py-0.2 rounded-xs bg-slate-800 text-slate-300 border border-slate-700 font-bold"></span>
    </div>
    <div id="popover-part-name" class="text-[10px] text-slate-300 py-1 font-medium truncate"></div>

    <div class="mt-1 py-1.5 border-t border-slate-800 space-y-1 text-[10px]">
        <div class="flex justify-between items-center">
            <span class="text-slate-400">Material Spec:</span>
            <span id="popover-mat-spec" class="text-slate-200 font-semibold truncate max-w-[150px]"></span>
        </div>
        <div class="flex justify-between items-center" id="popover-thick-row">
            <span class="text-slate-400">Thickness:</span>
            <span id="popover-mat-thick" class="text-slate-200 font-mono font-semibold"></span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-slate-400">Operations:</span>
            <span id="popover-ops-count" class="text-slate-200 font-bold"></span>
        </div>
    </div>

    <div id="popover-routing-container" class="mt-1 pt-1.5 border-t border-slate-800 text-[9px]">
        <div class="font-bold text-slate-400 uppercase tracking-wider mb-1">Process Routing:</div>
        <div id="popover-routing-list" class="space-y-0.5 max-h-32 overflow-y-auto pr-1"></div>
    </div>
    
    <div class="mt-2 pt-1 border-t border-slate-800/80 text-[9px] text-blue-400 italic text-right">
        Click part to view full details →
    </div>
</div>

<script>
    // Global cache of current page items
    window.currentTableItemsMap = window.currentTableItemsMap || {};

    // Open Part Detail Modal with rich specs & routing
    window.openPartDetailModal = function (itemId) {
        const item = window.currentTableItemsMap[itemId];
        if (!item) return;

        const setTxt = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        setTxt('modal-part-no', item.part_no || '-');
        setTxt('modal-part-name', item.part_name || '-');
        setTxt('modal-part-badge', item.is_assy ? 'Sub-Assembly' : 'Single Part');
        setTxt('modal-part-level', `Level ${item.active_level ?? 1}`);

        setTxt('modal-mat-spec', item.mat_spec && item.mat_spec !== '-' ? item.mat_spec : (item.is_assy ? 'Assembly Part' : '-'));
        setTxt('modal-mat-thick', item.mat_thick ? `${parseFloat(item.mat_thick).toFixed(2)} mm` : '-');
        setTxt('modal-total-ops', `${item.total_process || 0} Processes`);
        setTxt('modal-part-structure', item.is_assy ? 'Assembly (Multi-component)' : 'Sheet Metal Component');

        // Populate Process Routing Table
        const pList = item.process_list || [];
        setTxt('modal-ops-count-badge', `${pList.length} Steps Recorded`);

        const tbody = document.getElementById('modal-process-table-body');
        if (tbody) {
            if (pList.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="p-3 text-center text-slate-400 italic text-[11px]">
                            No specific tooling processes recorded for this part.
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = pList.map((p, idx) => `
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                        <td class="p-2 text-center text-slate-400 font-mono text-[10px] border-r border-slate-200 dark:border-slate-700">${idx + 1}</td>
                        <td class="p-2 text-center font-bold text-[10px] text-blue-600 dark:text-blue-400 border-r border-slate-200 dark:border-slate-700">${p.op}</td>
                        <td class="p-2 font-semibold text-slate-800 dark:text-slate-100 border-r border-slate-200 dark:border-slate-700">${p.name}</td>
                        <td class="p-2 text-slate-600 dark:text-slate-300 font-mono text-[10px]">${p.machine || '—'}</td>
                    </tr>
                `).join('');
            }
        }

        // Cost Comparison Summary
        const formatRupiah = (num) => 'Rp ' + Number(num || 0).toLocaleString('id-ID');

        setTxt('modal-cost-eng', formatRupiah(item.eng_cogs));
        setTxt('modal-cost-eng-sub', `Mat: ${formatRupiah(item.eng_mat_cost)} | Mfg: ${formatRupiah(item.eng_mfg_cost)}`);

        setTxt('modal-cost-sales', formatRupiah(item.sales_cogs));
        setTxt('modal-cost-sales-sub', `Mat: ${formatRupiah(item.sales_mat_cost)} | Mfg: ${formatRupiah(item.sales_mfg_cost)}`);

        if (item.sales_rev_cogs) {
            setTxt('modal-cost-sales-rev', formatRupiah(item.sales_rev_cogs));
            setTxt('modal-cost-sales-rev-sub', `Mat: ${formatRupiah(item.sales_rev_mat_cost)} | Mfg: ${formatRupiah(item.sales_rev_mfg_cost)}`);
        } else {
            setTxt('modal-cost-sales-rev', '—');
            setTxt('modal-cost-sales-rev-sub', '');
        }

        if (item.cust_cogs) {
            setTxt('modal-cost-cust', formatRupiah(item.cust_cogs));
            setTxt('modal-cost-cust-sub', `Mat: ${formatRupiah(item.cust_mat_cost)} | Mfg: ${formatRupiah(item.cust_mfg_cost)}`);
        } else {
            setTxt('modal-cost-cust', '—');
            setTxt('modal-cost-cust-sub', '');
        }

        const modal = document.getElementById('part-detail-modal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    };

    // Close button for modal using standard event listener
    document.addEventListener('click', function (e) {
        if (!e.target) return;
        if (e.target.id === 'btn-close-part-detail' || e.target.closest('#btn-close-part-detail') || e.target.closest('.btn-close-modal')) {
            const modal = document.getElementById('part-detail-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
    });

    // Global Floating Popover Engine (Never gets clipped by table containers!)
    window.showGlobalPartPopover = function (triggerEl, itemId) {
        const item = window.currentTableItemsMap[itemId];
        if (!item) return;

        const popover = document.getElementById('part-global-popover');
        if (!popover) return;

        const setTxt = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        setTxt('popover-part-no', item.part_no || '-');
        setTxt('popover-part-name', item.part_name || '-');
        setTxt('popover-part-badge', item.is_assy ? 'ASSY' : 'PART');

        setTxt('popover-mat-spec', item.mat_spec && item.mat_spec !== '-' ? item.mat_spec : (item.is_assy ? 'Sub-Assembly' : '-'));
        
        const thickRow = document.getElementById('popover-thick-row');
        if (thickRow) {
            thickRow.style.display = item.mat_thick ? '' : 'none';
            if (item.mat_thick) {
                setTxt('popover-mat-thick', `${parseFloat(item.mat_thick).toFixed(2)} mm`);
            }
        }

        const count = item.total_process || 0;
        setTxt('popover-ops-count', `${count} Processes`);

        const pList = item.process_list || [];
        const routingContainer = document.getElementById('popover-routing-container');
        const routingList = document.getElementById('popover-routing-list');

        if (routingList && routingContainer) {
            if (pList.length > 0) {
                routingContainer.style.display = '';
                const itemsHtml = pList.slice(0, 6).map(p => `
                    <div class="flex items-center justify-between text-slate-300">
                        <span class="font-mono text-slate-500">${p.op}:</span>
                        <span class="truncate max-w-[130px] font-medium">${p.name}</span>
                        ${p.machine ? `<span class="text-slate-500 text-[8px]">${p.machine}</span>` : ''}
                    </div>
                `).join('');
                const moreHtml = pList.length > 6 ? `<div class="text-[8px] text-slate-500 italic mt-0.5">+${pList.length - 6} more operations</div>` : '';
                routingList.innerHTML = itemsHtml + moreHtml;
            } else {
                routingContainer.style.display = 'none';
            }
        }

        // Calculate smart position relative to viewport (fixed positioning)
        popover.classList.remove('hidden');
        popover.style.opacity = '0';

        const rect = triggerEl.getBoundingClientRect();
        const popoverHeight = popover.offsetHeight || 180;
        const popoverWidth = popover.offsetWidth || 288;

        // Position horizontally (keep inside viewport)
        let left = rect.left;
        if (left + popoverWidth > window.innerWidth - 12) {
            left = window.innerWidth - popoverWidth - 12;
        }
        if (left < 12) left = 12;

        // Position vertically: flip downwards if too close to top
        let top = rect.top - popoverHeight - 8;
        if (top < 12) {
            // Not enough room above, place below
            top = rect.bottom + 8;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
        popover.style.opacity = '1';
    };

    window.hideGlobalPartPopover = function () {
        const popover = document.getElementById('part-global-popover');
        if (popover) {
            popover.classList.add('hidden');
        }
    };
</script>
