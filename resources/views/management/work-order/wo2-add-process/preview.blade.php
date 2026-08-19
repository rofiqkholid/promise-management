<style>
    /* -------------------------------------------------------------------------
       1. SCREEN ONLY STYLES (Frontend A4 Pages Preview)
       ------------------------------------------------------------------------- */
    .a4-container {
        width: 100%;
        background-color: #f1f5f9;
        padding: 1.5rem 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: calc(1rem * var(--scale, 1));
    }
    .a4-page-wrapper {
        width: calc(210mm * var(--scale, 1));
        height: calc(297mm * var(--scale, 1));
        margin: 0 auto;
        position: relative;
        box-sizing: border-box;
    }
    .a4-page {
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin: 0;
        padding: 15mm; /* Samakan persis dengan @page { margin: 15mm 15mm } di CSS print */
        position: absolute;
        top: 0;
        left: 0;
        box-sizing: border-box;
        font-family: 'Times New Roman', Times, serif;
        color: black;
        width: 210mm;
        height: 297mm;
        overflow: hidden; /* visual overflow safeguard */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        
        transform: scale(var(--scale, 1));
        transform-origin: top left;
        
        /* Enforce uniform 11px font size on screen and print */
        font-size: 11px !important;
        line-height: 1.4 !important;
    }
    
    /* Ensure all child text elements inherit 11px */
    .a4-page table, 
    .a4-page th, 
    .a4-page td, 
    .a4-page p, 
    .a4-page span, 
    .a4-page div, 
    .a4-page label,
    .a4-page input {
        font-size: 11px !important;
    }
    
    .a4-page .doc-title,
    #wo-measure-area .doc-title {
        font-size: 16px !important;
    }
    
    .a4-page-content {
        flex-grow: 1;
    }
    .no-break {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    
    .box-checkbox {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 13px !important;
        height: 13px !important;
        border: 1px solid #000000 !important;
        box-sizing: border-box !important;
        flex-shrink: 0 !important;
        line-height: 1 !important;
        font-size: 10px !important;
        font-weight: bold !important;
        color: #000000 !important;
        background-color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* -------------------------------------------------------------------------
       2. PRINT ONLY STYLES (Native Browser Print Override)
       ------------------------------------------------------------------------- */
    @media print {
        @page {
            size: A4 portrait;
            margin: 15mm 15mm !important; /* Proper print margins */
        }
        /* Reset parent wrappers for clean full-bleed printing and remove borders/lines */
        html, body, .min-h-screen, .split-container, .wo-preview-panel {
            overflow: visible !important;
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
            position: static !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            background: white !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        header, sidebar, nav, button, .no-print, .action-toolbar, .wo-form-panel, aside, [class*="sidebar"], [class*="navbar"] {
            display: none !important;
        }
        body {
            background: white !important;
            color: black !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .a4-container {
            padding: 0 !important;
            margin: 0 !important;
            background: none !important;
        }
        .a4-page-wrapper {
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
            page-break-after: always !important;
            break-after: page !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .a4-page-wrapper:last-child,
        .a4-page-wrapper:last-of-type {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }
        .a4-page {
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            max-height: 267mm !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important; /* Handled natively by browser margin */
            page-break-after: avoid !important;
            break-after: avoid !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            box-shadow: none !important;
            border: none !important;
            transform: none !important; /* Turn off scale in print */
            position: static !important;
            overflow: hidden !important;
        }
        /* Force sharp borders in print */
        table, th, td {
            border-color: #000000 !important;
        }
        #wo-measure-area {
            display: none !important;
            height: 0 !important;
            max-height: 0 !important;
            overflow: hidden !important;
            position: absolute !important;
            visibility: hidden !important;
        }
    }

    #wo-measure-area {
        width: 210mm;
        padding: 15mm;
        box-sizing: border-box;
        visibility: hidden;
        position: absolute;
        top: -9999px;
        left: -9999px;
        overflow: hidden;
        height: auto;
        background: white;
        font-family: 'Times New Roman', Times, serif;
        color: black;
        font-size: 11px !important;
        line-height: 1.4 !important;
    }
    #wo-measure-area table, 
    #wo-measure-area th, 
    #wo-measure-area td, 
    #wo-measure-area p, 
    #wo-measure-area span, 
    #wo-measure-area div, 
    #wo-measure-area label,
    #wo-measure-area input {
        font-size: 11px !important;
    }
</style>

<div class="a4-container" x-data="{
    scale: 1,
    pages: [],
    
    init() {
        this.updateScale();
        window.addEventListener('resize', () => this.updateScale());
        if (this.$data && typeof this.$data.showPreview !== 'undefined') {
            this.$watch('showPreview', () => {
                setTimeout(() => this.updateScale(), 250);
            });
        }
        
        // Initial pagination
        this.paginateProducts();
        
        // Watches to trigger re-pagination
        this.$watch('products', () => this.paginateProducts());
        this.$watch('approvals', () => this.paginateProducts());
        this.$watch('remarks', () => this.paginateProducts());
        this.$watch('priority', () => this.paginateProducts());
        this.$watch('urgent_reason', () => this.paginateProducts());
        this.$watch('urgent_confirmed_by', () => this.paginateProducts());
        this.$watch('urgent_confirmed_at', () => this.paginateProducts());
        if (typeof this.selected_processes !== 'undefined') {
            this.$watch('selected_processes', () => this.paginateProducts());
        }
    },
    
    updateScale() {
        const container = this.$el.parentElement;
        if (!container) return;
        const containerWidth = container.clientWidth;
        const padding = 48; // Left + right padding
        const targetWidth = containerWidth - padding;
        const a4WidthPx = 210 * 96 / 25.4; // ~793.7px
        if (targetWidth > 0 && targetWidth < a4WidthPx) {
            this.scale = targetWidth / a4WidthPx;
        } else {
            this.scale = 1;
        }
        this.$el.style.setProperty('--scale', this.scale);
    },
    
    paginateProducts() {
        this.$nextTick(() => {
            const measureArea = document.getElementById('wo-measure-area');
            if (!measureArea) return;
            
            // Measure actual A4 height and padding in pixels
            const tempPage = document.createElement('div');
            tempPage.style.height = '297mm';
            tempPage.style.paddingTop = '15mm';
            tempPage.style.paddingBottom = '15mm';
            tempPage.style.visibility = 'hidden';
            tempPage.style.position = 'absolute';
            document.body.appendChild(tempPage);
            const pageHeight = tempPage.getBoundingClientRect().height;
            const style = window.getComputedStyle(tempPage);
            const paddingTop = parseFloat(style.paddingTop);
            const paddingBottom = parseFloat(style.paddingBottom);
            const paddingHeight = paddingTop + paddingBottom;
            document.body.removeChild(tempPage);
            
            const usablePageHeight = pageHeight - paddingHeight;
            
            const headerEl = document.getElementById('measure-header');
            const page1InfoEl = document.getElementById('measure-page1-info');
            const tableHeaderEl = document.getElementById('measure-table-header');
            const footerEl = document.getElementById('measure-footer');
            
            const headerHeight = headerEl ? headerEl.getBoundingClientRect().height : 0;
            const page1InfoHeight = page1InfoEl ? page1InfoEl.getBoundingClientRect().height : 0;
            const tableHeaderHeight = tableHeaderEl ? tableHeaderEl.getBoundingClientRect().height : 0;
            const footerHeight = footerEl ? footerEl.getBoundingClientRect().height : 0;
            const tableLabelEl = document.getElementById('measure-table-label');
            const tableLabelHeight = tableLabelEl ? tableLabelEl.getBoundingClientRect().height : 0;
            
            // Measure rows
            const rowElements = measureArea.querySelectorAll('.measure-product-row');
            const rowHeights = Array.from(rowElements).map(el => el.getBoundingClientRect().height);
            
            const products = this.products || [];
            
            if (products.length === 0) {
                this.pages = [{
                    pageNumber: 1,
                    products: [],
                    showInfo: true,
                    showFooter: true
                }];
                return;
            }
            
            let calculatedPages = [];
            let i = 0;
            let pageNum = 1;
            
            // Page 1 Overhead & Usable heights
            const gapHeight = 20; // 1.25rem = 20px from space-y-5
            const safetyBuffer = 8; // Safety buffer against sub-pixel font rendering variations
            
            // Page 1 Table Header height includes the attached data label (tableLabelHeight) + thead
            const page1TableHeaderHeight = tableLabelHeight + tableHeaderHeight;
            
            // Page 1 (no footer) has 2 gaps: header-info and info-table
            let page1Usable = usablePageHeight - (headerHeight + page1InfoHeight + page1TableHeaderHeight) - (2 * gapHeight) - safetyBuffer;
            
            // Page 1 with footer has 3 gaps: header-info, info-table, table-footer
            let page1UsableWithFooter = usablePageHeight - (headerHeight + page1InfoHeight + page1TableHeaderHeight + footerHeight) - (3 * gapHeight) - safetyBuffer;
            
            // Other pages (no footer) have 1 gap: header-table (only thead, no <p> label)
            let otherUsable = usablePageHeight - (headerHeight + tableHeaderHeight) - (1 * gapHeight) - safetyBuffer;
            
            // Other pages with footer have 2 gaps: header-table, table-footer
            let otherUsableWithFooter = usablePageHeight - (headerHeight + tableHeaderHeight + footerHeight) - (2 * gapHeight) - safetyBuffer;
            
            // Check if all rows + footer fit on Page 1
            let totalRowsHeight = rowHeights.reduce((a, b) => a + b, 0);
            if (totalRowsHeight <= page1UsableWithFooter) {
                calculatedPages.push({
                    pageNumber: 1,
                    products: [...products],
                    showInfo: true,
                    showFooter: true
                });
                this.pages = calculatedPages;
                return;
            }
            
            // Otherwise, fill Page 1
            let page1Rows = [];
            let page1Height = 0;
            while (i < products.length) {
                let rowH = rowHeights[i] || 28;
                if (page1Height + rowH <= page1Usable) {
                    page1Rows.push(products[i]);
                    page1Height += rowH;
                    i++;
                } else {
                    break;
                }
            }
            if (page1Rows.length === 0 && products.length > 0) {
                page1Rows.push(products[i]);
                i++;
            }
            calculatedPages.push({
                pageNumber: 1,
                products: page1Rows,
                showInfo: true,
                showFooter: false
            });
            
            // Other pages
            pageNum = 2;
            
            while (i < products.length) {
                let remainingRowsHeight = 0;
                for (let j = i; j < products.length; j++) {
                    remainingRowsHeight += rowHeights[j] || 28;
                }
                
                if (remainingRowsHeight <= otherUsableWithFooter) {
                    calculatedPages.push({
                        pageNumber: pageNum,
                        products: products.slice(i),
                        showInfo: false,
                        showFooter: true
                    });
                    i = products.length;
                    break;
                }
                
                let pageRows = [];
                let pageHeightAcc = 0;
                while (i < products.length) {
                    let rowH = rowHeights[i] || 28;
                    if (pageHeightAcc + rowH <= otherUsable) {
                        pageRows.push(products[i]);
                        pageHeightAcc += rowH;
                        i++;
                    } else {
                        break;
                    }
                }
                if (pageRows.length === 0 && i < products.length) {
                    pageRows.push(products[i]);
                    i++;
                }
                calculatedPages.push({
                    pageNumber: pageNum,
                    products: pageRows,
                    showInfo: false,
                    showFooter: false
                });
                pageNum++;
            }
            
            if (calculatedPages.length > 0 && !calculatedPages[calculatedPages.length - 1].showFooter) {
                calculatedPages.push({
                    pageNumber: pageNum,
                    products: [],
                    showInfo: false,
                    showFooter: true
                });
            }
            
            this.pages = calculatedPages;
        });
    }
}">
    
    <template x-for="(page, pIdx) in pages" :key="pIdx">
        <div class="a4-page-wrapper">
            <div class="a4-page">
            <div class="a4-page-content space-y-5">
                {{-- Document Header Table --}}
                <table class="w-full border-collapse border border-slate-900 text-xs text-slate-900" style="font-family: 'Times New Roman', Times, serif;">
                    <tbody>
                        <tr class="divide-x divide-slate-900">
                            <td class="w-[30%] p-2 text-center border-b border-slate-900 align-middle">
                                <img src="{{ asset('assets/image/sai-logo.png') }}" class="h-12 mx-auto object-contain mb-3">
                                <div class="text-[12px] font-bold uppercase tracking-tight leading-none text-center">PT SUMMIT ADYAWINSA INDONESIA</div>
                            </td>
                            <td class="w-[45%] p-3 text-center font-extrabold text-base doc-title uppercase tracking-wide border-b border-slate-900 align-middle">
                                WORK ORDER (SPK)
                            </td>
                            <td class="w-[25%] text-[11px] border-b border-slate-900 p-0">
                                <table class="w-full h-full text-[10px] border-collapse">
                                    <tbody>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900 w-[50%]">Document Number</td>
                                            <td class="p-1">: <span x-text="document_no"></span></td>
                                        </tr>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900">Department</td>
                                            <td class="p-1">: <span x-text="doc_department"></span></td>
                                        </tr>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900">Publish Date</td>
                                            <td class="p-1">: <span x-text="formatDateStr(doc_publish_date)"></span></td>
                                        </tr>
                                        <tr class="border-b border-slate-900">
                                            <td class="p-1 font-semibold border-r border-slate-900">Revision</td>
                                            <td class="p-1">: <span x-text="String(doc_revision_no).padStart(2, '0')"></span></td>
                                        </tr>
                                        <tr>
                                            <td class="p-1 font-semibold border-r border-slate-900">Page</td>
                                            <td class="p-1">: <span x-text="page.pageNumber + ' of ' + pages.length"></span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- Content specific to Page 1 --}}
                <template x-if="page.showInfo">
                    <div class="space-y-4">
                        {{-- SPK Info & Priority Block --}}
                        <div class="flex justify-between items-start text-xs text-slate-900">
                            <div class="space-y-1.5 text-xs">
                                <div>Release Date: <span class="font-bold" x-text="formatDateStr(released_at) || '—'"></span></div>
                                <div>No. <span class="font-bold" x-text="work_order_no"></span></div>
                                <div>To: <span class="font-bold" x-text="typeof isEditable !== 'undefined' ? (selected_processes.length ? (getDepartmentName(department_id) + (support_departments.length ? ' / ' + support_departments.map(d => d.name).join(', ') : '')) : '—') : (target_departments_full || '—')"></span></div>
                            </div>
                            <div class="text-[11px] w-32">
                                <span class="block font-bold mb-1.5">Priority:</span>
                                <div class="space-y-1">
                                    <label class="flex items-center gap-1.5 cursor-default">
                                        <div class="box-checkbox select-none">
                                            <span x-show="priority === 'URGENT'">&check;</span>
                                        </div>
                                        <span class="font-semibold text-[11px]">URGENT</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-default">
                                        <div class="box-checkbox select-none">
                                            <span x-show="priority === 'STANDARD'">&check;</span>
                                        </div>
                                        <span class="font-semibold text-[11px]">STANDARD</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-default">
                                        <div class="box-checkbox select-none">
                                            <span x-show="priority === 'LOW'">&check;</span>
                                        </div>
                                        <span class="font-semibold text-[11px]">LOW</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Dear Sir/Madam and Message with Urgent Confirmation (Side by Side) --}}
                        <div class="text-xs text-slate-900 space-y-1.5">
                            <div class="flex justify-between items-start gap-4">
                                <div class="space-y-1 self-end pb-0.5">
                                    <p>Dear Sir/Madam,</p>
                                    <p>Please prepare the items listed below:</p>
                                </div>
                                <div x-show="priority === 'URGENT'" class="w-52 flex-shrink-0">
                                    <table class="w-full border-collapse border-2 border-blue-700 text-xs text-blue-900 bg-blue-50/40">
                                        <tbody>
                                            <tr class="border-b border-blue-700 bg-blue-100/60">
                                                <td class="px-2 py-0.5 font-extrabold text-[10px] uppercase tracking-wider text-center text-blue-800">
                                                    Urgent Confirmed
                                                </td>
                                            </tr>
                                            <tr class="border-b border-blue-700">
                                                <td class="px-2 py-1.5 italic text-[10px] text-blue-900 text-center" x-text="urgent_reason || 'Waiting for Marketing GM urgent confirmation...'">
                                                </td>
                                            </tr>
                                            <tr class="text-[9px] font-semibold text-center text-blue-900 bg-blue-100/30">
                                                <td class="px-2 py-0.5">
                                                    Date: <span class="font-bold" x-text="urgent_confirmed_at || '—'"></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-y-1 gap-x-2 py-1">
                                <template x-for="proc in (typeof processesList !== 'undefined' ? processesList : (detailData ? detailData.processes : []))" :key="proc.id || proc.process_id">
                                    <template x-if="typeof isEditable !== 'undefined' ? selected_processes.map(Number).includes(Number(proc.id)) : true">
                                        <div class="flex items-center gap-1.5">
                                            <div class="box-checkbox select-none">&check;</div>
                                            <span class="text-[11px] text-black font-semibold" x-text="proc.process_name"></span>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Table 1: Parts List (BOM) --}}
                <div class="text-xs text-slate-900" x-show="page.products.length > 0 || page.pageNumber === 1">
                    <p class="font-bold mb-1.5" x-show="page.showInfo">Attached data or information:</p>
                    <table class="w-full text-center border-collapse border border-slate-900 text-xs">
                        <thead>
                            <tr class="divide-x divide-slate-900 border-b border-slate-900 font-bold bg-slate-50/50">
                                <th class="p-1.5 w-8">No</th>
                                <th class="p-1.5 w-20">Customer</th>
                                <th class="p-1.5 w-16">Model</th>
                                <th class="p-1.5">Part Number</th>
                                <th class="p-1.5">Part Name</th>
                                <th class="p-1.5">Additional Process</th>
                                <th class="p-1.5 w-24">Process Qty</th>
                                <th class="p-1.5">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="page.products.length === 0">
                                <tr>
                                    <td colspan="8" class="p-4 text-center text-slate-400 italic">No products added.</td>
                                </tr>
                            </template>
                            <template x-for="(prod, idx) in page.products" :key="idx">
                                <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle">
                                    <td class="p-1.5" x-text="products.indexOf(prod) + 1"></td>
                                    <td class="p-1.5" x-text="prod.customer_code"></td>
                                    <td class="p-1.5" x-text="prod.model_name"></td>
                                    <td class="p-1.5 font-bold" x-text="prod.customer_part_no"></td>
                                    <td class="p-1.5 text-left" x-text="prod.customer_part_name"></td>
                                    <td class="p-1.5 font-bold text-left" x-text="prod.add_process_name || '—'"></td>
                                    <td class="p-1.5 font-bold" x-text="(prod.add_process_qty || '0') + ' ' + (prod.add_process_unit || '')"></td>
                                    <td class="p-1.5 text-left" x-text="prod.remarks || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Content specific to the Last Page --}}
                <template x-if="page.showFooter">
                    <div class="space-y-4 no-break">
                        {{-- Table 2: SPK Target Schedule & Realization --}}
                        <div class="text-xs text-slate-900">
                            <table class="w-full text-center border-collapse border border-slate-900 text-xs">
                                <thead>
                                    <tr class="divide-x divide-slate-900 border-b border-slate-900 font-bold bg-slate-50/50">
                                        <th class="p-1.5 w-8">No</th>
                                        <th class="p-1.5">First Sample Date</th>
                                        <th class="p-1.5">DueDate (Plan)</th>
                                        <th class="p-1.5">DueDate Closed (Actual)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle">
                                        <td class="p-1.5">1</td>
                                        <td class="p-1.5" x-text="formatDateStr(first_sample_date) || '—'"></td>
                                        <td class="p-1.5 font-bold text-black" x-text="formatDateStr(due_date_plan) || '—'"></td>
                                        <td class="p-1.5 text-center font-bold text-rose-600">
                                            <template x-for="(dateStr, ruleId) in due_dates_closed" :key="ruleId">
                                                <div class="leading-relaxed" x-show="dateStr">
                                                    <span x-text="formatDateStr(dateStr)"></span>
                                                    <span x-text="' (' + getDeptCodeByRuleId(ruleId) + ')'" class="text-[9px] text-slate-500 font-semibold"></span>
                                                </div>
                                            </template>
                                            <template x-if="Object.values(due_dates_closed).filter(Boolean).length === 0">
                                                <span>—</span>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Remarks Block --}}
                        <div class="text-xs text-slate-900 border border-slate-900 p-2.5 bg-slate-50/50 space-y-1.5">
                            <div>
                                <span class="font-bold">Remarks (Marketing):</span>
                                <span class="ml-1" x-text="remarks || '—'"></span>
                            </div>
                            <template x-for="(app, idx) in approvals" :key="idx">
                                <div class="pl-3 text-slate-655" x-show="app.remarks">
                                    <span class="font-bold" x-text="app.approver_position + ' (' + app.department_code + '):'"></span>
                                    <span class="italic" x-text="'&ldquo;' + app.remarks + '&rdquo;'"></span>
                                </div>
                            </template>
                        </div>

                        {{-- Signature Table (Approval Workflow) --}}
                        <div class="table w-full border border-slate-900 text-xs text-slate-900 text-center">
                            <div class="table-row-group">
                                <div class="table-row font-bold">
                                    <div class="table-cell p-1.5 border-r border-b border-slate-900 last:border-r-0" :style="{ width: (100 / (1 + approvals.length)) + '%' }">Prepared</div>
                                    <template x-for="(step, idx) in approvals" :key="idx">
                                        <div class="table-cell p-1.5 border-r border-b border-slate-900 last:border-r-0" x-text="step.action_label" :style="{ width: (100 / (1 + approvals.length)) + '%' }"></div>
                                    </template>
                                </div>
                                <div class="table-row">
                                    {{-- Prepared / Creator --}}
                                    <div class="table-cell p-2 border-r border-slate-900 last:border-r-0 align-top" :style="{ width: (100 / (1 + approvals.length)) + '%' }">
                                        <div class="flex flex-col justify-between items-center h-24 w-full">
                                            <div class="flex flex-col items-center">
                                                <div class="inline-block border-2 border-emerald-600 text-emerald-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm transform -rotate-3 select-none origin-center">PREPARED</div>
                                                <div class="text-[9px] text-slate-500 mt-1" x-text="formatDateStr(created_at)"></div>
                                            </div>
                                            <div class="mt-auto">
                                                <div class="font-bold text-[11px]" x-text="created_by"></div>
                                                <div class="text-[9px] text-slate-400">Staff MKT</div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Approvers --}}
                                    <template x-for="(step, idx) in approvals" :key="idx">
                                        <div class="table-cell p-2 border-r border-slate-900 last:border-r-0 align-top" :style="{ width: (100 / (1 + approvals.length)) + '%' }">
                                            <div class="flex flex-col justify-between items-center h-24 w-full">
                                                <div class="flex flex-col items-center w-full">
                                                    <template x-if="step.status === 'Approved'">
                                                        <div class="flex flex-col items-center">
                                                            <div class="inline-block border-2 border-emerald-600 text-emerald-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm transform -rotate-3 select-none origin-center" x-text="(step.action_label || 'Checked').toUpperCase()"></div>
                                                            <div class="text-[9px] text-slate-500 mt-1" x-text="step.approved_at"></div>
                                                            <div x-show="step.due_date_closed" class="text-[9px] text-rose-600 font-bold mt-1">
                                                                Due Close: <span x-text="formatDateStr(step.due_date_closed)"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="step.status === 'Rejected'">
                                                        <div class="flex flex-col items-center">
                                                            <div class="inline-block border-2 border-rose-600 text-rose-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm transform -rotate-3 select-none origin-center">REJECTED</div>
                                                            <div class="text-[9px] text-slate-500 mt-1" x-text="step.approved_at"></div>
                                                        </div>
                                                    </template>
                                                    <template x-if="step.status === 'Pending'">
                                                        <div class="text-[10px] text-amber-500 font-bold italic animate-pulse py-1">PENDING</div>
                                                    </template>
                                                    <template x-if="step.status === 'Waiting'">
                                                        <div class="text-[9px] text-slate-400 py-1">WAITING</div>
                                                    </template>
                                                </div>
                                                <div class="mt-auto w-full text-center">
                                                    <div class="font-bold text-[11px] text-slate-900" x-text="step.approver_name || '—'"></div>
                                                    <div class="text-[9px] text-slate-400" x-text="step.approver_position"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

