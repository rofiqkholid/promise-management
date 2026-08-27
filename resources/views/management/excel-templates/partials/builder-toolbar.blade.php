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

        <button @click="treemapOpen = !treemapOpen" 
                class="inline-flex items-center gap-1.5 px-3 h-8 border text-xs font-semibold rounded shadow-2xs transition-colors"
                :class="treemapOpen ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50'"
                title="Toggle Left Tree Map & Variable Explorer">
            <i class="fa-solid fa-sitemap text-[11px]" :class="treemapOpen ? 'text-white' : 'text-blue-600'"></i>
            <span>Tree Map</span>
        </button>

        <button @click="toggleSidebar()" 
                class="inline-flex items-center gap-1.5 px-3 h-8 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded hover:bg-slate-50 shadow-2xs transition-colors">
            <i class="fa-solid text-blue-600" :class="sidebarOpen ? 'fa-angles-right' : 'fa-sliders'"></i>
            <span x-text="sidebarOpen ? 'Collapse' : 'Inspector'"></span>
        </button>
    </div>
</div>
