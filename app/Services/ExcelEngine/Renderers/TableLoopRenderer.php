<?php

namespace App\Services\ExcelEngine\Renderers;

use App\Services\ExcelEngine\Core\RowShiftTracker;
use App\Services\ExcelEngine\Core\StyleCloner;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TableLoopRenderer
{
    protected StyleCloner $styleCloner;
    protected FormulaCompiler $formulaCompiler;
    protected SingleFieldRenderer $fieldRenderer;

    /**
     * Track already expanded start rows per sheet to prevent duplicate row insertions
     */
    protected array $expandedRows = [];

    public function __construct(
        ?StyleCloner $styleCloner = null,
        ?FormulaCompiler $formulaCompiler = null,
        ?SingleFieldRenderer $fieldRenderer = null
    ) {
        $this->styleCloner = $styleCloner ?? new StyleCloner();
        $this->formulaCompiler = $formulaCompiler ?? new FormulaCompiler();
        $this->fieldRenderer = $fieldRenderer ?? new SingleFieldRenderer();
    }

    /**
     * Render all table loops / sections
     */
    public function render(
        Spreadsheet $spreadsheet,
        array $sections,
        array $payload,
        RowShiftTracker $shiftTracker,
        array $sheetNameMap = [],
        array $conditionalRules = []
    ): void {
        $this->expandedRows = [];

        // Cluster sections that belong to the same multi-row template block
        $clusters = $this->clusterSections($sections);

        foreach ($clusters as $cluster) {
            $this->renderSectionCluster($spreadsheet, $cluster, $payload, $shiftTracker, $sheetNameMap, $conditionalRules);
        }
    }

    /**
     * Render all table loops / sections directly onto a specific worksheet instance
     */
    public function renderOnSheet(
        Worksheet $sheet,
        array $sections,
        array $payload,
        RowShiftTracker $shiftTracker,
        array $conditionalRules = []
    ): void {
        $this->expandedRows = [];
        $clusters = $this->clusterSections($sections);

        foreach ($clusters as $cluster) {
            $this->renderClusterOnGivenSheet($sheet, $cluster, $payload, $shiftTracker, [], $conditionalRules);
        }
    }

    /**
     * Cluster adjacent sections that belong to the same multi-row item block
     */
    protected function clusterSections(array $sections): array
    {
        $clusters = [];
        foreach ($sections as $section) {
            $sheetId = $section['sheet'] ?? $section['sheet_index'] ?? 0;
            $startRow = (int)($section['start_row'] ?? 1);
            $group = $section['group'] ?? $section['key'] ?? '';

            // Check if section can join an existing cluster (same sheet and adjacent start_row within 2 rows)
            $matchedClusterIdx = null;
            foreach ($clusters as $idx => $cluster) {
                if ($cluster['sheet'] === $sheetId) {
                    $minRow = min($cluster['start_rows']);
                    $maxRow = max($cluster['start_rows']);
                    if (($startRow >= $minRow - 1 && $startRow <= $maxRow + 1) || ($group && $cluster['group'] === $group)) {
                        $matchedClusterIdx = $idx;
                        break;
                    }
                }
            }

            if ($matchedClusterIdx !== null) {
                $clusters[$matchedClusterIdx]['sections'][] = $section;
                $clusters[$matchedClusterIdx]['start_rows'][] = $startRow;
            } else {
                $clusters[] = [
                    'sheet' => $sheetId,
                    'group' => $group,
                    'start_rows' => [$startRow],
                    'sections' => [$section]
                ];
            }
        }
        return $clusters;
    }

    /**
     * Render a cluster of sections with Dynamic Child Row Expansion per parent
     */
    protected function renderSectionCluster(
        Spreadsheet $spreadsheet,
        array $cluster,
        array $payload,
        RowShiftTracker $shiftTracker,
        array $sheetNameMap,
        array $conditionalRules
    ): void {
        $sheetSections = $cluster['sections'];
        $sheet = $this->resolveWorksheet($spreadsheet, $sheetSections[0], $sheetNameMap);
        if (!$sheet) {
            return;
        }

        $this->renderClusterOnGivenSheet($sheet, $cluster, $payload, $shiftTracker, $sheetNameMap, $conditionalRules);
    }

    /**
     * Core execution on a given Worksheet instance
     */
    protected function renderClusterOnGivenSheet(
        Worksheet $sheet,
        array $cluster,
        array $payload,
        RowShiftTracker $shiftTracker,
        array $sheetNameMap,
        array $conditionalRules
    ): void {
        $sheetIdentifier = $cluster['sheet'];
        $sheetSections = $cluster['sections'];

        // 1. Resolve source data items array
        $items = null;
        $processMappings = [];
        $processRenderTypes = [];
        $footerFormulas = [];

        // Collect target columns of all conditional rules for this sheet to prevent overwrite
        $conditionalTargetCols = [];
        foreach ($conditionalRules as $cRule) {
            $tSpec = $cRule['target_cell'] ?? ($cRule['target_column'] ?? '');
            if (!empty($tSpec) && preg_match('/^([A-Z]+)/i', $tSpec, $m)) {
                $conditionalTargetCols[] = strtoupper($m[1]);
            }
        }
        $conditionalTargetCols = array_unique($conditionalTargetCols);

        foreach ($sheetSections as $sec) {
            if ($items === null) {
                $items = $this->resolveDataSource($sec, $payload);
            }

            $loopMode = $sec['loop_mode'] ?? 'flat';
            if ($loopMode === 'nested_block') {
                $nestedSections[] = $sec;
            }

            if (!empty($sec['footer_formulas'])) {
                $footerFormulas = array_merge($footerFormulas, $sec['footer_formulas']);
            }
        }

        if (empty($items) || !is_array($items)) {
            return;
        }

        $items = array_values($items);
        $totalItems = count($items);
        if ($totalItems === 0) {
            return;
        }

        $distinctStartRows = array_unique($cluster['start_rows']);
        sort($distinctStartRows);

        $minStartRow = min($distinctStartRows);
        $maxStartRow = max($distinctStartRows);
        $templateBlockSize = max(1, ($maxStartRow - $minStartRow + 1));

        // Determine where child nested blocks begin and collect their column ranges
        $minProcessStartRow = $minStartRow;
        $nestedBlockRanges = [];
        foreach ($sheetSections as $sec) {
            $secLoopMode = $sec['loop_mode'] ?? 'flat';
            $secStart = (int)($sec['start_row'] ?? $minStartRow);
            if ($secLoopMode === 'nested_block') {
                if ($minProcessStartRow === $minStartRow || $secStart < $minProcessStartRow) {
                    $minProcessStartRow = $secStart;
                }
                $mappings = $sec['mappings'] ?? $sec['columns'] ?? [];
                $colIndices = [];
                foreach ($mappings as $mDef) {
                    $colLetter = is_array($mDef) ? ($mDef['column'] ?? null) : $mDef;
                    if ($colLetter) {
                        $colIndices[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colLetter);
                    }
                }
                if (!empty($colIndices)) {
                    $nestedBlockRanges[] = [
                        'start_row' => $secStart,
                        'min_col' => min($colIndices),
                        'max_col' => max($colIndices) + 2,
                        'formulas' => $sec['row_formulas'] ?? $sec['formulas'] ?? [],
                    ];
                }
            }
        }
        $processStartOffsetInBlock = max(0, $minProcessStartRow - $minStartRow);

        $nestedSections = array_values(array_filter($sheetSections, function ($sec) {
            return ($sec['loop_mode'] ?? 'flat') === 'nested_block';
        }));

        $actualMinStartRow = $shiftTracker->getShiftedRow($sheetIdentifier, $minStartRow);

        $blankRowsAfter = 0;
        foreach ($sheetSections as $sec) {
            if (!empty($sec['blank_rows_after'])) {
                $blankRowsAfter = max($blankRowsAfter, (int)$sec['blank_rows_after']);
            }
        }

        // 2. Calculate dynamic height for each parent item by checking all nested child arrays
        $itemRowHeights = [];
        foreach ($items as $i => $itemData) {
            $maxChildCount = 1;
            foreach ($itemData as $k => $v) {
                if (is_array($v) && !empty($v)) {
                    $maxChildCount = max($maxChildCount, count($v));
                }
            }
            
            // Item height is at least the template block size, or expanded to fit all child items
            $itemRowHeights[$i] = max($templateBlockSize, $maxChildCount + $processStartOffsetInBlock);
        }

        $totalBlankRows = $totalItems * $blankRowsAfter;
        $totalRequiredRows = array_sum($itemRowHeights) + $totalBlankRows;

        // Determine if this cluster uses overwrite behavior (static pre-existing rows)
        $isOverwrite = false;
        foreach ($sheetSections as $sec) {
            if (($sec['insert_behavior'] ?? '') === 'overwrite') {
                $isOverwrite = true;
                break;
            }
        }

        // 3. Pre-insert rows dynamically for all items and their children
        $expansionKey = "{$sheetIdentifier}_{$minStartRow}_{$maxStartRow}";
        if (!$isOverwrite && $totalRequiredRows > $templateBlockSize && empty($this->expandedRows[$expansionKey])) {
            $totalNewRows = $totalRequiredRows - $templateBlockSize;
            $templateBottomRow = $actualMinStartRow + $templateBlockSize - 1;

            $sheet->insertNewRowBefore($templateBottomRow + 1, $totalNewRows);
            $shiftTracker->recordShift($sheetIdentifier, $templateBottomRow, $totalNewRows);

            $this->expandedRows[$expansionKey] = true;
        }

        // 4. Populate each parent item and its child processes row by row
        $runningStartRow = $actualMinStartRow;

        foreach ($items as $itemIdx => $itemData) {
            $autoIdx = $itemIdx + 1;
            $currentParentHeight = $itemRowHeights[$itemIdx];

            for ($offset = 0; $offset < $currentParentHeight; $offset++) {
                $currentRowNum = $runningStartRow + $offset;
                $targetTemplateRow = $minStartRow + min($offset, $templateBlockSize - 1);

                // Clone style & replicate native template formulas if this is a newly inserted row
                if ($currentRowNum > ($actualMinStartRow + $templateBlockSize - 1)) {
                    $templateSrcRow = $actualMinStartRow + min($offset, $templateBlockSize - 1);
                    $this->styleCloner->cloneRowStyle($sheet, $templateSrcRow, $currentRowNum);

                    // Auto-replicate and shift native template formulas from templateSrcRow to currentRowNum
                    if ($offset < $templateBlockSize) {
                        $rowShift = $currentRowNum - $templateSrcRow;
                        $highestColumn = $sheet->getHighestDataColumn($templateSrcRow);
                        $highestColIndex = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn), 60);
                        for ($c = 1; $c <= $highestColIndex; $c++) {
                            $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                            if ($sheet->cellExists($cLetter . $templateSrcRow)) {
                                $srcCell = $sheet->getCell($cLetter . $templateSrcRow);
                                if ($srcCell->isFormula()) {
                                    $srcFormula = $srcCell->getValue();
                                    $shiftedFormula = $this->formulaCompiler->shiftFormulaRows($srcFormula, $rowShift);
                                    $sheet->getCell($cLetter . $currentRowNum)->setValueExplicit(
                                        $shiftedFormula,
                                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA
                                    );
                                }
                            }
                        }
                    }

                    // Clear any cloned formula values in child rows (offset >= templateBlockSize)
                    if ($offset >= $templateBlockSize) {
                        $highestColumn = $sheet->getHighestDataColumn($currentRowNum);
                        $highestColIndex = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn), 60);
                        for ($c = 1; $c <= $highestColIndex; $c++) {
                            $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                            if ($sheet->cellExists($cLetter . $currentRowNum)) {
                                $cell = $sheet->getCell($cLetter . $currentRowNum);
                                if ($cell->isFormula() || $cell->getValue() !== null) {
                                    $cell->setValue('');
                                }
                            }
                        }
                    }
                }

                $context = [
                    'row' => $currentRowNum,
                    'start_row' => $actualMinStartRow,
                    'end_row' => $actualMinStartRow + $totalRequiredRows - 1,
                    'sheet_map' => $sheetNameMap,
                    'block_index' => $itemIdx,
                    'row_offset' => $offset,
                ];

                // Find sections that match this template row (only for offset < templateBlockSize)
                $matchingSections = [];
                if ($offset < $templateBlockSize) {
                    $matchingSections = array_filter($sheetSections, function ($sec) use ($targetTemplateRow, $minStartRow) {
                        return (int)($sec['start_row'] ?? $minStartRow) === $targetTemplateRow;
                    });
                }

                $renderedColumnsThisRow = [];

                foreach ($matchingSections as $section) {
                    $secStartRow = (int)($section['start_row'] ?? $minStartRow);
                    $secRelativeOffset = max(0, $targetTemplateRow - $secStartRow);
                    $secLoopMode = $section['loop_mode'] ?? 'flat';

                    $mappings = $section['mappings'] ?? $section['columns'] ?? [];
                    $rowFormulas = $section['row_formulas'] ?? $section['formulas'] ?? [];
                    $staticValues = $section['static_values'] ?? [];
                    $renderTypes = $section['render_types'] ?? [];

                    foreach ($mappings as $k => $mDef) {
                        $colLetter = is_array($mDef) ? ($mDef['column'] ?? null) : $mDef;
                        if ($colLetter) {
                            $renderedColumnsThisRow[$colLetter] = true;
                        }
                    }

                    $targetDataForSection = $itemData;
                    $lookupOffset = $secRelativeOffset;

                    if ($secLoopMode === 'nested_block') {
                        $childArr = $this->resolveSectionChildArray($section, $itemData);
                        if (!empty($childArr) && isset($childArr[$secRelativeOffset])) {
                            $targetDataForSection = $childArr[$secRelativeOffset];
                            $lookupOffset = 0; // Direct lookup on child array item
                        } else {
                            // If no child data exists on this row for this nested section, clear mapped cells
                            foreach ($mappings as $k => $mDef) {
                                $colLetter = is_array($mDef) ? ($mDef['column'] ?? null) : $mDef;
                                if ($colLetter && (empty($conditionalTargetCols) || !in_array(strtoupper($colLetter), $conditionalTargetCols))) {
                                    $sheet->setCellValue($colLetter . $currentRowNum, null);
                                }
                            }
                            continue;
                        }
                    }

                    $this->renderRowMappings(
                        $sheet,
                        $mappings,
                        $targetDataForSection,
                        $currentRowNum,
                        $autoIdx,
                        $staticValues,
                        $renderTypes,
                        $context,
                        $lookupOffset,
                        $conditionalTargetCols
                    );

                    // Render formulas ONLY on template rows (offset < templateBlockSize)
                    $this->renderRowFormulas($sheet, $rowFormulas, $context);
                }

                // Automatic child cascading for each nested section independently
                foreach ($nestedSections as $nSec) {
                    $nStartRow = (int)($nSec['start_row'] ?? $minStartRow);
                    $nStartOffset = max(0, $nStartRow - $minStartRow);
                    
                    if ($offset < $nStartOffset) {
                        continue;
                    }

                    $childOffset = $offset - $nStartOffset;
                    $nMappings = $nSec['mappings'] ?? $nSec['columns'] ?? [];
                    $nRenderTypes = $nSec['render_types'] ?? [];

                    $targetChildArray = $this->resolveSectionChildArray($nSec, $itemData);
                    if (empty($targetChildArray) || !isset($targetChildArray[$childOffset])) {
                        continue;
                    }

                    $childItemData = $targetChildArray[$childOffset];
                    if (!is_array($childItemData)) {
                        continue;
                    }

                    // Render mappings for this child row if not yet rendered this row
                    $cascadeMappings = [];
                    foreach ($nMappings as $pKey => $pDef) {
                        $pCol = is_array($pDef) ? ($pDef['column'] ?? null) : $pDef;
                        if ($pCol && empty($renderedColumnsThisRow[$pCol])) {
                            $cascadeMappings[$pKey] = $pDef;
                        }
                    }

                    if (!empty($cascadeMappings)) {
                        $this->renderRowMappings(
                            $sheet,
                            $cascadeMappings,
                            $childItemData,
                            $currentRowNum,
                            $autoIdx,
                            [],
                            $nRenderTypes,
                            $context,
                            0, // Direct lookup on childItemData
                            $conditionalTargetCols
                        );
                    }
                }

                // Safely replicate formulas strictly belonging to nested block column ranges (e.g. Cost in Col AA)
                if (!empty($nestedBlockRanges)) {
                    foreach ($nestedBlockRanges as $secRange) {
                        $secStartOffset = max(0, $secRange['start_row'] - $minStartRow);
                        if ($offset > $secStartOffset) {
                            $secTemplateSrcRow = $actualMinStartRow + $secStartOffset;
                            $rowShift = $currentRowNum - $secTemplateSrcRow;

                            for ($c = $secRange['min_col']; $c <= $secRange['max_col']; $c++) {
                                $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                                if (empty($renderedColumnsThisRow[$cLetter])) {
                                    $srcCell = $sheet->getCell($cLetter . $secTemplateSrcRow);
                                    if ($srcCell->isFormula()) {
                                        $srcFormula = $srcCell->getValue();
                                        $shiftedFormula = $this->formulaCompiler->shiftFormulaRows($srcFormula, $rowShift);
                                        $sheet->getCell($cLetter . $currentRowNum)->setValueExplicit(
                                            $shiftedFormula,
                                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA
                                        );
                                    }
                                }
                            }

                            if (!empty($secRange['formulas'])) {
                                $this->renderRowFormulas($sheet, $secRange['formulas'], $context);
                            }
                        }
                    }
                }

                // Conditional rules (only evaluate on rows starting from the process block row, e.g. row 14+)
                if (!empty($conditionalRules) && $offset >= $processStartOffsetInBlock) {
                    $ruleOffset = $offset - $processStartOffsetInBlock;
                    $this->renderRowConditionalRules($sheet, $conditionalRules, $itemData, $currentRowNum, $context, $ruleOffset);
                }
            }

            $runningStartRow += $currentParentHeight;

            // Insert Blank Rows Between Items if configured
            if (!$isOverwrite && $blankRowsAfter > 0 && ($itemIdx < $totalItems - 1)) {
                $sheet->insertNewRowBefore($runningStartRow, $blankRowsAfter);
                $shiftTracker->recordShift($sheetIdentifier, $runningStartRow - 1, $blankRowsAfter);
                $runningStartRow += $blankRowsAfter;
                $totalRequiredRows += $blankRowsAfter;
            }
        }

        // Insert Blank Rows After Final Item / Loop if configured
        if (!$isOverwrite && $blankRowsAfter > 0) {
            $lastDataRow = $runningStartRow - 1;
            $sheet->insertNewRowBefore($lastDataRow + 1, $blankRowsAfter);
            $shiftTracker->recordShift($sheetIdentifier, $lastDataRow, $blankRowsAfter);
            $runningStartRow += $blankRowsAfter;
            $totalRequiredRows += $blankRowsAfter;
        }

        // Auto-expand native template footer formulas referencing the template loop end row (e.g. $AM$13:$AM$15 -> $AM$13:$AM$31)
        if (!$isOverwrite && $totalRequiredRows > $templateBlockSize) {
            $templateBottomRow = $actualMinStartRow + $templateBlockSize - 1;
            $finalEndRow = $actualMinStartRow + $totalRequiredRows - 1;

            $highestRow = min($sheet->getHighestDataRow(), 300);
            $highestCol = $sheet->getHighestDataColumn();
            $highestColIdx = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol), 60);

            for ($r = $finalEndRow + 1; $r <= $highestRow; $r++) {
                for ($c = 1; $c <= $highestColIdx; $c++) {
                    $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    if ($sheet->cellExists("{$cLetter}{$r}")) {
                        $cell = $sheet->getCell("{$cLetter}{$r}");
                        if ($cell->isFormula()) {
                            $formula = $cell->getValue();
                            $pattern = '/(:\\$?([A-Z]{1,3})\\$?)' . $templateBottomRow . '(?![0-9])/i';
                            if (preg_match($pattern, $formula)) {
                                $updatedFormula = preg_replace($pattern, '${1}' . $finalEndRow, $formula);
                                $cell->setValueExplicit($updatedFormula, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA);
                            }
                        }
                    }
                }
            }
        }

        // Render Footer Formulas if defined
        if (!empty($footerFormulas)) {
            $finalEndRow = $actualMinStartRow + $totalRequiredRows - 1;
            $footerContext = [
                'row' => $finalEndRow,
                'start_row' => $actualMinStartRow,
                'end_row' => $finalEndRow,
                'sheet_map' => $sheetNameMap,
            ];

            $this->renderFooterFormulas($sheet, $footerFormulas, $footerContext, $shiftTracker, $sheetIdentifier);
        }
    }

    /**
     * Render column mappings for a specific row with rowOffset sub-process support
     */
    protected function renderRowMappings(
        Worksheet $sheet,
        array $mappings,
        array $itemData,
        int $rowNum,
        int $autoIdx,
        array $staticValues = [],
        array $renderTypes = [],
        array $context = [],
        int $rowOffset = 0,
        array $conditionalTargetCols = []
    ): void {
        foreach ($mappings as $key => $mappingDef) {
            $columnLetter = is_array($mappingDef) ? ($mappingDef['column'] ?? null) : $mappingDef;
            if (!$columnLetter) {
                continue;
            }

            if (!empty($conditionalTargetCols) && in_array(strtoupper($columnLetter), $conditionalTargetCols)) {
                // Handled exclusively by conditional rules
                continue;
            }

            $cellCoordinate = $columnLetter . $rowNum;
            $valueType = is_array($mappingDef) ? ($mappingDef['type'] ?? 'variable') : 'variable';
            $format = is_array($mappingDef) ? ($mappingDef['format'] ?? 'default') : ($renderTypes[$key] ?? 'default');

            $cleanKey = str_contains($key, '__') ? explode('__', $key)[0] : $key;

            $val = null;
            if ($valueType === 'auto_increment' || $cleanKey === 'idx' || $cleanKey === 'no' || $cleanKey === 'row_number' || $cleanKey === 'auto_number') {
                if ($rowOffset === 0) {
                    $startFrom = is_array($mappingDef) ? (int)($mappingDef['start_from'] ?? 1) : 1;
                    $val = $autoIdx + $startFrom - 1;
                    $format = ($format === 'default') ? 'number' : $format;
                }
            } elseif (str_starts_with($key, 'static_col_') || isset($staticValues[$key])) {
                $val = $staticValues[$key] ?? (is_array($mappingDef) ? ($mappingDef['static_value'] ?? '') : '');
                
                // Dynamic formula expression (e.g. =I{row}*J{row})
                if (is_string($val) && str_starts_with(trim($val), '=')) {
                    $compiledFormula = $this->formulaCompiler->compile(trim($val), $context);
                    $sheet->getCell($cellCoordinate)->setValueExplicit($compiledFormula, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA);
                    if ($format !== 'default') {
                        $this->fieldRenderer->applyFormatOnly($sheet, $cellCoordinate, $format, is_array($mappingDef) ? $mappingDef : []);
                    }
                    continue;
                }
            } else {
                // Direct lookup on item (only on rowOffset 0 for parent fields)
                if ($rowOffset === 0 && array_key_exists($cleanKey, $itemData)) {
                    $val = $itemData[$cleanKey];
                } else {
                    $preferredSource = $context['preferred_source'] ?? null;
                    $val = $this->resolveNestedFieldValue($itemData, $cleanKey, $rowOffset, $preferredSource);
                }
            }

            $opts = is_array($mappingDef) ? $mappingDef : [];
            $this->fieldRenderer->applyValueAndFormat($sheet, $cellCoordinate, $val, $format, $opts);
        }
    }

    /**
     * Resolve child array for a specific nested block section
     */
    protected function resolveSectionChildArray(array $section, array $itemData): ?array
    {
        $source = $section['source'] ?? ($section['data_source'] ?? null);
        if ($source && isset($itemData[$source]) && is_array($itemData[$source])) {
            return array_values($itemData[$source]);
        }

        $group = strtolower($section['group'] ?? '');
        if (str_contains($group, 'add') && isset($itemData['additional_processes']) && is_array($itemData['additional_processes'])) {
            return array_values($itemData['additional_processes']);
        }
        if (str_contains($group, 'add') && isset($itemData['add_processes']) && is_array($itemData['add_processes'])) {
            return array_values($itemData['add_processes']);
        }
        if (!str_contains($group, 'add') && isset($itemData['processes']) && is_array($itemData['processes'])) {
            return array_values($itemData['processes']);
        }

        return null;
    }

    /**
     * Resolve field value from nested arrays on itemData (matching rowOffset for child items)
     */
    protected function resolveNestedFieldValue(array $itemData, string $key, int $rowOffset = 0, ?string $preferredSource = null)
    {
        $cleanKey = str_contains($key, '__') ? explode('__', $key)[0] : $key;

        // If a preferred nested array source is given, check it first
        if ($preferredSource && isset($itemData[$preferredSource]) && is_array($itemData[$preferredSource])) {
            $subItems = array_values($itemData[$preferredSource]);
            if (isset($subItems[$rowOffset]) && is_array($subItems[$rowOffset])) {
                if (array_key_exists($key, $subItems[$rowOffset])) {
                    return $subItems[$rowOffset][$key];
                }
                if (array_key_exists($cleanKey, $subItems[$rowOffset])) {
                    return $subItems[$rowOffset][$cleanKey];
                }
            }
        }

        // Dynamically inspect any nested arrays present on itemData
        foreach ($itemData as $nestedKey => $nestedVal) {
            if (is_array($nestedVal) && !empty($nestedVal)) {
                $subItems = array_values($nestedVal);
                
                // 1. Try matching exact rowOffset for multi-row child items
                if (isset($subItems[$rowOffset]) && is_array($subItems[$rowOffset])) {
                    $item = $subItems[$rowOffset];
                    if (array_key_exists($key, $item)) {
                        return $item[$key];
                    }
                    if (array_key_exists($cleanKey, $item)) {
                        return $item[$cleanKey];
                    }
                }

                // 2. If rowOffset > 0 and no item exists at that offset, do not overwrite with item 0
                if ($rowOffset > 0 && count($subItems) <= $rowOffset) {
                    continue;
                }

                // 3. Fallback for rowOffset 0: check first sub-item
                if ($rowOffset === 0 && isset($subItems[0]) && is_array($subItems[0])) {
                    $item0 = $subItems[0];
                    if (array_key_exists($key, $item0)) {
                        return $item0[$key];
                    }
                    if (array_key_exists($cleanKey, $item0)) {
                        return $item0[$cleanKey];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Evaluate conditional rules for a specific row
     */
    protected function renderRowConditionalRules(
        Worksheet $sheet,
        array $conditionalRules,
        array $itemData,
        int $rowNum,
        array $context,
        int $rowOffset = 0
    ): void {
        foreach ($conditionalRules as $rule) {
            $targetSpec = $rule['target_cell'] ?? '';
            if (empty($targetSpec)) {
                continue;
            }

            preg_match('/^([A-Z]+)/i', $targetSpec, $colMatches);
            $colLetter = !empty($colMatches[1]) ? strtoupper($colMatches[1]) : '';
            if (!$colLetter) {
                continue;
            }

            $cellCoordinate = $colLetter . $rowNum;
            $format = $rule['render_type'] ?? 'default';

            $branches = [];
            // Primary branch
            $branches[] = [
                'field_key' => $rule['field_key'] ?? '',
                'operator' => $rule['operator'] ?? 'equals',
                'value' => $rule['value'] ?? '',
                'conditions' => $rule['conditions'] ?? [],
                'output_type' => $rule['output_type'] ?? 'field_value',
                'output_field_key' => $rule['output_field_key'] ?? '',
                'output_static_value' => $rule['output_static_value'] ?? '',
            ];
            // Additional ELSE IF branches
            if (!empty($rule['branches']) && is_array($rule['branches'])) {
                foreach ($rule['branches'] as $b) {
                    $branches[] = [
                        'field_key' => $b['field_key'] ?? ($rule['field_key'] ?? ''),
                        'operator' => $b['operator'] ?? 'equals',
                        'value' => $b['value'] ?? '',
                        'conditions' => $b['conditions'] ?? [],
                        'output_type' => $b['output_type'] ?? 'field_value',
                        'output_field_key' => $b['output_field_key'] ?? '',
                        'output_static_value' => $b['output_static_value'] ?? '',
                    ];
                }
            }

            $matchedBranch = null;
            $matchedSourceItem = $itemData;

            foreach ($branches as $branch) {
                $sourceItemRef = $itemData;
                if ($this->evaluateBranch($branch, $itemData, $rowOffset, $sourceItemRef)) {
                    $matchedBranch = $branch;
                    $matchedSourceItem = $sourceItemRef ?? $itemData;
                    break; // Short-circuit: first matching branch wins!
                }
            }

            if ($matchedBranch !== null) {
                $outputType = $matchedBranch['output_type'] ?? 'field_value';
                if ($outputType === 'static_value') {
                    $outVal = $matchedBranch['output_static_value'] ?? '';
                } else {
                    $outFieldKey = $matchedBranch['output_field_key'] ?? '';
                    $outVal = $matchedSourceItem[$outFieldKey] ?? ($itemData[$outFieldKey] ?? null);
                    if ($outVal === null) {
                        $outVal = $this->resolveNestedFieldValue($itemData, $outFieldKey, $rowOffset);
                    }
                }

                if ($outVal !== null) {
                    $this->fieldRenderer->applyValueAndFormat($sheet, $cellCoordinate, $outVal, $format, $rule);
                } else {
                    $sheet->setCellValue($cellCoordinate, null);
                }
            } elseif (isset($rule['else_static_value']) && $rule['else_static_value'] !== '') {
                // Apply fallback ELSE value
                $this->fieldRenderer->applyValueAndFormat($sheet, $cellCoordinate, $rule['else_static_value'], $format, $rule);
            } else {
                // No matching branch and no ELSE value -> clear cell
                $sheet->setCellValue($cellCoordinate, null);
            }
        }
    }

    /**
     * Evaluate a branch condition with support for multiple AND / OR sub-conditions
     */
    protected function evaluateBranch(array $branch, array $itemData, int $rowOffset = 0, &$matchedSourceItem = null): bool
    {
        $primaryKey = $branch['field_key'] ?? '';
        $primaryOp = $branch['operator'] ?? 'equals';
        $primaryVal = $branch['value'] ?? '';

        $primaryActual = $this->resolveConditionFieldValue($itemData, $primaryKey, $rowOffset, $matchedSourceItem);
        $result = $this->evaluateComparison($primaryActual, $primaryOp, $primaryVal);

        // Evaluate any secondary AND / OR sub-conditions
        if (!empty($branch['conditions']) && is_array($branch['conditions'])) {
            foreach ($branch['conditions'] as $sub) {
                $gate = strtoupper($sub['logic_gate'] ?? 'AND');
                $subKey = $sub['field_key'] ?? '';
                $subOp = $sub['operator'] ?? 'equals';
                $subExpected = $sub['value'] ?? '';

                $tempRef = null;
                $subActual = $this->resolveConditionFieldValue($itemData, $subKey, $rowOffset, $tempRef);
                $subMatched = $this->evaluateComparison($subActual, $subOp, $subExpected);

                if ($gate === 'OR') {
                    $result = ($result || $subMatched);
                } else {
                    $result = ($result && $subMatched);
                }
            }
        }

        return $result;
    }

    /**
     * Resolve field value from itemData or child items matching rowOffset
     */
    protected function resolveConditionFieldValue(array $itemData, string $fieldKey, int $rowOffset = 0, &$sourceItemRef = null)
    {
        foreach ($itemData as $nestedKey => $nestedVal) {
            if (is_array($nestedVal) && !empty($nestedVal)) {
                $subItems = array_values($nestedVal);
                if (isset($subItems[$rowOffset]) && is_array($subItems[$rowOffset]) && array_key_exists($fieldKey, $subItems[$rowOffset])) {
                    $sourceItemRef = $subItems[$rowOffset];
                    return $subItems[$rowOffset][$fieldKey];
                }
                if ($rowOffset === 0 && isset($subItems[0]) && is_array($subItems[0]) && array_key_exists($fieldKey, $subItems[0])) {
                    $sourceItemRef = $subItems[0];
                    return $subItems[0][$fieldKey];
                }
            }
        }

        if (array_key_exists($fieldKey, $itemData)) {
            $sourceItemRef = $itemData;
            return $itemData[$fieldKey];
        }

        return null;
    }

    /**
     * Compare two values using operator
     */
    protected function evaluateComparison($actual, string $operator, $expected): bool
    {
        $actualStr = is_scalar($actual) ? strtolower(trim((string)$actual)) : '';
        $expectedStr = is_scalar($expected) ? strtolower(trim((string)$expected)) : '';

        switch ($operator) {
            case 'equals':
            case '==':
                return $actualStr === $expectedStr;
            case 'not_equals':
            case '!=':
                return $actualStr !== $expectedStr;
            case 'contains':
                return str_contains($actualStr, $expectedStr);
            case 'starts_with':
                return str_starts_with($actualStr, $expectedStr);
            case 'ends_with':
                return str_ends_with($actualStr, $expectedStr);
            case 'greater_than':
            case '>':
                return (float)$actual > (float)$expected;
            case 'greater_equal':
            case '>=':
                return (float)$actual >= (float)$expected;
            case 'less_than':
            case '<':
                return (float)$actual < (float)$expected;
            case 'less_equal':
            case '<=':
                return (float)$actual <= (float)$expected;
            case 'is_empty':
                return $actual === null || $actual === '' || $actual === '-';
            case 'is_not_empty':
                return $actual !== null && $actual !== '' && $actual !== '-';
            default:
                return $actualStr === $expectedStr;
        }
    }

    /**
     * Render row-level formula expressions
     */
    protected function renderRowFormulas(Worksheet $sheet, array $rowFormulas, array $context): void
    {
        foreach ($rowFormulas as $colOrKey => $formulaDef) {
            $columnLetter = is_array($formulaDef) ? ($formulaDef['column'] ?? $colOrKey) : $colOrKey;
            $pattern = is_array($formulaDef) ? ($formulaDef['formula'] ?? '') : $formulaDef;

            if (empty($pattern)) {
                continue;
            }

            $cellCoordinate = $columnLetter . $context['row'];
            $compiledFormula = $this->formulaCompiler->compile($pattern, $context);

            $sheet->getCell($cellCoordinate)->setValueExplicit($compiledFormula, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA);

            if (is_array($formulaDef) && !empty($formulaDef['format'])) {
                $this->fieldRenderer->applyValueAndFormat($sheet, $cellCoordinate, null, $formulaDef['format'], $formulaDef);
            }
        }
    }

    /**
     * Render footer aggregate formulas (e.g. SUM, AVERAGE)
     */
    protected function renderFooterFormulas(
        Worksheet $sheet,
        array $footerFormulas,
        array $context,
        RowShiftTracker $shiftTracker,
        $sheetIdentifier
    ): void {
        foreach ($footerFormulas as $footerKey => $footerDef) {
            if (!is_array($footerDef)) {
                continue;
            }

            $targetCellPattern = $footerDef['target_cell'] ?? null;
            $formulaPattern = $footerDef['formula'] ?? null;

            if (!$targetCellPattern || !$formulaPattern) {
                continue;
            }

            $targetCell = $this->formulaCompiler->resolveTargetCell($targetCellPattern, $context);
            $compiledFormula = $this->formulaCompiler->compile($formulaPattern, $context);

            $sheet->getCell($targetCell)->setValueExplicit($compiledFormula, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_FORMULA);

            if (!empty($footerDef['format'])) {
                $this->fieldRenderer->applyValueAndFormat($sheet, $targetCell, null, $footerDef['format'], $footerDef);
            }
        }
    }

    /**
     * Resolve worksheet from section definition
     */
    protected function resolveWorksheet(Spreadsheet $spreadsheet, array $section, array $sheetNameMap): ?Worksheet
    {
        if (!empty($section['sheet'])) {
            $targetName = $sheetNameMap[$section['sheet']] ?? $section['sheet'];
            $sheet = $spreadsheet->getSheetByName($targetName);
            if ($sheet) return $sheet;
        }

        if (isset($section['sheet_index'])) {
            $index = (int)$section['sheet_index'];
            if ($index < $spreadsheet->getSheetCount()) {
                return $spreadsheet->getSheet($index);
            }
        }

        return $spreadsheet->getActiveSheet();
    }

    /**
     * Extract data array from payload
     */
    protected function resolveDataSource(array $section, array $payload): ?array
    {
        $dataSource = $section['data_source'] ?? $section['group'] ?? $section['key'] ?? null;

        if ($dataSource && !empty($payload[$dataSource]) && is_array($payload[$dataSource])) {
            return $payload[$dataSource];
        }

        if ($dataSource && !empty($payload['sections'][$dataSource]) && is_array($payload['sections'][$dataSource])) {
            return $payload['sections'][$dataSource];
        }

        if ($dataSource && !empty($payload['table_loops'][$dataSource]) && is_array($payload['table_loops'][$dataSource])) {
            return $payload['table_loops'][$dataSource];
        }

        // Fallback: search for first array of arrays in payload
        foreach ($payload as $k => $val) {
            if (is_array($val) && !empty($val) && is_array(reset($val))) {
                return $val;
            }
        }

        return null;
    }
}
