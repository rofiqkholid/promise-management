@php
    $groupedFields = $groupedFields ?? ($systemFields ?? collect())->groupBy('group');
@endphp

<!-- LEFT TREE MAP PANEL: VARIABLE PALETTE & MAPPED EXPLORER -->
<div x-show="treemapOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="w-88 bg-white dark:bg-slate-900 border-r border-slate-300 dark:border-slate-800 flex flex-col flex-shrink-0 z-30 shadow-lg h-full overflow-hidden"
     x-data="{ treeTab: 'variables', treeSearch: '', expandedGroups: {} }">

    <!-- TREE MAP HEADER -->
    <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950 shrink-0">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-sitemap text-blue-600 text-sm"></i>
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Tree Map Explorer</h3>
        </div>
        <button @click="treemapOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors" title="Collapse Tree Map">
            <i class="fa-solid fa-angles-left text-xs"></i>
        </button>
    </div>

    <!-- TABS: VARIABLES PALETTE VS MAPPED CELLS -->
    <div class="p-2 border-b border-slate-200 dark:border-slate-800 bg-slate-100/60 dark:bg-slate-900/60 shrink-0">
        <div class="grid grid-cols-2 gap-1 bg-slate-200/80 dark:bg-slate-800 p-0.5 rounded-sm">
            <button type="button" 
                    @click="treeTab = 'variables'"
                    :class="treeTab === 'variables' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 font-bold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800'"
                    class="py-1.5 px-2 text-[11px] rounded-sm transition-all flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-cubes text-[10px]"></i>
                <span>Variables Tree</span>
            </button>
            <button type="button" 
                    @click="treeTab = 'mappings'"
                    :class="treeTab === 'mappings' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 font-bold shadow-2xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-800'"
                    class="py-1.5 px-2 text-[11px] rounded-sm transition-all flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-list-check text-[10px]"></i>
                <span>Active Mappings</span>
            </button>
        </div>

        <!-- SEARCH INPUT -->
        <div class="relative mt-2">
            <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
            <input type="text" 
                   x-model="treeSearch" 
                   @input="handleTreeSearch($event.target.value)"
                   placeholder="Search fields, cells, rules..."
                   class="w-full pl-7 pr-7 py-1 text-xs bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-500 placeholder:text-slate-400">
            <button x-show="treeSearch.length > 0" 
                    @click="treeSearch = ''; handleTreeSearch('')" 
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-[10px]">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- TAB 1 CONTENT: SYSTEM VARIABLES PALETTE TREE -->
    <div x-show="treeTab === 'variables'" class="flex-1 overflow-y-auto overflow-x-hidden p-2 space-y-2 text-xs">
        <div class="px-1 text-[10px] text-slate-400 dark:text-slate-500 italic flex items-center justify-between">
            <span>Tip: Drag field or click to bind selected cell</span>
            <span class="text-[9px] font-bold text-slate-500" id="treeFieldCountBadge">{{ count($systemFields ?? []) }} Fields</span>
        </div>

        <div id="treeVariablesContainer" class="space-y-1.5">
            @foreach($groupedFields as $groupKey => $fields)
            <div class="tree-group-card border border-slate-200 dark:border-slate-800 rounded-sm overflow-hidden bg-slate-50/50 dark:bg-slate-950/40"
                 x-data="{ open: true }"
                 data-group="{{ strtolower($groupKey) }}">
                
                <!-- Group Folder Header -->
                <button type="button" 
                        @click="open = !open"
                        class="w-full px-2.5 py-1.5 bg-slate-100/90 dark:bg-slate-900 flex items-center justify-between text-left hover:bg-slate-200/70 transition-colors">
                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5 truncate">
                        <i class="fa-regular text-amber-500 text-xs" :class="open ? 'fa-folder-open' : 'fa-folder'"></i>
                        <span>{{ strtoupper($groupKey) }}</span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <span class="px-1.5 py-0.2 bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded text-[9px] font-mono font-bold">{{ count($fields) }}</span>
                        <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </div>
                </button>

                <!-- Group Items List -->
                <div x-show="open" class="p-1 space-y-0.5 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    @foreach($fields as $f)
                    @php
                        $dataType = strtolower($f->data_type ?? 'string');
                        $typeBadge = match($dataType) {
                            'number', 'decimal', 'integer' => ['NUM', 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300'],
                            'currency', 'money' => ['RP', 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300'],
                            'date', 'datetime' => ['DATE', 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300'],
                            'image' => ['IMG', 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300'],
                            'qr' => ['QR', 'bg-pink-100 text-pink-700 border-pink-200 dark:bg-pink-950/60 dark:text-pink-300'],
                            default => ['STR', 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400']
                        };
                    @endphp
                    <div class="tree-variable-item group flex items-center justify-between p-1.5 rounded-sm hover:bg-blue-50/70 dark:hover:bg-blue-950/40 border border-transparent hover:border-blue-200 dark:hover:border-blue-800 cursor-grab active:cursor-grabbing transition-all text-xs"
                         draggable="true"
                         data-key="{{ $f->field_key }}"
                         data-label="{{ $f->label }}"
                         data-type="{{ $dataType }}"
                         data-group="{{ strtolower($groupKey) }}"
                         title="Drag to cell or click to assign ({{ $f->field_key }})">
                        
                        <div class="flex items-center gap-1.5 min-w-0 flex-1">
                            <i class="fa-solid fa-grip-vertical text-[10px] text-slate-300 group-hover:text-blue-500 shrink-0"></i>
                            <div class="min-w-0">
                                <span class="font-medium text-slate-700 dark:text-slate-200 block truncate text-[11px] leading-tight group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                    {{ $f->label }}
                                </span>
                                <code class="text-[9px] font-mono text-slate-400 dark:text-slate-500 block truncate">{{ $f->field_key }}</code>
                            </div>
                        </div>

                        <div class="flex items-center gap-1 shrink-0 ms-1">
                            <span class="px-1 py-0.2 rounded text-[8px] font-mono font-bold border {{ $typeBadge[1] }}">
                                {{ $typeBadge[0] }}
                            </span>
                            <span class="tree-mapped-badge hidden px-1 text-emerald-600 dark:text-emerald-400 text-[10px]" title="Already Mapped">
                                <i class="fa-solid fa-circle-check"></i>
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- TAB 2 CONTENT: ACTIVE MAPPINGS EXPLORER -->
    <div x-show="treeTab === 'mappings'" class="flex-1 overflow-y-auto overflow-x-hidden p-2 space-y-2.5 text-xs">
        
        <!-- SECTION A: SINGLE CELLS TREE -->
        <div class="border border-slate-200 dark:border-slate-800 rounded-sm overflow-hidden bg-slate-50/50 dark:bg-slate-950/40"
             x-data="{ open: true }">
            <button type="button" 
                    @click="open = !open"
                    class="w-full px-2.5 py-1.5 bg-emerald-50/70 dark:bg-emerald-950/40 border-b border-emerald-100 dark:border-emerald-900/60 flex items-center justify-between text-left hover:bg-emerald-100/60 transition-colors">
                <span class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                    <i class="fa-solid fa-tag text-[10px]"></i>
                    <span>Single Cell Mappings</span>
                </span>
                <div class="flex items-center gap-1.5">
                    <span class="px-1.5 py-0.2 bg-emerald-200 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 rounded text-[9px] font-mono font-bold" id="treeSingleCountBadge">0</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-emerald-600 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </div>
            </button>
            <div x-show="open" id="treeSingleItemsList" class="p-1 space-y-1 max-h-52 overflow-y-auto overflow-x-hidden bg-white dark:bg-slate-900">
                <p class="text-[10px] text-slate-400 italic p-2 text-center">No single cells mapped yet.</p>
            </div>
        </div>

        <!-- SECTION B: TABLE LOOPS TREE -->
        <div class="border border-slate-200 dark:border-slate-800 rounded-sm overflow-hidden bg-slate-50/50 dark:bg-slate-950/40"
             x-data="{ open: true }">
            <button type="button" 
                    @click="open = !open"
                    class="w-full px-2.5 py-1.5 bg-sky-50/70 dark:bg-sky-950/40 border-b border-sky-100 dark:border-sky-900/60 flex items-center justify-between text-left hover:bg-sky-100/60 transition-colors">
                <span class="text-[11px] font-bold text-sky-800 dark:text-sky-300 flex items-center gap-1.5">
                    <i class="fa-solid fa-rotate text-[10px]"></i>
                    <span>Table Loop Groups</span>
                </span>
                <div class="flex items-center gap-1.5">
                    <span class="px-1.5 py-0.2 bg-sky-200 dark:bg-sky-900 text-sky-800 dark:text-sky-200 rounded text-[9px] font-mono font-bold" id="treeLoopCountBadge">0</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-sky-600 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </div>
            </button>
            <div x-show="open" id="treeLoopItemsList" class="p-1 space-y-2 max-h-64 overflow-y-auto overflow-x-hidden bg-white dark:bg-slate-900">
                <p class="text-[10px] text-slate-400 italic p-2 text-center">No table loops configured yet.</p>
            </div>
        </div>

        <!-- SECTION C: CONDITIONAL RULES TREE -->
        <div class="border border-slate-200 dark:border-slate-800 rounded-sm overflow-hidden bg-slate-50/50 dark:bg-slate-950/40"
             x-data="{ open: true }">
            <button type="button" 
                    @click="open = !open"
                    class="w-full px-2.5 py-1.5 bg-amber-50/70 dark:bg-amber-950/40 border-b border-amber-100 dark:border-amber-900/60 flex items-center justify-between text-left hover:bg-amber-100/60 transition-colors">
                <span class="text-[11px] font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                    <i class="fa-solid fa-code-branch text-[10px]"></i>
                    <span>Conditional IF Rules</span>
                </span>
                <div class="flex items-center gap-1.5">
                    <span class="px-1.5 py-0.2 bg-amber-200 dark:bg-amber-900 text-amber-800 dark:text-amber-200 rounded text-[9px] font-mono font-bold" id="treeRuleCountBadge">0</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-amber-600 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                </div>
            </button>
            <div x-show="open" id="treeRuleItemsList" class="p-1 space-y-1 max-h-52 overflow-y-auto overflow-x-hidden bg-white dark:bg-slate-900">
                <p class="text-[10px] text-slate-400 italic p-2 text-center">No conditional rules defined yet.</p>
            </div>
        </div>
    </div>
</div>
