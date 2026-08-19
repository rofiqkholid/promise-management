{{-- ===== QUOTATION DOWNLOAD MODAL ===== --}}
<div id="modal-quotation-export" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-sm shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in">
        
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-3.5 bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-sm bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-file-excel text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Export Quotation Tooling</h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Configure currency and exchange rate to IDR before downloading Excel format</p>
                </div>
            </div>
            <button type="button" onclick="closeQuotationExportModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-5 space-y-4 text-xs">
            
            {{-- Alert message area --}}
            <div id="export-modal-alert" class="hidden p-3 rounded-sm text-[11px] font-semibold"></div>

            {{-- 1. Currency Input --}}
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Currency <span class="text-rose-500">*</span>
                </label>
                <div class="space-y-2">
                    <select id="export-currency-select" onchange="handleExportCurrencyChange(this.value)" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-800 dark:text-slate-100 font-semibold focus:outline-none focus:border-indigo-500">
                        <option value="China Yuan" data-code="CNY">China Yuan (CNY)</option>
                        <option value="US Dollar" data-code="USD">US Dollar (USD)</option>
                        <option value="Japanese Yen" data-code="JPY">Japanese Yen (JPY)</option>
                        <option value="Euro" data-code="EUR">Euro (EUR)</option>
                        <option value="Thai Baht" data-code="THB">Thai Baht (THB)</option>
                        <option value="CUSTOM">Other Currency (Custom)...</option>
                    </select>

                    <div id="export-currency-custom-container" class="hidden space-y-1">
                        <input type="text" id="export-currency-custom" placeholder="e.g. SGD or Singapore Dollar" class="w-full px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500">
                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block">* Enter 3-letter currency code (e.g. SGD, MYR, AUD, GBP) for auto-fetch.</span>
                    </div>
                </div>
            </div>

            {{-- 2. Exchange Rate Input & API Auto-Fetch --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                        Exchange Rate to IDR <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" id="btn-fetch-exchange-rate" onclick="fetchExchangeRateFromApi()"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/80 border border-indigo-200 dark:border-indigo-800 rounded-sm transition-all active:scale-95 cursor-pointer">
                        <i id="icon-fetch-spinner" class="fa-solid fa-arrows-rotate text-[10px]"></i>
                        <span>Auto-Fetch</span>
                    </button>
                </div>
                <div class="relative flex items-center">
                    <span class="absolute left-3 text-slate-400 font-bold text-xs">Rp</span>
                    <input type="number" step="0.01" id="export-exchange-rate" value="" placeholder="e.g. 2275.00"
                           class="w-full pl-9 pr-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-sm font-mono font-bold text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500">
                </div>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 block">
                    * Exchange rate is automatically fetched via ExchangeRate-API (live conversion to IDR).
                </span>
            </div>

        </div>

        {{-- Footer --}}
        <div class="px-5 py-3 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2">
            <button type="button" onclick="closeQuotationExportModal()"
                    class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-sm hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer">
                Cancel
            </button>
            <button type="button" onclick="submitQuotationExport()"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-sm shadow-sm hover:shadow transition-all active:scale-98 cursor-pointer">
                <i class="fa-solid fa-download text-[11px]"></i>
                <span>Download Excel</span>
            </button>
        </div>

    </div>
</div>

<script>
    let currentExportUrl = '';

    function openQuotationExportModal(downloadUrl) {
        currentExportUrl = downloadUrl;
        $('#export-modal-alert').addClass('hidden');
        $('#modal-quotation-export').removeClass('hidden').addClass('flex');
    }

    function closeQuotationExportModal() {
        $('#modal-quotation-export').addClass('hidden').removeClass('flex');
    }

    function handleExportCurrencyChange(val) {
        if (val === 'CUSTOM') {
            $('#export-currency-custom-container').removeClass('hidden');
            $('#export-currency-custom').focus();
        } else {
            $('#export-currency-custom-container').addClass('hidden');
        }
    }

    function fetchExchangeRateFromApi() {
        let selectVal = $('#export-currency-select').val();
        let currencyCode = '';

        if (selectVal === 'CUSTOM') {
            currencyCode = $.trim($('#export-currency-custom').val());
        } else {
            let option = $('#export-currency-select option:selected');
            currencyCode = option.attr('data-code') || selectVal;
        }

        if (!currencyCode) {
            showAlert('Please select or enter a currency first', 'error');
            return;
        }

        const $btn = $('#btn-fetch-exchange-rate');
        const $icon = $('#icon-fetch-spinner');
        
        $btn.prop('disabled', true);
        $icon.addClass('fa-spinner animate-spin').removeClass('fa-arrows-rotate');

        $.ajax({
            url: "{{ route('management.api.exchange-rate') }}",
            type: 'GET',
            data: { currency: currencyCode },
            success: function(res) {
                if (res.status === 'success' && res.rate) {
                    let numericRate = Number(res.rate);
                    let formattedInputValue = numericRate.toFixed(2);
                    $('#export-exchange-rate').val(formattedInputValue);

                    let formattedDisplayRate = numericRate.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    let displayName = (res.currency_name && res.currency_name !== res.currency)
                        ? `${res.currency_name} (${res.currency})`
                        : res.currency;
                    showAlert(`Success! 1 ${displayName} = Rp ${formattedDisplayRate}`, 'success');
                } else {
                    showAlert(res.message || 'Failed to fetch exchange rate', 'error');
                }
            },
            error: function(err) {
                let msg = err.responseJSON?.message || 'Failed to connect to Exchange Rate API';
                showAlert(msg, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $icon.removeClass('fa-spinner animate-spin').addClass('fa-arrows-rotate');
            }
        });
    }

    function showAlert(msg, type) {
        const $alert = $('#export-modal-alert');
        $alert.removeClass('hidden bg-rose-50 text-rose-700 border-rose-200 bg-emerald-50 text-emerald-700 border-emerald-200 border');
        
        if (type === 'success') {
            $alert.addClass('bg-emerald-50 text-emerald-700 border border-emerald-200');
        } else {
            $alert.addClass('bg-rose-50 text-rose-700 border border-rose-200');
        }
        
        $alert.text(msg);
    }

    function submitQuotationExport() {
        if (!currentExportUrl) return;

        let selectVal = $('#export-currency-select').val();
        let currencyName = selectVal;
        
        if (selectVal === 'CUSTOM') {
            currencyName = $.trim($('#export-currency-custom').val());
            if (!currencyName) {
                showAlert('Please enter custom currency name', 'error');
                return;
            }
        }

        let rate = $.trim($('#export-exchange-rate').val());
        if (!rate || isNaN(rate) || Number(rate) <= 0) {
            showAlert('Please enter a valid exchange rate', 'error');
            return;
        }

        let separator = currentExportUrl.includes('?') ? '&' : '?';
        let finalUrl = currentExportUrl + separator + 'currency=' + encodeURIComponent(currencyName) + '&exchange_rate=' + encodeURIComponent(rate);

        window.open(finalUrl, '_blank');
        closeQuotationExportModal();
    }
</script>
