<!-- COLLAPSIBLE INSPECTOR PANEL -->
<div x-show="sidebarOpen" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     class="w-88 bg-white dark:bg-slate-900 border-l border-slate-300 dark:border-slate-800 flex flex-col flex-shrink-0 z-30 shadow-lg h-full overflow-hidden">
    
    <!-- FIXED INSPECTOR HEADER (MATCHES TREEMAP HEADER) -->
    <div class="p-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950 shrink-0">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-sliders text-blue-600 text-sm"></i>
            <h3 class="text-xs font-bold text-slate-800 dark:text-white uppercase tracking-wider">Mapping Inspector</h3>
        </div>
        <button @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors" title="Collapse Inspector">
            <i class="fa-solid fa-angles-right text-xs"></i>
        </button>
    </div>

    <!-- SCROLLABLE INSPECTOR BODY -->
    <div class="p-2.5 space-y-2.5 flex-1 overflow-y-auto overflow-x-hidden text-xs">
        
        <!-- EMPTY STATE (NO SELECTION) -->
        <div id="noSelectionNotice" class="flex flex-col items-center justify-center text-center py-16 text-slate-400 dark:text-slate-500">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                <i class="fa-regular fa-hand-pointer text-xl text-slate-400 dark:text-slate-500"></i>
            </div>
            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">No Cell Selected</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 max-w-[200px]">Click any cell on the Excel grid or drag a variable from the Tree Map.</p>
        </div>

        <!-- MAPPING CONTROLS FORM (SHOWN WHEN CELL IS SELECTED) -->
        <div id="mappingControlForm" class="hidden space-y-2.5">
            
            <!-- SELECTED CELL OVERVIEW CARD -->
            <div class="bg-gradient-to-r from-blue-50/70 to-indigo-50/70 dark:from-blue-950/30 dark:to-indigo-950/30 p-2.5 border border-blue-200/80 dark:border-blue-800/60 rounded-sm space-y-1.5 shadow-2xs">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cell:</span>
                        <span class="text-lg font-black text-blue-600 dark:text-blue-400 font-mono leading-none" id="selectedCellLabel">--</span>
                    </div>
                    
                    <div id="cellInfoBadgesContainer" class="flex flex-wrap items-center justify-end gap-1 text-[10px] min-w-0">
                        <span id="badgeMappingType" class="px-2 py-0.5 bg-sky-600 text-white font-bold rounded-sm shadow-2xs max-w-full text-right leading-tight break-words text-[9px] uppercase">Unmapped</span>
                        <span id="badgeCellOrigin" class="px-1.5 py-0.5 bg-slate-200/80 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono font-medium rounded-sm truncate hidden max-w-[130px] text-[9px]">Excel Text</span>
                    </div>
                </div>

                <!-- Active Mapping Value Preview Row -->
                <div id="cellActiveValuePreview" class="text-[10px] font-mono text-slate-700 dark:text-slate-200 bg-white/90 dark:bg-slate-900/90 px-2 py-1 rounded-sm border border-blue-200/60 dark:border-blue-900/50 hidden flex items-center gap-1.5 min-w-0">
                    <span id="cellActiveValuePrefix" class="text-[9px] font-bold text-slate-400 font-sans uppercase shrink-0">Mapped:</span>
                    <span id="cellActiveValueText" class="font-bold text-blue-700 dark:text-blue-300 truncate break-all"></span>
                </div>
            </div>

            <!-- MODE TABS: 1. SINGLE CELL | 2. TABLE LOOP -->
            <div class="grid grid-cols-2 p-1 bg-slate-100 dark:bg-slate-800/90 rounded-lg border border-slate-200/80 dark:border-slate-700/60 gap-1 shadow-inner">
                <button type="button" 
                        id="tabSingleBtn"
                        @click="inspectorTab = 'single'"
                        :class="inspectorTab === 'single' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 font-bold shadow-xs border border-slate-200/60 dark:border-slate-800' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 font-medium'"
                        class="py-1.5 px-2 rounded-md text-[11px] flex items-center justify-center gap-1.5 transition-all">
                    <i class="fa-solid fa-tag text-[10px]"></i>
                    <span>Single Cell</span>
                </button>
                <button type="button" 
                        id="tabLoopBtn"
                        @click="inspectorTab = 'loop'"
                        :class="inspectorTab === 'loop' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 font-bold shadow-xs border border-slate-200/60 dark:border-slate-800' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 font-medium'"
                        class="py-1.5 px-2 rounded-md text-[11px] flex items-center justify-center gap-1.5 transition-all">
                    <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
                    <span>Table Loop</span>
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- CARD 1: TARGET VARIABLE / FORMULA SELECTOR               -->
            <!-- (ACTIVE FOR BOTH SINGLE FIELD & TABLE LOOP MODES)        -->
            <!-- ======================================================== -->
            <div x-show="inspectorTab === 'single' || inspectorTab === 'loop'" class="border border-slate-200 dark:border-slate-800 rounded-lg p-2.5 bg-slate-50/50 dark:bg-slate-950/40 space-y-2">
                <!-- Source Mode Pill Switcher (Variable vs Free Text) -->
                <div class="grid grid-cols-2 p-1 bg-slate-200/70 dark:bg-slate-800/70 rounded-lg gap-1 border border-slate-200 dark:border-slate-700/50">
                    <button type="button" id="sourceTypeVariableBtn" class="py-2 px-2.5 bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 font-bold shadow-2xs rounded-md text-[11px] flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-database text-[10px]"></i>
                        <span x-text="inspectorTab === 'loop' ? 'Loop Variable' : 'System Variable'">System Variable</span>
                    </button>
                    <button type="button" id="sourceTypeStaticBtn" class="py-2 px-2.5 text-slate-600 dark:text-slate-400 hover:text-slate-800 rounded-md text-[11px] font-medium flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-font text-[10px] text-emerald-600"></i>
                        <span>Free Text / Formula</span>
                    </button>
                </div>

                <!-- Variable Dropdown Box -->
                <div id="variableSourceBox" class="space-y-1.5">
                    <!-- Smart Suggest Banner -->
                    <div id="smartSuggestBanner" class="hidden border border-purple-200 dark:border-purple-800 rounded-md overflow-hidden bg-purple-50/50 dark:bg-purple-950/30">
                        <button id="suggestAccordionToggle" type="button" class="w-full px-2 py-1 bg-purple-100/80 dark:bg-purple-900/40 hover:bg-purple-200/80 flex items-center justify-between text-left transition-colors">
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-wand-magic-sparkles text-purple-600 dark:text-purple-400 text-xs"></i>
                                <span class="text-[10px] font-semibold text-purple-800 dark:text-purple-200">Smart Suggestions</span>
                                <span id="suggestMatchBadge" class="px-1 py-0.1 bg-purple-600 text-white text-[8px] font-bold rounded-sm">0</span>
                            </div>
                            <i id="suggestAccordionIcon" class="fa-solid fa-chevron-down text-purple-500 text-[8px] transition-transform duration-200"></i>
                        </button>
                        <div id="suggestAccordionBody" class="hidden p-1.5 border-t border-purple-200 dark:border-purple-800/60 bg-purple-50/40 dark:bg-purple-950/20">
                            <div id="suggestedPillsContainer" class="flex flex-wrap gap-1"></div>
                        </div>
                    </div>

                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                        <span x-show="inspectorTab === 'single'">Target System Variable</span>
                        <span x-show="inspectorTab === 'loop'">Column System Variable</span>
                    </label>
                    <select id="globalFieldSelect" class="w-full">
                        <option value="">-- Select System Field Variable --</option>
                        <optgroup label="SYSTEM PRESET / UTILITIES">
                            <option value="auto_number" data-group="utility" data-type="integer">[Auto Number] Row Index (1, 2, 3...)</option>
                        </optgroup>
                        @php
                            $groupedFields = $groupedFields ?? ($systemFields ?? collect())->groupBy('group');
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

                <!-- Static Value / Formula Box -->
                <div id="staticSourceBox" class="hidden space-y-1.5">
                    <label class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Free Text or Formula Input</label>
                    <input type="text" id="staticValueInput" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 px-2.5 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-emerald-500 rounded-md font-mono" placeholder="e.g. JAC270C or =C{row}*0.9">
                    
                    <button id="btnConvertDynamicFormula" type="button" class="w-full py-1 px-2 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 text-emerald-800 dark:text-emerald-200 text-[11px] font-semibold rounded-md border border-emerald-300 dark:border-emerald-700 flex items-center justify-center gap-1 transition-colors">
                        <i class="fa-solid fa-wand-magic-sparkles text-emerald-600 text-[10px]"></i>
                        <span>Convert to Dynamic ({row})</span>
                    </button>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- CARD 2: TABLE LOOP SPECIFIC REPEATER CONFIGURATION       -->
            <!-- (SHOWN ONLY WHEN IN LOOP TAB)                            -->
            <!-- ======================================================== -->
            <div x-show="inspectorTab === 'loop'" class="border border-sky-200 dark:border-sky-900/60 rounded-lg p-2.5 bg-sky-50/40 dark:bg-sky-950/20 space-y-2">
                <!-- Group Name -->
                <div>
                    <label class="block text-[10px] font-bold text-sky-800 dark:text-sky-300 uppercase tracking-wide mb-1">Loop Group Identifier</label>
                    <input type="text" id="loopGroupName" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 px-2.5 h-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-sky-500 rounded-md font-mono" placeholder="e.g. items_detail">
                </div>

                <!-- Loop Mode -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Loop Strategy</label>
                    <select id="loopModeSelect" class="hidden">
                        <option value="flat">Single-Row Loop (Flat Table)</option>
                        <option value="nested_block">Nested Parent-Child Block (Multi-Row)</option>
                    </select>
                    <div class="grid grid-cols-2 gap-1.5">
                        <button type="button" data-loop-mode="flat" class="loop-mode-btn p-1.5 border rounded-md text-left bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-2xs">
                            <span class="text-[11px] font-bold flex items-center gap-1"><i class="fa-solid fa-list text-[10px]"></i> Flat Table</span>
                            <span class="text-[9px] text-slate-400 block">Single-row list</span>
                        </button>
                        <button type="button" data-loop-mode="nested_block" class="loop-mode-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-md text-left bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400">
                            <span class="text-[11px] font-bold flex items-center gap-1"><i class="fa-solid fa-layer-group text-[10px]"></i> Nested Block</span>
                            <span class="text-[9px] text-slate-400 block">Parent + child rows</span>
                        </button>
                    </div>
                </div>

                <!-- Direction & Row Behavior -->
                <div class="grid grid-cols-2 gap-1.5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Direction</label>
                        <select id="loopDirectionSelect" class="hidden">
                            <option value="down">Vertical</option>
                            <option value="right">Horizontal</option>
                        </select>
                        <div class="grid grid-cols-2 gap-1">
                            <button type="button" data-dir-val="down" class="dir-btn py-1 border rounded-md text-center bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-2xs font-bold text-[9px]">
                                Down
                            </button>
                            <button type="button" data-dir-val="right" class="dir-btn py-1 border border-slate-200 dark:border-slate-800 rounded-md text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-[9px]">
                                Right
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Row Behavior</label>
                        <select id="loopInsertBehaviorSelect" class="hidden">
                            <option value="insert_duplicate">Insert</option>
                            <option value="overwrite">Overwrite</option>
                        </select>
                        <div class="grid grid-cols-2 gap-1">
                            <button type="button" data-behavior-val="insert_duplicate" class="behavior-btn py-1 border rounded-md text-center bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-2xs font-bold text-[9px]">
                                Insert
                            </button>
                            <button type="button" data-behavior-val="overwrite" class="behavior-btn py-1 border border-slate-200 dark:border-slate-800 rounded-md text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-[9px]">
                                Overwrite
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Blank Rows After -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Blank Rows After Loop</label>
                    <input type="number" id="blankRowsAfterInput" min="0" max="20" value="0" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 px-2.5 h-7.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-sky-500 rounded-md font-mono" placeholder="0">
                </div>

                <!-- Collapsible Advanced Settings (Sheet Splitter & Stop Condition) -->
                <div class="border-t border-sky-200 dark:border-sky-900/60 pt-1.5">
                    <button type="button" @click="showAdvancedLoop = !showAdvancedLoop" class="w-full flex items-center justify-between text-[10px] font-bold text-sky-700 dark:text-sky-300 uppercase py-1">
                        <span>Advanced Loop Settings</span>
                        <i class="fa-solid fa-chevron-down text-[8px] transition-transform" :class="showAdvancedLoop ? 'rotate-180' : ''"></i>
                    </button>
                    
                    <div x-show="showAdvancedLoop" class="space-y-2 pt-1.5">
                        <!-- Sheet Loop -->
                        <div class="p-2 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-md space-y-1">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="splitSheetPerParentToggle" class="w-3.5 h-3.5 text-indigo-600 border-slate-300 rounded-xs">
                                <span class="text-[11px] font-bold text-indigo-950 dark:text-indigo-200">1 Sheet Tab Per Parent Item</span>
                            </label>
                            <div id="sheetNamingContainer" class="hidden pt-1">
                                <label class="block text-[9px] font-bold text-indigo-900 dark:text-indigo-300 mb-0.5">Sheet Tab Name Variable</label>
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

                        <!-- Stop Condition -->
                        <div id="stopConditionContainer" class="hidden space-y-1">
                            <label class="block text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase">Stop Condition</label>
                            <select id="stopConditionType" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 px-2 h-7.5 text-xs rounded-md">
                                <option value="cell_value_contains">Cell Contains</option>
                                <option value="cell_value_equals">Cell Equals</option>
                                <option value="is_empty">Cell Is Blank</option>
                            </select>
                            <input type="text" id="stopConditionValue" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 px-2 h-7.5 text-xs rounded-md" placeholder="e.g. TOTAL COST">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- CARD 3: DYNAMIC CONDITIONAL LOGIC (IF / ELSE IF / ELSE) -->
            <!-- (ACTIVE FOR BOTH SINGLE FIELD & TABLE LOOP MODES)        -->
            <!-- ======================================================== -->
            <div class="border border-amber-300/80 dark:border-amber-900/60 rounded-lg p-2.5 bg-gradient-to-b from-amber-50/50 to-white dark:from-amber-950/20 dark:to-slate-900 space-y-2.5 shadow-2xs">
                <!-- Clean Toggle Header -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <div class="relative inline-flex items-center">
                            <input type="checkbox" id="toggleEnableConditionalLogic" class="sr-only peer">
                            <div class="w-8 h-4.5 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-amber-500"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <i class="fa-solid fa-code-branch text-amber-500"></i>
                            Conditional Logic (IF / THEN)
                        </span>
                    </label>
                    <span id="logicBranchCountBadge" class="hidden px-2 py-0.5 bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 text-[10px] font-bold rounded-full border border-amber-200 dark:border-amber-800">1 Rule</span>
                </div>
                
                <div id="conditionalLogicContainer" class="hidden space-y-2 pt-1 border-t border-amber-200/60 dark:border-amber-900/50">
                    <!-- Primary IF Branch -->
                    <div class="p-2.5 bg-white dark:bg-slate-900/90 rounded-md border border-amber-200/80 dark:border-amber-800/60 shadow-2xs space-y-2">
                        <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
                            <span>Primary IF Condition</span>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="block text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">IF Source Field</label>
                            <select id="ruleSourceFieldSelect" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                                <option value="">-- Match with System Field --</option>
                                @foreach($groupedFields as $groupName => $fields)
                                    <optgroup label="GROUP: {{ strtoupper($groupName) }}">
                                        @foreach($fields as $field)
                                            <option value="{{ $field->field_key }}">{{ $field->label }} ({{ $field->field_key }})</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="space-y-1">
                                <label class="block text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Operator</label>
                                <select id="ruleOperatorSelect" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
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
                            <div class="space-y-1">
                                <label class="block text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Match Value</label>
                                <input type="text" id="ruleMatchValueInput" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 font-mono" placeholder="e.g. DIE">
                            </div>
                        </div>

                        <!-- Sub-conditions container (AND / OR) -->
                        <div id="primarySubConditionsList" class="space-y-1.5 pt-1 border-t border-slate-100 dark:border-slate-800"></div>
                        <button type="button" id="btnAddPrimarySubCondition" class="w-full py-1 px-2 text-[10px] font-semibold text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded border border-dashed border-amber-300/80 dark:border-amber-700/60 flex items-center justify-center gap-1 transition-colors">
                            <i class="fa-solid fa-plus text-[8px]"></i>
                            <span>Add AND / OR Condition</span>
                        </button>

                        <div class="space-y-1 pt-1 border-t border-slate-100 dark:border-slate-800">
                            <label class="block text-[9px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wide flex items-center gap-1">
                                <i class="fa-solid fa-arrow-right text-[8px]"></i>
                                <span>THEN Output Value</span>
                            </label>
                            <div class="flex items-center gap-1.5">
                                <select id="ruleOutputTypeSelect" class="w-24 text-[10px] font-bold border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 rounded-md h-8 px-1.5 text-slate-700 dark:text-slate-200">
                                    <option value="field_value">Field</option>
                                    <option value="static_value">Custom</option>
                                </select>
                                <div id="ruleOutputFieldBox" class="flex-1 min-w-0">
                                    <select id="ruleOutputFieldSelect" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100">
                                        <option value="">-- Use Selected Field --</option>
                                        @foreach($groupedFields as $groupName => $fields)
                                            <optgroup label="GROUP: {{ strtoupper($groupName) }}">
                                                @foreach($fields as $field)
                                                    <option value="{{ $field->field_key }}">{{ $field->label }} ({{ $field->field_key }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="ruleOutputStaticBox" class="hidden flex-1 min-w-0">
                                    <input type="text" id="ruleOutputStaticInput" class="w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 font-mono" placeholder="Custom output value">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Container for Dynamic ELSE IF Branches -->
                    <div id="elseIfBranchesList" class="space-y-1.5"></div>

                    <!-- Button to Add ELSE IF Branch -->
                    <button type="button" id="btnAddElseIfBranch" class="w-full py-1.5 px-3 bg-white dark:bg-slate-900 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-dashed border-amber-300 dark:border-amber-700 rounded-md text-[11px] font-bold flex items-center justify-center gap-1.5 transition-all shadow-2xs hover:border-amber-400">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Add ELSE IF Branch</span>
                    </button>

                    <!-- ELSE Fallback -->
                    <div class="p-2 bg-slate-50 dark:bg-slate-900/60 rounded-md border border-slate-200/80 dark:border-slate-800 space-y-1">
                        <label class="block text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">ELSE Fallback Output (Optional)</label>
                        <input type="text" id="ruleElseStaticInput" class="w-full text-xs font-mono border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-md h-7.5 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-slate-400" placeholder="Leave blank to keep default loop value">
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- CARD 4: COMPACT RENDER FORMAT SELECTOR                   -->
            <!-- (ACTIVE FOR BOTH SINGLE FIELD & TABLE LOOP MODES)        -->
            <!-- ======================================================== -->
            <div x-show="inspectorTab === 'single' || inspectorTab === 'loop'" class="border border-slate-200 dark:border-slate-800 rounded-sm p-2 bg-slate-50/50 dark:bg-slate-950/40 space-y-1.5">
                <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Output & Render Format</label>
                <select id="singleRenderType" class="hidden">
                    <option value="default" selected>Default Template</option>
                    <option value="text">General</option>
                    <option value="number">Numeric</option>
                    <option value="currency">Currency</option>
                    <option value="percentage">Percentage</option>
                    <option value="date">Date</option>
                    <option value="long_date">Long Date</option>
                    <option value="image">Image</option>
                    <option value="qr">QR Code</option>
                </select>

                <div class="grid grid-cols-3 gap-1" id="renderTypeButtonGrid">
                    <button type="button" data-render-val="default" class="render-type-btn p-1.5 border rounded-sm text-center bg-white dark:bg-slate-900 border-purple-500 text-purple-600 dark:text-purple-400 shadow-2xs font-bold ring-1 ring-purple-500/30">
                        <i class="fa-solid fa-file-excel text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">Default</span>
                    </button>
                    <button type="button" data-render-val="text" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-solid fa-font text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">General</span>
                    </button>
                    <button type="button" data-render-val="number" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-solid fa-hashtag text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">Numeric</span>
                    </button>
                    <button type="button" data-render-val="currency" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-solid fa-rupiah-sign text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">Currency</span>
                    </button>
                    <button type="button" data-render-val="percentage" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-solid fa-percent text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">Percent</span>
                    </button>
                    <button type="button" data-render-val="date" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-regular fa-calendar-days text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">Date</span>
                    </button>
                    <button type="button" data-render-val="long_date" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-solid fa-calendar-day text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">Long Date</span>
                    </button>
                    <button type="button" data-render-val="image" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-regular fa-image text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">Image</span>
                    </button>
                    <button type="button" data-render-val="qr" class="render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300">
                        <i class="fa-solid fa-qrcode text-xs block mb-0.5"></i>
                        <span class="text-[10px] font-bold block leading-none">QR Code</span>
                    </button>
                </div>

                <div id="imageSizeContainer" class="hidden pt-1.5 border-t border-slate-200 dark:border-slate-800">
                    <label class="block text-[10px] font-bold text-slate-600 dark:text-slate-400 mb-1">Bounds Size (Width x Height px)</label>
                    <input type="text" id="singleImageSize" class="w-full bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 px-2.5 h-7 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-purple-500 font-mono rounded-sm" placeholder="e.g. 100x40" value="100x40">
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- ACTION BUTTONS: SINGLE FIELD MODE                        -->
            <!-- ======================================================== -->
            <div x-show="inspectorTab === 'single'" class="flex items-center gap-1.5 pt-1">
                <button id="btnAssignSingle" disabled class="flex-1 h-9 px-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:bg-slate-200 dark:disabled:bg-slate-800 disabled:text-slate-400 text-white text-xs font-bold rounded-sm shadow-2xs transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span>Assign Single Mapping</span>
                </button>
                <button id="btnUnsetSingle" type="button" class="hidden h-9 px-3 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 text-xs font-bold rounded-sm transition-colors" title="Unset mapping">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- ACTION BUTTONS: TABLE LOOP MODE                          -->
            <!-- ======================================================== -->
            <div x-show="inspectorTab === 'loop'" class="flex items-center gap-1.5 pt-1">
                <button id="btnAssignLoop" disabled class="flex-1 h-9 px-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:bg-slate-200 dark:disabled:bg-slate-800 disabled:text-slate-400 text-white text-xs font-bold rounded-sm shadow-2xs transition-colors flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span>Assign Loop Column</span>
                </button>
                <button id="btnUnsetLoop" type="button" class="hidden h-9 px-3 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 text-xs font-bold rounded-sm transition-colors" title="Unset loop column">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>
