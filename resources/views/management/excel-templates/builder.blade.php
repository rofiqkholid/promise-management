@extends('layouts.app')

@section('title', 'Visual Mapping Studio · Promise Management')
@section('page_title', 'Visual Mapping Studio')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<style>
    .excel-table-container { overflow: auto; position: relative; user-select: none; max-height: 100%; width: 100%; isolation: isolate; z-index: 1; }
    .excel-table { border-collapse: collapse; table-layout: fixed; font-size: 11px; }
    
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
        border: 1px solid #f3f4f6; 
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
    
    /* Ensure cell text content can overflow over empty neighboring cells smoothly, with pointer-events: none so clicks pass to target cell */
    .excel-table td .cell-content-wrap {
        display: inline-block;
        white-space: nowrap;
        position: relative;
        z-index: 2;
        pointer-events: none;
    }

    .excel-table td * {
        pointer-events: none;
    }
    
    /* Custom Cell Borders matching Excel styles & colors */
    .excel-table td.b-top { border-top-style: solid !important; }
    .excel-table td.b-bottom { border-bottom-style: solid !important; }
    .excel-table td.b-left { border-left-style: solid !important; }
    .excel-table td.b-right { border-right-style: solid !important; }
    
    .excel-table td.cell-formula { background-color: #fef9c3 !important; }
    .excel-table td.cell-mapped-single { background-color: #dcfce7 !important; border: 2px solid #16a34a !important; }
    .excel-table td.cell-mapped-loop { background-color: #e0f2fe !important; border: 2px dashed #0284c7 !important; }
    .excel-table td.cell-selected { 
        outline: 1px solid #2563eb !important; 
        outline-offset: -1px;
        box-shadow: inset 0 0 0 1px #2563eb !important;
        background-color: #eff6ff !important; 
        z-index: 5 !important; 
    }
    .badge-cell { font-size: 9px; padding: 1px 3px; border-radius: 2px; display: inline-block; margin-left: 3px; font-weight: 600; }
</style>
@endpush

@section('content')
<div id="mainStudioCanvas" 
     class="flex h-[calc(100vh-64px)] mt-16 overflow-hidden bg-white dark:bg-slate-900 flex-col border-t border-slate-300 dark:border-slate-800"
     :class="isFullscreen ? 'fixed inset-0 z-[9999] bg-white dark:bg-slate-900 w-screen h-screen !mt-0 !h-screen' : ''"
     x-data="visualMapperStudio()">

    <!-- ===== TOP COMPACT METADATA BAR ===== -->
    <div class="flex items-center justify-between px-4 py-2 bg-slate-100 dark:bg-slate-900 border-b border-slate-300 dark:border-slate-800 flex-shrink-0 text-xs text-slate-800 dark:text-slate-100 gap-3">
        
        <!-- Action Buttons (Height: h-7 / 28px) -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('management.excel-templates.index') }}"
               class="inline-flex items-center gap-1.5 px-2.5 h-7 bg-white dark:bg-slate-800 hover:bg-slate-50 border border-slate-300 dark:border-slate-600 rounded-xs text-xs font-normal text-slate-700 dark:text-slate-200">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Back
            </a>
            <button id="btnSaveConfig"
                    class="inline-flex items-center gap-1.5 px-3 h-7 bg-emerald-600 hover:bg-emerald-700 rounded-xs text-xs font-medium text-white shadow-xs">
                <i class="fa-solid fa-floppy-disk text-[10px]"></i> Save Mapping
            </button>
        </div>

        <!-- Compact Template Info Row & Sheet Tabs -->
        <div class="flex items-center gap-2 px-3 h-7 border border-slate-300 dark:border-slate-800 rounded-xs bg-white dark:bg-slate-950 flex-1 min-w-0">
            <div class="flex items-center gap-1.5 min-w-0 flex-shrink-0">
                <i class="fa-solid fa-file-excel text-emerald-600 text-sm flex-shrink-0"></i>
                <span class="font-bold text-slate-800 dark:text-slate-100 truncate text-xs">{{ $template->template_name }}</span>
            </div>
            <span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded text-[10px] font-semibold uppercase flex-shrink-0">{{ $template->template_type }}</span>
            <span class="px-1.5 py-0.5 bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 rounded text-[10px] font-mono font-semibold flex-shrink-0">Rev {{ $template->revision ?? '0' }}</span>
            
            <!-- Multi-Sheet Dropdown Switcher (Always Visible) -->
            <div class="flex items-center gap-1.5 ms-2 ps-2 border-s border-slate-200 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex-shrink-0"><i class="fa-regular fa-file-excel me-1 text-emerald-600"></i>Sheet:</span>
                <select @change="changeSheet($event.target.value)" 
                        @if(empty($sheetNames) || count($sheetNames) <= 1) disabled @endif
                        class="bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none cursor-pointer disabled:bg-slate-100 dark:disabled:bg-slate-800 disabled:text-slate-400 disabled:cursor-not-allowed">
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

        <!-- Zoom Controls, Fullscreen & Sidebar Toggle (Height: h-7 / 28px) -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <div class="flex items-center bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xs px-2 h-7 gap-2 text-xs">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-[10px]"></i>
                <button @click="zoomOut()" class="hover:text-blue-600 font-bold px-1" title="Zoom Out (-)"><i class="fa-solid fa-minus text-[10px]"></i></button>
                <input type="range" min="50" max="150" step="5" x-model="zoomLevel" @input="handleSliderZoom()" class="w-20 h-1 bg-slate-200 rounded-lg appearance-none cursor-pointer">
                <button @click="zoomIn()" class="hover:text-blue-600 font-bold px-1" title="Zoom In (+)"><i class="fa-solid fa-plus text-[10px]"></i></button>
                <span class="text-[11px] font-mono text-slate-600 dark:text-slate-300 w-9 text-right" x-text="zoomLevel + '%'"></span>
                <button @click="resetZoom()" class="text-[10px] text-blue-600 underline ms-0.5">100%</button>
            </div>

            <!-- Fullscreen Main Content Button -->
            <button @click="toggleFullscreen()" 
                    class="inline-flex items-center gap-1.5 px-2.5 h-7 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium rounded-xs hover:bg-slate-50"
                    :title="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen Main Content'">
                <i class="fa-solid" :class="isFullscreen ? 'fa-compress' : 'fa-expand'"></i>
                <span x-text="isFullscreen ? 'Exit Full' : 'Fullscreen'"></span>
            </button>

            <button @click="toggleSidebar()" 
                    class="inline-flex items-center gap-1.5 px-2.5 h-7 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium rounded-xs hover:bg-slate-50">
                <i class="fa-solid" :class="sidebarOpen ? 'fa-angles-right' : 'fa-sliders'"></i>
                <span x-text="sidebarOpen ? 'Collapse' : 'Inspector'"></span>
            </button>
        </div>
    </div>

    <!-- ===== MAIN SPREADSHEET CANVAS ===== -->
    <div class="flex-1 flex overflow-hidden relative">

        <div class="flex-1 flex flex-col overflow-hidden bg-slate-100 dark:bg-slate-950 p-2 relative"
             @wheel="handleWheelZoom($event)">
            
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
                    
                    <!-- Embedded Images Overlay -->
                    <template x-for="(img, idx) in images" :key="idx">
                        <img :src="img.src" 
                             :style="getImageStyle(img)"
                             class="absolute z-20 pointer-events-none shadow-xs" />
                    </template>

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

                                                $bClasses = [];
                                                if ($cell['borders']['top']['active']) $bClasses[] = 'b-top';
                                                if ($cell['borders']['bottom']['active']) $bClasses[] = 'b-bottom';
                                                if ($cell['borders']['left']['active']) $bClasses[] = 'b-left';
                                                if ($cell['borders']['right']['active']) $bClasses[] = 'b-right';
                                                $bClassStr = implode(' ', $bClasses);

                                                $style = "{$bTop} {$bBottom} {$bLeft} {$bRight} ";
                                                if ($cell['fill_color']) $style .= "background-color: {$cell['fill_color']}; ";
                                                if ($cell['font_color']) $style .= "color: {$cell['font_color']}; ";
                                                if ($cell['is_bold']) $style .= "font-weight: bold; ";
                                                if ($cell['is_italic']) $style .= "font-style: italic; ";
                                                if ($cell['align']) $style .= "text-align: {$cell['align']}; ";

                                                $colSpan = $cell['merge']['colspan'] ?? 1;
                                                $rowSpan = $cell['merge']['rowspan'] ?? 1;
                                            @endphp
                                            <td data-cell="{{ $cell['cell'] }}" 
                                                data-col="{{ $cell['col'] }}" 
                                                data-row="{{ $cell['row'] }}"
                                                data-colspan="{{ $colSpan }}"
                                                data-rowspan="{{ $rowSpan }}"
                                                data-is-formula="{{ $cell['is_formula'] ? 'true' : 'false' }}"
                                                @if($colSpan > 1) colspan="{{ $colSpan }}" @endif
                                                @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif
                                                :style="`height: ${({{ $rowHeights[$rowIndex + 1] ?? 26 }}) * (zoomLevel / 100)}px; line-height: ${({{ $rowHeights[$rowIndex + 1] ?? 26 }}) * (zoomLevel / 100) - 4}px; font-size: ${11 * (zoomLevel / 100)}px; padding: ${1 * (zoomLevel / 100)}px ${4 * (zoomLevel / 100)}px; {{ $style }}`"
                                                class="{{ $bClassStr }} {{ $cell['is_formula'] ? 'cell-formula' : '' }}">
                                                <span class="cell-content-wrap">{{ $cell['value'] }}</span>
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
             class="w-96 bg-white dark:bg-slate-900 border-l border-slate-300 dark:border-slate-800 flex flex-col overflow-y-auto flex-shrink-0 z-[500] shadow-lg">
            
            <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950">
                <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-sliders text-blue-600"></i> Cell Mapping Inspector
                </h3>
                <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-angles-right"></i></button>
            </div>

            <div class="p-4 space-y-4 flex-1">
                <div id="noSelectionNotice" class="flex flex-col items-center justify-center text-center py-12 text-slate-400 dark:text-slate-500">
                    <i class="fa-regular fa-hand-pointer text-4xl mb-3 text-slate-300 dark:text-slate-600"></i>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Click any cell on the Excel preview grid</p>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">to open mapping controls.</p>
                </div>

                <div id="mappingControlForm" class="hidden space-y-4">
                    <div class="bg-blue-50 dark:bg-blue-950/40 p-3 border border-blue-200 dark:border-blue-800 rounded-xs flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider block">Selected Coordinate</span>
                            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="selectedCellLabel">C9</span>
                        </div>
                        <i class="fa-solid fa-crosshairs text-blue-400 text-xl"></i>
                    </div>

                    <!-- Data Source Mode Switcher -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Data Source Mode</label>
                        <div class="flex bg-slate-100 dark:bg-slate-950 p-1 border border-slate-200 dark:border-slate-800 text-xs font-semibold">
                            <button type="button" id="sourceTypeVariableBtn" class="flex-1 py-1.5 bg-white dark:bg-slate-800 text-blue-600 shadow-xs transition-all">
                                <i class="fa-solid fa-database me-1"></i> System Variable
                            </button>
                            <button type="button" id="sourceTypeStaticBtn" class="flex-1 py-1.5 text-slate-500 hover:text-slate-700 transition-all">
                                <i class="fa-solid fa-square-root-variable me-1"></i> Custom Text / Formula
                            </button>
                        </div>
                    </div>

                    <!-- Variable Source Box -->
                    <div id="variableSourceBox">
                        <!-- Accordion Smart Suggestion Container -->
                        <div id="smartSuggestBanner" class="hidden mb-2 border border-purple-200 dark:border-purple-800 rounded-xs overflow-hidden bg-purple-50/50 dark:bg-purple-950/30">
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

                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Target System Field Variable</label>
                        <select id="globalFieldSelect" class="w-full">
                            <option value="">-- Select System Field Variable --</option>
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
                    <div id="staticSourceBox" class="hidden space-y-1">
                        <label class="block text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Custom Text or Formula Input</label>
                        <input type="text" id="staticValueInput" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 h-9 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 rounded-none font-mono" placeholder="e.g. JAC270C-45/45 or =C{row}*0.9">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight">Tulis teks biasa atau formula Excel (Gunakan <code class="bg-slate-200 dark:bg-slate-800 px-1 font-semibold">{row}</code> untuk nomor baris dinamis, misal: <code class="bg-slate-200 dark:bg-slate-800 px-1 font-semibold">=C{row}*0.9</code>).</p>
                    </div>

                    <!-- Tab Mode -->
                    <div class="flex bg-slate-100 dark:bg-slate-950 p-1 border border-slate-200 dark:border-slate-800 text-xs font-semibold">
                        <button id="tabSingleBtn" class="flex-1 py-1.5 bg-white dark:bg-slate-800 text-blue-600 shadow-xs">Single Field</button>
                        <button id="tabLoopBtn" class="flex-1 py-1.5 text-slate-500 hover:text-slate-700">Table Loop</button>
                    </div>

                    <!-- Single Mapping Tab -->
                    <div id="singleFieldBox" class="space-y-3">
                        <!-- Value Type / Render Options -->
                        <div class="space-y-2 pt-1 border-t border-slate-200 dark:border-slate-800">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Value Type / Render</label>
                                <select id="singleRenderType" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-1 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none">
                                    <option value="text">Standard Text / Raw</option>
                                    <option value="number">Numeric / Decimal</option>
                                    <option value="currency">Currency (Rp #,##0)</option>
                                    <option value="date">Date (YYYY-MM-DD)</option>
                                    <option value="image">Dynamic Image / Stamp</option>
                                    <option value="qr">Dynamic QR Code</option>
                                </select>
                            </div>
                            <div id="imageSizeContainer" class="hidden">
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">Image Size (Width x Height px)</label>
                                <input type="text" id="singleImageSize" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 py-1 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none" placeholder="e.g. 100x40" value="100x40">
                            </div>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button id="btnAssignSingle" disabled class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white text-xs font-medium transition-colors">
                                Assign Single Mapping
                            </button>
                            <button id="btnUnsetSingle" type="button" class="hidden px-3 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-rose-600 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-medium transition-colors" title="Unset / Remove mapping from selected cell">
                                <i class="fa-solid fa-trash-can me-1"></i> Unset
                            </button>
                        </div>
                    </div>

                    <!-- Loop Mapping Tab -->
                    <div id="loopFieldBox" class="hidden space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Loop Strategy / Mode</label>
                            <select id="loopModeSelect" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 h-9 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none">
                                <option value="flat">Single-Row Loop (Flat Table)</option>
                                <option value="nested_block">Nested Parent-Child Block (Multi-Row Repeater)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Loop Direction</label>
                                <select id="loopDirectionSelect" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none">
                                    <option value="down">Vertical (Down / Rows)</option>
                                    <option value="right">Horizontal (Right / Columns)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Row Behavior</label>
                                <select id="loopInsertBehaviorSelect" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-2 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none">
                                    <option value="insert_duplicate">Insert New Rows & Copy Style</option>
                                    <option value="overwrite">Overwrite Existing Cells (No Insert)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Loop Group Identifier</label>
                            <input type="text" id="loopGroupName" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none" placeholder="e.g. tooling_detail / material">
                        </div>
                        <div id="stopConditionContainer" class="hidden pt-2 border-t border-slate-200 dark:border-slate-800">
                            <label class="block text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wide mb-1">
                                Visual Stop Condition 
                                <span class="text-[10px] text-slate-400 font-normal lowercase">(Active for Overwrite & Horizontal modes)</span>
                            </label>
                            <select id="stopConditionType" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 h-8 text-xs text-slate-800 dark:text-slate-100 mb-2 focus:outline-none focus:border-blue-500 rounded-none">
                                <option value="cell_value_contains">Cell Value Contains</option>
                                <option value="cell_value_equals">Cell Value Exact Equals</option>
                                <option value="is_empty">Cell Is Blank / Empty</option>
                            </select>
                            <input type="text" id="stopConditionValue" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 px-3 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 rounded-none" placeholder="e.g. TOTAL COST">
                        </div>
                        <div class="flex gap-2">
                            <button id="btnAssignLoop" disabled class="flex-1 py-2 bg-sky-600 hover:bg-sky-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white text-xs font-medium transition-colors">
                                Assign Table Loop Column
                            </button>
                            <button id="btnUnsetLoop" type="button" class="hidden px-3 py-2 bg-slate-200 dark:bg-slate-800 hover:bg-rose-600 hover:text-white text-slate-700 dark:text-slate-300 text-xs font-medium transition-colors" title="Unset / Remove loop column from selected cell">
                                <i class="fa-solid fa-trash-can me-1"></i> Unset
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Generated Config Payload View -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-code text-blue-600"></i> Config Payload
                        </h4>
                        <button id="btnClearAll" class="px-2 py-0.5 text-xs text-rose-600 border border-rose-200 hover:bg-rose-50">Reset</button>
                    </div>
                    <pre id="jsonConfigViewer" class="bg-slate-950 text-emerald-400 p-3 rounded-none text-[11px] overflow-auto max-h-[200px] font-mono border border-slate-800"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function visualMapperStudio() {
        return {
            sidebarOpen: true,
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
                    }, 200);
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

            handleSliderZoom() {
                // Alpine x-model handles smooth zoom level
            },

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
                if (!cellTd) return 'display: none;';
                const left = cellTd.offsetLeft;
                const top = cellTd.offsetTop;
                return `left: ${left}px; top: ${top}px; width: ${img.width}px; height: ${img.height}px;`;
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
            width: '100%'
        }).on('change', function() {
            const val = $(this).val();
            document.getElementById('btnAssignSingle').disabled = !val;
            document.getElementById('btnAssignLoop').disabled = !val;

            const selectedOpt = $(this).find('option:selected');
            const dataType = selectedOpt.data('type');
            if (dataType === 'number' || dataType === 'decimal') {
                singleRenderType.value = 'number';
            } else if (dataType === 'currency' || dataType === 'money') {
                singleRenderType.value = 'currency';
            } else if (dataType === 'date' || dataType === 'datetime') {
                singleRenderType.value = 'date';
            }
            if (typeof getAutoGroupName === 'function' && !loopGroupName.dataset.userModified) {
                const mode = document.getElementById('loopModeSelect').value || 'flat';
                loopGroupName.value = getAutoGroupName(mode);
            }
        });

        const tabSingleBtn = document.getElementById('tabSingleBtn');
        const tabLoopBtn = document.getElementById('tabLoopBtn');
        const singleFieldBox = document.getElementById('singleFieldBox');
        const loopFieldBox = document.getElementById('loopFieldBox');

        tabSingleBtn.addEventListener('click', () => {
            singleFieldBox.classList.remove('hidden');
            loopFieldBox.classList.add('hidden');
            tabSingleBtn.className = "flex-1 py-1.5 bg-white dark:bg-slate-800 text-blue-600 shadow-xs";
            tabLoopBtn.className = "flex-1 py-1.5 text-slate-500 hover:text-slate-700";
        });

        tabLoopBtn.addEventListener('click', () => {
            loopFieldBox.classList.remove('hidden');
            singleFieldBox.classList.add('hidden');
            tabLoopBtn.className = "flex-1 py-1.5 bg-white dark:bg-slate-800 text-blue-600 shadow-xs";
            tabSingleBtn.className = "flex-1 py-1.5 text-slate-500 hover:text-slate-700";
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
            if (mappingConfig.table_loops) {
                mappingConfig.table_loops.forEach(loop => {
                    if (!loop.direction) loop.direction = 'down';
                    if (!loop.insert_behavior) loop.insert_behavior = 'insert_duplicate';
                });
            }
            jsonConfigViewer.textContent = JSON.stringify(mappingConfig, null, 2);
            applyVisualHighlights();
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
                    const renderTag = item.render_type && item.render_type !== 'text' ? ` [${item.render_type.toUpperCase()}]` : '';
                    td.insertAdjacentHTML('beforeend', `<span class="badge bg-emerald-600 text-white badge-cell">${item.field_key}${renderTag}</span>`);
                }
            });

            // Highlight table loop mapped columns
            mappingConfig.table_loops.forEach(loop => {
                const startRow = loop.start_row;
                Object.entries(loop.columns).forEach(([fieldKey, colLetter]) => {
                    const td = document.querySelector(`#excelGrid td[data-col="${colLetter}"][data-row="${startRow}"]`);
                    if (td) {
                        td.classList.add('cell-mapped-loop');
                        td.insertAdjacentHTML('beforeend', `<span class="badge bg-sky-600 text-white badge-cell">${loop.group}:${fieldKey}</span>`);
                    }
                });
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

            if (existingSingle && existingSingle.value_type === 'static') {
                setDataSourceMode('static');
                staticValueInput.value = existingSingle.static_value || '';
            } else if (existingLoopStaticVal) {
                setDataSourceMode('static');
                staticValueInput.value = existingLoopStaticVal;
            } else {
                setDataSourceMode('variable');
                const activeFieldKey = existingSingle ? existingSingle.field_key : (existingLoopCol || '');
                $('#globalFieldSelect').val(activeFieldKey).trigger('change');
            }

            // Smart Multi-Suggestions Check for current clicked cell (Accordion View)
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
                        pillBtn.className = 'px-2 py-0.5 bg-purple-100 dark:bg-purple-900/50 hover:bg-purple-600 hover:text-white text-purple-700 dark:text-purple-300 text-[11px] font-semibold rounded-xs transition-colors border border-purple-200 dark:border-purple-700 text-left';
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
                singleRenderType.value = existingSingle.render_type || 'text';
                singleImageSize.value = existingSingle.image_size || '100x40';
                document.getElementById('btnUnsetSingle').classList.remove('hidden');
                tabSingleBtn.click();
            } else {
                singleRenderType.value = 'text';
                singleImageSize.value = '100x40';
                document.getElementById('btnUnsetSingle').classList.add('hidden');
            }
            toggleImageSizeVisibility();

            if (existingLoopCol) {
                loopGroupName.value = existingLoopGroup || '';
                document.getElementById('loopModeSelect').value = existingLoopMode;
                document.getElementById('loopDirectionSelect').value = existingLoopDirection;
                document.getElementById('loopInsertBehaviorSelect').value = existingLoopBehavior;
                document.getElementById('btnUnsetLoop').classList.remove('hidden');
                if (!existingSingle) tabLoopBtn.click();
            } else {
                document.getElementById('btnUnsetLoop').classList.add('hidden');
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
                sourceTypeStaticBtn.className = "flex-1 py-1.5 bg-white dark:bg-slate-800 text-emerald-600 shadow-xs transition-all";
                sourceTypeVariableBtn.className = "flex-1 py-1.5 text-slate-500 hover:text-slate-700 transition-all";
                staticSourceBox.classList.remove('hidden');
                variableSourceBox.classList.add('hidden');
                document.getElementById('btnAssignSingle').disabled = !staticValueInput.value.trim();
                document.getElementById('btnAssignLoop').disabled = !staticValueInput.value.trim();
            } else {
                sourceTypeVariableBtn.className = "flex-1 py-1.5 bg-white dark:bg-slate-800 text-blue-600 shadow-xs transition-all";
                sourceTypeStaticBtn.className = "flex-1 py-1.5 text-slate-500 hover:text-slate-700 transition-all";
                variableSourceBox.classList.remove('hidden');
                staticSourceBox.classList.add('hidden');
                const val = $('#globalFieldSelect').val();
                document.getElementById('btnAssignSingle').disabled = !val;
                document.getElementById('btnAssignLoop').disabled = !val;
            }
        }

        sourceTypeVariableBtn.addEventListener('click', () => setDataSourceMode('variable'));
        sourceTypeStaticBtn.addEventListener('click', () => setDataSourceMode('static'));

        staticValueInput.addEventListener('input', function() {
            if (currentDataSourceMode === 'static') {
                const val = this.value.trim();
                document.getElementById('btnAssignSingle').disabled = !val;
                document.getElementById('btnAssignLoop').disabled = !val;
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
                    sheet_name: activeSheetName,
                    render_type: singleRenderType.value
                };
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
                        sheet_name: activeSheetName,
                        render_type: singleRenderType.value
                    };
                    if (singleRenderType.value === 'image' || singleRenderType.value === 'qr') {
                        singleObj.image_size = singleImageSize.value.trim() || '100x40';
                    }
                    mappingConfig.single_fields.push(singleObj);
                    document.getElementById('btnUnsetSingle').classList.remove('hidden');
                }
            }

            renderJSONViewer();
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
                    alert('Please enter custom text or formula for this loop column!');
                    return;
                }
                fieldKey = 'static_col_' + currentSelectedCell.col + '_' + currentSelectedCell.row;
            } else {
                fieldKey = $('#globalFieldSelect').val();
                if (!fieldKey) {
                    alert('Please select system field variable!');
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
            });

            const loopDirection = document.getElementById('loopDirectionSelect').value;
            const insertBehavior = document.getElementById('loopInsertBehaviorSelect').value;

            let existingLoop = mappingConfig.table_loops.find(l => l.group === group);
            if (!existingLoop) {
                existingLoop = {
                    group: group,
                    sheet_index: activeSheetIndex,
                    sheet_name: activeSheetName,
                    loop_mode: loopMode,
                    direction: loopDirection,
                    insert_behavior: insertBehavior,
                    start_row: currentSelectedCell.row,
                    stop_condition: {
                        type: stopConditionType.value,
                        column: currentSelectedCell.col,
                        value: stopConditionValue.value.trim()
                    },
                    columns: {},
                    static_values: {}
                };
                mappingConfig.table_loops.push(existingLoop);
            } else {
                existingLoop.loop_mode = loopMode;
                existingLoop.direction = loopDirection;
                existingLoop.insert_behavior = insertBehavior;
                existingLoop.sheet_index = activeSheetIndex;
                existingLoop.sheet_name = activeSheetName;
                if (!existingLoop.static_values) existingLoop.static_values = {};
            }

            existingLoop.columns[fieldKey] = currentSelectedCell.col;
            if (isStaticMode) {
                if (!existingLoop.static_values) existingLoop.static_values = {};
                existingLoop.static_values[fieldKey] = staticVal;
            }

            mappingConfig.table_loops = mappingConfig.table_loops.filter(l => Object.keys(l.columns).length > 0);

            document.getElementById('btnUnsetLoop').classList.remove('hidden');
            renderJSONViewer();
        });

        document.getElementById('btnUnsetSingle').addEventListener('click', function () {
            if (!currentSelectedCell) return;
            mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== currentSelectedCell.cell);
            
            $('#globalFieldSelect').val('').trigger('change');
            document.getElementById('btnUnsetSingle').classList.add('hidden');
            renderJSONViewer();
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
                alert(data.message || 'Mapping config saved successfully!');
            })
            .catch(err => alert('Failed to save mapping config.'));
        });

        document.getElementById('btnClearAll').addEventListener('click', function () {
            if (confirm('Reset all visual mappings?')) {
                mappingConfig.single_fields = [];
                mappingConfig.table_loops = [];
                renderJSONViewer();
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

        renderJSONViewer();
    });
</script>
@endpush