{{-- -------------------------------------------------------------------------
   3. HIDDEN MEASUREMENT AREA (2-Pass DOM Pagination Source)
   ------------------------------------------------------------------------- --}}
<div id="wo-measure-area">
    {{-- Header Table to measure --}}
    <div id="measure-header">
        <table class="w-full border-collapse border border-slate-900 text-xs text-slate-900" style="font-family: 'Times New Roman', Times, serif;">
            <tbody>
                <tr class="divide-x divide-slate-900">
                    <td class="w-[30%] p-2 text-center border-b border-slate-900 align-middle">
                        <img src="{{ asset('assets/image/sai-logo.png') }}" class="h-12 mx-auto object-contain mb-3">
                        <div class="text-[12px] font-bold uppercase tracking-tight leading-none text-center">PT SUMMIT ADYAWINSA INDONESIA</div>
                    </td>
                    <td class="w-[45%] p-3 text-center font-extrabold text-base doc-title uppercase tracking-wide border-b border-slate-900 align-middle">
                        WORK ORDER (SPK)
                    </td>
                    <td class="w-[25%] text-[11px] border-b border-slate-900 p-0">
                        <table class="w-full h-full text-[10px] border-collapse">
                            <tbody>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900 w-[50%]">Document Number</td>
                                    <td class="p-1">: <span x-text="document_no"></span></td>
                                </tr>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900">Department</td>
                                    <td class="p-1">: <span x-text="doc_department"></span></td>
                                </tr>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900">Publish Date</td>
                                    <td class="p-1">: <span x-text="formatDateStr(doc_publish_date)"></span></td>
                                </tr>
                                <tr class="border-b border-slate-900">
                                    <td class="p-1 font-semibold border-r border-slate-900">Revision</td>
                                    <td class="p-1">: <span x-text="String(doc_revision_no).padStart(2, '0')"></span></td>
                                </tr>
                                <tr>
                                    <td class="p-1 font-semibold border-r border-slate-900">Page</td>
                                    <td class="p-1">: <span>1 of 1</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Page 1 Info block to measure --}}
    <div id="measure-page1-info" class="space-y-4">
        <div class="flex justify-between items-start text-xs text-slate-900">
            <div class="space-y-1.5 text-xs">
                <div>Release Date: <span class="font-bold" x-text="formatDateStr(released_at) || '—'"></span></div>
                <div>No. <span class="font-bold" x-text="work_order_no"></span></div>
                <div>To: <span class="font-bold" x-text="typeof isEditable !== 'undefined' ? (selected_processes.length ? (getDepartmentName(department_id) + (support_departments.length ? ' / ' + support_departments.map(d => d.name).join(', ') : '')) : '—') : (target_departments_full || '—')"></span></div>
            </div>
            <div class="text-[11px] w-32">
                <span class="block font-bold mb-1.5">Priority:</span>
                <div class="space-y-1">
                    <label class="flex items-center gap-1.5 cursor-default">
                        <div class="box-checkbox select-none">
                            <span x-show="priority === 'URGENT'">&check;</span>
                        </div>
                        <span class="font-semibold text-[11px]">URGENT</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-default">
                        <div class="box-checkbox select-none">
                            <span x-show="priority === 'STANDARD'">&check;</span>
                        </div>
                        <span class="font-semibold text-[11px]">STANDARD</span>
                    </label>
                    <label class="flex items-center gap-1.5 cursor-default">
                        <div class="box-checkbox select-none">
                            <span x-show="priority === 'LOW'">&check;</span>
                        </div>
                        <span class="font-semibold text-[11px]">LOW</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="text-xs text-slate-900 space-y-1.5">
            <div class="flex justify-between items-start gap-4">
                <div class="space-y-1 self-end pb-0.5">
                    <p>Dear Sir/Madam,</p>
                    <p>Please prepare the items listed below:</p>
                </div>
                <div x-show="priority === 'URGENT'" class="w-52 flex-shrink-0">
                    <table class="w-full border-collapse border-2 border-blue-700 text-xs text-blue-900 bg-blue-50/40">
                        <tbody>
                            <tr class="border-b border-blue-700 bg-blue-100/60">
                                <td class="px-2 py-0.5 font-extrabold text-[10px] uppercase tracking-wider text-center text-blue-800">
                                    Urgent Confirmed
                                </td>
                            </tr>
                            <tr class="border-b border-blue-700">
                                <td class="px-2 py-1.5 italic text-[10px] text-blue-900 text-center" x-text="urgent_reason || 'Waiting for Marketing GM urgent confirmation...'">
                                </td>
                            </tr>
                            <tr class="text-[9px] font-semibold text-center text-blue-900 bg-blue-100/30">
                                <td class="px-2 py-0.5">
                                    Date: <span class="font-bold" x-text="urgent_confirmed_at || '—'"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-y-1 gap-x-2 py-1">
                <template x-for="proc in (typeof processesList !== 'undefined' ? processesList : (detailData ? detailData.processes : []))" :key="proc.id || proc.process_id">
                    <template x-if="typeof isEditable !== 'undefined' ? selected_processes.map(Number).includes(Number(proc.id)) : true">
                        <div class="flex items-center gap-1.5">
                            <div class="box-checkbox select-none">&check;</div>
                            <span class="text-[11px] text-black font-semibold" x-text="proc.process_name"></span>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>

    {{-- Table Header & Product Rows to measure --}}
    <div id="measure-table-container" class="text-xs text-slate-900">
        <p id="measure-table-label" class="font-bold mb-1.5">Attached data or information:</p>
        <table class="w-full text-center border-collapse border border-slate-900 text-xs">
            <thead id="measure-table-header">
                <tr class="divide-x divide-slate-900 border-b border-slate-900 font-bold bg-slate-50/50">
                    <th class="p-1.5 w-8">No</th>
                    <th class="p-1.5 w-20">Customer</th>
                    <th class="p-1.5 w-16">Model</th>
                    <th class="p-1.5">Part Number</th>
                    <th class="p-1.5">Part Name</th>
                    <th class="p-1.5">Additional Process</th>
                    <th class="p-1.5 w-24">Process Qty</th>
                    <th class="p-1.5">Remarks</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(prod, idx) in products" :key="idx">
                    <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle measure-product-row">
                        <td class="p-1.5" x-text="idx + 1"></td>
                        <td class="p-1.5" x-text="prod.customer_code"></td>
                        <td class="p-1.5" x-text="prod.model_name"></td>
                        <td class="p-1.5 font-bold" x-text="prod.customer_part_no"></td>
                        <td class="p-1.5 text-left" x-text="prod.customer_part_name"></td>
                        <td class="p-1.5 font-bold text-left" x-text="prod.add_process_name || '—'"></td>
                        <td class="p-1.5 font-bold" x-text="(prod.add_process_qty || '0') + ' ' + (prod.add_process_unit || '')"></td>
                        <td class="p-1.5 text-left" x-text="prod.remarks || '—'"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Footer to measure --}}
    <div id="measure-footer" class="space-y-4">
        <div class="text-xs text-slate-900">
            <table class="w-full text-center border-collapse border border-slate-900 text-xs">
                <thead>
                    <tr class="divide-x divide-slate-900 border-b border-slate-900 font-bold bg-slate-50/50">
                        <th class="p-1.5 w-8">No</th>
                        <th class="p-1.5">First Sample Date</th>
                        <th class="p-1.5">DueDate (Plan)</th>
                        <th class="p-1.5">DueDate Closed (Actual)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="divide-x divide-y divide-slate-900 border-b border-slate-900 align-middle">
                        <td class="p-1.5">1</td>
                        <td class="p-1.5" x-text="formatDateStr(first_sample_date) || '—'"></td>
                        <td class="p-1.5 font-bold text-black" x-text="formatDateStr(due_date_plan) || '—'"></td>
                        <td class="p-1.5 text-center font-bold text-rose-600">
                            <template x-for="(dateStr, ruleId) in due_dates_closed" :key="ruleId">
                                <div class="leading-relaxed" x-show="dateStr">
                                    <span x-text="formatDateStr(dateStr)"></span>
                                    <span x-text="' (' + getDeptCodeByRuleId(ruleId) + ')'" class="text-[9px] text-slate-500 font-semibold"></span>
                                </div>
                            </template>
                            <template x-if="Object.values(due_dates_closed).filter(Boolean).length === 0">
                                <span>—</span>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-xs text-slate-900 border border-slate-900 p-2.5 bg-slate-50/50 space-y-1.5">
            <div>
                <span class="font-bold">Remarks (Marketing):</span>
                <span class="ml-1" x-text="remarks || '—'"></span>
            </div>
            <template x-for="(app, idx) in approvals" :key="idx">
                <div class="pl-3 text-slate-655" x-show="app.remarks">
                    <span class="font-bold" x-text="app.approver_position + ' (' + app.department_code + '):'"></span>
                    <span class="italic" x-text="'&ldquo;' + app.remarks + '&rdquo;'"></span>
                </div>
            </template>
        </div>

        <div class="table w-full border border-slate-900 text-xs text-slate-900 text-center">
            <div class="table-row-group">
                <div class="table-row font-bold">
                    <div class="table-cell p-1.5 border-r border-b border-slate-900 last:border-r-0" :style="{ width: (100 / (1 + approvals.length)) + '%' }">Prepared</div>
                    <template x-for="(step, idx) in approvals" :key="idx">
                        <div class="table-cell p-1.5 border-r border-b border-slate-900 last:border-r-0" x-text="step.action_label" :style="{ width: (100 / (1 + approvals.length)) + '%' }"></div>
                    </template>
                </div>
                <div class="table-row">
                    <div class="table-cell p-2 border-r border-slate-900 last:border-r-0 align-top" :style="{ width: (100 / (1 + approvals.length)) + '%' }">
                        <div class="flex flex-col justify-between items-center h-24 w-full">
                            <div class="flex flex-col items-center">
                                <div class="inline-block border-2 border-emerald-600 text-emerald-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm transform -rotate-3 select-none origin-center">PREPARED</div>
                                <div class="text-[9px] text-slate-500 mt-1" x-text="formatDateStr(created_at)"></div>
                            </div>
                            <div class="mt-auto">
                                <div class="font-bold text-[11px]" x-text="created_by"></div>
                                <div class="text-[9px] text-slate-400">Staff MKT</div>
                            </div>
                        </div>
                    </div>
                    <template x-for="(step, idx) in approvals" :key="idx">
                        <div class="table-cell p-2 border-r border-slate-900 last:border-r-0 align-top" :style="{ width: (100 / (1 + approvals.length)) + '%' }">
                            <div class="flex flex-col justify-between items-center h-24 w-full">
                                <div class="flex flex-col items-center w-full">
                                    <template x-if="step.status === 'Approved'">
                                        <div class="flex flex-col items-center">
                                            <div class="inline-block border-2 border-emerald-600 text-emerald-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm transform -rotate-3 select-none origin-center" x-text="(step.action_label || 'Checked').toUpperCase()"></div>
                                            <div class="text-[9px] text-slate-500 mt-1" x-text="step.approved_at"></div>
                                            <div x-show="step.due_date_closed" class="text-[9px] text-rose-600 font-bold mt-1">
                                                Due Close: <span x-text="formatDateStr(step.due_date_closed)"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="step.status === 'Rejected'">
                                        <div class="flex flex-col items-center">
                                            <div class="inline-block border-2 border-rose-600 text-rose-600 text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-sm transform -rotate-3 select-none origin-center">REJECTED</div>
                                            <div class="text-[9px] text-slate-500 mt-1" x-text="step.approved_at"></div>
                                        </div>
                                    </template>
                                    <template x-if="step.status === 'Pending'">
                                        <div class="text-[10px] text-amber-500 font-bold italic animate-pulse py-1">PENDING</div>
                                    </template>
                                    <template x-if="step.status === 'Waiting'">
                                        <div class="text-[9px] text-slate-400 py-1">WAITING</div>
                                    </template>
                                </div>
                                <div class="mt-auto w-full text-center">
                                    <div class="font-bold text-[11px] text-slate-900" x-text="step.approver_name || '—'"></div>
                                    <div class="text-[9px] text-slate-400" x-text="step.approver_position"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
</div>