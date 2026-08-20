@props([
    'id' => null,
    'filters' => null,
])

@php
    $tableId = $id ?? 'dt-' . uniqid();
@endphp

<div class="bg-white dark:bg-slate-800 rounded-sm border border-slate-300 dark:border-slate-700 relative" id="{{ $tableId }}-container">
    @if($filters)
    <!-- Filter Source Definition for DataTable Popover -->
    <div id="{{ $tableId }}-filters-source" class="hidden">
        {{ $filters }}
    </div>
    @endif

    <div class="p-4">
        <table id="{{ $tableId }}" {{ $attributes->merge(['class' => 'custom-table w-full text-left']) }}>
            {{ $slot }}
        </table>
    </div>
</div>

@once
<script>
    /**
     * Global DataTable Helper with Filter Popup, Wide Search & Bottom Controls
     */
    window.defaultDataTable = function (selector, userConfig = {}) {
        if (typeof $ === 'undefined') return console.error('jQuery required');

        const defaults = {
            processing: true,
            serverSide: false,
            scrollCollapse: true,
            autoWidth: false,
            ordering: true,
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            // Top: Buttons on left, Search, Columns & Filters grouped on right. Bottom: Rows Select & Info on left, Pagination on right
            dom: "<'flex flex-col sm:flex-row justify-between items-center mb-4 gap-3'<'dt-top-left flex flex-wrap items-center gap-2'B><'dt-top-right flex flex-wrap items-center justify-end gap-2'f>>r<'overflow-x-auto w-full relative border border-slate-300 dark:border-slate-700 rounded-sm't><'flex flex-col sm:flex-row justify-between items-center mt-4 gap-4 text-xs text-slate-500 dark:text-slate-400'<'flex flex-wrap items-center gap-3'l i><'flex items-center'p>>",
            buttons: [
                { extend: 'excel', text: '<i class="fa-solid fa-file-excel"></i>', className: 'dt-button buttons-excel' },
                { extend: 'pdf', text: '<i class="fa-solid fa-file-pdf"></i>', className: 'dt-button buttons-pdf' },
                { extend: 'print', text: '<i class="fa-solid fa-print"></i>', className: 'dt-button buttons-print' }
            ],
            language: {
                processing: '',
                search: "_INPUT_",
                searchPlaceholder: "Search records...",
                paginate: { previous: '<i class="fa-solid fa-chevron-left"></i>', next: '<i class="fa-solid fa-chevron-right"></i>' },
                emptyTable: `
                    <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                        <div>
                            <i class="fa-solid fa-folder-open text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No Records Found</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">It looks like there are no records matching your current criteria. Try adding a new record or adjusting your filters.</p>
                    </div>
                `,
                zeroRecords: `
                    <div class="py-16 flex flex-col items-center justify-center text-center w-full">
                        <div>
                            <i class="fa-solid fa-magnifying-glass text-3xl text-slate-300 dark:text-slate-600 m-4"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-2">No Matching Results</h4>
                        <p class="text-xs text-gray-400 dark:text-gray-500 max-w-xs mx-auto font-medium leading-relaxed">We couldn't find any data matching your search. Try using different keywords or clearing your filters.</p>
                    </div>
                `,
                lengthMenu: "Show _MENU_ rows",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total records)"
            }
        };

        const options = $.extend(true, {}, defaults, userConfig);
        
        // CSRF Token Injection
        if (options.ajax && typeof options.ajax === 'object') {
            options.ajax.headers = { ...options.ajax.headers, 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') };
        }

        const dt = $(selector).DataTable(options);
        const $table = $(selector);
        const tableId = $table.attr('id') || ('dt-' + Math.random().toString(36).substring(7));

        function setupToolbarControls() {
            const $wrapper = $table.closest('.dataTables_wrapper');
            let $topRight = $wrapper.find('.dt-top-right');
            if (!$topRight.length) {
                const $filter = $wrapper.find('.dataTables_filter');
                $topRight = $filter.length ? $filter.parent() : $wrapper.find('> div:first-child');
            }
            if (!$topRight.length) return;

            // 1. Column Visibility (ColVis) Dropdown
            const colvisId = 'dt-colvis-' + tableId;
            if (!$(`#${colvisId}-btn`).length) {
                const colvisBtnHtml = `
                    <div class="relative inline-block text-left dt-colvis-dropdown-wrapper shrink-0">
                        <button type="button" id="${colvisId}-btn"
                                class="inline-flex items-center gap-2 px-3 h-9 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-sm text-xs font-medium text-slate-700 dark:text-slate-200 transition-colors shadow-2xs cursor-pointer">
                            <i class="fa-solid fa-table-columns text-slate-400 text-xs"></i>
                            <span>Columns</span>
                        </button>

                        <div id="${colvisId}-panel" class="hidden fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 sm:translate-x-0 sm:translate-y-0 sm:left-auto sm:top-full sm:right-0 sm:absolute sm:inset-auto sm:mt-2 z-[999] w-[calc(100vw-2.5rem)] max-w-xs sm:w-64 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm shadow-2xl p-4 text-left">
                            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-table-columns text-blue-600 dark:text-blue-400 text-xs"></i>
                                    <span class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Toggle Columns</span>
                                </div>
                                <button type="button" id="${colvisId}-close" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-base leading-none cursor-pointer">&times;</button>
                            </div>
                            <div id="${colvisId}-list" class="space-y-1 text-xs max-h-64 overflow-y-auto pr-1"></div>
                            <div class="flex items-center justify-between pt-3 mt-3 border-t border-slate-200 dark:border-slate-700 text-xs">
                                <button type="button" id="${colvisId}-show-all" class="font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">
                                    Show All
                                </button>
                                <button type="button" id="${colvisId}-done" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-sm transition-colors cursor-pointer">
                                    Done
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                $topRight.append(colvisBtnHtml);

                const $colvisList = $(`#${colvisId}-list`);
                dt.columns().every(function(colIdx) {
                    const col = this;
                    const $header = $(col.header());
                    let title = $header.text().trim();
                    if (!title) {
                        title = $header.attr('title') || $header.data('title') || `Column ${colIdx + 1}`;
                    }
                    title = title.replace(/[\n\r]+/g, ' ').replace(/\s{2,}/g, ' ').trim();

                    const isVisible = col.visible();
                    const itemHtml = `
                        <label class="flex items-center gap-2.5 px-2 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-sm cursor-pointer select-none text-slate-700 dark:text-slate-200">
                            <input type="checkbox" data-col-idx="${colIdx}" ${isVisible ? 'checked' : ''}
                                   class="w-3.5 h-3.5 rounded-sm border-slate-300 dark:border-slate-600 text-blue-600 focus:outline-none cursor-pointer">
                            <span class="text-xs font-medium truncate">${title}</span>
                        </label>
                    `;
                    $colvisList.append(itemHtml);
                });

                $colvisList.on('change', 'input[type="checkbox"]', function() {
                    const idx = $(this).data('col-idx');
                    const isChecked = $(this).is(':checked');
                    dt.column(idx).visible(isChecked);
                });

                $(`#${colvisId}-show-all`).on('click', function() {
                    $colvisList.find('input[type="checkbox"]').each(function() {
                        if (!$(this).is(':checked')) {
                            $(this).prop('checked', true).trigger('change');
                        }
                    });
                });

                const $colvisPanel = $(`#${colvisId}-panel`);
                const $colvisBtn = $(`#${colvisId}-btn`);

                $colvisBtn.on('click', function(e) {
                    e.stopPropagation();
                    $colvisPanel.toggleClass('hidden');
                });

                $(`#${colvisId}-close, #${colvisId}-done`).on('click', function() {
                    $colvisPanel.addClass('hidden');
                });

                $(document).on('click', function(e) {
                    if (!$(e.target).closest(`#${colvisId}-btn, #${colvisId}-panel`).length) {
                        $colvisPanel.addClass('hidden');
                    }
                });
            }

            // 2. Filter Source & Dropdown
            let $filterSource = null;
            if (userConfig.filterContainer) {
                $filterSource = $(userConfig.filterContainer);
            } else if (tableId && $(`#${tableId}-filters-source`).length) {
                $filterSource = $(`#${tableId}-filters-source`);
            } else if (tableId && $(`[data-filter-for="#${tableId}"], [data-filter-for="${tableId}"]`).length) {
                $filterSource = $(`[data-filter-for="#${tableId}"], [data-filter-for="${tableId}"]`);
            } else {
                const $container = $wrapper.closest('#' + tableId + '-container').parent();
                const $prevToolbar = $container.find('.table-filter-bar, [data-filter-toolbar], #filter-toolbar');
                if ($prevToolbar.length) {
                    $filterSource = $prevToolbar;
                }
            }

            if ($filterSource && $filterSource.length) {
                const popoverId = 'dt-filter-popover-' + tableId;
                if (!$(`#${popoverId}-btn`).length) {
                    const filterBtnHtml = `
                        <div class="relative inline-block text-left dt-filter-dropdown-wrapper shrink-0">
                            <button type="button" id="${popoverId}-btn"
                                    class="inline-flex items-center gap-2 px-3 h-9 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/50 rounded-sm text-xs font-medium text-slate-700 dark:text-slate-200 transition-colors shadow-2xs cursor-pointer">
                                <i class="fa-solid fa-filter text-slate-400 text-xs"></i>
                                <span>Filters</span>
                                <span id="${popoverId}-badge" class="hidden inline-flex items-center justify-center px-1.5 min-w-4 h-4 text-[10px] font-extrabold text-white bg-blue-600 rounded-full">0</span>
                            </button>

                            <div id="${popoverId}-panel" class="hidden fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 sm:translate-x-0 sm:translate-y-0 sm:left-auto sm:top-full sm:right-0 sm:absolute sm:inset-auto sm:mt-2 z-[999] w-[calc(100vw-2.5rem)] max-w-sm sm:w-96 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-sm shadow-2xl p-4 text-left">
                                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-200 dark:border-slate-700">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-sliders text-blue-600 dark:text-blue-400 text-xs"></i>
                                        <span class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Filter Records</span>
                                    </div>
                                    <button type="button" id="${popoverId}-close" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-base leading-none cursor-pointer">&times;</button>
                                </div>
                                <div id="${popoverId}-content" class="space-y-3 text-xs max-h-80 overflow-y-auto pr-1"></div>
                                <div class="flex items-center justify-between pt-3 mt-3 border-t border-slate-200 dark:border-slate-700">
                                    <button type="button" id="${popoverId}-reset" class="text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 hover:underline cursor-pointer">
                                        Reset All
                                    </button>
                                    <button type="button" id="${popoverId}-apply" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-sm transition-colors cursor-pointer">
                                        Done
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    $topRight.append(filterBtnHtml);

                    const $panelContent = $(`#${popoverId}-content`);
                    $filterSource.children().appendTo($panelContent);
                    $filterSource.addClass('hidden').hide();

                    function updateActiveBadge() {
                        let count = 0;
                        $panelContent.find('input, select, textarea').each(function() {
                            const $el = $(this);
                            if ($el.attr('type') === 'checkbox' || $el.attr('type') === 'radio') {
                                if ($el.is(':checked') && $el.val() !== '') count++;
                            } else if ($el.val() && String($el.val()).trim() !== '') {
                                count++;
                            }
                        });
                        const $badge = $(`#${popoverId}-badge`);
                        if (count > 0) {
                            $badge.text(count).removeClass('hidden').show();
                        } else {
                            $badge.text('0').addClass('hidden').hide();
                        }
                    }

                    $panelContent.on('change input', 'input, select, textarea', function() {
                        updateActiveBadge();
                    });

                    const $panel = $(`#${popoverId}-panel`);
                    const $btn = $(`#${popoverId}-btn`);

                    function openPopover() {
                        $panel.removeClass('hidden');
                    }

                    function closePopover() {
                        $panel.addClass('hidden');
                    }

                    $btn.on('click', function(e) {
                        e.stopPropagation();
                        if ($panel.hasClass('hidden')) {
                            openPopover();
                        } else {
                            closePopover();
                        }
                    });

                    $(`#${popoverId}-close`).on('click', function() {
                        closePopover();
                    });

                    $(`#${popoverId}-apply`).on('click', function() {
                        const $form = $panelContent.find('form');
                        if ($form.length) {
                            $form.submit();
                        }
                        closePopover();
                    });

                    $(`#${popoverId}-reset`).on('click', function() {
                        $panelContent.find('input, select, textarea').each(function() {
                            const $el = $(this);
                            if ($el.attr('type') === 'checkbox' || $el.attr('type') === 'radio') {
                                $el.prop('checked', false);
                            } else {
                                $el.val('');
                            }
                            $el.trigger('change');
                        });
                        updateActiveBadge();
                        const $form = $panelContent.find('form');
                        if ($form.length) {
                            window.location.href = $form.attr('action') || window.location.pathname;
                        } else if (typeof dt.draw === 'function') {
                            dt.draw();
                        }
                    });

                    $(document).on('click', function(e) {
                        if (!$(e.target).closest(`#${popoverId}-btn, #${popoverId}-panel`).length) {
                            closePopover();
                        }
                    });

                    updateActiveBadge();
                }
            }
        }

        // Execute immediately and on init
        setupToolbarControls();
        dt.on('init', function() {
            setupToolbarControls();
        });

        return dt;
    };
</script>
@endonce