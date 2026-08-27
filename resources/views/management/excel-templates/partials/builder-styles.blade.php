<style>
    .excel-table-container { overflow: auto; position: relative; user-select: none; max-height: 100%; width: 100%; isolation: isolate; z-index: 1; }
    
    .excel-table {
        table-layout: fixed !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin: 0 !important;
        background-color: #ffffff;
    }
    .dark .excel-table {
        background-color: #0f172a;
    }

    /* 100% Fixed Sticky Row & Column Headers (Isolated Stacking Context) */
    .excel-table th.col-header { 
        position: sticky; 
        top: 0; 
        z-index: 20; 
        background-color: #f1f5f9; 
        text-align: center; 
        border: 1px solid #cbd5e1; 
        font-weight: 600; 
        color: #475569;
        box-shadow: inset 0 -1px 0 #cbd5e1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        box-sizing: border-box;
    }
    .dark .excel-table th.col-header {
        background-color: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }

    .excel-table th.row-header { 
        position: sticky; 
        left: 0; 
        z-index: 20; 
        width: 36px; 
        min-width: 36px; 
        max-width: 36px; 
        background-color: #f1f5f9; 
        text-align: center; 
        border: 1px solid #cbd5e1; 
        font-weight: 600; 
        color: #475569; 
        box-shadow: inset -1px 0 0 #cbd5e1; 
        box-sizing: border-box; 
    }
    .dark .excel-table th.row-header {
        background-color: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }

    .excel-table th.corner-header { 
        position: sticky; 
        top: 0; 
        left: 0; 
        z-index: 25; 
        background-color: #e2e8f0; 
        width: 36px; 
        min-width: 36px; 
        max-width: 36px; 
        border: 1px solid #cbd5e1; 
        box-sizing: border-box; 
    }
    .dark .excel-table th.corner-header {
        background-color: #334155;
        border-color: #475569;
    }

    .excel-table tbody tr {
        content-visibility: auto;
        contain-intrinsic-size: 26px;
    }

    /* Cells default subtle gridline (MS Excel overflow behavior: text overflows if next cell is empty) */
    .excel-table td { 
        border: 1px solid #e2e8f0; 
        cursor: pointer; 
        padding: 2px 4px; 
        height: 24px; 
        white-space: nowrap; 
        background-color: transparent; 
        vertical-align: middle; 
        position: relative; 
        overflow: visible; 
        font-size: 11px; 
        box-sizing: border-box; 
    }
    .dark .excel-table td {
        border-color: #334155;
    }

    /* Cells with explicit content / background mask any text overflow from previous cells */
    .excel-table td.cell-has-value {
        background-color: #ffffff;
        z-index: 2;
    }
    .dark .excel-table td.cell-has-value {
        background-color: #0f172a;
    }
    .excel-table td.cell-has-fill {
        z-index: 2;
    }
    
    /* Ensure cell text content can wrap or overflow over empty neighboring cells smoothly */
    .excel-table td .cell-content-wrap {
        display: inline-block;
        position: relative;
        z-index: 3;
        pointer-events: none;
        max-width: none;
        white-space: nowrap;
        vertical-align: middle;
    }
    .excel-table td.cell-wrapped .cell-content-wrap {
        max-width: 100%;
        white-space: pre-wrap !important;
        word-break: break-word !important;
        overflow: hidden;
    }

    .excel-table td * {
        pointer-events: none;
    }
    
    /* Custom Cell Borders matching Excel styles & colors */
    .excel-table td.cell-formula { background-color: #fef9c3 !important; z-index: 2; }
    .excel-table td.cell-mapped-single { background-color: #dcfce7 !important; border: 2px solid #16a34a !important; z-index: 2; }
    .excel-table td.cell-mapped-loop { background-color: #e0f2fe !important; border: 2px dashed #0284c7 !important; z-index: 2; }
    .excel-table td.cell-formula-ref { 
        outline: 2px dashed #ec4899 !important; 
        outline-offset: -2px; 
        background-color: #fce7f3 !important; 
        z-index: 10 !important; 
    }
    
    /* When mapped cell is NOT selected, keep badge and content strictly inside cell boundary without bleeding */
    .excel-table td.cell-mapped-single:not(.cell-selected),
    .excel-table td.cell-mapped-loop:not(.cell-selected) {
        overflow: hidden !important;
    }
    .excel-table td.cell-mapped-single .cell-content-wrap,
    .excel-table td.cell-mapped-loop .cell-content-wrap {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Active Selected Cell */
    .excel-table td.cell-selected { 
        outline: 2px solid #2563eb !important; 
        outline-offset: -1px; 
        box-shadow: inset 0 0 0 1px #2563eb !important; 
        background-color: #eff6ff !important; 
        z-index: 35 !important; 
        overflow: visible !important;
    }
    .dark .excel-table td.cell-selected {
        background-color: #1e293b !important;
    }

    /* Badge Default: compact with ellipsis to strictly preserve cell bounds */
    .badge-cell { 
        font-size: 9px; 
        padding: 1px 4px; 
        border-radius: 3px; 
        display: inline-block; 
        margin-left: 3px; 
        font-weight: 700; 
        letter-spacing: 0.02em; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.15); 
        max-width: calc(100% - 4px); 
        overflow: hidden; 
        text-overflow: ellipsis; 
        white-space: nowrap; 
        vertical-align: middle; 
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .badge-cell-single {
        border: 1px solid #14532d !important;
    }
    .badge-cell-loop {
        border: 1px solid #075985 !important;
    }

    /* Active Selected Cell Badge Overlay Expansion */
    .excel-table td.cell-selected .badge-cell {
        position: absolute;
        left: 2px;
        top: 50%;
        transform: translateY(-50%);
        max-width: none !important;
        width: max-content !important;
        overflow: visible !important;
        white-space: nowrap !important;
        z-index: 50 !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.28), 0 0 0 1.5px rgba(255,255,255,0.8) !important;
        padding: 2px 7px !important;
        font-size: 10px !important;
        pointer-events: none;
    }

    /* Drag and Drop Drop-Target Hover Feedback */
    .excel-table td.cell-drop-hover {
        outline: 2px dashed #059669 !important;
        outline-offset: -2px;
        background-color: #ecfdf5 !important;
        z-index: 36 !important;
    }
    .dark .excel-table td.cell-drop-hover {
        background-color: #064e3b !important;
        outline-color: #34d399 !important;
    }

    /* Ensure Select2 Dropdowns open above everything (including fullscreen z-50) */
    .select2-container--open {
        z-index: 999999 !important;
    }
</style>
