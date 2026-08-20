@extends('layouts.app')

@section('title', 'Visual Mapping Studio · Promise Management')
@section('page_title', 'Visual Mapping Studio')

@push('styles')
<style>
    .excel-table-container { overflow: auto; position: relative; user-select: none; max-height: 100%; width: 100%; isolation: isolate; z-index: 1; }
    
    /* 100% Fixed Sticky Row & Column Headers (Isolated Stacking Context) */
    .excel-table th.col-header { 
        position: sticky; 
        top: 0; 
        z-index: 10; 
        background-color: #f1f5f9; 
        text-align: center; 
        border: 1px solid #cbd5e1; 
        font-weight: 600; 
        color: #475569;
        box-shadow: inset 0 -1px 0 #cbd5e1;
    }
    .excel-table th.row-header { 
        position: sticky; 
        left: 0; 
        z-index: 10; 
        width: 36px; 
        background-color: #f1f5f9; 
        text-align: center; 
        border: 1px solid #cbd5e1; 
        font-weight: 600; 
        color: #475569;
        box-shadow: inset -1px 0 0 #cbd5e1;
    }
    .excel-table th.corner-header { 
        position: sticky; 
        top: 0; 
        left: 0; 
        z-index: 15; 
        background-color: #e2e8f0; 
        width: 36px;
        border: 1px solid #cbd5e1;
    }

    /* Cells default subtle gridline (MS Excel overflow behavior: text overflows if next cell is empty) */
    .excel-table td { 
        border: 1px solid #e2e8f0; 
        cursor: pointer; 
        padding: 2px 4px; 
        height: 24px; 
        white-space: nowrap; 
        background: #fff; 
        vertical-align: middle; 
        position: relative; 
        overflow: visible; 
        font-size: 11px; 
    }
    
    /* Ensure cell text content can wrap or overflow over empty neighboring cells smoothly */
    .excel-table td .cell-content-wrap {
        display: inline-block;
        position: relative;
        z-index: 2;
        pointer-events: none;
        max-width: 100%;
    }

    .excel-table td * {
        pointer-events: none;
    }
    
    /* Custom Cell Borders matching Excel styles & colors */
    .excel-table td.cell-formula { background-color: #fef9c3 !important; }
    .excel-table td.cell-mapped-single { background-color: #dcfce7 !important; border: 2px solid #16a34a !important; }
    .excel-table td.cell-mapped-loop { background-color: #e0f2fe !important; border: 2px dashed #0284c7 !important; }
    .excel-table td.cell-formula-ref { 
        outline: 2px dashed #ec4899 !important; 
        outline-offset: -2px; 
        background-color: #fce7f3 !important; 
        z-index: 4 !important; 
    }
    .excel-table td.cell-selected { 
        outline: 1px solid #2563eb !important; 
        outline-offset: -1px;
        box-shadow: inset 0 0 0 1px #2563eb !important;
        background-color: #eff6ff !important; 
        z-index: 5 !important; 
    }
    .badge-cell { 
        font-size: 9px; 
        padding: 1px 4px; 
        border-radius: 3px; 
        display: inline-block; 
        margin-left: 3px; 
        font-weight: 700; 
        letter-spacing: 0.02em;
        box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }
    .badge-cell-single {
        border: 1px solid #14532d !important; /* dark green border */
    }
    .badge-cell-loop {
        border: 1px solid #075985 !important; /* dark sky border */
    }

    /* Ensure Select2 Dropdowns open above everything (including fullscreen z-50) */
    .select2-container--open {
        z-index: 999999 !important;
    }
</style>
@endpush

@section('content')
<div id="mainStudioCanvas" 
     class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-white dark:bg-slate-900 flex-col border-t border-slate-300 dark:border-slate-800"
     :class="isFullscreen ? 'fixed inset-0 z-50 bg-white dark:bg-slate-900 w-screen h-screen !mt-0 !h-screen' : ''"
     x-data="visualMapperStudio()">

    <!-- ===== TOP COMPACT METADATA BAR ===== -->
    <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 flex-shrink-0 text-xs text-slate-800 dark:text-slate-100 gap-3 relative z-10">
        
        <!-- Left: Action Buttons (Back & Save) -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('management.excel-templates.index') }}"
               class="inline-flex items-center gap-1.5 px-3 h-8 bg-white dark:bg-slate-800 hover:bg-slate-50 border border-slate-300 dark:border-slate-700 rounded text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-2xs transition-colors">
                <i class="fa-solid fa-arrow-left text-[11px]"></i> Back
            </a>
            <button id="btnSaveConfig"
                    class="inline-flex items-center gap-1.5 px-3.5 h-8 bg-emerald-600 hover:bg-emerald-700 rounded text-xs font-bold text-white shadow-xs transition-colors">
                <i class="fa-solid fa-floppy-disk text-[11px]"></i> Save Mapping
            </button>
        </div>

        <!-- Center: Template Info & Sheet Switcher Card -->
        <div class="flex items-center gap-2 px-3 h-8 border border-slate-300 dark:border-slate-700 rounded bg-white dark:bg-slate-950 shadow-2xs flex-1 min-w-0 justify-between">
            <div class="flex items-center gap-2 min-w-0">
                <i class="fa-solid fa-file-excel text-emerald-600 text-base shrink-0"></i>
                <span class="font-bold text-slate-800 dark:text-slate-100 truncate text-xs" title="{{ $template->template_name }}">{{ $template->template_name }}</span>
                
                <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded text-[10px] font-bold uppercase tracking-wide shrink-0">{{ $template->template_type }}</span>
                @if(($template->direction ?? 'export') === 'import')
                    <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 shrink-0">
                        <i class="fa-solid fa-cloud-arrow-up text-[10px] text-purple-500"></i> Import
                    </span>
                @else
                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1 shrink-0">
                        <i class="fa-solid fa-cloud-arrow-down text-[10px] text-blue-500"></i> Export
                    </span>
                @endif
                <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 rounded text-[10px] font-mono font-bold shrink-0">Rev {{ $template->revision ?? '0' }}</span>
            </div>
            
            <!-- Multi-Sheet Selector -->
            <div class="flex items-center gap-1.5 ms-2 ps-3 border-s border-slate-200 dark:border-slate-800 shrink-0">
                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                    <i class="fa-regular fa-folder-open text-amber-500"></i> Sheet:
                </span>
                <select @change="changeSheet($event.target.value)" 
                        @if(empty($sheetNames) || count($sheetNames) <= 1) disabled @endif
                        class="bg-slate-100 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2.5 h-6 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded cursor-pointer disabled:bg-slate-100 dark:disabled:bg-slate-900 disabled:text-slate-400 disabled:cursor-not-allowed">
                    @if(!empty($sheetNames))
                        @foreach($sheetNames as $sIdx => $sName)
                            <option value="{{ route('management.excel-templates.builder', [$template->id, 'sheet' => $sIdx]) }}" 
                                    {{ $sIdx == ($activeSheetIndex ?? 0) ? 'selected' : '' }}>
                                {{ $sName }}
                            </option>
                        @endforeach
                    @else
                        <option value="">Sheet1</option>
                    @endif
                </select>
            </div>
        </div>

        <!-- Right: Zoom Controls, Fullscreen & Sidebar Inspector Toggle -->
        <div class="flex items-center gap-1.5 shrink-0">
            <div class="flex items-center bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded px-2.5 h-8 gap-2 text-xs shadow-2xs">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-[11px]"></i>
                <button @click="zoomOut()" class="hover:text-blue-600 font-bold px-1 transition-colors" title="Zoom Out (-)"><i class="fa-solid fa-minus text-[10px]"></i></button>
                <input type="range" min="50" max="150" step="5" x-model="zoomLevel" @input="handleSliderZoom()" class="w-16 h-1 bg-slate-200 rounded-lg appearance-none cursor-pointer">
                <button @click="zoomIn()" class="hover:text-blue-600 font-bold px-1 transition-colors" title="Zoom In (+)"><i class="fa-solid fa-plus text-[10px]"></i></button>
                <span class="text-[11px] font-mono font-bold text-slate-700 dark:text-slate-300 w-9 text-right" x-text="zoomLevel + '%'"></span>
                <button @click="resetZoom()" class="text-[10px] text-blue-600 font-semibold hover:underline ms-0.5">Reset</button>
            </div>

            <button @click="payloadDrawerOpen = !payloadDrawerOpen" 
                    class="inline-flex items-center gap-1.5 px-3 h-8 border text-xs font-semibold rounded shadow-2xs transition-colors"
                    :class="payloadDrawerOpen ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50'"
                    title="Toggle Config Payload Drawer from bottom">
                <i class="fa-solid fa-code text-[11px]" :class="payloadDrawerOpen ? 'text-white' : 'text-indigo-600'"></i>
                <span>JSON Payload</span>
            </button>

            <button @click="toggleFullscreen()" 
                    class="inline-flex items-center gap-1.5 px-3 h-8 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded hover:bg-slate-50 shadow-2xs transition-colors"
                    :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen Main Content'">
                <i class="fa-solid" :class="isFullscreen ? 'fa-compress text-slate-500' : 'fa-expand text-slate-500'"></i>
                <span x-text="isFullscreen ? 'Exit' : 'Fullscreen'"></span>
            </button>

            <button @click="toggleSidebar()" 
                    class="inline-flex items-center gap-1.5 px-3 h-8 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded hover:bg-slate-50 shadow-2xs transition-colors">
                <i class="fa-solid text-blue-600" :class="sidebarOpen ? 'fa-angles-right' : 'fa-sliders'"></i>
                <span x-text="sidebarOpen ? 'Collapse' : 'Inspector'"></span>
            </button>
        </div>
    </div>

    <!-- ===== MAIN SPREADSHEET CANVAS ===== -->
    <div class="flex-1 flex overflow-hidden relative">

        <div class="flex-1 flex flex-col overflow-hidden bg-slate-100 dark:bg-slate-950 p-2 relative"
             @wheel="handleWheelZoom($event)">
            
            <!-- BOTTOM DRILLDOWN JSON PAYLOAD DRAWER (Positioned over Canvas z-20) -->
            <div x-show="payloadDrawerOpen" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-full opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0 opacity-100"
                 x-transition:leave-end="translate-y-full opacity-0"
                 class="absolute bottom-0 left-0 right-0 z-20 bg-slate-950 text-emerald-400 border-t-2 border-indigo-600 shadow-2xl flex flex-col max-h-[320px]">
                <div class="px-4 py-2 bg-slate-900 border-b border-slate-800 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-code text-indigo-400 text-xs"></i>
                        <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Live Config JSON Payload</h4>
                        <span class="text-[10px] bg-indigo-900/60 text-indigo-300 px-1.5 py-0.5 rounded font-mono">mapping_config</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="btnClearAll" class="px-2 py-0.5 text-[10px] text-rose-400 hover:text-white bg-rose-950/60 hover:bg-rose-900 border border-rose-800 rounded transition-colors">
                            <i class="fa-solid fa-rotate-left me-1"></i> Reset Mapping
                        </button>
                        <button @click="payloadDrawerOpen = false" class="text-slate-400 hover:text-white p-1" title="Close Drawer">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>
                </div>
                <pre id="jsonConfigViewer" class="p-4 text-[11px] font-mono overflow-auto flex-1 select-all bg-slate-950/90 text-emerald-400 leading-relaxed"></pre>
            </div>
            
            <!-- Full Canvas Loading Overlay (Increased Opacity) -->
            <div x-show="canvasLoading" 
                 x-transition:enter="transition opacity ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition opacity ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute inset-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md flex flex-col items-center justify-center gap-3">
                <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <div class="text-center">
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Rendering Spreadsheet Canvas...</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Parsing Excel grid, styles, and cell bounds</p>
                </div>
            </div>
            
            <!-- Compact Legend Bar -->
            <div class="flex items-center justify-between px-3 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 mb-2 text-xs flex-shrink-0">
                <span class="text-slate-500 text-[11px]"><i class="fa-solid fa-circle-info text-blue-500 me-1"></i>Scroll normally to move. Press & Hold <strong>Ctrl + Scroll</strong> to Zoom smoothly.</span>
                <div class="flex items-center gap-3 text-[11px]">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-emerald-200 border border-emerald-600 inline-block"></span> Single Cell</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-sky-200 border border-sky-600 inline-block"></span> Table Loop</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 bg-amber-200 border border-amber-500 inline-block"></span> Formula (Locked)</span>
                </div>
            </div>

            <!-- Spreadsheet Grid Container -->
            <div class="flex-1 excel-table-container border border-slate-300 dark:border-slate-800 bg-white dark:bg-slate-900" id="tableScrollContainer">
                <div class="relative inline-block min-w-full">
                    


                    <table class="excel-table w-full text-slate-800 dark:text-slate-200" 
                           id="excelGrid"
                           :style="`font-size: ${11 * (zoomLevel / 100)}px;`">
                        <thead>
                            <tr>
                                <th class="corner-header">#</th>
                                @if(!empty($gridData[0]))
                                    @foreach($gridData[0] as $colCell)
                                        <th class="col-header" :style="`width: ${({{ $colWidths[$colCell['col']] ?? 85 }}) * (zoomLevel / 100)}px;`">{{ $colCell['col'] }}</th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gridData as $rowIndex => $rowCells)
                                <tr :style="`height: ${({{ $rowHeights[$rowIndex + 1] ?? 26 }}) * (zoomLevel / 100)}px;`">
                                    <th class="row-header">{{ $rowIndex + 1 }}</th>
                                    @foreach($rowCells as $cell)
                                        @if(isset($cell['merge']['hidden']) && $cell['merge']['hidden'])
                                            {{-- Hide merged slave cell --}}
                                        @else
                                            @php
                                                $bTop = $cell['borders']['top']['active'] ? "border-top: {$cell['borders']['top']['css']} !important;" : '';
                                                $bBottom = $cell['borders']['bottom']['active'] ? "border-bottom: {$cell['borders']['bottom']['css']} !important;" : '';
                                                $bLeft = $cell['borders']['left']['active'] ? "border-left: {$cell['borders']['left']['css']} !important;" : '';
                                                $bRight = $cell['borders']['right']['active'] ? "border-right: {$cell['borders']['right']['css']} !important;" : '';

                                                $style = "{$bTop} {$bBottom} {$bLeft} {$bRight} ";
                                                if ($cell['fill_color']) $style .= "background-color: {$cell['fill_color']}; ";
                                                if ($cell['font_color']) $style .= "color: {$cell['font_color']}; ";
                                                if ($cell['is_bold']) $style .= "font-weight: bold; ";
                                                if ($cell['is_italic']) $style .= "font-style: italic; ";
                                                if ($cell['align']) $style .= "text-align: {$cell['align']}; ";
                                                if (!empty($cell['font_family'])) $style .= "font-family: '{$cell['font_family']}', 'Segoe UI', Calibri, sans-serif; ";
                                                
                                                if (!empty($cell['valign'])) {
                                                    $vaMap = ['top' => 'top', 'center' => 'middle', 'bottom' => 'bottom', 'justify' => 'middle'];
                                                    $va = $vaMap[$cell['valign']] ?? 'middle';
                                                    $style .= "vertical-align: {$va}; ";
                                                }

                                                $colSpan = $cell['merge']['colspan'] ?? 1;
                                                $rowSpan = $cell['merge']['rowspan'] ?? 1;

                                                $wrapText = !empty($cell['wrap_text']);
                                                $cellValueStr = (string)$cell['value'];

                                                $rotStyle = '';
                                                $rotDeg = (int)($cell['text_rotation'] ?? 0);
                                                if ($rotDeg !== 0) {
                                                    if ($rotDeg == 255) {
                                                        // Vertical stacked text
                                                        $rotStyle = 'writing-mode: vertical-lr; text-orientation: upright; display: inline-block; white-space: nowrap;';
                                                    } else {
                                                        // In PhpSpreadsheet:
                                                        // 0..90: counter-clockwise rotation (upward). 90 = vertical up (-90deg in CSS)
                                                        // -1..-90 or 91..180: clockwise rotation (downward). -90 or 180 = vertical down (90deg in CSS)
                                                        $cssDeg = 0;
                                                        if ($rotDeg > 0 && $rotDeg <= 90) {
                                                            $cssDeg = -$rotDeg; // 90 in Excel -> -90deg in CSS
                                                        } elseif ($rotDeg < 0 && $rotDeg >= -90) {
                                                            $cssDeg = -$rotDeg; // -90 in Excel -> 90deg in CSS
                                                        } elseif ($rotDeg > 90 && $rotDeg <= 180) {
                                                            $cssDeg = 90 - $rotDeg; // 180 in Excel -> -90deg in CSS
                                                        }

                                                        if ($cssDeg !== 0) {
                                                            $rotStyle = "display: inline-block; transform: rotate({$cssDeg}deg); transform-origin: center center; white-space: nowrap;";
                                                        }
                                                    }
                                                }
                                                
                                                $wrapStyle = '';
                                                if ($wrapText) {
                                                    $wrapStyle = 'white-space: pre-wrap !important; word-break: break-word !important; line-height: 1.25 !important; display: block !important;';
                                                } elseif (str_contains($cellValueStr, "\n")) {
                                                    $wrapStyle = 'white-space: pre-line !important; line-height: 1.25 !important; display: block !important;';
                                                }

                                                $finalWrapStyle = trim("{$rotStyle} {$wrapStyle}");
                                                $fontSizePt = (float)($cell['font_size'] ?? 11);
                                            @endphp
                                            <td data-cell="{{ $cell['cell'] }}" 
                                                data-col="{{ $cell['col'] }}" 
                                                data-row="{{ $cell['row'] }}"
                                                data-colspan="{{ $colSpan }}"
                                                data-rowspan="{{ $rowSpan }}"
                                                data-is-formula="{{ $cell['is_formula'] ? 'true' : 'false' }}"
                                                data-raw-formula="{{ $cell['raw_formula'] ?? '' }}"
                                                @if($colSpan > 1) colspan="{{ $colSpan }}" @endif
                                                @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif
                                                :style="`height: ${({{ $rowHeights[$rowIndex + 1] ?? 26 }}) * (zoomLevel / 100)}px; ${ {{ var_export($wrapText, true) }} ? '' : 'line-height: ' + (({{ $rowHeights[$rowIndex + 1] ?? 26 }}) * (zoomLevel / 100) - 4) + 'px;' } font-size: ${ {{ $fontSizePt }} * (zoomLevel / 100)}pt; padding: ${1 * (zoomLevel / 100)}px ${4 * (zoomLevel / 100)}px; {{ $style }}`"
                                                class="relative {{ $cell['is_formula'] ? 'cell-formula' : '' }}">
                                                
                                                <!-- Embedded Images attached directly to cell anchor -->
                                                <template x-for="(img, idx) in images.filter(i => i.cell === '{{ $cell['cell'] }}')" :key="idx">
                                                    <img :src="img.src" 
                                                         :style="`left: ${(img.offsetX || 0) * (zoomLevel / 100)}px; top: ${(img.offsetY || 0) * (zoomLevel / 100)}px; width: ${(img.width || 100) * (zoomLevel / 100)}px; height: ${(img.height || 50) * (zoomLevel / 100)}px;`"
                                                         class="absolute z-20 pointer-events-none shadow-xs object-contain max-w-none max-h-none" />
                                                </template>

                                                <span class="cell-content-wrap" style="{{ $finalWrapStyle }}">{!! nl2br(e($cellValueStr)) !!}</span>
                                                @if($cell['is_formula'])
                                                    <i class="fa-solid fa-lock text-amber-500 text-[9px] ms-0.5" title="Formula protected"></i>
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- COLLAPSIBLE INSPECTOR PANEL -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="w-96 bg-white dark:bg-slate-900 border-l border-slate-300 dark:border-slate-800 flex flex-col flex-shrink-0 z-30 shadow-lg h-full overflow-hidden">
            
            <!-- FIXED INSPECTOR HEADER -->
            <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950 shrink-0">
                <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-blue-600"></i> Cell Mapping Inspector
                </h3>
                <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors"><i class="fa-solid fa-angles-right"></i></button>
            </div>

            <!-- SCROLLABLE INSPECTOR BODY -->
            <div class="p-3 space-y-3 flex-1 overflow-y-auto" x-data="{ accSection1: true, accSection2: true, accSection3: true, accSection4: true }">
                <div id="noSelectionNotice" class="flex flex-col items-center justify-center text-center py-12 text-slate-400 dark:text-slate-500">
                    <i class="fa-regular fa-hand-pointer text-4xl mb-3 text-slate-300 dark:text-slate-600"></i>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Click any cell on the Excel preview grid</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">to open mapping controls.</p>
                </div>

                <div id="mappingControlForm" class="hidden space-y-3">
                    <!-- Selected Cell Header Card with Compact Ultra-Clean Layout -->
                    <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 dark:from-blue-950/40 dark:to-indigo-950/40 p-2.5 border border-blue-200/80 dark:border-blue-800/60 rounded-md space-y-2 shadow-2xs">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5 shrink-0">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cell:</span>
                                <span class="text-xl font-black text-blue-600 dark:text-blue-400 font-mono leading-none" id="selectedCellLabel">C9</span>
                            </div>
                            
                            <!-- Badges in clean right-aligned container -->
                            <div id="cellInfoBadgesContainer" class="flex flex-wrap items-center justify-end gap-1 text-[10px] min-w-0">
                                <span id="badgeMappingType" class="px-2 py-0.5 bg-sky-600 text-white font-bold rounded shadow-2xs max-w-full text-right leading-tight break-words">Unmapped</span>
                                <span id="badgeCellOrigin" class="px-1.5 py-0.5 bg-slate-200/80 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-medium rounded truncate hidden max-w-[150px]">Excel Text</span>
                            </div>
                        </div>

                        <!-- Active Mapping Preview Row -->
                        <div id="cellActiveValuePreview" class="text-[10px] font-mono text-slate-700 dark:text-slate-200 bg-white/90 dark:bg-slate-900/90 px-2 py-1 rounded border border-blue-100 dark:border-blue-900/50 hidden flex items-center gap-1.5 min-w-0">
                            <span id="cellActiveValuePrefix" class="text-[9px] font-bold text-slate-400 font-sans uppercase shrink-0">Mapped:</span>
                            <span id="cellActiveValueText" class="font-bold text-blue-700 dark:text-blue-300 truncate break-all"></span>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- SECTION 1: ACCORDION - DATA SOURCE MODE    -->
                    <!-- ========================================== -->
                    <div class="border border-slate-200 dark:border-slate-800 rounded-md overflow-hidden bg-slate-50 dark:bg-slate-950">
                        <button type="button" @click="accSection1 = !accSection1" class="w-full p-2.5 bg-slate-100/80 dark:bg-slate-900 flex items-center justify-between text-left transition-colors">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5 uppercase tracking-wide">
                                <i class="fa-solid fa-database text-blue-600 dark:text-blue-400"></i> 1. Data Source Mode
                            </span>
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="accSection1 ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="accSection1" class="p-3 space-y-2 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 dark:bg-slate-950 rounded-md">
                                <button type="button" id="sourceTypeVariableBtn" title="Map cell directly to system database field variables" class="py-2 px-2 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-xs rounded flex flex-col items-center justify-center transition-all">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-table-columns text-xs"></i>
                                        <span class="text-xs font-bold">System Variable</span>
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-normal mt-0.5">Database fields</span>
                                </button>
                                <button type="button" id="sourceTypeStaticBtn" title="Enter custom free text string or Excel dynamic formula" class="py-2 px-2 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded flex flex-col items-center justify-center transition-all">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-font text-xs text-emerald-600"></i>
                                        <span class="text-xs font-bold">Free Text / Formula</span>
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-normal mt-0.5">Static text & formulas</span>
                                </button>
                            </div>

                            <!-- Variable Source Box -->
                            <div id="variableSourceBox">
                                <!-- Accordion Smart Suggestion Container -->
                                <div id="smartSuggestBanner" class="hidden mb-2 border border-purple-200 dark:border-purple-800 rounded overflow-hidden bg-purple-50/50 dark:bg-purple-950/30">
                                    <button id="suggestAccordionToggle" type="button" class="w-full px-2.5 py-1.5 bg-purple-100/80 dark:bg-purple-900/40 hover:bg-purple-200/80 dark:hover:bg-purple-900/60 flex items-center justify-between text-left transition-colors">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-wand-magic-sparkles text-purple-600 dark:text-purple-400 text-xs"></i>
                                            <span class="text-xs font-semibold text-purple-800 dark:text-purple-200">Smart Mapping Suggestions</span>
                                            <span id="suggestMatchBadge" class="px-1.5 py-0.2 bg-purple-600 text-white text-[10px] font-bold rounded-full">0</span>
                                        </div>
                                        <i id="suggestAccordionIcon" class="fa-solid fa-chevron-down text-purple-500 text-[10px] transition-transform duration-200"></i>
                                    </button>
                                    <div id="suggestAccordionBody" class="hidden p-2 border-t border-purple-200 dark:border-purple-800/60 bg-purple-50/40 dark:bg-purple-950/20">
                                        <div id="suggestedPillsContainer" class="flex flex-wrap gap-1">
                                            <!-- Dynamic pills rendered via JS -->
                                        </div>
                                    </div>
                                </div>

                                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target System Field Variable</label>
                                <select id="globalFieldSelect" class="w-full">
                                    <option value="">-- Select System Field Variable --</option>
                                    <optgroup label="SYSTEM PRESET / UTILITIES">
                                        <option value="auto_number" data-group="utility" data-type="integer">[Auto Number] Row Index (1, 2, 3...)</option>
                                    </optgroup>
                                    @php
                                        $groupedFields = $systemFields->groupBy('group');
                                    @endphp
                                    @foreach($groupedFields as $groupName => $fields)
                                        <optgroup label="GROUP: {{ strtoupper($groupName) }}">
                                            @foreach($fields as $field)
                                                <option value="{{ $field->field_key }}" data-group="{{ $field->group ?? $groupName }}" data-type="{{ $field->data_type ?? 'string' }}">{{ $field->label }} ({{ $field->field_key }})</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Custom Text / Formula Box -->
                            <div id="staticSourceBox" class="hidden space-y-2">
                                <label class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Free Text or Formula Input</label>
                                <input type="text" id="staticValueInput" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 h-9 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 rounded font-mono" placeholder="e.g. JAC270C-45/45 or =C{row}*0.9">
                                
                                <!-- Convert to Dynamic Formula Action Button -->
                                <div class="pt-0.5">
                                    <button id="btnConvertDynamicFormula" type="button" class="w-full py-1.5 px-3 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/50 dark:hover:bg-emerald-800/80 text-emerald-800 dark:text-emerald-200 text-xs font-semibold rounded border border-emerald-300 dark:border-emerald-700 flex items-center justify-center gap-1.5 transition-colors">
                                        <i class="fa-solid fa-wand-magic-sparkles text-emerald-600 dark:text-emerald-400"></i> Convert to Dynamic Formula ({row})
                                    </button>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 leading-tight">
                                        Click to convert static cell numbers (e.g. <code class="bg-slate-100 dark:bg-slate-800 px-1 font-mono text-emerald-600">=C15*D15</code>) into dynamic row references (<code class="bg-slate-100 dark:bg-slate-800 px-1 font-mono text-emerald-600">=C{row}*D{row}</code>) for loops.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- SECTION 2: ACCORDION - MAPPING STRUCTURE   -->
                    <!-- ========================================== -->
                    <div class="border border-slate-200 dark:border-slate-800 rounded-md overflow-hidden bg-slate-50 dark:bg-slate-950">
                        <button type="button" @click="accSection3 = !accSection3" class="w-full p-2.5 bg-slate-100/80 dark:bg-slate-900 flex items-center justify-between text-left transition-colors">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5 uppercase tracking-wide">
                                <i class="fa-solid fa-layer-group text-indigo-600 dark:text-indigo-400"></i> 2. Target Structure & Loop Config
                            </span>
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="accSection3 ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="accSection3" class="p-3 space-y-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <!-- Tab Mode Selector -->
                            <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 dark:bg-slate-950 rounded-md">
                                <button id="tabSingleBtn" type="button" title="Single cell mapping for headers, labels, or static fields" class="py-2 px-2 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-xs rounded flex flex-col items-center justify-center transition-all">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-tag text-xs"></i>
                                        <span class="text-xs font-bold">Single Field</span>
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-normal mt-0.5">Fixed cell mapping</span>
                                </button>
                                <button id="tabLoopBtn" type="button" title="Dynamic repeater loop for table rows and nested items" class="py-2 px-2 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded flex flex-col items-center justify-center transition-all">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-rotate text-xs text-sky-500"></i>
                                        <span class="text-xs font-bold">Table Loop</span>
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-normal mt-0.5">Dynamic table repeater</span>
                                </button>
                            </div>

                            <!-- Single Mapping Tab Content -->
                            <div id="singleFieldBox" class="space-y-3 pt-1">
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 italic">Assigns the selected cell directly to a single field or free text formula value.</p>
                            </div>

                            <!-- Loop Mapping Tab Content -->
                            <div id="loopFieldBox" class="hidden space-y-3 pt-1">
                                <!-- Loop Strategy Card Grid -->
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Loop Strategy / Mode</label>
                                    <select id="loopModeSelect" class="hidden">
                                        <option value="flat">Single-Row Loop (Flat Table)</option>
                                        <option value="nested_block">Nested Parent-Child Block (Multi-Row Repeater)</option>
                                    </select>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <button type="button" data-loop-mode="flat" title="Repeat parent item data line-by-line in a simple flat list" class="loop-mode-btn p-2 border rounded text-left transition-all bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-xs">
                                            <div class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-list text-xs"></i>
                                                <span class="text-[11px] font-bold">Flat Table</span>
                                            </div>
                                            <p class="text-[9px] text-slate-500 dark:text-slate-400 leading-tight mt-1">Single-row list repeater</p>
                                        </button>
                                        <button type="button" data-loop-mode="nested_block" title="Parent item block containing nested child process rows" class="loop-mode-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-left transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                            <div class="flex items-center gap-1.5">
                                                <i class="fa-solid fa-layer-group text-xs"></i>
                                                <span class="text-[11px] font-bold">Nested Block</span>
                                            </div>
                                            <p class="text-[9px] text-slate-500 dark:text-slate-400 leading-tight mt-1">Parent block with child rows</p>
                                        </button>
                                    </div>
                                </div>

                                <!-- Direction & Behavior Cards Container -->
                                <div id="directionBehaviorCardContainer" class="space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Loop Direction</label>
                                            <select id="loopDirectionSelect" class="hidden">
                                                <option value="down">Vertical (Down / Rows)</option>
                                                <option value="right">Horizontal (Right / Columns)</option>
                                            </select>
                                            <div class="grid grid-cols-2 gap-1">
                                                <button type="button" data-dir-val="down" title="Repeat rows downwards" class="dir-btn py-1.5 px-1 border rounded text-center transition-all bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-xs">
                                                    <i class="fa-solid fa-arrow-down-short-wide text-xs block"></i>
                                                    <span class="text-[9px] font-bold block mt-0.5">Vertical</span>
                                                </button>
                                                <button type="button" data-dir-val="right" title="Repeat columns rightwards" class="dir-btn py-1.5 px-1 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                                    <i class="fa-solid fa-arrow-right-long text-xs block"></i>
                                                    <span class="text-[9px] font-bold block mt-0.5">Horizontal</span>
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Row Behavior</label>
                                            <select id="loopInsertBehaviorSelect" class="hidden">
                                                <option value="insert_duplicate">Insert New Rows & Copy Style</option>
                                                <option value="overwrite">Overwrite Existing Cells (No Insert)</option>
                                            </select>
                                            <div class="grid grid-cols-2 gap-1">
                                                <button type="button" data-behavior-val="insert_duplicate" title="Insert extra new rows pushing content down" class="behavior-btn py-1.5 px-1 border rounded text-center transition-all bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-xs">
                                                    <i class="fa-solid fa-square-plus text-xs block"></i>
                                                    <span class="text-[9px] font-bold block mt-0.5">Insert</span>
                                                </button>
                                                <button type="button" data-behavior-val="overwrite" title="Overwrite fixed cells without inserting rows" class="behavior-btn py-1.5 px-1 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                                    <i class="fa-solid fa-pen-to-square text-xs block"></i>
                                                    <span class="text-[9px] font-bold block mt-0.5">Overwrite</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Sheet Loop Active Info Banner -->
                                <div id="sheetLoopNoticeBanner" class="hidden p-2 bg-indigo-50/90 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 rounded text-[10px] text-indigo-800 dark:text-indigo-200 flex items-center gap-1.5">
                                    <i class="fa-solid fa-circle-info text-indigo-600 dark:text-indigo-400 text-xs shrink-0"></i>
                                    <span>Sheet Loop Active: Each Parent Item creates 1 Sheet Tab. Child rows repeat inside each tab.</span>
                                </div>

                                <!-- Group Identifier & Blank Rows -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Group Identifier</label>
                                        <input type="text" id="loopGroupName" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2.5 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-sky-500 rounded font-mono" placeholder="e.g. tooling_detail">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Blank Rows After</label>
                                        <input type="number" id="blankRowsAfterInput" min="0" max="20" value="0" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2.5 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-sky-500 rounded font-mono" placeholder="0">
                                    </div>
                                </div>

                                <div id="stopConditionContainer" class="hidden pt-2 border-t border-slate-200 dark:border-slate-800">
                                    <label class="block text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wide mb-1">
                                        Visual Stop Condition 
                                        <span class="text-[9px] text-slate-400 font-normal lowercase">(Active for Overwrite & Horizontal)</span>
                                    </label>
                                    <select id="stopConditionType" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2.5 h-8 text-xs text-slate-800 dark:text-slate-100 mb-1.5 focus:outline-none focus:border-sky-500 rounded">
                                        <option value="cell_value_contains">Cell Value Contains</option>
                                        <option value="cell_value_equals">Cell Value Exact Equals</option>
                                        <option value="is_empty">Cell Is Blank / Empty</option>
                                    </select>
                                    <input type="text" id="stopConditionValue" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2.5 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-sky-500 rounded" placeholder="e.g. TOTAL COST">
                                </div>

                                <!-- Sheet Loop (Split Sheet per Parent Item) Configuration Card -->
                                <div class="p-2.5 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-md space-y-1.5">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" id="splitSheetPerParentToggle" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                        <span class="text-xs font-bold text-indigo-950 dark:text-indigo-200 uppercase tracking-wide">
                                            Enable Sheet Loop (1 Sheet per Parent)
                                        </span>
                                    </label>
                                    <p class="text-[10px] text-slate-600 dark:text-slate-400 leading-tight">
                                        Clones worksheet per parent item. Child process rows loop inside each tab.
                                    </p>
                                    <div id="sheetNamingContainer" class="hidden pt-1">
                                        <label class="block text-[10px] font-bold text-indigo-900 dark:text-indigo-300 mb-1">Sheet Tab Naming Field</label>
                                        <select id="sheetNameFieldSelect" class="w-full">
                                            <option value="">Auto (Default: Part No / Item Index)</option>
                                            @foreach($groupedFields as $groupName => $fields)
                                                <optgroup label="GROUP: {{ strtoupper($groupName) }}">
                                                    @foreach($fields as $field)
                                                        <option value="{{ $field->field_key }}">{{ $field->label }} ({{ $field->field_key }})</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- SECTION 3: ACCORDION - CONDITIONAL RULES   -->
                    <!-- ========================================== -->
                    <div class="border border-slate-200 dark:border-slate-800 rounded-md overflow-hidden bg-slate-50 dark:bg-slate-950">
                        <button type="button" @click="accSection4 = !accSection4" class="w-full p-2.5 bg-slate-100/80 dark:bg-slate-900 flex items-center justify-between text-left transition-colors">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5 uppercase tracking-wide">
                                <i class="fa-solid fa-code-branch text-amber-500"></i> 3. Conditional Cell Rules (IF-THEN)
                            </span>
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="accSection4 ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="accSection4" class="p-3 space-y-2.5 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-snug">
                                Define dynamic conditional logic (e.g. <i>If <b>ebd_category</b> equals <b>"jig"</b>, write value to cell <b>I14</b></i>).
                            </p>

                            <!-- Rule Builder Form -->
                            <div class="p-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-md space-y-2">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">IF Source Variable</label>
                                    <select id="ruleSourceFieldSelect" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1.5 text-slate-800 dark:text-slate-100">
                                        <option value="">-- Select Source Field --</option>
                                        @foreach($groupedFields as $groupName => $fields)
                                            <optgroup label="GROUP: {{ strtoupper($groupName) }}">
                                                @foreach($fields as $field)
                                                    <option value="{{ $field->field_key }}">{{ $field->label }} ({{ $field->field_key }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-1.5">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Operator</label>
                                        <select id="ruleOperatorSelect" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1 text-slate-800 dark:text-slate-100">
                                            <option value="equals">Equals (==)</option>
                                            <option value="not_equals">Not Equals (!=)</option>
                                            <option value="contains">Contains</option>
                                            <option value="starts_with">Starts With</option>
                                            <option value="ends_with">Ends With</option>
                                            <option value="greater_than">Greater Than (&gt;)</option>
                                            <option value="greater_equal">Greater or Equal (&gt;=)</option>
                                            <option value="less_than">Less Than (&lt;)</option>
                                            <option value="less_equal">Less or Equal (&lt;=)</option>
                                            <option value="is_empty">Is Empty</option>
                                            <option value="is_not_empty">Is Not Empty</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Match Value</label>
                                        <input type="text" id="ruleMatchValueInput" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1 text-slate-800 dark:text-slate-100" placeholder="e.g. jig">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">THEN Target Cell / Column</label>
                                    <input type="text" id="ruleTargetCellInput" class="w-full text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1.5 text-blue-600 dark:text-blue-400 uppercase" placeholder="e.g. I14 or I">
                                </div>

                                <div class="space-y-1">
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Output Value Source (IF True)</label>
                                    <select id="ruleOutputTypeSelect" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1 text-slate-800 dark:text-slate-100">
                                        <option value="field_value">Use System Field Value</option>
                                        <option value="static_value">Use Custom Free Text / Value</option>
                                    </select>

                                    <div id="ruleOutputFieldBox" class="pt-1">
                                        <select id="ruleOutputFieldSelect" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1.5 text-slate-800 dark:text-slate-100">
                                            <option value="">-- Select Output Field --</option>
                                            @foreach($groupedFields as $groupName => $fields)
                                                <optgroup label="GROUP: {{ strtoupper($groupName) }}">
                                                    @foreach($fields as $field)
                                                        <option value="{{ $field->field_key }}">{{ $field->label }} ({{ $field->field_key }})</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div id="ruleOutputStaticBox" class="hidden pt-1">
                                        <input type="text" id="ruleOutputStaticInput" class="w-full text-xs font-mono border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1.5 text-slate-800 dark:text-slate-100" placeholder="e.g. Jig Processed">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">ELSE Fallback Text (Optional)</label>
                                    <input type="text" id="ruleElseStaticInput" class="w-full text-xs font-mono border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded p-1 text-slate-800 dark:text-slate-100" placeholder="e.g. - (Leave blank if none)">
                                </div>

                                <button id="btnAddConditionalRule" type="button" class="w-full py-1.5 px-3 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded shadow-2xs transition-colors flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-plus text-xs"></i> Add Conditional Rule
                                </button>
                            </div>

                            <!-- List of Active Conditional Rules -->
                            <div class="space-y-1.5 pt-1">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide block">Active Conditional Rules</span>
                                <div id="activeRulesList" class="space-y-1 max-h-40 overflow-y-auto">
                                    <!-- Rendered dynamically via JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ========================================== -->
                    <!-- SECTION 4: ACCORDION - RENDER FORMAT       -->
                    <!-- ========================================== -->
                    <div class="border border-slate-200 dark:border-slate-800 rounded-md overflow-hidden bg-slate-50 dark:bg-slate-950">
                        <button type="button" @click="accSection2 = !accSection2" class="w-full p-2.5 bg-slate-100/80 dark:bg-slate-900 flex items-center justify-between text-left transition-colors">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5 uppercase tracking-wide">
                                <i class="fa-solid fa-wand-magic-sparkles text-purple-600 dark:text-purple-400"></i> 4. Output & Render Format
                            </span>
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200" :class="accSection2 ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="accSection2" class="p-3 space-y-2 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                            <!-- Hidden Real Select for Single Render Type -->
                            <select id="singleRenderType" class="hidden">
                                <option value="default" selected>Default Template (Inherit)</option>
                                <option value="text">General (No Format / Overwrite)</option>
                                <option value="number">Numeric / Decimal</option>
                                <option value="currency">Currency (Rp #,##0)</option>
                                <option value="percentage">Percentage (%)</option>
                                <option value="date">Date (dd-mm-yyyy)</option>
                                <option value="long_date">Long Date (dd mmmm yyyy)</option>
                                <option value="image">Dynamic Image / Stamp</option>
                                <option value="qr">Dynamic QR Code</option>
                            </select>

                            <!-- Visual Card Grid Selector for Render Type -->
                            <div class="grid grid-cols-3 gap-1.5 pt-1" id="renderTypeButtonGrid">
                                <button type="button" data-render-val="default" title="Inherit original format from Excel template" class="render-type-btn p-2 border rounded text-center transition-all bg-white dark:bg-slate-900 border-purple-500 text-purple-600 dark:text-purple-400 shadow-xs font-bold ring-1 ring-purple-500/30">
                                    <i class="fa-solid fa-file-excel text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">Template Default</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">Inherit Format</span>
                                </button>
                                <button type="button" data-render-val="text" title="General format without specific numeric code (Overwrite template format)" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-solid fa-font text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">General</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">No Format</span>
                                </button>
                                <button type="button" data-render-val="number" title="Format values as numeric decimal numbers" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-solid fa-hashtag text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">Numeric</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">Decimal #0</span>
                                </button>
                                <button type="button" data-render-val="currency" title="Format numbers as Rupiah currency (Rp)" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-solid fa-rupiah-sign text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">Currency</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">Rp #,##0</span>
                                </button>
                                <button type="button" data-render-val="percentage" title="Format numbers as percentage (%)" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-solid fa-percent text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">Percentage</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">Format 0.00%</span>
                                </button>
                                <button type="button" data-render-val="date" title="Format date as dd-mm-yyyy" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-regular fa-calendar-days text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">Date</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">dd-mm-yyyy</span>
                                </button>
                                <button type="button" data-render-val="long_date" title="Format long date as dd mmmm yyyy" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-solid fa-calendar-day text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">Long Date</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">dd mmmm yyyy</span>
                                </button>
                                <button type="button" data-render-val="image" title="Embed dynamic image asset or stamp" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-regular fa-image text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">Image</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">Stamp/Logo</span>
                                </button>
                                <button type="button" data-render-val="qr" title="Generate dynamic QR code barcode from text/URL" class="render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                                    <i class="fa-solid fa-qrcode text-sm mb-1 block"></i>
                                    <span class="text-[10px] font-bold block leading-none">QR Code</span>
                                    <span class="text-[8px] text-slate-400 block mt-0.5">Barcode</span>
                                </button>
                            </div>

                            <div id="imageSizeContainer" class="hidden pt-1.5 border-t border-slate-200 dark:border-slate-800">
                                <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Image Bounds Size (Width x Height px)</label>
                                <input type="text" id="singleImageSize" class="w-full bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2.5 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-purple-500 font-mono rounded" placeholder="e.g. 100x40" value="100x40">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FIXED INSPECTOR BOTTOM ACTION BAR (ASSIGN & UNSET BUTTONS) -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 shrink-0">
                <div id="fixedSingleActionGroup" class="flex gap-2">
                    <button id="btnAssignSingle" disabled class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white text-xs font-bold rounded shadow-xs transition-colors flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-check text-xs"></i> Assign Single Mapping
                    </button>
                    <button id="btnUnsetSingle" type="button" class="hidden px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded transition-colors" title="Unset / Remove mapping from selected cell">
                        <i class="fa-solid fa-trash-can me-1"></i> Unset
                    </button>   
                </div>
                <div id="fixedLoopActionGroup" class="hidden flex gap-2">
                    <button id="btnAssignLoop" disabled class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white text-xs font-bold rounded shadow-xs transition-colors flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-check text-xs"></i> Assign Table Loop Column
                    </button>
                    <button id="btnUnsetLoop" type="button" class="hidden px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded transition-colors" title="Unset / Remove loop column from selected cell">
                        <i class="fa-solid fa-trash-can me-1"></i> Unset
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function visualMapperStudio() {
        return {
            sidebarOpen: true,
            payloadDrawerOpen: false,
            isFullscreen: false,
            canvasLoading: true,
            zoomLevel: 100,
            images: @json($images ?? []),
            colWidths: @json($colWidths ?? []),
            rowHeights: @json($rowHeights ?? []),

            init() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.canvasLoading = false;
                        this.updateImagePositions();
                    }, 200);
                });
            },

            updateImagePositions() {
                const container = document.getElementById('tableScrollContainer');
                if (!container) return;
                const containerRect = container.getBoundingClientRect();
                
                this.images.forEach(img => {
                    const cellTd = document.querySelector(`#excelGrid td[data-cell="${img.cell}"]`);
                    if (cellTd) {
                        const tdRect = cellTd.getBoundingClientRect();
                        img._computedTop = (tdRect.top - containerRect.top + container.scrollTop + (img.offsetY || 0));
                        img._computedLeft = (tdRect.left - containerRect.left + container.scrollLeft + (img.offsetX || 0));
                        img._computedWidth = img.width || cellTd.offsetWidth;
                        img._computedHeight = img.height || cellTd.offsetHeight;
                    }
                });
            },

            changeSheet(url) {
                if (!url) return;
                this.canvasLoading = true;
                window.location.href = url;
            },

            toggleSidebar() {
                this.sidebarOpen = !this.sidebarOpen;
            },

            toggleFullscreen() {
                const elem = document.getElementById('mainStudioCanvas');
                if (!this.isFullscreen) {
                    if (elem.requestFullscreen) {
                        elem.requestFullscreen();
                    } else if (elem.webkitRequestFullscreen) {
                        elem.webkitRequestFullscreen();
                    } else if (elem.msRequestFullscreen) {
                        elem.msRequestFullscreen();
                    }
                    this.isFullscreen = true;
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.msExitFullscreen) {
                        document.msExitFullscreen();
                    }
                    this.isFullscreen = false;
                }
            },

            zoomIn() {
                if (this.zoomLevel < 150) this.zoomLevel = Math.min(150, parseInt(this.zoomLevel) + 10);
            },

            zoomOut() {
                if (this.zoomLevel > 50) this.zoomLevel = Math.max(50, parseInt(this.zoomLevel) - 10);
            },

            resetZoom() {
                this.zoomLevel = 100;
            },

            handleSliderZoom() {},

            handleWheelZoom(e) {
                if (!e.ctrlKey) return;
                e.preventDefault();
                if (e.deltaY < 0) {
                    if (this.zoomLevel < 150) this.zoomLevel = Math.min(150, parseInt(this.zoomLevel) + 5);
                } else {
                    if (this.zoomLevel > 50) this.zoomLevel = Math.max(50, parseInt(this.zoomLevel) - 5);
                }
            },

            getImageStyle(img) {
                const cellTd = document.querySelector(`#excelGrid td[data-cell="${img.cell}"]`);
                const gridTable = document.getElementById('excelGrid');
                if (!cellTd || !gridTable) return 'display: none;';

                const scale = (parseInt(this.zoomLevel) || 100) / 100;
                
                // Position relative to table container
                const left = (cellTd.offsetLeft + (img.offsetX || 0));
                const top = (cellTd.offsetTop + (img.offsetY || 0));
                const width = (img.width || (cellTd.offsetWidth / scale)) * scale;
                const height = (img.height || (cellTd.offsetHeight / scale)) * scale;
                
                return `left: ${left}px; top: ${top}px; width: ${width}px; height: ${height}px; opacity: 1; pointer-events: none; max-width: none; max-height: none;`;
            }
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        const templateId = @json($template->id);
        let mappingConfig = @json($template->mapping_config ?? ['template_type' => $template->template_type, 'single_fields' => [], 'table_loops' => []]);
        
        if (!mappingConfig.single_fields) mappingConfig.single_fields = [];
        if (!mappingConfig.table_loops) mappingConfig.table_loops = [];

        mappingConfig.table_loops.forEach(loop => {
            if (!loop.direction) loop.direction = 'down';
            if (!loop.insert_behavior) loop.insert_behavior = 'insert_duplicate';
        });

        let currentSelectedCell = null;

        const excelGrid = document.getElementById('excelGrid');
        const noSelectionNotice = document.getElementById('noSelectionNotice');
        const mappingControlForm = document.getElementById('mappingControlForm');
        const selectedCellLabel = document.getElementById('selectedCellLabel');
        const jsonConfigViewer = document.getElementById('jsonConfigViewer');

        // Init Select2 for global system field variable dropdown with custom styling
        $('#globalFieldSelect').select2({
            placeholder: '-- Select System Field Variable --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#mainStudioCanvas')
        }).on('change', function() {
            const val = $(this).val();
            document.getElementById('btnAssignSingle').disabled = !val;
            document.getElementById('btnAssignLoop').disabled = !val;

            const selectedOpt = $(this).find('option:selected');
            const dataType = selectedOpt.data('type');
            if (dataType === 'number' || dataType === 'decimal') {
                updateRenderTypeCards('number');
            } else if (dataType === 'currency' || dataType === 'money') {
                updateRenderTypeCards('currency');
            } else if (dataType === 'date' || dataType === 'datetime') {
                updateRenderTypeCards('date');
            } else if (dataType === 'image') {
                updateRenderTypeCards('image');
            } else if (dataType === 'qr') {
                updateRenderTypeCards('qr');
            } else {
                updateRenderTypeCards('default');
            }
            toggleImageSizeVisibility();

            if (typeof getAutoGroupName === 'function' && !loopGroupName.dataset.userModified) {
                const mode = document.getElementById('loopModeSelect').value || 'flat';
                loopGroupName.value = getAutoGroupName(mode);
            }
        });

        // Init Select2 for Sheet Tab Naming Field dropdown
        $('#sheetNameFieldSelect').select2({
            placeholder: 'Auto (Default: Part No / Item Index)',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#mainStudioCanvas')
        }).on('change', function() {
            updateCurrentGroupSheetLoopConfig();
        });

        // Init Select2 for IF Source Variable dropdown with tags support
        $('#ruleSourceFieldSelect').select2({
            placeholder: '-- Select IF Source Variable or type custom variable --',
            allowClear: true,
            width: '100%',
            tags: true,
            dropdownParent: $('#mainStudioCanvas')
        });

        // Init Select2 for Output Field dropdown with tags support
        $('#ruleOutputFieldSelect').select2({
            placeholder: '-- Select Output Field or type custom variable --',
            allowClear: true,
            width: '100%',
            tags: true,
            dropdownParent: $('#mainStudioCanvas')
        });

        const tabSingleBtn = document.getElementById('tabSingleBtn');
        const tabLoopBtn = document.getElementById('tabLoopBtn');
        const singleFieldBox = document.getElementById('singleFieldBox');
        const loopFieldBox = document.getElementById('loopFieldBox');

        tabSingleBtn.addEventListener('click', () => {
            singleFieldBox.classList.remove('hidden');
            loopFieldBox.classList.add('hidden');
            document.getElementById('fixedSingleActionGroup').classList.remove('hidden');
            document.getElementById('fixedLoopActionGroup').classList.add('hidden');
            tabSingleBtn.className = "py-2 px-2 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-xs rounded flex flex-col items-center justify-center transition-all";
            tabLoopBtn.className = "py-2 px-2 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded flex flex-col items-center justify-center transition-all";
        });

        tabLoopBtn.addEventListener('click', () => {
            loopFieldBox.classList.remove('hidden');
            singleFieldBox.classList.add('hidden');
            document.getElementById('fixedLoopActionGroup').classList.remove('hidden');
            document.getElementById('fixedSingleActionGroup').classList.add('hidden');
            tabLoopBtn.className = "py-2 px-2 bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-xs rounded flex flex-col items-center justify-center transition-all";
            tabSingleBtn.className = "py-2 px-2 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded flex flex-col items-center justify-center transition-all";
        });

        const loopGroupName = document.getElementById('loopGroupName');
        const stopConditionType = document.getElementById('stopConditionType');
        const stopConditionValue = document.getElementById('stopConditionValue');

        const singleRenderType = document.getElementById('singleRenderType');
        const singleImageSize = document.getElementById('singleImageSize');
        const imageSizeContainer = document.getElementById('imageSizeContainer');

        function toggleImageSizeVisibility() {
            const val = singleRenderType.value;
            if (val === 'image' || val === 'qr') {
                imageSizeContainer.classList.remove('hidden');
            } else {
                imageSizeContainer.classList.add('hidden');
            }
        }

        singleRenderType.addEventListener('change', toggleImageSizeVisibility);

        function renderJSONViewer() {
            if (mappingConfig.single_fields) {
                mappingConfig.single_fields.forEach(item => {
                    if (item.render_type === 'default') {
                        delete item.render_type;
                    }
                });
            }
            if (mappingConfig.table_loops) {
                mappingConfig.table_loops.forEach(loop => {
                    if (!loop.direction) loop.direction = 'down';
                    if (!loop.insert_behavior) loop.insert_behavior = 'insert_duplicate';
                    if (loop.render_types) {
                        Object.keys(loop.render_types).forEach(key => {
                            if (loop.render_types[key] === 'default') {
                                delete loop.render_types[key];
                            }
                        });
                        if (Object.keys(loop.render_types).length === 0) {
                            delete loop.render_types;
                        }
                    }
                });
            }
            jsonConfigViewer.textContent = JSON.stringify(mappingConfig, null, 2);
            applyVisualHighlights();
            renderActiveRulesList();
        }

        function applyVisualHighlights() {
            document.querySelectorAll('#excelGrid td').forEach(td => {
                td.classList.remove('cell-mapped-single', 'cell-mapped-loop');
                td.style.boxShadow = '';
                const badge = td.querySelector('.badge-cell');
                if (badge) badge.remove();
            });

            // Highlight single mapped cells
            mappingConfig.single_fields.forEach(item => {
                const td = document.querySelector(`#excelGrid td[data-cell="${item.cell}"]`);
                if (td) {
                    td.classList.add('cell-mapped-single');
                    const renderTag = (item.render_type && item.render_type !== 'text' && item.render_type !== 'general' && item.render_type !== 'default') ? ` [${item.render_type.toUpperCase()}]` : '';
                    td.insertAdjacentHTML('beforeend', `<span class="badge bg-emerald-600 text-white badge-cell badge-cell-single">${item.field_key}${renderTag}</span>`);
                }
            });

            // Highlight table loop mapped columns
            mappingConfig.table_loops.forEach(loop => {
                const startRow = loop.start_row;
                Object.entries(loop.columns).forEach(([fieldKey, colLetter]) => {
                    const td = document.querySelector(`#excelGrid td[data-col="${colLetter}"][data-row="${startRow}"]`);
                    if (td) {
                        td.classList.add('cell-mapped-loop');
                        const rType = loop.render_types ? loop.render_types[fieldKey] : null;
                        const renderTag = (rType && rType !== 'text' && rType !== 'general' && rType !== 'default') ? ` [${rType.toUpperCase()}]` : '';
                        td.insertAdjacentHTML('beforeend', `<span class="badge bg-sky-600 text-white badge-cell badge-cell-loop">${loop.group}:${fieldKey}${renderTag}</span>`);
                    }
                });
            });

            // Highlight conditional rule target cells
            if (mappingConfig.conditional_rules) {
                mappingConfig.conditional_rules.forEach(rule => {
                    if (rule.target_cell) {
                        const td = document.querySelector(`#excelGrid td[data-cell="${rule.target_cell}"]`);
                        if (td && !td.querySelector('.badge-cell')) {
                            td.classList.add('cell-mapped-single');
                            td.insertAdjacentHTML('beforeend', `<span class="badge bg-amber-500 text-white badge-cell badge-cell-single">IF:${rule.field_key}=>${rule.target_cell}</span>`);
                        }
                    }
                });
            }
        }

        // SECTION 4: CONDITIONAL RULES JS HANDLERS
        const ruleOutputTypeSelect = document.getElementById('ruleOutputTypeSelect');
        const ruleOutputFieldBox = document.getElementById('ruleOutputFieldBox');
        const ruleOutputStaticBox = document.getElementById('ruleOutputStaticBox');
        const btnAddConditionalRule = document.getElementById('btnAddConditionalRule');
        const activeRulesList = document.getElementById('activeRulesList');

        if (!mappingConfig.conditional_rules) {
            mappingConfig.conditional_rules = [];
        }

        if (ruleOutputTypeSelect) {
            ruleOutputTypeSelect.addEventListener('change', function () {
                if (this.value === 'static_value') {
                    ruleOutputStaticBox.classList.remove('hidden');
                    ruleOutputFieldBox.classList.add('hidden');
                } else {
                    ruleOutputFieldBox.classList.remove('hidden');
                    ruleOutputStaticBox.classList.add('hidden');
                }
            });
        }

        const ruleOperatorSelect = document.getElementById('ruleOperatorSelect');
        const ruleMatchValueInput = document.getElementById('ruleMatchValueInput');
        if (ruleOperatorSelect && ruleMatchValueInput) {
            ruleOperatorSelect.addEventListener('change', function () {
                if (this.value === 'is_empty' || this.value === 'is_not_empty') {
                    ruleMatchValueInput.value = '';
                    ruleMatchValueInput.disabled = true;
                    ruleMatchValueInput.placeholder = 'Not needed';
                } else {
                    ruleMatchValueInput.disabled = false;
                    ruleMatchValueInput.placeholder = 'e.g. jig';
                }
            });
        }

        function renderActiveRulesList() {
            if (!activeRulesList) return;
            activeRulesList.innerHTML = '';

            const rules = mappingConfig.conditional_rules || [];
            if (rules.length === 0) {
                activeRulesList.innerHTML = '<p class="text-[10px] text-slate-400 italic">No conditional rules defined yet.</p>';
                return;
            }

            rules.forEach((rule, idx) => {
                const opLabel = rule.operator === 'equals' ? '==' : rule.operator;
                const targetText = rule.target_cell || rule.target_column || '?';
                const outputText = rule.output_type === 'static_value' ? `"${rule.output_static_value}"` : rule.output_field_key;

                const card = document.createElement('div');
                card.className = "p-2 bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded text-[11px] flex items-center justify-between gap-1.5";
                card.innerHTML = `
                    <div class="min-w-0">
                        <span class="font-bold text-amber-900 dark:text-amber-300">IF</span>
                        <code class="text-slate-800 dark:text-slate-200 font-mono text-[10px]">${rule.field_key}</code>
                        <span class="text-amber-800 dark:text-amber-400 font-bold">${opLabel}</span>
                        <code class="text-slate-800 dark:text-slate-200 font-mono text-[10px]">"${rule.value || ''}"</code>
                        <span class="font-bold text-blue-600 dark:text-blue-400">THEN ${targetText} =</span>
                        <span class="font-mono text-[10px] text-emerald-600 dark:text-emerald-400 font-bold">${outputText}</span>
                    </div>
                    <button type="button" data-idx="${idx}" class="btn-remove-rule text-rose-500 hover:text-rose-700 p-1 shrink-0" title="Delete Rule">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </button>
                `;
                activeRulesList.appendChild(card);
            });

            activeRulesList.querySelectorAll('.btn-remove-rule').forEach(btn => {
                btn.addEventListener('click', function () {
                    const index = parseInt(this.dataset.idx);
                    mappingConfig.conditional_rules.splice(index, 1);
                    renderJSONViewer();
                    markDirty();
                    if (window.showToast) window.showToast('Conditional rule removed', 'info');
                });
            });
        }

        if (btnAddConditionalRule) {
            btnAddConditionalRule.addEventListener('click', function () {
                const srcField = $('#ruleSourceFieldSelect').val();
                const operator = document.getElementById('ruleOperatorSelect').value;
                const matchVal = document.getElementById('ruleMatchValueInput').value.trim();
                const targetCell = document.getElementById('ruleTargetCellInput').value.trim().toUpperCase();
                const outputType = ruleOutputTypeSelect.value;
                const outputField = $('#ruleOutputFieldSelect').val();
                const outputStatic = document.getElementById('ruleOutputStaticInput').value.trim();
                const elseStatic = document.getElementById('ruleElseStaticInput').value.trim();

                if (!srcField) {
                    alert('Please select IF Source Variable!');
                    return;
                }
                if (!targetCell) {
                    alert('Please enter THEN Target Cell or Column (e.g. I14)!');
                    return;
                }
                if (outputType === 'field_value' && !outputField) {
                    alert('Please select Output System Field!');
                    return;
                }
                if (outputType === 'static_value' && !outputStatic) {
                    alert('Please enter Custom Output Text!');
                    return;
                }

                const ruleObj = {
                    id: 'rule_' + Date.now(),
                    sheet_index: activeSheetIndex,
                    sheet_name: activeSheetName,
                    field_key: srcField,
                    operator: operator,
                    value: matchVal,
                    target_cell: targetCell,
                    output_type: outputType,
                    output_field_key: outputType === 'field_value' ? outputField : '',
                    output_static_value: outputType === 'static_value' ? outputStatic : '',
                    else_static_value: elseStatic,
                    render_type: 'default'
                };

                if (!mappingConfig.conditional_rules) {
                    mappingConfig.conditional_rules = [];
                }
                mappingConfig.conditional_rules.push(ruleObj);

                renderJSONViewer();
                markDirty();
                if (window.showToast) window.showToast('Conditional rule added!', 'success');

                // Clear inputs
                document.getElementById('ruleMatchValueInput').value = '';
                document.getElementById('ruleOutputStaticInput').value = '';
                document.getElementById('ruleElseStaticInput').value = '';
                $('#ruleSourceFieldSelect').val('').trigger('change');
                $('#ruleOutputFieldSelect').val('').trigger('change');
            });
        }

        const smartSuggestBanner = document.getElementById('smartSuggestBanner');
        const suggestedFieldLabel = document.getElementById('suggestedFieldLabel');
        const btnApplySuggestion = document.getElementById('btnApplySuggestion');
        let currentSuggestedFieldKey = null;

        const systemFieldsList = [];
        document.querySelectorAll('#globalFieldSelect option').forEach(opt => {
            if (opt.value) {
                systemFieldsList.push({
                    key: opt.value,
                    label: opt.textContent,
                    cleanKey: opt.value.toLowerCase().replace(/_/g, ' '),
                    cleanLabel: opt.textContent.toLowerCase()
                });
            }
        });

        function findSuggestionsForCell(tdElement, col, row) {
            const matchMap = new Map(); // key -> { sf, score }

            // Common unit/symbol noise to strip when extracting key phrases
            const stripUnits = (txt) => {
                if (!txt) return '';
                return txt
                    .replace(/\([^)]*\)/g, '') // remove (kg), (pc), (%), (/kg), etc.
                    .replace(/\[[^\]]*\]/g, '')
                    .replace(/[\/\\#$€£¥%]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            };

            const evaluateText = (rawTxt, distanceWeight = 1.0) => {
                if (!rawTxt || rawTxt.length < 2) return;

                const cleanRaw = rawTxt.trim().toLowerCase();
                const stripped = stripUnits(cleanRaw).toLowerCase();

                // Skip generic unit-only texts like "(kg)", "kg", "(%)", "pc"
                if (stripped.length < 2) return;

                systemFieldsList.forEach(sf => {
                    let score = 0;
                    const sfKey = sf.key.toLowerCase();
                    const sfCleanKey = sf.cleanKey;
                    const sfCleanLabel = stripUnits(sf.cleanLabel.toLowerCase());

                    // 1. Exact Match on stripped text (Highest Priority)
                    if (sfCleanKey === stripped || sfCleanLabel === stripped) {
                        score += 100 * distanceWeight;
                    } 
                    // 2. High Similarity (Phrase is exact substring of label or vice versa)
                    else if (sfCleanLabel.startsWith(stripped) || stripped.startsWith(sfCleanLabel)) {
                        score += 70 * distanceWeight;
                    }
                    else if (sfCleanKey.includes(stripped) || sfCleanLabel.includes(stripped)) {
                        // Ensure it's not matching just generic words
                        if (stripped.length >= 4) {
                            score += 40 * distanceWeight;
                        }
                    }

                    if (score > 0) {
                        const existing = matchMap.get(sf.key);
                        if (!existing || score > existing.score) {
                            matchMap.set(sf.key, { sf, score });
                        }
                    }
                });
            };

            // 1. Direct cell text
            let directText = tdElement.querySelector('.cell-content-wrap')?.textContent?.trim()?.toLowerCase();
            evaluateText(directText, 1.5);

            // Convert Column Letter to Index
            const colLetterToNum = (letter) => {
                let num = 0;
                for (let i = 0; i < letter.length; i++) {
                    num = num * 26 + letter.charCodeAt(i) - 64;
                }
                return num;
            };

            const numToColLetter = (num) => {
                let letter = '';
                while (num > 0) {
                    let rem = (num - 1) % 26;
                    letter = String.fromCharCode(65 + rem) + letter;
                    num = Math.floor((num - rem) / 26);
                }
                return letter;
            };

            const colNum = colLetterToNum(col);
            const colSpan = parseInt(tdElement.dataset.colspan || '1');

            // 2. Scan LEFT across rows (Horizontal headers - closest gets higher weight)
            for (let stepLeft = 1; stepLeft <= 4; stepLeft++) {
                const checkColNum = colNum - stepLeft;
                if (checkColNum < 1) break;
                const checkColLetter = numToColLetter(checkColNum);
                const weight = 1.2 / stepLeft;

                for (let rOffset of [0, -1, 1]) {
                    const checkRow = row + rOffset;
                    if (checkRow < 1) continue;

                    const leftTd = document.querySelector(`#excelGrid td[data-col="${checkColLetter}"][data-row="${checkRow}"]`);
                    if (leftTd) {
                        const leftText = leftTd.querySelector('.cell-content-wrap')?.textContent?.trim();
                        evaluateText(leftText, weight);
                    }
                }
            }

            // 3. Scan UPWARDS across columns spanned by target cell (Vertical headers - closest row gets highest weight)
            for (let stepUp = 1; stepUp <= 4; stepUp++) {
                const targetRow = row - stepUp;
                if (targetRow < 1) break;
                const weight = 1.3 / stepUp; // Row immediately above gets highest weight

                for (let c = colNum; c < colNum + colSpan; c++) {
                    const checkColLetter = numToColLetter(c);
                    const aboveTd = document.querySelector(`#excelGrid td[data-col="${checkColLetter}"][data-row="${targetRow}"]`);
                    if (aboveTd) {
                        const aboveText = aboveTd.querySelector('.cell-content-wrap')?.textContent?.trim();
                        evaluateText(aboveText, weight);
                    }
                }
            }

            // Sort matches by score descending and return TOP 3 candidates only
            const sortedMatches = Array.from(matchMap.values())
                .sort((a, b) => b.score - a.score)
                .filter(item => item.score >= 35) // Filter out noise / low relevance matches
                .slice(0, 3)
                .map(item => item.sf);

            return sortedMatches;
        }
        const suggestAccordionToggle = document.getElementById('suggestAccordionToggle');
        const suggestAccordionBody = document.getElementById('suggestAccordionBody');
        const suggestAccordionIcon = document.getElementById('suggestAccordionIcon');
        const suggestMatchBadge = document.getElementById('suggestMatchBadge');

        suggestAccordionToggle.addEventListener('click', function () {
            const isHidden = suggestAccordionBody.classList.contains('hidden');
            if (isHidden) {
                suggestAccordionBody.classList.remove('hidden');
                suggestAccordionIcon.classList.add('rotate-180');
            } else {
                suggestAccordionBody.classList.add('hidden');
                suggestAccordionIcon.classList.remove('rotate-180');
            }
        });

        excelGrid.addEventListener('click', function (e) {
            const td = e.target.closest('td');
            if (!td) return;

            document.querySelectorAll('#excelGrid td').forEach(c => c.classList.remove('cell-selected'));
            td.classList.add('cell-selected');

            currentSelectedCell = {
                cell: td.dataset.cell,
                col: td.dataset.col,
                row: parseInt(td.dataset.row),
                isFormula: td.dataset.isFormula === 'true'
            };

            selectedCellLabel.textContent = currentSelectedCell.cell;
            noSelectionNotice.classList.add('hidden');
            mappingControlForm.classList.remove('hidden');

            const ruleTargetInput = document.getElementById('ruleTargetCellInput');
            if (ruleTargetInput) ruleTargetInput.value = currentSelectedCell.cell;

            if (window.Alpine) {
                const AlpineData = Alpine.$data(document.querySelector('[x-data="visualMapperStudio()"]'));
                if (AlpineData) AlpineData.sidebarOpen = true;
            }

            let existingSingle = mappingConfig.single_fields.find(f => f.cell === currentSelectedCell.cell);
            let existingLoopCol = null;
            let existingLoopGroup = null;
            let existingLoopMode = 'flat';
            let existingLoopDirection = 'down';
            let existingLoopBehavior = 'insert_duplicate';
            let existingLoopStaticVal = null;

            mappingConfig.table_loops.forEach(loop => {
                if (parseInt(loop.start_row) === currentSelectedCell.row) {
                    Object.entries(loop.columns).forEach(([key, colLetter]) => {
                        if (colLetter === currentSelectedCell.col) {
                            existingLoopCol = key;
                            existingLoopGroup = loop.group;
                            existingLoopMode = loop.loop_mode || 'flat';
                            existingLoopDirection = loop.direction || 'down';
                            existingLoopBehavior = loop.insert_behavior || 'insert_duplicate';
                            if (loop.static_values && loop.static_values[key]) {
                                existingLoopStaticVal = loop.static_values[key];
                            }
                        }
                    });
                }
            });

            // Update Header Cell Info Badges & Active Value Preview
            const badgeMappingType = document.getElementById('badgeMappingType');
            const badgeCellOrigin = document.getElementById('badgeCellOrigin');
            const cellActiveValuePreview = document.getElementById('cellActiveValuePreview');
            const cellActiveValuePrefix = document.getElementById('cellActiveValuePrefix');
            const cellActiveValueText = document.getElementById('cellActiveValueText');

            const rawCellWrap = td.querySelector('.cell-content-wrap');
            const rawExcelText = rawCellWrap ? rawCellWrap.textContent.trim() : '';
            const rawFormula = td.dataset.rawFormula || (rawExcelText.startsWith('=') ? rawExcelText : '');

            if (existingSingle) {
                const rTypeTag = (existingSingle.render_type && existingSingle.render_type !== 'text') ? ` [${existingSingle.render_type.toUpperCase()}]` : '';
                const singleModeLabel = existingSingle.value_type === 'static' ? 'Single (Free Text)' : `Single (${existingSingle.field_key})${rTypeTag}`;
                badgeMappingType.className = "px-2 py-0.5 bg-emerald-600 text-white font-bold rounded shadow-2xs leading-tight text-[10px]";
                badgeMappingType.textContent = singleModeLabel;

                cellActiveValuePrefix.textContent = "Mapped:";
                cellActiveValuePreview.classList.remove('hidden');
                cellActiveValueText.textContent = existingSingle.value_type === 'static' ? (existingSingle.static_value || '') : existingSingle.field_key;
            } else if (existingLoopCol) {
                const matchedLoopObj = mappingConfig.table_loops.find(l => l.group === existingLoopGroup);
                const loopRTypeTag = (matchedLoopObj && matchedLoopObj.render_types && matchedLoopObj.render_types[existingLoopCol]) ? ` [${matchedLoopObj.render_types[existingLoopCol].toUpperCase()}]` : '';
                const loopModeLabel = existingLoopStaticVal ? `Loop: ${existingLoopGroup}` : `Loop [${existingLoopGroup}]: ${existingLoopCol}${loopRTypeTag}`;
                badgeMappingType.className = "px-2 py-0.5 bg-sky-600 text-white font-bold rounded shadow-2xs leading-tight text-[10px]";
                badgeMappingType.textContent = loopModeLabel;

                cellActiveValuePrefix.textContent = "Mapped:";
                cellActiveValuePreview.classList.remove('hidden');
                cellActiveValueText.textContent = existingLoopStaticVal ? existingLoopStaticVal : existingLoopCol;
            } else {
                badgeMappingType.className = "px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold rounded text-[10px]";
                badgeMappingType.textContent = "Unmapped";
                
                if (rawExcelText) {
                    cellActiveValuePrefix.textContent = rawFormula ? "Formula Result:" : "Excel Value:";
                    cellActiveValuePreview.classList.remove('hidden');
                    cellActiveValueText.textContent = rawExcelText;
                } else {
                    cellActiveValuePreview.classList.add('hidden');
                    cellActiveValueText.textContent = '';
                }
            }

            if (rawFormula) {
                badgeCellOrigin.classList.remove('hidden');
                badgeCellOrigin.textContent = `Formula: "${rawFormula.length > 20 ? rawFormula.substring(0, 20) + '...' : rawFormula}"`;
            } else if (rawExcelText) {
                badgeCellOrigin.classList.remove('hidden');
                badgeCellOrigin.textContent = `Excel: "${rawExcelText.length > 15 ? rawExcelText.substring(0, 15) + '...' : rawExcelText}"`;
            } else {
                badgeCellOrigin.classList.add('hidden');
            }

            if (existingSingle && existingSingle.value_type === 'static') {
                setDataSourceMode('static');
                staticValueInput.value = existingSingle.static_value || '';
                highlightFormulaReferencedCells(existingSingle.static_value || '');
            } else if (existingLoopStaticVal) {
                setDataSourceMode('static');
                staticValueInput.value = existingLoopStaticVal;
                highlightFormulaReferencedCells(existingLoopStaticVal || '');
            } else if (currentSelectedCell.isFormula || rawFormula) {
                if (rawFormula) {
                    staticValueInput.value = rawFormula;
                    highlightFormulaReferencedCells(rawFormula);
                    
                    if (!existingSingle && !existingLoopCol) {
                        setDataSourceMode('static');
                    } else {
                        setDataSourceMode('variable');
                        const activeFieldKey = existingSingle ? existingSingle.field_key : (existingLoopCol || '');
                        $('#globalFieldSelect').val(activeFieldKey).trigger('change');
                    }
                } else {
                    setDataSourceMode('variable');
                    const activeFieldKey = existingSingle ? existingSingle.field_key : (existingLoopCol || '');
                    $('#globalFieldSelect').val(activeFieldKey).trigger('change');
                }
            } else {
                setDataSourceMode('variable');
                const activeFieldKey = existingSingle ? existingSingle.field_key : (existingLoopCol || '');
                $('#globalFieldSelect').val(activeFieldKey).trigger('change');
                highlightFormulaReferencedCells('');
            }

            const suggestedPillsContainer = document.getElementById('suggestedPillsContainer');
            suggestedPillsContainer.innerHTML = '';
            suggestAccordionBody.classList.add('hidden');
            suggestAccordionIcon.classList.remove('rotate-180');

            const activeFieldKey = existingSingle ? existingSingle.field_key : (existingLoopCol || '');
            if (!activeFieldKey) {
                const suggestions = findSuggestionsForCell(td, currentSelectedCell.col, currentSelectedCell.row);
                if (suggestions.length > 0) {
                    suggestMatchBadge.textContent = suggestions.length;
                    suggestions.forEach(s => {
                        const pillBtn = document.createElement('button');
                        pillBtn.type = 'button';
                        pillBtn.className = 'px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 hover:bg-purple-600 hover:text-white text-purple-700 dark:text-purple-300 text-[11px] font-semibold rounded-sm transition-colors border border-purple-200 dark:border-purple-700 text-left';
                        pillBtn.textContent = s.label;
                        pillBtn.title = `Click to use ${s.key}`;
                        pillBtn.addEventListener('click', () => {
                            setDataSourceMode('variable');
                            $('#globalFieldSelect').val(s.key).trigger('change');
                            smartSuggestBanner.classList.add('hidden');
                        });
                        suggestedPillsContainer.appendChild(pillBtn);
                    });
                    smartSuggestBanner.classList.remove('hidden');
                } else {
                    smartSuggestBanner.classList.add('hidden');
                }
            } else {
                smartSuggestBanner.classList.add('hidden');
            }

            if (existingSingle) {
                updateRenderTypeCards(existingSingle.render_type || 'default');
                singleImageSize.value = existingSingle.image_size || '100x40';
                document.getElementById('btnUnsetSingle').classList.remove('hidden');
                tabSingleBtn.click();
            } else {
                singleImageSize.value = '100x40';
                document.getElementById('btnUnsetSingle').classList.add('hidden');
            }

            if (existingLoopCol) {
                loopGroupName.value = existingLoopGroup || '';
                updateLoopModeCards(existingLoopMode);
                updateDirectionCards(existingLoopDirection);
                updateBehaviorCards(existingLoopBehavior);

                const matchedLoop = mappingConfig.table_loops.find(l => l.group === existingLoopGroup);
                if (matchedLoop) {
                    document.getElementById('blankRowsAfterInput').value = matchedLoop.blank_rows_after || 0;
                    const isSheetLoop = !!(matchedLoop.split_sheet_per_parent || matchedLoop.sheet_loop);
                    document.getElementById('splitSheetPerParentToggle').checked = isSheetLoop;
                    
                    $('#sheetNameFieldSelect').val(matchedLoop.sheet_name_field || '').trigger('change');
                    toggleSheetLoopControls();

                    const activeLoopRenderType = (matchedLoop.render_types && matchedLoop.render_types[existingLoopCol]) ? matchedLoop.render_types[existingLoopCol] : 'default';
                    if (!existingSingle) {
                        updateRenderTypeCards(activeLoopRenderType);
                    }
                }

                document.getElementById('btnUnsetLoop').classList.remove('hidden');
                if (!existingSingle) tabLoopBtn.click();
            } else {
                document.getElementById('splitSheetPerParentToggle').checked = false;
                $('#sheetNameFieldSelect').val('').trigger('change');
                toggleSheetLoopControls();
                document.getElementById('btnUnsetLoop').classList.add('hidden');
                if (!existingSingle) {
                    updateRenderTypeCards('default');
                }
            }
            toggleStopConditionVisibility();
        });

        let currentDataSourceMode = 'variable';

        const sourceTypeVariableBtn = document.getElementById('sourceTypeVariableBtn');
        const sourceTypeStaticBtn = document.getElementById('sourceTypeStaticBtn');
        const variableSourceBox = document.getElementById('variableSourceBox');
        const staticSourceBox = document.getElementById('staticSourceBox');
        const staticValueInput = document.getElementById('staticValueInput');

        function setDataSourceMode(mode) {
            currentDataSourceMode = mode;
            if (mode === 'static') {
                sourceTypeStaticBtn.className = "py-2 px-2 bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-xs rounded flex flex-col items-center justify-center transition-all font-bold ring-1 ring-emerald-500/30";
                sourceTypeVariableBtn.className = "py-2 px-2 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded flex flex-col items-center justify-center transition-all";
                staticSourceBox.classList.remove('hidden');
                variableSourceBox.classList.add('hidden');
                document.getElementById('btnAssignSingle').disabled = !staticValueInput.value.trim();
                document.getElementById('btnAssignLoop').disabled = !staticValueInput.value.trim();
            } else {
                sourceTypeVariableBtn.className = "py-2 px-2 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-xs rounded flex flex-col items-center justify-center transition-all font-bold ring-1 ring-blue-500/30";
                sourceTypeStaticBtn.className = "py-2 px-2 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded flex flex-col items-center justify-center transition-all";
                variableSourceBox.classList.remove('hidden');
                staticSourceBox.classList.add('hidden');
                const val = $('#globalFieldSelect').val();
                document.getElementById('btnAssignSingle').disabled = !val;
                document.getElementById('btnAssignLoop').disabled = !val;
            }
        }

        if (sourceTypeVariableBtn) sourceTypeVariableBtn.addEventListener('click', () => setDataSourceMode('variable'));
        if (sourceTypeStaticBtn) sourceTypeStaticBtn.addEventListener('click', () => setDataSourceMode('static'));

        // Helper to update button card active states
        function updateRenderTypeCards(activeVal) {
            if (!activeVal) activeVal = 'default';
            if (activeVal === 'general') activeVal = 'text';
            document.querySelectorAll('#renderTypeButtonGrid .render-type-btn').forEach(btn => {
                const val = btn.dataset.renderVal;
                if (val === activeVal || (activeVal === 'text' && val === 'general') || (activeVal === 'general' && val === 'text')) {
                    btn.className = "render-type-btn p-2 border rounded text-center transition-all bg-white dark:bg-slate-900 border-purple-500 text-purple-600 dark:text-purple-400 shadow-xs font-bold ring-1 ring-purple-500/30";
                } else {
                    btn.className = "render-type-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300";
                }
            });
            document.getElementById('singleRenderType').value = activeVal;
            toggleImageSizeVisibility();
        }

        document.querySelectorAll('#renderTypeButtonGrid .render-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateRenderTypeCards(this.dataset.renderVal);
            });
        });

        // Loop Mode Buttons
        function updateLoopModeCards(activeVal) {
            document.querySelectorAll('.loop-mode-btn').forEach(btn => {
                const val = btn.dataset.loopMode;
                if (val === activeVal) {
                    btn.className = "loop-mode-btn p-2 border rounded text-left transition-all bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-xs font-bold ring-1 ring-sky-500/30";
                } else {
                    btn.className = "loop-mode-btn p-2 border border-slate-200 dark:border-slate-800 rounded text-left transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300";
                }
            });
            document.getElementById('loopModeSelect').value = activeVal;
            toggleSheetLoopControls();
        }

        document.querySelectorAll('.loop-mode-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateLoopModeCards(this.dataset.loopMode);
            });
        });

        // Direction Buttons
        function updateDirectionCards(activeVal) {
            document.querySelectorAll('.dir-btn').forEach(btn => {
                const val = btn.dataset.dirVal;
                if (val === activeVal) {
                    btn.className = "dir-btn py-1.5 px-1 border rounded text-center transition-all bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-xs font-bold ring-1 ring-sky-500/30";
                } else {
                    btn.className = "dir-btn py-1.5 px-1 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300";
                }
            });
            document.getElementById('loopDirectionSelect').value = activeVal;
            toggleStopConditionVisibility();
        }

        document.querySelectorAll('.dir-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateDirectionCards(this.dataset.dirVal);
            });
        });

        // Row Behavior Buttons
        function updateBehaviorCards(activeVal) {
            document.querySelectorAll('.behavior-btn').forEach(btn => {
                const val = btn.dataset.behaviorVal;
                if (val === activeVal) {
                    btn.className = "behavior-btn py-1.5 px-1 border rounded text-center transition-all bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-xs font-bold ring-1 ring-sky-500/30";
                } else {
                    btn.className = "behavior-btn py-1.5 px-1 border border-slate-200 dark:border-slate-800 rounded text-center transition-all bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300";
                }
            });
            document.getElementById('loopInsertBehaviorSelect').value = activeVal;
            toggleStopConditionVisibility();
        }

        document.querySelectorAll('.behavior-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateBehaviorCards(this.dataset.behaviorVal);
            });
        });

        const dynamicFormulaRowToggle = document.getElementById('dynamicFormulaRowToggle');

        // Extract referenced cell coordinates from a formula string (e.g. "=SUM(N13:N16)" or "=I{row-1}+(I{row-1}*I{row-2})")
        function extractFormulaCellReferences(formulaStr) {
            if (!formulaStr || !formulaStr.trim().startsWith('=')) return [];

            const references = [];

            // 1. Match range references like N13:N16, A1:B5, N{row-3}:N{row}
            const rangeRegex = /([A-Z]{1,3})(\d+|\{row(?:[+-]\d+)?\})\s*:\s*([A-Z]{1,3})(\d+|\{row(?:[+-]\d+)?\})/gi;
            let match;
            const processedRanges = [];

            while ((match = rangeRegex.exec(formulaStr)) !== null) {
                processedRanges.push(match[0]);
                const startCol = match[1].toUpperCase();
                const startRowSpec = match[2];
                const endCol = match[3].toUpperCase();
                const endRowSpec = match[4];

                const resolveRow = (spec) => {
                    if (spec.startsWith('{row')) {
                        if (currentSelectedCell) {
                            const offsetMatch = spec.match(/\{row([+-]\d+)?\}/);
                            const offset = (offsetMatch && offsetMatch[1]) ? parseInt(offsetMatch[1]) : 0;
                            return currentSelectedCell.row + offset;
                        }
                        return null;
                    }
                    return parseInt(spec);
                };

                const colLetterToNum = (col) => {
                    let num = 0;
                    for (let i = 0; i < col.length; i++) {
                        num = num * 26 + col.charCodeAt(i) - 64;
                    }
                    return num;
                };

                const numToColLetter = (num) => {
                    let letter = '';
                    while (num > 0) {
                        let rem = (num - 1) % 26;
                        letter = String.fromCharCode(65 + rem) + letter;
                        num = Math.floor((num - rem) / 26);
                    }
                    return letter;
                };

                const r1 = resolveRow(startRowSpec);
                const r2 = resolveRow(endRowSpec);
                const c1 = colLetterToNum(startCol);
                const c2 = colLetterToNum(endCol);

                if (r1 !== null && r2 !== null) {
                    const minR = Math.min(r1, r2);
                    const maxR = Math.max(r1, r2);
                    const minC = Math.min(c1, c2);
                    const maxC = Math.max(c1, c2);

                    for (let c = minC; c <= maxC; c++) {
                        const colLetter = numToColLetter(c);
                        for (let r = minR; r <= maxR; r++) {
                            references.push({
                                col: colLetter,
                                rowNumber: r
                            });
                        }
                    }
                }
            }

            // 2. Match single cell references like N13, I{row-1} (ignoring those inside ranges)
            const singleRegex = /([A-Z]{1,3})(\d+|\{row(?:[+-]\d+)?\})/gi;
            while ((match = singleRegex.exec(formulaStr)) !== null) {
                // Check if this match is part of a range already processed
                const isInsideRange = processedRanges.some(rStr => rStr.includes(match[0]));
                if (!isInsideRange) {
                    let rowNum = null;
                    if (match[2].startsWith('{row')) {
                        if (currentSelectedCell) {
                            const offsetMatch = match[2].match(/\{row([+-]\d+)?\}/);
                            const offset = (offsetMatch && offsetMatch[1]) ? parseInt(offsetMatch[1]) : 0;
                            rowNum = currentSelectedCell.row + offset;
                        }
                    } else {
                        rowNum = parseInt(match[2]);
                    }

                    if (rowNum !== null) {
                        references.push({
                            col: match[1].toUpperCase(),
                            rowNumber: rowNum
                        });
                    }
                }
            }

            return references;
        }

        // Highlight cells referenced in formula on excelGrid
        function highlightFormulaReferencedCells(formulaStr) {
            // Remove previous formula reference highlights
            document.querySelectorAll('#excelGrid td.cell-formula-ref').forEach(c => {
                c.classList.remove('cell-formula-ref');
            });

            if (!formulaStr || !formulaStr.trim().startsWith('=')) return;

            const refs = extractFormulaCellReferences(formulaStr);
            const currentSelectedTd = document.querySelector('#excelGrid td.cell-selected');

            refs.forEach(ref => {
                if (ref.rowNumber && ref.rowNumber > 0) {
                    const selector = `#excelGrid td[data-col="${ref.col}"][data-row="${ref.rowNumber}"]`;
                    const targetTd = document.querySelector(selector);
                    if (targetTd && targetTd !== currentSelectedTd) {
                        targetTd.classList.add('cell-formula-ref');
                    }
                }
            });
        }

        const btnConvertDynamicFormula = document.getElementById('btnConvertDynamicFormula');

        // Convert hardcoded cell rows to dynamic {row} or relative {row±N} tags when button is clicked
        function convertFormulaToDynamicRow() {
            let val = staticValueInput.value.trim();
            if (!val.startsWith('=')) {
                if (window.showToast) window.showToast('Please enter a valid formula starting with = first.', 'warning');
                return;
            }

            if (currentSelectedCell) {
                const targetRow = currentSelectedCell.row;

                // Match static cell references like I15, C16, AB14
                val = val.replace(/\b([A-Z]{1,3})(\d+)\b/gi, (match, col, rowStr) => {
                    const cellRow = parseInt(rowStr);
                    const diff = cellRow - targetRow;
                    if (diff === 0) {
                        return `${col}{row}`;
                    } else if (diff < 0) {
                        return `${col}{row${diff}}`; // e.g. I15 at target row 17 -> I{row-2}
                    } else {
                        return `${col}{row+${diff}}`; // e.g. I18 at target row 17 -> I{row+1}
                    }
                });

                staticValueInput.value = val;
                highlightFormulaReferencedCells(val);
                if (window.showToast) window.showToast('Converted formula to dynamic row references ({row})', 'success');
            }
        }

        if (btnConvertDynamicFormula) {
            btnConvertDynamicFormula.addEventListener('click', convertFormulaToDynamicRow);
        }

        staticValueInput.addEventListener('input', function() {
            if (currentDataSourceMode === 'static') {
                const val = this.value.trim();
                document.getElementById('btnAssignSingle').disabled = !val;
                document.getElementById('btnAssignLoop').disabled = !val;
                highlightFormulaReferencedCells(val);
            }
        });

        function toggleStopConditionVisibility() {
            const dir = document.getElementById('loopDirectionSelect').value;
            const behavior = document.getElementById('loopInsertBehaviorSelect').value;
            const container = document.getElementById('stopConditionContainer');
            if (dir === 'right' || behavior === 'overwrite') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        function updateCurrentGroupSheetLoopConfig() {
            const group = loopGroupName.value.trim();
            const isChecked = document.getElementById('splitSheetPerParentToggle').checked;
            const fieldVal = document.getElementById('sheetNameFieldSelect').value;

            if (group) {
                const targetLoop = mappingConfig.table_loops.find(l => l.group === group);
                if (targetLoop) {
                    if (isChecked) {
                        targetLoop.split_sheet_per_parent = true;
                        if (fieldVal) targetLoop.sheet_name_field = fieldVal;
                        else delete targetLoop.sheet_name_field;
                    } else {
                        delete targetLoop.split_sheet_per_parent;
                        delete targetLoop.sheet_name_field;
                    }
                    renderJSONViewer();
                }
            }
        }

        function toggleSheetLoopControls() {
            const isSheetLoop = document.getElementById('splitSheetPerParentToggle').checked;
            const loopMode = document.getElementById('loopModeSelect').value;
            const namingContainer = document.getElementById('sheetNamingContainer');
            const dirBehaviorContainer = document.getElementById('directionBehaviorCardContainer');
            const noticeBanner = document.getElementById('sheetLoopNoticeBanner');

            if (isSheetLoop) {
                namingContainer.classList.remove('hidden');
                noticeBanner.classList.remove('hidden');
                
                // If Flat mode with Sheet Loop, Parent is split per sheet, so direction/behavior is redundant.
                // But for Nested Block mode, Child rows loop inside each sheet tab so direction/behavior is fully active!
                if (loopMode === 'nested_block') {
                    dirBehaviorContainer.classList.remove('hidden');
                } else {
                    dirBehaviorContainer.classList.add('hidden');
                }
            } else {
                namingContainer.classList.add('hidden');
                dirBehaviorContainer.classList.remove('hidden');
                noticeBanner.classList.add('hidden');
            }
        }

        document.getElementById('splitSheetPerParentToggle').addEventListener('change', function() {
            toggleSheetLoopControls();
            updateCurrentGroupSheetLoopConfig();
        });

        document.getElementById('sheetNameFieldSelect').addEventListener('change', function() {
            updateCurrentGroupSheetLoopConfig();
        });

        document.getElementById('loopDirectionSelect').addEventListener('change', toggleStopConditionVisibility);
        document.getElementById('loopInsertBehaviorSelect').addEventListener('change', toggleStopConditionVisibility);

        const activeSheetIndex = @json($activeSheetIndex ?? 0);
        const activeSheetName = @json($sheetNames[$activeSheetIndex ?? 0] ?? 'Sheet1');

        function getAutoGroupName(mode) {
            const selectedOpt = $('#globalFieldSelect').find('option:selected');
            const dataGroup = selectedOpt.data('group');
            const sheetSlug = 'sheet' + (activeSheetIndex + 1);
            const cleanMode = mode === 'nested_block' ? 'nested' : mode;

            if (dataGroup) {
                const cleanGroup = dataGroup.toLowerCase().replace(/[^a-z0-9_]/g, '_');
                return sheetSlug + '_' + cleanGroup + '_' + cleanMode;
            }
            return sheetSlug + '_' + cleanMode;
        }

        document.getElementById('loopModeSelect').addEventListener('change', function () {
            const mode = this.value;
            if (!loopGroupName.dataset.userModified) {
                loopGroupName.value = getAutoGroupName(mode);
            }
        });

        loopGroupName.addEventListener('input', function () {
            this.dataset.userModified = this.value.trim() !== '' ? 'true' : '';
        });

        document.getElementById('btnAssignSingle').addEventListener('click', function () {
            if (!currentSelectedCell) return;
            
            mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== currentSelectedCell.cell);

            const rVal = (singleRenderType.value === 'text') ? 'general' : singleRenderType.value;

            if (currentDataSourceMode === 'static') {
                const val = staticValueInput.value.trim();
                if (!val) {
                    alert('Please enter custom text or formula!');
                    return;
                }
                const singleObj = {
                    field_key: 'static_' + currentSelectedCell.cell,
                    value_type: 'static',
                    static_value: val,
                    cell: currentSelectedCell.cell,
                    sheet_index: activeSheetIndex,
                    sheet_name: activeSheetName
                };
                if (rVal && rVal !== 'default') {
                    singleObj.render_type = rVal;
                }
                mappingConfig.single_fields.push(singleObj);
                document.getElementById('btnUnsetSingle').classList.remove('hidden');
            } else {
                const fieldKey = $('#globalFieldSelect').val();
                if (fieldKey) {
                    const singleObj = {
                        field_key: fieldKey,
                        value_type: 'variable',
                        cell: currentSelectedCell.cell,
                        sheet_index: activeSheetIndex,
                        sheet_name: activeSheetName
                    };
                    if (rVal && rVal !== 'default') {
                        singleObj.render_type = rVal;
                    }
                    if (rVal === 'image' || rVal === 'qr') {
                        singleObj.image_size = singleImageSize.value.trim() || '100x40';
                    }
                    mappingConfig.single_fields.push(singleObj);
                    document.getElementById('btnUnsetSingle').classList.remove('hidden');
                }
            }

            renderJSONViewer();
            markDirty();
            if (window.showToast) window.showToast(`Single mapping assigned to cell ${currentSelectedCell.cell}`, 'success');
        });

        document.getElementById('btnAssignLoop').addEventListener('click', function () {
            if (!currentSelectedCell) return;
            const loopMode = document.getElementById('loopModeSelect').value;

            let fieldKey = '';
            let isStaticMode = (currentDataSourceMode === 'static');
            let staticVal = '';

            if (isStaticMode) {
                staticVal = staticValueInput.value.trim();
                if (!staticVal) {
                    if (window.showToast) window.showToast('Please enter custom text or formula for this loop column!', 'warning');
                    else alert('Please enter custom text or formula for this loop column!');
                    return;
                }
                fieldKey = 'static_col_' + currentSelectedCell.col + '_' + currentSelectedCell.row;
            } else {
                fieldKey = $('#globalFieldSelect').val();
                if (!fieldKey) {
                    if (window.showToast) window.showToast('Please select system field variable!', 'warning');
                    else alert('Please select system field variable!');
                    return;
                }
            }

            let rawGroup = loopGroupName.value.trim() || getAutoGroupName(loopMode);
            let group = rawGroup;

            mappingConfig.table_loops.forEach(loop => {
                if (loop.columns[fieldKey]) {
                    delete loop.columns[fieldKey];
                }
                if (loop.static_values && loop.static_values[fieldKey]) {
                    delete loop.static_values[fieldKey];
                }
                if (loop.render_types && loop.render_types[fieldKey]) {
                    delete loop.render_types[fieldKey];
                    if (Object.keys(loop.render_types).length === 0) {
                        delete loop.render_types;
                    }
                }
            });

            const loopDirection = document.getElementById('loopDirectionSelect').value;
            const insertBehavior = document.getElementById('loopInsertBehaviorSelect').value;
            const isSheetLoopActive = document.getElementById('splitSheetPerParentToggle').checked;
            const sheetNameFieldVal = document.getElementById('sheetNameFieldSelect').value;
            const blankRowsAfterVal = parseInt(document.getElementById('blankRowsAfterInput').value) || 0;

            let existingLoop = mappingConfig.table_loops.find(l => l.group === group);
            if (!existingLoop) {
                existingLoop = {
                    group: group,
                    sheet_index: activeSheetIndex,
                    sheet_name: activeSheetName,
                    loop_mode: loopMode,
                    direction: loopDirection,
                    insert_behavior: insertBehavior,
                    blank_rows_after: blankRowsAfterVal,
                    start_row: currentSelectedCell.row,
                    stop_condition: {
                        type: stopConditionType.value,
                        column: currentSelectedCell.col,
                        value: stopConditionValue.value.trim()
                    },
                    columns: {},
                    static_values: {}
                };
                if (isSheetLoopActive) {
                    existingLoop.split_sheet_per_parent = true;
                    if (sheetNameFieldVal) existingLoop.sheet_name_field = sheetNameFieldVal;
                }
                mappingConfig.table_loops.push(existingLoop);
            } else {
                existingLoop.loop_mode = loopMode;
                existingLoop.direction = loopDirection;
                existingLoop.insert_behavior = insertBehavior;
                existingLoop.blank_rows_after = blankRowsAfterVal;
                existingLoop.sheet_index = activeSheetIndex;
                existingLoop.sheet_name = activeSheetName;
                if (isSheetLoopActive) {
                    existingLoop.split_sheet_per_parent = true;
                    if (sheetNameFieldVal) existingLoop.sheet_name_field = sheetNameFieldVal;
                    else delete existingLoop.sheet_name_field;
                } else {
                    delete existingLoop.split_sheet_per_parent;
                    delete existingLoop.sheet_name_field;
                }
                if (!existingLoop.static_values) existingLoop.static_values = {};
            }

            existingLoop.columns[fieldKey] = currentSelectedCell.col;

            const rVal = (singleRenderType.value === 'text') ? 'general' : singleRenderType.value;
            if (rVal && rVal !== 'default') {
                if (!existingLoop.render_types) existingLoop.render_types = {};
                existingLoop.render_types[fieldKey] = rVal;
            } else {
                if (existingLoop.render_types && existingLoop.render_types[fieldKey]) {
                    delete existingLoop.render_types[fieldKey];
                }
                if (existingLoop.render_types && Object.keys(existingLoop.render_types).length === 0) {
                    delete existingLoop.render_types;
                }
            }
            if (isStaticMode) {
                if (!existingLoop.static_values) existingLoop.static_values = {};
                existingLoop.static_values[fieldKey] = staticVal;
            }

            mappingConfig.table_loops = mappingConfig.table_loops.filter(l => Object.keys(l.columns).length > 0);

            document.getElementById('btnUnsetLoop').classList.remove('hidden');
            renderJSONViewer();
            markDirty();
            if (window.showToast) window.showToast(`Loop column '${fieldKey}' assigned to cell ${currentSelectedCell.cell}`, 'success');
        });

        document.getElementById('btnUnsetSingle').addEventListener('click', function () {
            if (!currentSelectedCell) return;
            mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== currentSelectedCell.cell);
            
            $('#globalFieldSelect').val('').trigger('change');
            document.getElementById('btnUnsetSingle').classList.add('hidden');
            renderJSONViewer();
            markDirty();
            if (window.showToast) window.showToast('Single mapping removed', 'info');
        });

        document.getElementById('btnUnsetLoop').addEventListener('click', function () {
            if (!currentSelectedCell) return;
            const fieldKey = $('#globalFieldSelect').val();

            mappingConfig.table_loops.forEach(loop => {
                if (fieldKey && loop.columns[fieldKey]) {
                    delete loop.columns[fieldKey];
                } else {
                    Object.entries(loop.columns).forEach(([key, colLetter]) => {
                        if (colLetter === currentSelectedCell.col) {
                            delete loop.columns[key];
                        }
                    });
                }
            });

            mappingConfig.table_loops = mappingConfig.table_loops.filter(loop => Object.keys(loop.columns).length > 0);

            $('#globalFieldSelect').val('').trigger('change');
            document.getElementById('btnUnsetLoop').classList.add('hidden');
            renderJSONViewer();
            markDirty();
            if (window.showToast) window.showToast('Loop mapping removed', 'info');
        });

        document.getElementById('btnSaveConfig').addEventListener('click', function () {
            fetch(`/management/excel-templates/${templateId}/mapping`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ mapping_config: mappingConfig })
            })
            .then(res => res.json())
            .then(data => {
                markClean();
                if (window.showToast) {
                    window.showToast(data.message || 'Mapping config saved successfully!', 'success');
                } else {
                    alert(data.message || 'Mapping config saved successfully!');
                }
            })
            .catch(err => {
                if (window.showToast) window.showToast('Failed to save mapping config.', 'error');
                else alert('Failed to save mapping config.');
            });
        });

        document.getElementById('btnClearAll').addEventListener('click', function () {
            const doReset = () => {
                mappingConfig.single_fields = [];
                mappingConfig.table_loops = [];
                mappingConfig.conditional_rules = [];
                renderJSONViewer();
                markDirty();
                if (window.showToast) window.showToast('All visual mappings reset', 'info');
            };

            if (window.confirmDialog) {
                window.confirmDialog({
                    title: 'Reset All Mappings?',
                    text: 'Are you sure you want to reset all visual single & loop mappings?',
                    icon: 'warning',
                    confirmButtonText: 'Yes, Reset All',
                    onConfirm: doReset
                });
            } else if (confirm('Reset all visual mappings?')) {
                doReset();
            }
        });

        // Synchronize Alpine state on ESC key fullscreen exit
        document.addEventListener('fullscreenchange', function () {
            if (window.Alpine) {
                const AlpineData = Alpine.$data(document.querySelector('[x-data="visualMapperStudio()"]'));
                if (AlpineData) {
                    AlpineData.isFullscreen = !!document.fullscreenElement;
                }
            }
        });

        let hasUnsavedChanges = false;

        function markDirty() {
            hasUnsavedChanges = true;
        }

        function markClean() {
            hasUnsavedChanges = false;
        }

        window.addEventListener('beforeunload', function (e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });

        function saveMappingAndNavigate(targetUrl = null) {
            if (window.showToast) window.showToast('Saving mapping config...', 'info');

            fetch(`/management/excel-templates/${templateId}/mapping`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ mapping_config: mappingConfig })
            })
            .then(res => res.json())
            .then(data => {
                hasUnsavedChanges = false;
                if (window.showToast) window.showToast('Saved! Redirecting...', 'success');
                setTimeout(() => {
                    if (targetUrl) {
                        window.location.href = targetUrl;
                    } else {
                        history.back();
                    }
                }, 350);
            })
            .catch(err => {
                if (window.showToast) window.showToast('Failed to save mapping config.', 'error');
                else alert('Failed to save mapping config.');
            });
        }

        function showUnsavedModal(targetUrl = null) {
            if (window.Swal) {
                Swal.fire({
                    title: 'Unsaved Changes Detected!',
                    text: 'You have unsaved template mapping changes. How would you like to proceed?',
                    icon: 'warning',
                    showConfirmButton: true,
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Save & Leave',
                    denyButtonText: 'Leave Without Saving',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#2563eb',
                    denyButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    reverseButtons: false,
                    focusCancel: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveMappingAndNavigate(targetUrl);
                    } else if (result.isDenied) {
                        hasUnsavedChanges = false;
                        if (targetUrl) {
                            window.location.href = targetUrl;
                        } else {
                            history.back();
                        }
                    }
                });
            } else if (confirm('You have unsaved changes. Click OK to leave or Cancel to stay.')) {
                hasUnsavedChanges = false;
                if (targetUrl) window.location.href = targetUrl;
                else history.back();
            }
        }

        history.pushState(null, document.title, location.href);
        window.addEventListener('popstate', function (e) {
            if (hasUnsavedChanges) {
                history.pushState(null, document.title, location.href);
                showUnsavedModal(null);
            }
        });

        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href], button[data-navigate]');
            if (!link) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            if (hasUnsavedChanges) {
                e.preventDefault();
                e.stopPropagation();
                showUnsavedModal(href);
            }
        }, true);

        updateRenderTypeCards('default');
        renderJSONViewer();
    });
</script>
@endpush
