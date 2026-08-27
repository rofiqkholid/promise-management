<script>
    function visualMapperStudio() {
        return {
            treemapOpen: true,
            sidebarOpen: true,
            inspectorTab: 'single',
            showAdvancedLoop: false,
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
        const saveMappingUrl = @json(route('management.excel-templates.save-mapping', $template->id));
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

        let isProgrammaticChange = false;

        // Init Select2 for global system field variable dropdown
        $('#globalFieldSelect').select2({
            placeholder: '-- Select System Field Variable --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#mainStudioCanvas')
        }).on('change', function() {
            const val = $(this).val();
            document.getElementById('btnAssignSingle').disabled = !val;
            document.getElementById('btnAssignLoop').disabled = !val;

            if (isProgrammaticChange) return;

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
                const row = currentSelectedCell ? currentSelectedCell.row : 13;
                loopGroupName.value = getAutoGroupName(mode, row);
            }
        });

        // Init Select2 for Sheet Tab Naming Field dropdown
        $('#sheetNameFieldSelect').select2({
            placeholder: 'Auto (Default: Part No / Item Index)',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#mainStudioCanvas')
        }).on('change', function() {
            if (isProgrammaticChange) return;
            if (typeof syncInspectorToConfig === 'function') {
                syncInspectorToConfig();
            }
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
        function setInspectorTab(tabName) {
            const canvasEl = document.getElementById('mainStudioCanvas');
            if (canvasEl && window.Alpine) {
                const studio = Alpine.$data(canvasEl);
                if (studio) {
                    studio.inspectorTab = tabName;
                }
            }
        }

        const loopGroupName = document.getElementById('loopGroupName');
        const loopModeSelect = document.getElementById('loopModeSelect');
        const loopDirectionSelect = document.getElementById('loopDirectionSelect');
        const loopInsertBehaviorSelect = document.getElementById('loopInsertBehaviorSelect');
        const blankRowsAfterInput = document.getElementById('blankRowsAfterInput');
        const splitSheetPerParentToggle = document.getElementById('splitSheetPerParentToggle');
        const sheetNameFieldSelect = document.getElementById('sheetNameFieldSelect');
        const sheetNamingContainer = document.getElementById('sheetNamingContainer');
        const stopConditionContainer = document.getElementById('stopConditionContainer');
        const stopConditionType = document.getElementById('stopConditionType');
        const stopConditionValue = document.getElementById('stopConditionValue');

        const singleRenderType = document.getElementById('singleRenderType');
        const singleImageSize = document.getElementById('singleImageSize');
        const imageSizeContainer = document.getElementById('imageSizeContainer');

        const sourceTypeVariableBtn = document.getElementById('sourceTypeVariableBtn');
        const sourceTypeStaticBtn = document.getElementById('sourceTypeStaticBtn');
        const variableSourceBox = document.getElementById('variableSourceBox');
        const staticSourceBox = document.getElementById('staticSourceBox');
        const staticValueInput = document.getElementById('staticValueInput');
        let currentDataSourceMode = 'variable';

        function setDataSourceMode(mode) {
            currentDataSourceMode = mode;
            if (mode === 'static') {
                if (sourceTypeStaticBtn) sourceTypeStaticBtn.className = "py-1 px-1.5 bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 font-bold shadow-2xs rounded-sm flex items-center justify-center gap-1 text-[10px] transition-all ring-1 ring-emerald-500/30";
                if (sourceTypeVariableBtn) sourceTypeVariableBtn.className = "py-1 px-1.5 text-slate-600 dark:text-slate-400 hover:text-slate-800 rounded-sm flex items-center justify-center gap-1 text-[10px] transition-all";
                if (staticSourceBox) staticSourceBox.classList.remove('hidden');
                if (variableSourceBox) variableSourceBox.classList.add('hidden');
                if (staticValueInput) {
                    document.getElementById('btnAssignSingle').disabled = !staticValueInput.value.trim();
                    document.getElementById('btnAssignLoop').disabled = !staticValueInput.value.trim();
                }
            } else {
                if (sourceTypeVariableBtn) sourceTypeVariableBtn.className = "py-1 px-1.5 bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 font-bold shadow-2xs rounded-sm flex items-center justify-center gap-1 text-[10px] transition-all ring-1 ring-blue-500/30";
                if (sourceTypeStaticBtn) sourceTypeStaticBtn.className = "py-1 px-1.5 text-slate-600 dark:text-slate-400 hover:text-slate-800 rounded-sm flex items-center justify-center gap-1 text-[10px] transition-all";
                if (variableSourceBox) variableSourceBox.classList.remove('hidden');
                if (staticSourceBox) staticSourceBox.classList.add('hidden');
                const val = $('#globalFieldSelect').val();
                document.getElementById('btnAssignSingle').disabled = !val;
                document.getElementById('btnAssignLoop').disabled = !val;
            }
        }

        if (sourceTypeVariableBtn) sourceTypeVariableBtn.addEventListener('click', () => setDataSourceMode('variable'));
        if (sourceTypeStaticBtn) sourceTypeStaticBtn.addEventListener('click', () => setDataSourceMode('static'));

        function updateRenderTypeCards(activeVal) {
            if (!activeVal) activeVal = 'default';
            if (activeVal === 'general') activeVal = 'text';
            document.querySelectorAll('#renderTypeButtonGrid .render-type-btn').forEach(btn => {
                const val = btn.dataset.renderVal;
                if (val === activeVal || (activeVal === 'text' && val === 'general') || (activeVal === 'general' && val === 'text')) {
                    btn.className = "render-type-btn p-1.5 border rounded-sm text-center bg-white dark:bg-slate-900 border-purple-500 text-purple-600 dark:text-purple-400 shadow-2xs font-bold ring-1 ring-purple-500/30";
                } else {
                    btn.className = "render-type-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300";
                }
            });
            if (singleRenderType) singleRenderType.value = activeVal;
            toggleImageSizeVisibility();
        }

        document.querySelectorAll('#renderTypeButtonGrid .render-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateRenderTypeCards(this.dataset.renderVal);
            });
        });

        function updateLoopModeCards(activeVal) {
            document.querySelectorAll('.loop-mode-btn').forEach(btn => {
                const val = btn.dataset.loopMode;
                if (val === activeVal) {
                    btn.className = "loop-mode-btn p-1.5 border rounded-sm text-left bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-2xs font-bold ring-1 ring-sky-500/30";
                } else {
                    btn.className = "loop-mode-btn p-1.5 border border-slate-200 dark:border-slate-800 rounded-sm text-left bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300";
                }
            });
            if (loopModeSelect) loopModeSelect.value = activeVal;
            toggleSheetLoopControls();
        }

        document.querySelectorAll('.loop-mode-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateLoopModeCards(this.dataset.loopMode);
                if (typeof syncInspectorToConfig === 'function') syncInspectorToConfig();
            });
        });

        function updateDirectionCards(activeVal) {
            document.querySelectorAll('.dir-btn').forEach(btn => {
                const val = btn.dataset.dirVal;
                if (val === activeVal) {
                    btn.className = "dir-btn py-1 border rounded-sm text-center bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-2xs font-bold text-[9px] ring-1 ring-sky-500/30";
                } else {
                    btn.className = "dir-btn py-1 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-[9px] hover:border-slate-300";
                }
            });
            if (loopDirectionSelect) loopDirectionSelect.value = activeVal;
            toggleStopConditionVisibility();
        }

        document.querySelectorAll('.dir-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateDirectionCards(this.dataset.dirVal);
                if (typeof syncInspectorToConfig === 'function') syncInspectorToConfig();
            });
        });

        function updateBehaviorCards(activeVal) {
            document.querySelectorAll('.behavior-btn').forEach(btn => {
                const val = btn.dataset.behaviorVal;
                if (val === activeVal) {
                    btn.className = "behavior-btn py-1 border rounded-sm text-center bg-white dark:bg-slate-900 border-sky-500 text-sky-600 dark:text-sky-400 shadow-2xs font-bold text-[9px] ring-1 ring-sky-500/30";
                } else {
                    btn.className = "behavior-btn py-1 border border-slate-200 dark:border-slate-800 rounded-sm text-center bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 text-[9px] hover:border-slate-300";
                }
            });
            if (loopInsertBehaviorSelect) loopInsertBehaviorSelect.value = activeVal;
            toggleStopConditionVisibility();
        }

        document.querySelectorAll('.behavior-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                updateBehaviorCards(this.dataset.behaviorVal);
                if (typeof syncInspectorToConfig === 'function') syncInspectorToConfig();
            });
        });

        function toggleSheetLoopControls() {
            const toggle = document.getElementById('splitSheetPerParentToggle');
            const namingContainer = document.getElementById('sheetNamingContainer');
            if (namingContainer && toggle) {
                if (toggle.checked) {
                    namingContainer.classList.remove('hidden');
                } else {
                    namingContainer.classList.add('hidden');
                }
            }
        }

        function toggleStopConditionVisibility() {
            const dir = document.getElementById('loopDirectionSelect')?.value;
            const behavior = document.getElementById('loopInsertBehaviorSelect')?.value;
            const container = document.getElementById('stopConditionContainer');
            if (!container) return;
            if (dir === 'right' || behavior === 'overwrite') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        function toggleImageSizeVisibility() {
            if (!singleRenderType || !imageSizeContainer) return;
            const val = singleRenderType.value;
            if (val === 'image' || val === 'qr') {
                imageSizeContainer.classList.remove('hidden');
            } else {
                imageSizeContainer.classList.add('hidden');
            }
        }
        if (singleRenderType) singleRenderType.addEventListener('change', toggleImageSizeVisibility);

        function extractFormulaCellReferences(formulaStr) {
            if (!formulaStr || !formulaStr.trim().startsWith('=')) return [];

            const references = [];
            const rangeRegex = /([A-Z]{1,3})(\d+|\{row(?:[+-]\d+)?\})\s*:\s*([A-Z]{1,3})(\d+|\{row(?:[+-]\d+)?\})/gi;
            let match;
            const processedRanges = [];

            while ((match = rangeRegex.exec(formulaStr)) !== null) {
                processedRanges.push(match[0]);
                const startCol = match[1].toUpperCase();
                const startRowSpec = match[2];
                const endCol = match[3].toUpperCase();
                const endRowSpec = match[4];

                const parseRow = (spec) => {
                    if (spec.startsWith('{row')) {
                        const offset = spec.replace(/\{row|\}/g, '');
                        const offsetNum = offset ? parseInt(offset) : 0;
                        return currentSelectedCell ? (currentSelectedCell.row + offsetNum) : null;
                    }
                    return parseInt(spec);
                };

                const startRowNum = parseRow(startRowSpec);
                const endRowNum = parseRow(endRowSpec);

                if (startRowNum && endRowNum && startCol === endCol) {
                    const minR = Math.min(startRowNum, endRowNum);
                    const maxR = Math.max(startRowNum, endRowNum);
                    for (let r = minR; r <= maxR; r++) {
                        references.push({ col: startCol, rowNumber: r });
                    }
                }
            }

            const singleCellRegex = /\b([A-Z]{1,3})(\d+|\{row(?:[+-]\d+)?\})\b/gi;
            while ((match = singleCellRegex.exec(formulaStr)) !== null) {
                const fullMatch = match[0];
                const isInsideRange = processedRanges.some(rng => rng.includes(fullMatch));
                if (!isInsideRange) {
                    const col = match[1].toUpperCase();
                    const rowSpec = match[2];
                    let rowNum = null;
                    if (rowSpec.startsWith('{row')) {
                        const offset = rowSpec.replace(/\{row|\}/g, '');
                        const offsetNum = offset ? parseInt(offset) : 0;
                        rowNum = currentSelectedCell ? (currentSelectedCell.row + offsetNum) : null;
                    } else {
                        rowNum = parseInt(rowSpec);
                    }

                    if (rowNum) {
                        references.push({ col: col, rowNumber: rowNum });
                    }
                }
            }

            return references;
        }

        function highlightFormulaReferencedCells(formulaStr) {
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
            renderTreeMapActiveMappings();
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
                    td.insertAdjacentHTML('beforeend', `<span class="badge bg-emerald-600 text-white badge-cell badge-cell-single" title="${item.field_key}${renderTag}">${item.field_key}${renderTag}</span>`);
                }
            });

            // Highlight table loop mapped columns
            mappingConfig.table_loops.forEach(loop => {
                const startRow = loop.start_row;
                Object.entries(loop.columns).forEach(([rawFieldKey, colLetter]) => {
                    const td = document.querySelector(`#excelGrid td[data-col="${colLetter}"][data-row="${startRow}"]`);
                    if (td) {
                        td.classList.add('cell-mapped-loop');
                        const displayKey = rawFieldKey.includes('__') ? rawFieldKey.split('__')[0] : rawFieldKey;
                        const rType = loop.render_types ? loop.render_types[rawFieldKey] : null;
                        const renderTag = (rType && rType !== 'text' && rType !== 'general' && rType !== 'default') ? ` [${rType.toUpperCase()}]` : '';
                        td.insertAdjacentHTML('beforeend', `<span class="badge bg-sky-600 text-white badge-cell badge-cell-loop" title="${loop.group}:${displayKey}${renderTag}">${loop.group}:${displayKey}${renderTag}</span>`);
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
                            td.insertAdjacentHTML('beforeend', `<span class="badge bg-amber-500 text-white badge-cell badge-cell-single" title="IF:${rule.field_key}=>${rule.target_cell}">IF:${rule.field_key}=>${rule.target_cell}</span>`);
                        }
                    }
                });
            }
        }

        // DYNAMIC MULTI-BRANCH CONDITIONAL LOGIC HANDLERS
        const toggleEnableConditionalLogic = document.getElementById('toggleEnableConditionalLogic');
        const conditionalLogicContainer = document.getElementById('conditionalLogicContainer');
        const ruleSourceFieldSelect = document.getElementById('ruleSourceFieldSelect');
        const ruleOperatorSelect = document.getElementById('ruleOperatorSelect');
        const ruleMatchValueInput = document.getElementById('ruleMatchValueInput');
        const ruleOutputTypeSelect = document.getElementById('ruleOutputTypeSelect');
        const ruleOutputFieldBox = document.getElementById('ruleOutputFieldBox');
        const ruleOutputStaticBox = document.getElementById('ruleOutputStaticBox');
        const ruleOutputFieldSelect = document.getElementById('ruleOutputFieldSelect');
        const ruleOutputStaticInput = document.getElementById('ruleOutputStaticInput');
        const elseIfBranchesList = document.getElementById('elseIfBranchesList');
        const btnAddElseIfBranch = document.getElementById('btnAddElseIfBranch');
        const ruleElseStaticInput = document.getElementById('ruleElseStaticInput');
        const logicBranchCountBadge = document.getElementById('logicBranchCountBadge');

        if (!mappingConfig.conditional_rules) {
            mappingConfig.conditional_rules = [];
        }

        if (toggleEnableConditionalLogic) {
            toggleEnableConditionalLogic.addEventListener('change', function() {
                if (this.checked) {
                    conditionalLogicContainer.classList.remove('hidden');
                    if (!ruleSourceFieldSelect.value) {
                        ruleSourceFieldSelect.value = $('#globalFieldSelect').val() || '';
                    }
                    if (!ruleOutputFieldSelect.value) {
                        ruleOutputFieldSelect.value = $('#globalFieldSelect').val() || '';
                    }
                } else {
                    conditionalLogicContainer.classList.add('hidden');
                }
                updateLogicBadge();
            });
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

        if (ruleOperatorSelect && ruleMatchValueInput) {
            ruleOperatorSelect.addEventListener('change', function () {
                if (this.value === 'is_empty' || this.value === 'is_not_empty') {
                    ruleMatchValueInput.value = '';
                    ruleMatchValueInput.disabled = true;
                    ruleMatchValueInput.placeholder = 'Not needed';
                } else {
                    ruleMatchValueInput.disabled = false;
                    ruleMatchValueInput.placeholder = 'e.g. DIE';
                }
            });
        }

        function updateLogicBadge() {
            if (!logicBranchCountBadge || !toggleEnableConditionalLogic) return;
            if (!toggleEnableConditionalLogic.checked) {
                logicBranchCountBadge.classList.add('hidden');
                return;
            }
            const branchCount = 1 + (elseIfBranchesList ? elseIfBranchesList.children.length : 0);
            logicBranchCountBadge.textContent = branchCount === 1 ? '1 Rule' : `${branchCount} Rules`;
            logicBranchCountBadge.classList.remove('hidden');
        }

        function createSubConditionHTML(subData = {}) {
            const gate = subData.logic_gate || 'AND';
            const fieldKey = subData.field_key || '';
            const op = subData.operator || 'equals';
            const val = subData.value || '';

            let fieldOptionsHTML = '';
            if (ruleSourceFieldSelect) {
                fieldOptionsHTML = ruleSourceFieldSelect.innerHTML;
            }

            const div = document.createElement('div');
            div.className = "p-2 bg-amber-50/50 dark:bg-slate-950/70 rounded-md border border-amber-200/90 dark:border-amber-800/70 space-y-1.5 sub-condition-item shadow-2xs";
            div.innerHTML = `
                <div class="flex items-center gap-1.5">
                    <select class="sub-gate w-16 text-[10px] font-bold border border-amber-300 dark:border-amber-700 bg-amber-100 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 rounded h-7 px-1.5 focus:ring-1 focus:ring-amber-500">
                        <option value="AND" ${gate === 'AND' ? 'selected' : ''}>AND</option>
                        <option value="OR" ${gate === 'OR' ? 'selected' : ''}>OR</option>
                    </select>
                    <select class="sub-field flex-1 min-w-0 text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded h-7 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500">
                        ${fieldOptionsHTML}
                    </select>
                    <button type="button" class="btn-remove-sub-condition text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded p-1 text-xs transition-colors shrink-0" title="Remove Condition">
                        <i class="fa-solid fa-trash-can text-[11px]"></i>
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-1.5">
                    <select class="sub-op w-full text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded h-7 px-1.5 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500">
                        <option value="equals" ${op === 'equals' ? 'selected' : ''}>Equals (==)</option>
                        <option value="not_equals" ${op === 'not_equals' ? 'selected' : ''}>Not Equals (!=)</option>
                        <option value="contains" ${op === 'contains' ? 'selected' : ''}>Contains</option>
                        <option value="starts_with" ${op === 'starts_with' ? 'selected' : ''}>Starts With</option>
                        <option value="ends_with" ${op === 'ends_with' ? 'selected' : ''}>Ends With</option>
                        <option value="greater_than" ${op === 'greater_than' ? 'selected' : ''}>Greater Than (&gt;)</option>
                        <option value="greater_equal" ${op === 'greater_equal' ? 'selected' : ''}>Greater or Equal (&gt;=)</option>
                        <option value="less_than" ${op === 'less_than' ? 'selected' : ''}>Less Than (&lt;)</option>
                        <option value="less_equal" ${op === 'less_equal' ? 'selected' : ''}>Less or Equal (&lt;=)</option>
                        <option value="is_empty" ${op === 'is_empty' ? 'selected' : ''}>Is Empty</option>
                        <option value="is_not_empty" ${op === 'is_not_empty' ? 'selected' : ''}>Is Not Empty</option>
                    </select>
                    <input type="text" class="sub-val w-full text-xs border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded h-7 px-2 text-slate-800 dark:text-slate-100 font-mono focus:ring-1 focus:ring-amber-500" placeholder="Match value..." value="${val}">
                </div>
            `;

            const fieldSel = div.querySelector('.sub-field');
            if (fieldKey) fieldSel.value = fieldKey;

            const opSel = div.querySelector('.sub-op');
            const valInput = div.querySelector('.sub-val');
            opSel.addEventListener('change', function() {
                if (this.value === 'is_empty' || this.value === 'is_not_empty') {
                    valInput.disabled = true;
                    valInput.placeholder = 'Not needed';
                } else {
                    valInput.disabled = false;
                    valInput.placeholder = 'Match value...';
                }
            });

            if (op === 'is_empty' || op === 'is_not_empty') {
                valInput.disabled = true;
                valInput.placeholder = 'Not needed';
            }

            div.querySelector('.btn-remove-sub-condition').addEventListener('click', function() {
                div.remove();
            });

            return div;
        }

        function collectSubConditions(containerEl) {
            if (!containerEl) return [];
            const subs = [];
            containerEl.querySelectorAll('.sub-condition-item').forEach(item => {
                const gate = item.querySelector('.sub-gate')?.value || 'AND';
                const fKey = item.querySelector('.sub-field')?.value || '';
                const op = item.querySelector('.sub-op')?.value || 'equals';
                const val = item.querySelector('.sub-val')?.value.trim() || '';
                if (fKey) {
                    subs.push({
                        logic_gate: gate,
                        field_key: fKey,
                        operator: op,
                        value: val
                    });
                }
            });
            return subs;
        }

        const btnAddPrimarySubCondition = document.getElementById('btnAddPrimarySubCondition');
        if (btnAddPrimarySubCondition) {
            btnAddPrimarySubCondition.addEventListener('click', function() {
                const list = document.getElementById('primarySubConditionsList');
                if (list) {
                    const defaultField = ruleSourceFieldSelect ? ruleSourceFieldSelect.value : '';
                    list.appendChild(createSubConditionHTML({ field_key: defaultField }));
                }
            });
        }

        function createElseIfBranchHTML(branchData = {}) {
            const fieldKey = branchData.field_key || (ruleSourceFieldSelect ? ruleSourceFieldSelect.value : '');
            const op = branchData.operator || 'equals';
            const val = branchData.value || '';
            const outType = branchData.output_type || 'field_value';
            const outField = branchData.output_field_key || '';
            const outStatic = branchData.output_static_value || '';

            let fieldOptionsHTML = '';
            if (ruleSourceFieldSelect) {
                fieldOptionsHTML = ruleSourceFieldSelect.innerHTML;
            }

            const div = document.createElement('div');
            div.className = "p-2.5 bg-white dark:bg-slate-900/90 rounded-md border border-amber-300/80 dark:border-amber-700/80 shadow-2xs space-y-2 branch-item";
            div.innerHTML = `
                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
                        <span>ELSE IF Condition</span>
                    </span>
                    <button type="button" class="btn-remove-branch text-rose-500 hover:text-rose-700 p-0.5 rounded hover:bg-rose-50 dark:hover:bg-rose-950/40 text-xs transition-colors" title="Remove Branch">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
                <div class="space-y-1">
                    <label class="block text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">IF Source Field</label>
                    <select class="branch-field w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500">
                        ${fieldOptionsHTML}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-1">
                        <label class="block text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Operator</label>
                        <select class="branch-op w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500">
                            <option value="equals" ${op === 'equals' ? 'selected' : ''}>Equals (==)</option>
                            <option value="not_equals" ${op === 'not_equals' ? 'selected' : ''}>Not Equals (!=)</option>
                            <option value="contains" ${op === 'contains' ? 'selected' : ''}>Contains</option>
                            <option value="starts_with" ${op === 'starts_with' ? 'selected' : ''}>Starts With</option>
                            <option value="ends_with" ${op === 'ends_with' ? 'selected' : ''}>Ends With</option>
                            <option value="greater_than" ${op === 'greater_than' ? 'selected' : ''}>Greater Than (&gt;)</option>
                            <option value="greater_equal" ${op === 'greater_equal' ? 'selected' : ''}>Greater or Equal (&gt;=)</option>
                            <option value="less_than" ${op === 'less_than' ? 'selected' : ''}>Less Than (&lt;)</option>
                            <option value="less_equal" ${op === 'less_equal' ? 'selected' : ''}>Less or Equal (&lt;=)</option>
                            <option value="is_empty" ${op === 'is_empty' ? 'selected' : ''}>Is Empty</option>
                            <option value="is_not_empty" ${op === 'is_not_empty' ? 'selected' : ''}>Is Not Empty</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Match Value</label>
                        <input type="text" class="branch-val w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 focus:ring-1 focus:ring-amber-500 font-mono" placeholder="e.g. JIG" value="${val}">
                    </div>
                </div>

                <!-- Sub-conditions inside ELSE IF -->
                <div class="branch-sub-conditions-list space-y-1.5 pt-1 border-t border-slate-100 dark:border-slate-800"></div>
                <button type="button" class="btn-add-branch-sub-condition w-full py-1 px-2 text-[10px] font-semibold text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded border border-dashed border-amber-300/80 dark:border-amber-700/60 flex items-center justify-center gap-1 transition-colors">
                    <i class="fa-solid fa-plus text-[8px]"></i>
                    <span>Add AND / OR Condition</span>
                </button>

                <div class="space-y-1 pt-1 border-t border-slate-100 dark:border-slate-800">
                    <label class="block text-[9px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wide flex items-center gap-1">
                        <i class="fa-solid fa-arrow-right text-[8px]"></i>
                        <span>THEN Output Value</span>
                    </label>
                    <div class="flex items-center gap-1.5">
                        <select class="branch-out-type w-24 text-[10px] font-bold border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 rounded-md h-8 px-1.5 text-slate-700 dark:text-slate-200">
                            <option value="field_value" ${outType === 'field_value' ? 'selected' : ''}>Field</option>
                            <option value="static_value" ${outType === 'static_value' ? 'selected' : ''}>Custom</option>
                        </select>
                        <div class="branch-out-field-box flex-1 min-w-0 ${outType === 'static_value' ? 'hidden' : ''}">
                            <select class="branch-out-field w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100">
                                ${fieldOptionsHTML}
                            </select>
                        </div>
                        <div class="branch-out-static-box flex-1 min-w-0 ${outType === 'field_value' ? 'hidden' : ''}">
                            <input type="text" class="branch-out-static w-full text-xs border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-950 rounded-md h-8 px-2 text-slate-800 dark:text-slate-100 font-mono" placeholder="Custom output" value="${outStatic}">
                        </div>
                    </div>
                </div>
            `;

            const branchFieldSel = div.querySelector('.branch-field');
            if (fieldKey) branchFieldSel.value = fieldKey;

            const subList = div.querySelector('.branch-sub-conditions-list');
            if (branchData.conditions && Array.isArray(branchData.conditions)) {
                branchData.conditions.forEach(sub => {
                    subList.appendChild(createSubConditionHTML(sub));
                });
            }

            div.querySelector('.btn-add-branch-sub-condition').addEventListener('click', function() {
                subList.appendChild(createSubConditionHTML({ field_key: branchFieldSel.value }));
            });

            const outTypeSelect = div.querySelector('.branch-out-type');
            const outFieldBox = div.querySelector('.branch-out-field-box');
            const outStaticBox = div.querySelector('.branch-out-static-box');
            const outFieldSelect = div.querySelector('.branch-out-field');

            if (outField) {
                outFieldSelect.value = outField;
            }

            outTypeSelect.addEventListener('change', function() {
                if (this.value === 'static_value') {
                    outStaticBox.classList.remove('hidden');
                    outFieldBox.classList.add('hidden');
                } else {
                    outFieldBox.classList.remove('hidden');
                    outStaticBox.classList.add('hidden');
                }
            });

            div.querySelector('.btn-remove-branch').addEventListener('click', function() {
                div.remove();
                updateLogicBadge();
            });

            return div;
        }

        if (btnAddElseIfBranch) {
            btnAddElseIfBranch.addEventListener('click', function() {
                if (elseIfBranchesList) {
                    const defaultOutput = $('#globalFieldSelect').val() || '';
                    const branchEl = createElseIfBranchHTML({ output_field_key: defaultOutput });
                    elseIfBranchesList.appendChild(branchEl);
                    updateLogicBadge();
                }
            });
        }

        function collectElseIfBranches() {
            if (!elseIfBranchesList) return [];
            const branches = [];
            elseIfBranchesList.querySelectorAll('.branch-item').forEach(item => {
                const fKey = item.querySelector('.branch-field')?.value || '';
                const op = item.querySelector('.branch-op')?.value || 'equals';
                const val = item.querySelector('.branch-val')?.value.trim() || '';
                const outType = item.querySelector('.branch-out-type')?.value || 'field_value';
                const outField = item.querySelector('.branch-out-field')?.value || '';
                const outStatic = item.querySelector('.branch-out-static')?.value || '';
                const conditions = collectSubConditions(item.querySelector('.branch-sub-conditions-list'));

                branches.push({
                    field_key: fKey,
                    operator: op,
                    value: val,
                    conditions: conditions,
                    output_type: outType,
                    output_field_key: outField,
                    output_static_value: outStatic
                });
            });
            return branches;
        }

        function resetConditionalRuleInputs() {
            if (toggleEnableConditionalLogic) toggleEnableConditionalLogic.checked = false;
            if (conditionalLogicContainer) conditionalLogicContainer.classList.add('hidden');
            const primList = document.getElementById('primarySubConditionsList');
            if (primList) primList.innerHTML = '';
            if (elseIfBranchesList) elseIfBranchesList.innerHTML = '';
            const elseInput = document.getElementById('ruleElseStaticInput');
            if (elseInput) elseInput.value = '';
            const matchInput = document.getElementById('ruleMatchValueInput');
            if (matchInput) matchInput.value = '';
            const staticOutInput = document.getElementById('ruleOutputStaticInput');
            if (staticOutInput) staticOutInput.value = '';
            $('#ruleSourceFieldSelect').val('').trigger('change');
            $('#ruleOutputFieldSelect').val('').trigger('change');
            $('#ruleOperatorSelect').val('equals');
            $('#ruleOutputTypeSelect').val('field_value').trigger('change');
            updateLogicBadge();
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
            const matchMap = new Map();

            const stripUnits = (txt) => {
                if (!txt) return '';
                return txt
                    .replace(/\([^)]*\)/g, '')
                    .replace(/\[[^\]]*\]/g, '')
                    .replace(/[\/\\#$€£¥%]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            };

            const evaluateText = (rawTxt, distanceWeight = 1.0) => {
                if (!rawTxt || rawTxt.length < 2) return;

                const cleanRaw = rawTxt.trim().toLowerCase();
                const stripped = stripUnits(cleanRaw).toLowerCase();

                if (stripped.length < 2) return;

                systemFieldsList.forEach(sf => {
                    let score = 0;
                    const sfKey = sf.key.toLowerCase();
                    const sfCleanKey = sf.cleanKey;
                    const sfCleanLabel = stripUnits(sf.cleanLabel.toLowerCase());

                    if (sfCleanKey === stripped || sfCleanLabel === stripped) {
                        score += 100 * distanceWeight;
                    } else if (sfCleanLabel.startsWith(stripped) || stripped.startsWith(sfCleanLabel)) {
                        score += 70 * distanceWeight;
                    } else if (sfCleanKey.includes(stripped) || sfCleanLabel.includes(stripped)) {
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

            let directText = tdElement.querySelector('.cell-content-wrap')?.textContent?.trim()?.toLowerCase();
            evaluateText(directText, 1.5);

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

            for (let stepUp = 1; stepUp <= 4; stepUp++) {
                const targetRow = row - stepUp;
                if (targetRow < 1) break;
                const weight = 1.3 / stepUp;

                for (let c = colNum; c < colNum + colSpan; c++) {
                    const checkColLetter = numToColLetter(c);
                    const aboveTd = document.querySelector(`#excelGrid td[data-col="${checkColLetter}"][data-row="${targetRow}"]`);
                    if (aboveTd) {
                        const aboveText = aboveTd.querySelector('.cell-content-wrap')?.textContent?.trim();
                        evaluateText(aboveText, weight);
                    }
                }
            }

            const sortedMatches = Array.from(matchMap.values())
                .sort((a, b) => b.score - a.score)
                .filter(item => item.score >= 35)
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

            // Immediately update tree map explorer highlight for clicked cell
            renderTreeMapActiveMappings();

            if (window.Alpine) {
                const AlpineData = Alpine.$data(document.querySelector('[x-data="visualMapperStudio()"]'));
                if (AlpineData) AlpineData.sidebarOpen = true;
            }

            // 1. Check exact mapping for THIS cell on the active sheet
            const existingSingle = mappingConfig.single_fields.find(f => f.cell === currentSelectedCell.cell && (f.sheet_index === activeSheetIndex || f.sheet_name === activeSheetName || f.sheet_index === undefined));

            let existingLoopCol = null;
            let existingLoop = null;
            let existingLoopStaticVal = null;

            mappingConfig.table_loops.forEach(loop => {
                if ((loop.sheet_index === activeSheetIndex || loop.sheet_name === activeSheetName || loop.sheet_index === undefined) && parseInt(loop.start_row) === currentSelectedCell.row) {
                    if (loop.columns) {
                        Object.entries(loop.columns).forEach(([key, colLetter]) => {
                            if (colLetter === currentSelectedCell.col) {
                                existingLoopCol = key;
                                existingLoop = loop;
                                if (loop.static_values && loop.static_values[key]) {
                                    existingLoopStaticVal = loop.static_values[key];
                                }
                            }
                        });
                    }
                }
            });

            const existingRule = (mappingConfig.conditional_rules || []).find(r => r.target_cell === currentSelectedCell.cell && (r.sheet_index === activeSheetIndex || r.sheet_name === activeSheetName || r.sheet_index === undefined));

            const badgeMappingType = document.getElementById('badgeMappingType');
            const badgeCellOrigin = document.getElementById('badgeCellOrigin');
            const cellActiveMappingInfoCard = document.getElementById('cellActiveMappingInfoCard');
            const cellActiveTargetValue = document.getElementById('cellActiveTargetValue');
            const cellActiveLoopDetails = document.getElementById('cellActiveLoopDetails');
            const cellActiveLoopGroup = document.getElementById('cellActiveLoopGroup');
            const cellActiveFormatBadge = document.getElementById('cellActiveFormatBadge');
            const btnUnsetSingle = document.getElementById('btnUnsetSingle');
            const btnUnsetLoop = document.getElementById('btnUnsetLoop');

            const rawCellWrap = td.querySelector('.cell-content-wrap');
            const rawExcelText = rawCellWrap ? rawCellWrap.textContent.trim() : '';
            const rawFormula = td.dataset.rawFormula || (rawExcelText.startsWith('=') ? rawExcelText : '');

            if (rawFormula) {
                badgeCellOrigin.classList.remove('hidden');
                badgeCellOrigin.textContent = `Formula: "${rawFormula.length > 20 ? rawFormula.substring(0, 20) + '...' : rawFormula}"`;
            } else if (rawExcelText) {
                badgeCellOrigin.classList.remove('hidden');
                badgeCellOrigin.textContent = `Excel: "${rawExcelText.length > 15 ? rawExcelText.substring(0, 15) + '...' : rawExcelText}"`;
            } else {
                badgeCellOrigin.classList.add('hidden');
            }

            // Sync Conditional Rules form inputs with selected cell state
            if (existingRule) {
                toggleEnableConditionalLogic.checked = true;
                conditionalLogicContainer.classList.remove('hidden');
                $('#ruleSourceFieldSelect').val(existingRule.field_key || '').trigger('change');
                $('#ruleOperatorSelect').val(existingRule.operator || 'equals').trigger('change');
                document.getElementById('ruleMatchValueInput').value = existingRule.value || '';
                $('#ruleOutputTypeSelect').val(existingRule.output_type || 'field_value').trigger('change');
                if (existingRule.output_type === 'field_value') {
                    $('#ruleOutputFieldSelect').val(existingRule.output_field_key || '').trigger('change');
                } else {
                    document.getElementById('ruleOutputStaticInput').value = existingRule.output_static_value || '';
                }
                document.getElementById('ruleElseStaticInput').value = existingRule.else_static_value || '';
                
                const primSubList = document.getElementById('primarySubConditionsList');
                if (primSubList) {
                    primSubList.innerHTML = '';
                    if (existingRule.conditions && Array.isArray(existingRule.conditions)) {
                        existingRule.conditions.forEach(sub => {
                            primSubList.appendChild(createSubConditionHTML(sub));
                        });
                    }
                }
                
                elseIfBranchesList.innerHTML = '';
                if (existingRule.branches && Array.isArray(existingRule.branches)) {
                    existingRule.branches.forEach(branch => {
                        elseIfBranchesList.appendChild(createElseIfBranchHTML(branch));
                    });
                }
                updateLogicBadge();
            } else {
                toggleEnableConditionalLogic.checked = false;
                conditionalLogicContainer.classList.add('hidden');
                const primSubList = document.getElementById('primarySubConditionsList');
                if (primSubList) primSubList.innerHTML = '';
                elseIfBranchesList.innerHTML = '';
                document.getElementById('ruleMatchValueInput').value = '';
                document.getElementById('ruleOutputStaticInput').value = '';
                document.getElementById('ruleElseStaticInput').value = '';
                updateLogicBadge();
            }

            // Quick Clear Cell mapping action handler
            const btnQuickClearCell = document.getElementById('btnQuickClearCell');
            if (btnQuickClearCell) {
                btnQuickClearCell.onclick = function () {
                    if (!currentSelectedCell) return;
                    const cell = currentSelectedCell.cell;
                    mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== cell);
                    mappingConfig.table_loops.forEach(loop => {
                        if (parseInt(loop.start_row) === currentSelectedCell.row) {
                            Object.keys(loop.columns).forEach(k => {
                                if (loop.columns[k] === currentSelectedCell.col) delete loop.columns[k];
                            });
                        }
                    });
                    mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== cell);
                    renderJSONViewer();
                    markDirty();
                    if (window.showToast) window.showToast(`Cleared mappings on cell ${cell}`, 'info');
                    td.click();
                };
            }

            // 2. Purely cell-focused Inspector update
            if (existingLoop) {
                // FOCUS ON THIS LOOP COLUMN
                setInspectorTab('loop');
                badgeMappingType.className = "px-2 py-0.5 bg-sky-600 text-white font-bold rounded-sm shadow-2xs text-[9px] uppercase";
                badgeMappingType.textContent = "Loop Column";

                cellActiveValuePreview.classList.remove('hidden');
                cellActiveValuePrefix.textContent = "Mapped:";
                cellActiveValueText.textContent = `${existingLoopStaticVal || existingLoopCol} (${existingLoop.group})`;

                // Prefill Loop Inputs strictly for this loop
                loopGroupName.value = existingLoop.group || '';
                loopGroupName.dataset.userModified = 'true';
                document.getElementById('blankRowsAfterInput').value = (existingLoop.blank_rows_after !== undefined && existingLoop.blank_rows_after !== null) ? existingLoop.blank_rows_after : 0;
                document.getElementById('splitSheetPerParentToggle').checked = !!(existingLoop.split_sheet_per_parent || existingLoop.sheet_loop);
                
                isProgrammaticChange = true;
                $('#sheetNameFieldSelect').val(existingLoop.sheet_name_field || '').trigger('change');
                isProgrammaticChange = false;
                
                toggleSheetLoopControls();

                updateLoopModeCards(existingLoop.loop_mode || 'flat');
                updateDirectionCards(existingLoop.direction || 'down');
                updateBehaviorCards(existingLoop.insert_behavior || 'insert_duplicate');

                const activeFormat = (existingLoop.render_types && existingLoop.render_types[existingLoopCol]) ? existingLoop.render_types[existingLoopCol] : 'default';
                updateRenderTypeCards(activeFormat);

                if (existingLoopStaticVal) {
                    setDataSourceMode('static');
                    staticValueInput.value = existingLoopStaticVal;
                    highlightFormulaReferencedCells(existingLoopStaticVal);
                } else {
                    setDataSourceMode('variable');
                    isProgrammaticChange = true;
                    $('#globalFieldSelect').val(existingLoopCol).trigger('change');
                    isProgrammaticChange = false;
                }

                btnUnsetLoop.classList.remove('hidden');
                btnUnsetSingle.classList.add('hidden');

            } else if (existingSingle) {
                // FOCUS ON THIS SINGLE FIELD
                setInspectorTab('single');
                badgeMappingType.className = "px-2 py-0.5 bg-emerald-600 text-white font-bold rounded-sm shadow-2xs text-[9px] uppercase";
                badgeMappingType.textContent = "Single Field";

                cellActiveValuePreview.classList.remove('hidden');
                cellActiveValuePrefix.textContent = "Mapped:";
                cellActiveValueText.textContent = existingSingle.value_type === 'static' ? (existingSingle.static_value || '') : existingSingle.field_key;

                const singleFmt = existingSingle.render_type || 'default';
                updateRenderTypeCards(singleFmt);

                if (existingSingle.value_type === 'static') {
                    setDataSourceMode('static');
                    staticValueInput.value = existingSingle.static_value || '';
                    highlightFormulaReferencedCells(existingSingle.static_value || '');
                } else {
                    setDataSourceMode('variable');
                    isProgrammaticChange = true;
                    $('#globalFieldSelect').val(existingSingle.field_key).trigger('change');
                    isProgrammaticChange = false;
                }

                btnUnsetSingle.classList.remove('hidden');
                btnUnsetLoop.classList.add('hidden');

            } else if (existingRule) {
                // FOCUS ON THIS CONDITIONAL RULE (IN SINGLE MODE)
                setInspectorTab('single');
                badgeMappingType.className = "px-2 py-0.5 bg-amber-500 text-white font-bold rounded-sm shadow-2xs text-[9px] uppercase";
                badgeMappingType.textContent = "Single + Rule";

                const ruleOp = existingRule.operator === 'equals' ? '==' : existingRule.operator;
                const ruleOut = existingRule.output_type === 'static_value' ? `"${existingRule.output_static_value}"` : existingRule.output_field_key;
                cellActiveValuePreview.classList.remove('hidden');
                cellActiveValuePrefix.textContent = "Condition:";
                cellActiveValueText.textContent = `IF ${existingRule.field_key} ${ruleOp} "${existingRule.value || ''}" THEN ${ruleOut}`;

                const ruleFmt = existingRule.render_type || 'default';
                updateRenderTypeCards(ruleFmt);

                if (existingRule.output_type === 'static_value') {
                    setDataSourceMode('static');
                    staticValueInput.value = existingRule.output_static_value || '';
                } else if (existingRule.output_field_key) {
                    setDataSourceMode('variable');
                    isProgrammaticChange = true;
                    $('#globalFieldSelect').val(existingRule.output_field_key).trigger('change');
                    isProgrammaticChange = false;
                }

                btnUnsetSingle.classList.remove('hidden');
                btnUnsetLoop.classList.add('hidden');

            } else {
                // UNMAPPED CELL
                setInspectorTab('single');
                badgeMappingType.className = "px-2 py-0.5 bg-slate-500 text-white font-bold rounded-sm shadow-2xs text-[9px] uppercase";
                badgeMappingType.textContent = "Unmapped";

                if (rawExcelText) {
                    cellActiveValuePreview.classList.remove('hidden');
                    cellActiveValuePrefix.textContent = rawFormula ? "Formula:" : "Excel:";
                    cellActiveValueText.textContent = rawExcelText;
                } else {
                    cellActiveValuePreview.classList.add('hidden');
                }

                // Check if current row has an existing loop group on this sheet
                const rowLoop = mappingConfig.table_loops.find(l => (l.sheet_index === activeSheetIndex || l.sheet_name === activeSheetName) && parseInt(l.start_row) === currentSelectedCell.row);
                if (rowLoop) {
                    loopGroupName.value = rowLoop.group || '';
                    loopGroupName.dataset.userModified = 'true';
                    document.getElementById('blankRowsAfterInput').value = (rowLoop.blank_rows_after !== undefined && rowLoop.blank_rows_after !== null) ? rowLoop.blank_rows_after : 0;
                    document.getElementById('splitSheetPerParentToggle').checked = !!(rowLoop.split_sheet_per_parent || rowLoop.sheet_loop);
                    
                    isProgrammaticChange = true;
                    $('#sheetNameFieldSelect').val(rowLoop.sheet_name_field || '').trigger('change');
                    isProgrammaticChange = false;
                    
                    updateLoopModeCards(rowLoop.loop_mode || 'flat');
                    updateDirectionCards(rowLoop.direction || 'down');
                    updateBehaviorCards(rowLoop.insert_behavior || 'insert_duplicate');
                } else {
                    delete loopGroupName.dataset.userModified;
                    loopGroupName.value = getAutoGroupName('flat', currentSelectedCell.row);
                    document.getElementById('blankRowsAfterInput').value = 0;
                    document.getElementById('splitSheetPerParentToggle').checked = false;
                    
                    isProgrammaticChange = true;
                    $('#sheetNameFieldSelect').val('').trigger('change');
                    isProgrammaticChange = false;
                    
                    updateLoopModeCards('flat');
                    updateDirectionCards('down');
                    updateBehaviorCards('insert_duplicate');
                }

                toggleSheetLoopControls();
                updateRenderTypeCards('default');

                if (rawFormula) {
                    setDataSourceMode('static');
                    staticValueInput.value = rawFormula;
                    highlightFormulaReferencedCells(rawFormula);
                } else {
                    setDataSourceMode('variable');
                    isProgrammaticChange = true;
                    $('#globalFieldSelect').val('').trigger('change');
                    isProgrammaticChange = false;
                    staticValueInput.value = '';
                    highlightFormulaReferencedCells('');
                }

                btnUnsetSingle.classList.add('hidden');
                btnUnsetLoop.classList.add('hidden');
            }

            toggleStopConditionVisibility();
        });

        function syncLoopInspectorFromConfig() {
            if (!mappingConfig || !mappingConfig.table_loops) return;

            let targetLoopObj = null;
            if (currentSelectedCell) {
                targetLoopObj = mappingConfig.table_loops.find(l => {
                    if (parseInt(l.start_row) === currentSelectedCell.row) {
                        return Object.values(l.columns || {}).includes(currentSelectedCell.col);
                    }
                    return false;
                });

                if (!targetLoopObj) {
                    targetLoopObj = mappingConfig.table_loops.find(l => parseInt(l.start_row) === currentSelectedCell.row && (l.sheet_index === activeSheetIndex || l.sheet_name === activeSheetName));
                }
            }

            if (!targetLoopObj) {
                targetLoopObj = mappingConfig.table_loops.find(l => l.sheet_index === activeSheetIndex || l.sheet_name === activeSheetName) 
                    || (mappingConfig.table_loops.length > 0 ? mappingConfig.table_loops[0] : null);
            }

            if (targetLoopObj) {
                loopGroupName.value = targetLoopObj.group || '';
                loopGroupName.dataset.userModified = 'true';
                updateLoopModeCards(targetLoopObj.loop_mode || 'flat');
                updateDirectionCards(targetLoopObj.direction || 'down');
                updateBehaviorCards(targetLoopObj.insert_behavior || 'insert_duplicate');

                document.getElementById('blankRowsAfterInput').value = (targetLoopObj.blank_rows_after !== undefined && targetLoopObj.blank_rows_after !== null) ? targetLoopObj.blank_rows_after : 0;
                
                const isSheetLoop = !!(targetLoopObj.split_sheet_per_parent || targetLoopObj.sheet_loop);
                document.getElementById('splitSheetPerParentToggle').checked = isSheetLoop;
                
                isProgrammaticChange = true;
                $('#sheetNameFieldSelect').val(targetLoopObj.sheet_name_field || '').trigger('change');
                isProgrammaticChange = false;
                
                toggleSheetLoopControls();

                if (currentSelectedCell && targetLoopObj.columns) {
                    const matchedColKey = Object.keys(targetLoopObj.columns).find(k => targetLoopObj.columns[k] === currentSelectedCell.col);
                    if (matchedColKey && targetLoopObj.render_types && targetLoopObj.render_types[matchedColKey]) {
                        updateRenderTypeCards(targetLoopObj.render_types[matchedColKey]);
                    }
                }
            }
        }

        const tabLoopBtnEl = document.getElementById('tabLoopBtn');
        if (tabLoopBtnEl) {
            tabLoopBtnEl.addEventListener('click', function() {
                syncLoopInspectorFromConfig();
            });
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
            const sheetSlug = 'sheet' + (activeSheetIndex + 1);
            const rowNumber = currentSelectedCell ? currentSelectedCell.row : 'X';
            const cleanMode = mode === 'nested_block' ? 'nested' : mode;

            return sheetSlug + '_row' + rowNumber + '_' + cleanMode;
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
            
            // 1. Clean existing single field for this cell
            mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== currentSelectedCell.cell);

            // 2. Clean conflicting table loop assignment for this cell
            if (mappingConfig.table_loops) {
                mappingConfig.table_loops.forEach(loop => {
                    if (parseInt(loop.start_row) === currentSelectedCell.row) {
                        Object.keys(loop.columns).forEach(k => {
                            if (loop.columns[k] === currentSelectedCell.col) {
                                delete loop.columns[k];
                            }
                        });
                    }
                });
            }

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

                // Save Conditional Logic if enabled
                if (toggleEnableConditionalLogic && toggleEnableConditionalLogic.checked) {
                    const srcField = ruleSourceFieldSelect.value.trim() || ($('#globalFieldSelect').val() || '');
                    const op = ruleOperatorSelect.value || 'equals';
                    const matchVal = ruleMatchValueInput.value.trim();
                    const outType = ruleOutputTypeSelect.value || 'field_value';
                    const outField = (outType === 'field_value') ? (ruleOutputFieldSelect.value.trim() || ($('#globalFieldSelect').val() || '')) : null;
                    const outStatic = (outType === 'static_value') ? (ruleOutputStaticInput.value.trim() || staticValueInput.value.trim()) : null;
                    const elseStatic = ruleElseStaticInput.value.trim() || null;
                    const primaryConditions = collectSubConditions(document.getElementById('primarySubConditionsList'));
                    const branches = collectElseIfBranches();

                    const ruleObj = {
                        id: 'rule_' + Date.now(),
                        sheet_index: activeSheetIndex,
                        sheet_name: activeSheetName,
                        target_cell: currentSelectedCell.cell,
                        target_column: currentSelectedCell.col,
                        field_key: srcField,
                        operator: op,
                        value: matchVal,
                        conditions: primaryConditions,
                        output_type: outType,
                        output_field_key: outField,
                        output_static_value: outStatic,
                        else_static_value: elseStatic,
                        render_type: rVal,
                        branches: branches
                    };

                    mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== currentSelectedCell.cell);
                    mappingConfig.conditional_rules.push(ruleObj);
                } else {
                    mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== currentSelectedCell.cell);
                }
            }

            renderJSONViewer();
            markDirty();
            if (window.showToast) window.showToast(`Single mapping assigned to cell ${currentSelectedCell.cell}`, 'success');
        });

        document.getElementById('btnAssignLoop').addEventListener('click', function () {
            if (!currentSelectedCell) return;
            const loopMode = document.getElementById('loopModeSelect').value;

            // 1. Clean conflicting single field assignment for this cell
            mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== currentSelectedCell.cell);

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

            // 2. Clean previous loop mapping ONLY on this specific cell column
            mappingConfig.table_loops.forEach(loop => {
                if (parseInt(loop.start_row) === currentSelectedCell.row) {
                    Object.entries(loop.columns).forEach(([key, colLetter]) => {
                        if (colLetter === currentSelectedCell.col) {
                            delete loop.columns[key];
                            if (loop.static_values && loop.static_values[key]) {
                                delete loop.static_values[key];
                            }
                            if (loop.render_types && loop.render_types[key]) {
                                delete loop.render_types[key];
                            }
                        }
                    });
                }
            });

            // 3. Find or create loop definition for this group and start_row
            let existingLoop = mappingConfig.table_loops.find(l => 
                l.group === group && 
                parseInt(l.start_row) === currentSelectedCell.row &&
                (l.sheet_index === activeSheetIndex || l.sheet_name === activeSheetName || l.sheet_index === undefined)
            );

            if (!existingLoop) {
                existingLoop = {
                    group: group,
                    loop_mode: loopMode,
                    sheet_index: activeSheetIndex,
                    sheet_name: activeSheetName,
                    start_row: currentSelectedCell.row,
                    direction: document.getElementById('loopDirectionSelect').value,
                    insert_behavior: document.getElementById('loopInsertBehaviorSelect').value,
                    blank_rows_after: parseInt(document.getElementById('blankRowsAfterInput').value) || 0,
                    split_sheet_per_parent: document.getElementById('splitSheetPerParentToggle').checked,
                    sheet_name_field: $('#sheetNameFieldSelect').val() || null,
                    columns: {},
                    stop_condition: {
                        type: stopConditionType.value,
                        column: currentSelectedCell.col,
                        value: stopConditionValue.value.trim()
                    }
                };
                mappingConfig.table_loops.push(existingLoop);
            } else {
                existingLoop.loop_mode = loopMode;
                existingLoop.direction = document.getElementById('loopDirectionSelect').value;
                existingLoop.insert_behavior = document.getElementById('loopInsertBehaviorSelect').value;
                existingLoop.blank_rows_after = parseInt(document.getElementById('blankRowsAfterInput').value) || 0;
                existingLoop.split_sheet_per_parent = document.getElementById('splitSheetPerParentToggle').checked;
                existingLoop.sheet_name_field = $('#sheetNameFieldSelect').val() || null;
            }

            let targetKey = fieldKey;
            if (existingLoop.columns[fieldKey] && existingLoop.columns[fieldKey] !== currentSelectedCell.col) {
                targetKey = fieldKey + '__' + currentSelectedCell.col;
            }

            existingLoop.columns[targetKey] = currentSelectedCell.col;

            const rVal = (singleRenderType.value === 'text') ? 'general' : singleRenderType.value;
            if (rVal && rVal !== 'default') {
                if (!existingLoop.render_types) existingLoop.render_types = {};
                existingLoop.render_types[targetKey] = rVal;
            }

            if (isStaticMode) {
                if (!existingLoop.static_values) existingLoop.static_values = {};
                existingLoop.static_values[targetKey] = staticVal;
            }

            mappingConfig.table_loops = mappingConfig.table_loops.filter(l => Object.keys(l.columns).length > 0);

            // Save Conditional Logic if enabled
            if (toggleEnableConditionalLogic && toggleEnableConditionalLogic.checked) {
                const srcField = ruleSourceFieldSelect.value.trim() || fieldKey;
                const op = ruleOperatorSelect.value || 'equals';
                const matchVal = ruleMatchValueInput.value.trim();
                const outType = ruleOutputTypeSelect.value || 'field_value';
                const outField = (outType === 'field_value') ? (ruleOutputFieldSelect.value.trim() || fieldKey) : null;
                const outStatic = (outType === 'static_value') ? (ruleOutputStaticInput.value.trim() || staticVal) : null;
                const elseStatic = ruleElseStaticInput.value.trim() || null;
                const primaryConditions = collectSubConditions(document.getElementById('primarySubConditionsList'));
                const branches = collectElseIfBranches();

                const ruleObj = {
                    id: 'rule_' + Date.now(),
                    sheet_index: activeSheetIndex,
                    sheet_name: activeSheetName,
                    target_cell: currentSelectedCell.cell,
                    target_column: currentSelectedCell.col,
                    field_key: srcField,
                    operator: op,
                    value: matchVal,
                    conditions: primaryConditions,
                    output_type: outType,
                    output_field_key: outField,
                    output_static_value: outStatic,
                    else_static_value: elseStatic,
                    render_type: rVal,
                    branches: branches
                };

                mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== currentSelectedCell.cell);
                mappingConfig.conditional_rules.push(ruleObj);
            } else {
                mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== currentSelectedCell.cell);
            }

            document.getElementById('btnUnsetLoop').classList.remove('hidden');
            renderJSONViewer();
            markDirty();
            if (window.showToast) window.showToast(`Loop column '${fieldKey}' assigned to cell ${currentSelectedCell.cell}`, 'success');
        });

        document.getElementById('btnUnsetSingle').addEventListener('click', function () {
            if (!currentSelectedCell) return;
            mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== currentSelectedCell.cell);
            mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== currentSelectedCell.cell);
            
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
            mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== currentSelectedCell.cell);

            mappingConfig.table_loops = mappingConfig.table_loops.filter(loop => Object.keys(loop.columns).length > 0);

            $('#globalFieldSelect').val('').trigger('change');
            document.getElementById('btnUnsetLoop').classList.add('hidden');
            renderJSONViewer();
            markDirty();
            if (window.showToast) window.showToast('Loop mapping removed', 'info');
        });

        function syncInspectorToConfig() {
            if (!mappingConfig || !mappingConfig.table_loops) return;
            if (isProgrammaticChange) return;

            const blankRowsVal = parseInt(document.getElementById('blankRowsAfterInput')?.value) || 0;
            const isSheetLoop = !!document.getElementById('splitSheetPerParentToggle')?.checked;
            const sheetNameField = $('#sheetNameFieldSelect').val() || '';
            const loopDir = document.getElementById('loopDirectionSelect')?.value || 'down';
            const insertBeh = document.getElementById('loopInsertBehaviorSelect')?.value || 'insert_duplicate';
            const groupVal = loopGroupName ? loopGroupName.value.trim() : '';

            let updatedAny = false;
            mappingConfig.table_loops.forEach(loop => {
                const isMatchingGroup = groupVal && loop.group === groupVal;
                const isMatchingCell = currentSelectedCell && (
                    (loop.columns && Object.values(loop.columns).includes(currentSelectedCell.col) && parseInt(loop.start_row) === currentSelectedCell.row) ||
                    (parseInt(loop.start_row) === currentSelectedCell.row && (loop.sheet_index === activeSheetIndex || loop.sheet_name === activeSheetName))
                );

                if (isMatchingGroup || (!groupVal && isMatchingCell)) {
                    loop.blank_rows_after = blankRowsVal;
                    loop.direction = loopDir;
                    loop.insert_behavior = insertBeh;
                    if (isSheetLoop) {
                        loop.split_sheet_per_parent = true;
                        if (sheetNameField) loop.sheet_name_field = sheetNameField;
                        else delete loop.sheet_name_field;
                    } else {
                        delete loop.split_sheet_per_parent;
                        delete loop.sheet_name_field;
                    }
                    updatedAny = true;
                }
            });

            if (updatedAny) {
                renderJSONViewer();
                markDirty();
            }
        }

        const blankRowsInputEl = document.getElementById('blankRowsAfterInput');
        if (blankRowsInputEl) {
            blankRowsInputEl.addEventListener('input', syncInspectorToConfig);
            blankRowsInputEl.addEventListener('change', syncInspectorToConfig);
        }

        const splitSheetToggleEl = document.getElementById('splitSheetPerParentToggle');
        if (splitSheetToggleEl) {
            splitSheetToggleEl.addEventListener('change', () => {
                toggleSheetLoopControls();
                syncInspectorToConfig();
            });
        }

        const sheetNameFieldSelectEl = document.getElementById('sheetNameFieldSelect');
        if (sheetNameFieldSelectEl) {
            $(sheetNameFieldSelectEl).on('change', syncInspectorToConfig);
        }

        const loopDirSelectEl = document.getElementById('loopDirectionSelect');
        if (loopDirSelectEl) {
            loopDirSelectEl.addEventListener('change', syncInspectorToConfig);
        }

        const loopInsertBehSelectEl = document.getElementById('loopInsertBehaviorSelect');
        if (loopInsertBehSelectEl) {
            loopInsertBehSelectEl.addEventListener('change', syncInspectorToConfig);
        }

        document.getElementById('btnSaveConfig').addEventListener('click', function () {
            syncInspectorToConfig();
            fetch(saveMappingUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ mapping_config: mappingConfig })
            })
            .then(async res => {
                const data = await res.json().catch(() => null);
                if (!res.ok) {
                    const errorMsg = (data && data.message) ? data.message : `HTTP ${res.status}: ${res.statusText}`;
                    throw new Error(errorMsg);
                }
                markClean();
                if (window.showToast) {
                    window.showToast(data.message || 'Mapping config saved successfully!', 'success');
                } else {
                    alert(data.message || 'Mapping config saved successfully!');
                }
            })
            .catch(err => {
                console.error('Save mapping error:', err);
                const msg = err.message || 'Failed to save mapping config.';
                if (window.showToast) window.showToast(msg, 'error');
                else alert(msg);
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
            syncInspectorToConfig();
            if (window.showToast) window.showToast('Saving mapping config...', 'info');

            fetch(saveMappingUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ mapping_config: mappingConfig })
            })
            .then(async res => {
                const data = await res.json().catch(() => null);
                if (!res.ok) {
                    const errorMsg = (data && data.message) ? data.message : `HTTP ${res.status}: ${res.statusText}`;
                    throw new Error(errorMsg);
                }
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
                console.error('Save & navigate error:', err);
                const msg = err.message || 'Failed to save mapping config.';
                if (window.showToast) window.showToast(msg, 'error');
                else alert(msg);
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

        // ========================================================
        // TREE MAP EXPLORER & VARIABLE PALETTE LOGIC
        // ========================================================
        const colLetterToNum = (letter) => {
            if (!letter) return 0;
            let num = 0;
            const str = String(letter).toUpperCase().trim();
            for (let i = 0; i < str.length; i++) {
                num = num * 26 + str.charCodeAt(i) - 64;
            }
            return num;
        };

        const parseCellCoord = (cellStr) => {
            if (!cellStr) return { colNum: 0, rowNum: 0 };
            const m = String(cellStr).toUpperCase().trim().match(/^([A-Z]+)(\d+)$/);
            if (!m) return { colNum: 0, rowNum: 0 };
            return { colNum: colLetterToNum(m[1]), rowNum: parseInt(m[2]) || 0 };
        };

        function renderTreeMapActiveMappings() {
            const singleList = document.getElementById('treeSingleItemsList');
            const loopList = document.getElementById('treeLoopItemsList');
            const ruleList = document.getElementById('treeRuleItemsList');
            const singleBadge = document.getElementById('treeSingleCountBadge');
            const loopBadge = document.getElementById('treeLoopCountBadge');
            const ruleBadge = document.getElementById('treeRuleCountBadge');

            const singleFields = [...(mappingConfig.single_fields || [])];
            const tableLoops = [...(mappingConfig.table_loops || [])];
            const rules = [...(mappingConfig.conditional_rules || [])];

            if (singleBadge) singleBadge.textContent = singleFields.length;
            if (loopBadge) loopBadge.textContent = tableLoops.length;
            if (ruleBadge) ruleBadge.textContent = rules.length;

            // Sort single fields by row number then column letter
            singleFields.sort((a, b) => {
                const ca = parseCellCoord(a.cell);
                const cb = parseCellCoord(b.cell);
                if (ca.rowNum !== cb.rowNum) return ca.rowNum - cb.rowNum;
                return ca.colNum - cb.colNum;
            });

            // Sort table loops by start_row ascending
            tableLoops.sort((a, b) => (parseInt(a.start_row) || 0) - (parseInt(b.start_row) || 0));

            // 1. Render Single Fields in Tree Explorer
            if (singleList) {
                if (singleFields.length === 0) {
                    singleList.innerHTML = '<p class="text-[10px] text-slate-400 italic p-2 text-center">No single cells mapped yet.</p>';
                } else {
                    singleList.innerHTML = '';
                    singleFields.forEach((sf) => {
                        const item = document.createElement('div');
                        const rTypeTag = (sf.render_type && sf.render_type !== 'text' && sf.render_type !== 'general' && sf.render_type !== 'default') ? ` [${sf.render_type.toUpperCase()}]` : '';
                        const isSelected = currentSelectedCell && currentSelectedCell.cell === sf.cell;
                        item.setAttribute('data-cell', sf.cell);
                        item.className = `tree-mapping-item flex items-center justify-between p-1.5 rounded-sm ${isSelected ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-500 font-semibold shadow-2xs' : 'bg-emerald-50/30 dark:bg-emerald-950/20 hover:bg-emerald-100/50 border-emerald-200/50 dark:border-emerald-800/30'} border text-xs transition-colors cursor-pointer`;
                        item.innerHTML = `
                            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                <span class="px-1.5 py-0.5 bg-emerald-600 text-white font-mono font-bold rounded-xs text-[9px] shrink-0">${sf.cell}</span>
                                <div class="min-w-0 flex-1">
                                    <span class="font-medium text-slate-800 dark:text-slate-200 block truncate text-[11px]">${sf.value_type === 'static' ? 'Static: ' + sf.static_value : sf.field_key}${rTypeTag}</span>
                                </div>
                            </div>
                            <button type="button" data-cell="${sf.cell}" class="btn-tree-remove-single text-slate-400 hover:text-rose-600 p-1 ms-1 shrink-0 transition-colors" title="Remove Mapping">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        `;
                        item.addEventListener('click', (e) => {
                            if (e.target.closest('.btn-tree-remove-single')) return;
                            const targetTd = document.querySelector(`#excelGrid td[data-cell="${sf.cell}"]`);
                            if (targetTd) {
                                targetTd.click();
                                targetTd.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                            }
                        });
                        singleList.appendChild(item);
                    });

                    singleList.querySelectorAll('.btn-tree-remove-single').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const cRef = this.dataset.cell;
                            mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== cRef);
                            renderJSONViewer();
                            markDirty();
                            if (window.showToast) window.showToast(`Single mapping removed for cell ${cRef}`, 'info');
                        });
                    });
                }
            }

            // 2. Render Table Loops in Tree Explorer (Sorted by start_row, and columns alphabetically A -> Z)
            if (loopList) {
                if (tableLoops.length === 0) {
                    loopList.innerHTML = '<p class="text-[10px] text-slate-400 italic p-2 text-center">No table loops configured yet.</p>';
                } else {
                    loopList.innerHTML = '';
                    tableLoops.forEach((loop) => {
                        // Sort columns strictly by Excel column letter alphabetical order
                        const sortedColEntries = Object.entries(loop.columns || {}).sort((a, b) => {
                            return colLetterToNum(a[1]) - colLetterToNum(b[1]);
                        });

                        const card = document.createElement('div');
                        card.className = 'tree-mapping-item p-2 rounded-sm bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs space-y-1.5 shadow-2xs';
                        
                        let colsHtml = '';
                        sortedColEntries.forEach(([fk, colLetter]) => {
                            const rType = (loop.render_types && loop.render_types[fk]) ? ` [${loop.render_types[fk].toUpperCase()}]` : '';
                            const cellSpec = `${colLetter}${loop.start_row}`;
                            const isSelected = currentSelectedCell && currentSelectedCell.cell === cellSpec;
                            colsHtml += `
                                <div class="flex items-center justify-between text-[11px] py-1 px-2 ${isSelected ? 'bg-sky-50 dark:bg-sky-950/60 border-sky-500 font-semibold shadow-2xs' : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 hover:border-sky-300 hover:bg-sky-50/30 dark:hover:bg-sky-950/20'} rounded-sm border cursor-pointer transition-all"
                                     data-cell="${cellSpec}">
                                    <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                        <span class="w-5 h-5 flex items-center justify-center bg-sky-100 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 font-mono font-bold rounded-sm text-[10px] shrink-0 border border-sky-200 dark:border-sky-800">
                                            ${colLetter}
                                        </span>
                                        <span class="font-mono text-slate-700 dark:text-slate-200 truncate text-[11px]" title="${fk}">${fk}</span>
                                    </div>
                                    ${rType ? `<span class="px-1 py-0.2 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-xs text-[8px] font-bold font-mono shrink-0 ms-1">${rType.toUpperCase()}</span>` : ''}
                                </div>
                            `;
                        });

                        card.innerHTML = `
                            <div class="flex items-center justify-between gap-1 border-b border-slate-200 dark:border-slate-800 pb-1.5">
                                <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                    <span class="px-1.5 py-0.5 bg-sky-600 text-white font-mono font-bold rounded-sm text-[9px] shrink-0">Row ${loop.start_row}</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200 truncate text-[11px]" title="${loop.group}">${loop.group}</span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="px-1.5 py-0.2 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-sm text-[9px] font-mono font-bold">${sortedColEntries.length} cols</span>
                                    <button type="button" data-group="${loop.group}" class="btn-tree-remove-loop text-slate-400 hover:text-rose-600 p-0.5 transition-colors" title="Delete Loop Group">
                                        <i class="fa-solid fa-trash-can text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1 pt-0.5">
                                ${colsHtml || '<p class="text-[10px] text-slate-400 italic text-center py-1">No columns bound</p>'}
                            </div>
                        `;

                        card.querySelectorAll('[data-cell]').forEach(cEl => {
                            cEl.addEventListener('click', () => {
                                const targetCell = cEl.dataset.cell;
                                const targetTd = document.querySelector(`#excelGrid td[data-cell="${targetCell}"]`);
                                if (targetTd) {
                                    targetTd.click();
                                    targetTd.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                                }
                            });
                        });

                        loopList.appendChild(card);
                    });

                    loopList.querySelectorAll('.btn-tree-remove-loop').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const gName = this.dataset.group;
                            mappingConfig.table_loops = mappingConfig.table_loops.filter(l => l.group !== gName);
                            renderJSONViewer();
                            markDirty();
                            if (window.showToast) window.showToast(`Loop group '${gName}' removed`, 'info');
                        });
                    });
                }
            }

            // 3. Render Conditional Rules in Tree Explorer
            if (ruleList) {
                if (rules.length === 0) {
                    ruleList.innerHTML = '<p class="text-[10px] text-slate-400 italic p-2 text-center">No conditional rules defined yet.</p>';
                } else {
                    ruleList.innerHTML = '';
                    rules.forEach((r, rIdx) => {
                        const targetCell = r.target_cell || r.target_column || '';
                        const opLabel = r.operator === 'equals' ? '==' : r.operator;
                        const outText = r.output_type === 'static_value' ? `"${r.output_static_value}"` : r.output_field_key;
                        const isSelected = currentSelectedCell && currentSelectedCell.cell === targetCell;
                        const item = document.createElement('div');
                        item.setAttribute('data-cell', targetCell);
                        item.className = `tree-mapping-item flex items-center justify-between p-1.5 rounded-sm ${isSelected ? 'bg-amber-50 dark:bg-amber-950/60 border-amber-500 font-semibold shadow-2xs' : 'bg-amber-50/30 dark:bg-amber-950/20 hover:bg-amber-100/50 border-amber-200/50 dark:border-amber-800/30'} border text-xs transition-colors cursor-pointer`;
                        item.innerHTML = `
                            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                <span class="px-1.5 py-0.5 bg-amber-500 text-white font-mono font-bold rounded-xs text-[9px] shrink-0">${targetCell}</span>
                                <div class="min-w-0 flex-1">
                                    <span class="font-medium text-slate-800 dark:text-slate-200 block truncate text-[11px]">IF ${r.field_key} ${opLabel} "${r.value || ''}" &rarr; ${outText}</span>
                                </div>
                            </div>
                            <button type="button" data-idx="${rIdx}" class="btn-tree-remove-rule text-slate-400 hover:text-rose-600 p-1 ms-1 shrink-0 transition-colors" title="Remove Rule">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        `;
                        item.addEventListener('click', (e) => {
                            if (e.target.closest('.btn-tree-remove-rule')) return;
                            const targetTd = document.querySelector(`#excelGrid td[data-cell="${targetCell}"]`);
                            if (targetTd) {
                                targetTd.click();
                                targetTd.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                            }
                        });
                        ruleList.appendChild(item);
                    });

                    ruleList.querySelectorAll('.btn-tree-remove-rule').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const idx = parseInt(this.dataset.idx);
                            mappingConfig.conditional_rules.splice(idx, 1);
                            renderJSONViewer();
                            markDirty();
                            if (window.showToast) window.showToast('Conditional rule removed', 'info');
                        });
                    });
                }
            }

            // 4. Auto scroll highlighted active item in tree into view
            if (currentSelectedCell) {
                const activeTreeItem = document.querySelector(`.tree-mapping-item[data-cell="${currentSelectedCell.cell}"], [data-cell="${currentSelectedCell.cell}"]`);
                if (activeTreeItem) {
                    activeTreeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            // 4. Update Mapped Checkmarks on Palette Tree
            const boundKeys = new Set();
            singleFields.forEach(f => { if (f.field_key) boundKeys.add(f.field_key); });
            tableLoops.forEach(l => {
                Object.keys(l.columns || {}).forEach(k => boundKeys.add(k));
            });

            document.querySelectorAll('.tree-variable-item').forEach(item => {
                const key = item.dataset.key;
                const badge = item.querySelector('.tree-mapped-badge');
                if (badge) {
                    if (boundKeys.has(key)) {
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            });
        }

        window.handleTreeSearch = function(query) {
            const q = (query || '').toLowerCase().trim();
            
            // Search in variables palette
            document.querySelectorAll('.tree-group-card').forEach(groupCard => {
                let hasVisibleChild = false;
                groupCard.querySelectorAll('.tree-variable-item').forEach(item => {
                    const key = (item.dataset.key || '').toLowerCase();
                    const label = (item.dataset.label || '').toLowerCase();
                    if (!q || key.includes(q) || label.includes(q)) {
                        item.style.display = 'flex';
                        hasVisibleChild = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                groupCard.style.display = hasVisibleChild ? 'block' : 'none';
            });

            // Search in active mappings
            document.querySelectorAll('.tree-mapping-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = (!q || text.includes(q)) ? 'flex' : 'none';
            });
        };

        // Tree Variable Palette Drag & Click bindings
        document.querySelectorAll('.tree-variable-item').forEach(item => {
            // Dragstart
            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.dataset.key);
                e.dataTransfer.effectAllowed = 'copy';
            });

            // Click to assign
            item.addEventListener('click', function() {
                const key = this.dataset.key;
                if (!currentSelectedCell) {
                    if (window.showToast) window.showToast('Please click any cell on the Excel grid first to bind this field variable.', 'info');
                    else alert('Please click any cell on the Excel grid first to bind this field variable.');
                    return;
                }
                setDataSourceMode('variable');
                $('#globalFieldSelect').val(key).trigger('change');
                document.getElementById('btnAssignSingle').click();
            });
        });

        // Grid Cell Drag & Drop target listeners
        document.querySelectorAll('#excelGrid td').forEach(td => {
            td.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                this.classList.add('cell-drop-hover');
            });

            td.addEventListener('dragleave', function() {
                this.classList.remove('cell-drop-hover');
            });

            td.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('cell-drop-hover');
                const fieldKey = e.dataTransfer.getData('text/plain');
                if (!fieldKey) return;

                this.click();
                setDataSourceMode('variable');
                $('#globalFieldSelect').val(fieldKey).trigger('change');
                document.getElementById('btnAssignSingle').click();
            });
        });

        // ========================================================
        // SPREADSHEET GRID CONTEXT MENU (RIGHT-CLICK)
        // ========================================================
        const gridContextMenu = document.getElementById('gridContextMenu');
        const ctxMenuCellLabel = document.getElementById('ctxMenuCellLabel');
        const ctxMenuStatusLabel = document.getElementById('ctxMenuStatusLabel');
        const ctxMenuAssignSingle = document.getElementById('ctxMenuAssignSingle');
        const ctxMenuAssignLoop = document.getElementById('ctxMenuAssignLoop');
        const ctxMenuAddRule = document.getElementById('ctxMenuAddRule');
        const ctxMenuClearMapping = document.getElementById('ctxMenuClearMapping');

        if (gridContextMenu) {
            excelGrid.addEventListener('contextmenu', function(e) {
                const td = e.target.closest('td');
                if (!td) return;
                e.preventDefault();

                td.click(); // Select cell

                const cell = td.dataset.cell;
                ctxMenuCellLabel.textContent = `Cell ${cell}`;

                // Check mapping status
                const isSingle = mappingConfig.single_fields.some(f => f.cell === cell);
                let isLoop = false;
                mappingConfig.table_loops.forEach(loop => {
                    if (parseInt(loop.start_row) === parseInt(td.dataset.row)) {
                        if (Object.values(loop.columns).includes(td.dataset.col)) isLoop = true;
                    }
                });
                const isRule = (mappingConfig.conditional_rules || []).some(r => r.target_cell === cell);

                if (isSingle) {
                    ctxMenuStatusLabel.textContent = 'Single';
                    ctxMenuStatusLabel.className = 'text-[9px] px-1.5 py-0.2 rounded-xs bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 font-mono font-bold';
                } else if (isLoop) {
                    ctxMenuStatusLabel.textContent = 'Loop';
                    ctxMenuStatusLabel.className = 'text-[9px] px-1.5 py-0.2 rounded-xs bg-sky-100 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300 font-mono font-bold';
                } else if (isRule) {
                    ctxMenuStatusLabel.textContent = 'IF Rule';
                    ctxMenuStatusLabel.className = 'text-[9px] px-1.5 py-0.2 rounded-xs bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 font-mono font-bold';
                } else {
                    ctxMenuStatusLabel.textContent = 'Unmapped';
                    ctxMenuStatusLabel.className = 'text-[9px] px-1.5 py-0.2 rounded-xs bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono font-bold';
                }

                // Position menu within viewport
                const mouseX = e.clientX;
                const mouseY = e.clientY;
                const menuWidth = 215;
                const menuHeight = 180;
                
                const leftPos = (mouseX + menuWidth > window.innerWidth) ? (mouseX - menuWidth - 8) : mouseX;
                const topPos = (mouseY + menuHeight > window.innerHeight) ? (mouseY - menuHeight - 8) : mouseY;

                gridContextMenu.style.left = `${leftPos}px`;
                gridContextMenu.style.top = `${topPos}px`;
                gridContextMenu.classList.remove('hidden');
            });

            document.addEventListener('click', function(e) {
                if (!gridContextMenu.contains(e.target)) {
                    gridContextMenu.classList.add('hidden');
                }
            });

            ctxMenuAssignSingle.addEventListener('click', function() {
                gridContextMenu.classList.add('hidden');
                setInspectorTab('single');
                $('#globalFieldSelect').select2('open');
            });

            ctxMenuAssignLoop.addEventListener('click', function() {
                gridContextMenu.classList.add('hidden');
                setInspectorTab('loop');
            });

            ctxMenuAddRule.addEventListener('click', function() {
                gridContextMenu.classList.add('hidden');
                const isLoopRow = mappingConfig.table_loops.some(l => parseInt(l.start_row) === (currentSelectedCell ? currentSelectedCell.row : -1));
                setInspectorTab(isLoopRow ? 'loop' : 'single');
                if (toggleEnableConditionalLogic) {
                    toggleEnableConditionalLogic.checked = true;
                    toggleEnableConditionalLogic.dispatchEvent(new Event('change'));
                }
            });

            ctxMenuClearMapping.addEventListener('click', function() {
                gridContextMenu.classList.add('hidden');
                if (!currentSelectedCell) return;
                const cell = currentSelectedCell.cell;
                mappingConfig.single_fields = mappingConfig.single_fields.filter(f => f.cell !== cell);
                mappingConfig.table_loops.forEach(loop => {
                    if (parseInt(loop.start_row) === currentSelectedCell.row) {
                        Object.keys(loop.columns).forEach(k => {
                            if (loop.columns[k] === currentSelectedCell.col) delete loop.columns[k];
                        });
                    }
                });
                mappingConfig.conditional_rules = (mappingConfig.conditional_rules || []).filter(r => r.target_cell !== cell);
                renderJSONViewer();
                markDirty();
                if (window.showToast) window.showToast(`Cleared mappings on cell ${cell}`, 'info');
            });
        }

        updateRenderTypeCards('default');
        renderJSONViewer();
    });
</script>
