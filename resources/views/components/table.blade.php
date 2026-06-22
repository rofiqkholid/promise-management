<div class="bg-white dark:bg-slate-800 rounded-xs border border-slate-200 dark:border-slate-700 overflow-hidden relative">
    <div class="p-4 bg-white dark:bg-slate-800">
        <table {{ $attributes->merge(['class' => 'custom-table w-full text-left border-collapse']) }}>
            {{ $slot }}
        </table>
    </div>
</div>

@once
<script>
    /**
     * Global DataTable Helper
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
            dom: "<'flex flex-col sm:flex-row justify-between items-center mb-6 gap-4'<'flex items-center gap-3'l B><'w-full sm:w-auto'f>>r<'overflow-x-auto w-full relative border border-slate-200 dark:border-slate-700 rounded-xs't><'flex flex-col md:flex-row justify-between items-center gap-4 text-slate-500'i p>",
            buttons: [
                { extend: 'excel', text: '<i class="fa-solid fa-file-excel"></i>', className: 'dt-button buttons-excel' },
                { extend: 'pdf', text: '<i class="fa-solid fa-file-pdf"></i>', className: 'dt-button buttons-pdf' },
                { extend: 'print', text: '<i class="fa-solid fa-print"></i>', className: 'dt-button buttons-print' }
            ],
            language: {
                processing: '<div class="inline-flex items-center"><span class="animate-spin mr-2"></span> Loading...</div>',
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
                lengthMenu: "_MENU_",
                infoFiltered: ""
            }
        };

        const options = $.extend(true, {}, defaults, userConfig);
        
        // CSRF Token Injection
        if (options.ajax && typeof options.ajax === 'object') {
            options.ajax.headers = { ...options.ajax.headers, 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') };
        }

        const dt = $(selector).DataTable(options);

        return dt;
    };
</script>
@endonce
