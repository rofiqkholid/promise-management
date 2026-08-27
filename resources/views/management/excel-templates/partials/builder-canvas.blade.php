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
        <div class="relative inline-block">
            @php
                $totalGridWidth = 36;
                if (!empty($gridData[0])) {
                    foreach ($gridData[0] as $colCell) {
                        $totalGridWidth += ($colWidths[$colCell['col']] ?? 85);
                    }
                }
            @endphp

            <table class="excel-table text-slate-800 dark:text-slate-200" 
                   id="excelGrid"
                   :style="`font-size: ${11 * (zoomLevel / 100)}px; width: ${ {{ $totalGridWidth }} * (zoomLevel / 100)}px; min-width: ${ {{ $totalGridWidth }} * (zoomLevel / 100)}px; max-width: ${ {{ $totalGridWidth }} * (zoomLevel / 100)}px;`">
                <colgroup>
                    <col :style="`width: ${36 * (zoomLevel / 100)}px; min-width: ${36 * (zoomLevel / 100)}px; max-width: ${36 * (zoomLevel / 100)}px;`">
                    @if(!empty($gridData[0]))
                        @foreach($gridData[0] as $colCell)
                            <col :style="`width: ${({{ $colWidths[$colCell['col']] ?? 85 }}) * (zoomLevel / 100)}px; min-width: ${({{ $colWidths[$colCell['col']] ?? 85 }}) * (zoomLevel / 100)}px; max-width: ${({{ $colWidths[$colCell['col']] ?? 85 }}) * (zoomLevel / 100)}px;`">
                        @endforeach
                    @endif
                </colgroup>
                <thead>
                    <tr>
                        <th class="corner-header">#</th>
                        @if(!empty($gridData[0]))
                            @foreach($gridData[0] as $colCell)
                                <th class="col-header" :style="`width: ${({{ $colWidths[$colCell['col']] ?? 85 }}) * (zoomLevel / 100)}px; min-width: ${({{ $colWidths[$colCell['col']] ?? 85 }}) * (zoomLevel / 100)}px; max-width: ${({{ $colWidths[$colCell['col']] ?? 85 }}) * (zoomLevel / 100)}px;`">{{ $colCell['col'] }}</th>
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
                                                $rotStyle = 'writing-mode: vertical-lr; text-orientation: upright; display: inline-block; white-space: nowrap;';
                                            } else {
                                                $cssDeg = 0;
                                                if ($rotDeg > 0 && $rotDeg <= 90) {
                                                    $cssDeg = -$rotDeg;
                                                } elseif ($rotDeg < 0 && $rotDeg >= -90) {
                                                    $cssDeg = -$rotDeg;
                                                } elseif ($rotDeg > 90 && $rotDeg <= 180) {
                                                    $cssDeg = 90 - $rotDeg;
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
                                        $hasValue = ($cellValueStr !== '' && $cellValueStr !== null);
                                        $hasFill = !empty($cell['fill_color']);
                                        $isCellWrapped = $wrapText || str_contains($cellValueStr, "\n");
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
                                        class="relative {{ $cell['is_formula'] ? 'cell-formula' : '' }} {{ $hasValue ? 'cell-has-value' : 'cell-empty' }} {{ $hasFill ? 'cell-has-fill' : '' }} {{ $isCellWrapped ? 'cell-wrapped' : '' }}">
                                        
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

    <!-- Sleek Right-Click Floating Context Menu -->
    <div id="gridContextMenu" class="fixed z-50 hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-sm shadow-xl py-1 w-52 text-xs text-slate-700 dark:text-slate-200">
        <div class="px-3 py-1.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 font-bold text-[10px] text-slate-500 uppercase tracking-wider flex items-center justify-between">
            <span id="ctxMenuCellLabel">Cell --</span>
            <span id="ctxMenuStatusLabel" class="text-[9px] px-1.5 py-0.2 rounded-xs bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono font-bold">Unmapped</span>
        </div>
        <button type="button" id="ctxMenuAssignSingle" class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-blue-600 transition-colors">
            <i class="fa-solid fa-tag text-emerald-600 text-xs w-4"></i>
            <span>Set as Single Field</span>
        </button>
        <button type="button" id="ctxMenuAssignLoop" class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-blue-600 transition-colors">
            <i class="fa-solid fa-rotate text-sky-600 text-xs w-4"></i>
            <span>Set as Loop Column</span>
        </button>
        <button type="button" id="ctxMenuAddRule" class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:text-blue-600 transition-colors">
            <i class="fa-solid fa-code-branch text-amber-500 text-xs w-4"></i>
            <span>Add IF-THEN Logic</span>
        </button>
        <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
        <button type="button" id="ctxMenuClearMapping" class="w-full px-3 py-2 text-left flex items-center gap-2 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-rose-600 transition-colors">
            <i class="fa-solid fa-trash-can text-xs w-4"></i>
            <span>Clear Cell Mapping</span>
        </button>
    </div>
</div>
