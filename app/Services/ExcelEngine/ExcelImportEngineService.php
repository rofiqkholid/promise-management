<?php

namespace App\Services\ExcelEngine;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelImportEngineService
{
    /**
     * Extract structured payload from uploaded .xlsx file based on mapping_config.
     * Output structure is symmetrical to $payloadData used in ExcelExportEngineService.
     * 
     * @param string $filePath Full path to uploaded customer/vendor file
     * @param array $mappingConfig Dynamic mapping JSON structure from database
     * @return array Extracted data payload
     */
    public function import(string $filePath, array $mappingConfig): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Uploaded file not found: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $defaultSheet = $spreadsheet->getActiveSheet();

        $extractedData = [
            'single_fields' => [],
            'table_loops'   => []
        ];

        // Check if Sheet Loop mode is active in mapping config or if workbook has multiple sheets
        $isSheetLoopMode = false;
        if (!empty($mappingConfig['table_loops'])) {
            foreach ($mappingConfig['table_loops'] as $lc) {
                if (!empty($lc['split_sheet_per_parent']) || (!empty($lc['sheet_loop']) && $lc['sheet_loop'] === true)) {
                    $isSheetLoopMode = true;
                    break;
                }
            }
        }

        $allSheets = $spreadsheet->getAllSheets();
        if ($isSheetLoopMode && count($allSheets) > 1) {
            // ── MULTI-SHEET PARSING MODE ──────────────────────────────────────
            $extractedData['parts'] = [];

            foreach ($allSheets as $sheetIndex => $currentSheet) {
                $sheetItem = [];

                // 1. Single Fields per Sheet
                if (!empty($mappingConfig['single_fields'])) {
                    foreach ($mappingConfig['single_fields'] as $fieldMapping) {
                        $fieldKey       = $fieldMapping['field_key'] ?? null;
                        $cellCoordinate = $fieldMapping['cell'] ?? null;

                        if ($fieldKey && $cellCoordinate) {
                            $val = $this->getCellValue($currentSheet, $cellCoordinate);
                            $sheetItem[$fieldKey] = $val;
                        }
                    }
                }

                // 2. Table Loops per Sheet
                if (!empty($mappingConfig['table_loops'])) {
                    foreach ($mappingConfig['table_loops'] as $loopConfig) {
                        $group         = $loopConfig['group'] ?? 'processes';
                        $startRow      = (int)($loopConfig['start_row'] ?? 0);
                        $stopCondition = $loopConfig['stop_condition'] ?? [];
                        $columns       = $loopConfig['columns'] ?? [];

                        if (!$startRow || empty($columns)) continue;

                        $sheetItem[$group] = [];
                        $currentRow = $startRow;
                        $maxRowGuard = $startRow + 500;

                        while ($currentRow < $maxRowGuard) {
                            if ($this->shouldStopLoop($currentSheet, $currentRow, $stopCondition)) break;

                            $rowPayload = [];
                            $hasAnyData = false;

                            foreach ($columns as $fieldKey => $columnLetter) {
                                $cellCoordinate = $columnLetter . $currentRow;
                                $cellValue = $this->getCellValue($currentSheet, $cellCoordinate);
                                if ($cellValue !== null && $cellValue !== '') {
                                    $hasAnyData = true;
                                }
                                $rowPayload[$fieldKey] = $cellValue;
                            }

                            if (!$hasAnyData) break;
                            $sheetItem[$group][] = $rowPayload;
                            $currentRow++;
                        }
                    }
                }

                // If sheet contained any data, append to parts array
                if (!empty($sheetItem)) {
                    $extractedData['parts'][] = $sheetItem;
                }
            }

            // Expose primary array at root
            $extractedData['table_loops']['parts'] = $extractedData['parts'];
            return $extractedData;
        }

        // ── 1. PARSE SINGLE FIELDS (Header / Master Data) ──────────────────────
        if (!empty($mappingConfig['single_fields'])) {
            foreach ($mappingConfig['single_fields'] as $fieldMapping) {
                $fieldKey       = $fieldMapping['field_key'] ?? null;
                $cellCoordinate = $fieldMapping['cell'] ?? null;
                $sheetIdx       = $fieldMapping['sheet_index'] ?? 0;
                $targetSheet    = $spreadsheet->getSheet($sheetIdx) ?? $defaultSheet;

                if ($fieldKey && $cellCoordinate) {
                    $val = $this->getCellValue($targetSheet, $cellCoordinate);
                    $extractedData['single_fields'][$fieldKey] = $val;
                    // Also expose at root level for seamless controller access
                    $extractedData[$fieldKey] = $val;
                }
            }
        }

        // ── 2. PARSE TABLE LOOPS (Flat Tables & Nested Parent-Child Blocks) ────
        if (!empty($mappingConfig['table_loops'])) {
            foreach ($mappingConfig['table_loops'] as $loopConfig) {
                $group         = $loopConfig['group'] ?? null;
                $loopMode      = $loopConfig['loop_mode'] ?? 'flat';
                $startRow      = (int)($loopConfig['start_row'] ?? 0);
                $sheetIdx      = $loopConfig['sheet_index'] ?? 0;
                $stopCondition = $loopConfig['stop_condition'] ?? [];
                $columns       = $loopConfig['columns'] ?? [];
                $targetSheet   = $spreadsheet->getSheet($sheetIdx) ?? $defaultSheet;

                if (!$group || !$startRow || empty($columns)) {
                    continue;
                }

                $extractedData['table_loops'][$group] = [];
                $loopRecords = [];

                $currentRow = $startRow;
                $maxRowGuard = $startRow + 1000; // Safety threshold
                $currentParentItem = null;

                // Identify parent-level fields vs child-level fields in columns
                $parentFields = [];
                $childFields  = [];
                foreach (array_keys($columns) as $fKey) {
                    if ($this->isParentFieldKey($fKey)) {
                        $parentFields[] = $fKey;
                    } else {
                        $childFields[] = $fKey;
                    }
                }

                while ($currentRow < $maxRowGuard) {
                    if ($this->shouldStopLoop($targetSheet, $currentRow, $stopCondition)) {
                        break;
                    }

                    $rowPayload = [];
                    $hasAnyData = false;

                    foreach ($columns as $fieldKey => $columnLetter) {
                        $cellCoordinate = $columnLetter . $currentRow;
                        $cellValue = $this->getCellValue($targetSheet, $cellCoordinate);

                        if ($cellValue !== null && $cellValue !== '') {
                            $hasAnyData = true;
                        }
                        $rowPayload[$fieldKey] = $cellValue;
                    }

                    if (!$hasAnyData) {
                        break;
                    }

                    if ($loopMode === 'nested_block') {
                        // Check if current row contains a new Parent Identifier value (e.g. part_no)
                        $hasParentData = false;
                        foreach ($parentFields as $pKey) {
                            if (!empty(trim((string)($rowPayload[$pKey] ?? '')))) {
                                $hasParentData = true;
                                break;
                            }
                        }

                        if ($hasParentData || $currentParentItem === null) {
                            if ($currentParentItem !== null) {
                                $loopRecords[] = $currentParentItem;
                            }

                            // Start a new Parent Item block
                            $currentParentItem = $rowPayload;
                            $currentParentItem['processes'] = [];
                            $currentParentItem['children']  = [];

                            // Extract child process fields if present on this first row
                            $childRow = array_intersect_key($rowPayload, array_flip($childFields));
                            if ($this->hasAnyNonEmptyValue($childRow)) {
                                $currentParentItem['processes'][] = $childRow;
                                $currentParentItem['children'][]  = $childRow;
                            }
                        } else {
                            // Append to existing Parent Item's processes/children
                            $childRow = array_intersect_key($rowPayload, array_flip($childFields));
                            // Also merge any extra parent fields if mapped on multi-row parent template
                            foreach ($parentFields as $pKey) {
                                if (!empty($rowPayload[$pKey]) && empty($currentParentItem[$pKey])) {
                                    $currentParentItem[$pKey] = $rowPayload[$pKey];
                                }
                            }

                            if ($this->hasAnyNonEmptyValue($childRow)) {
                                $currentParentItem['processes'][] = $childRow;
                                $currentParentItem['children'][]  = $childRow;
                            }
                        }
                    } else {
                        // Flat Single-Row Table
                        $loopRecords[] = $rowPayload;
                    }

                    $currentRow++;
                }

                if ($loopMode === 'nested_block' && $currentParentItem !== null) {
                    $loopRecords[] = $currentParentItem;
                }

                $extractedData['table_loops'][$group] = $loopRecords;
                // Expose group directly at root level (e.g. $extractedData['items'])
                $extractedData[$group] = $loopRecords;
            }
        }

        return $extractedData;
    }

    /**
     * Determine if a field key represents a Parent-level attribute
     */
    private function isParentFieldKey(string $fieldKey): bool
    {
        $parentKeywords = [
            'part_no', 'part_number', 'part_name', 'mat_spec', 'material_spec',
            'mat_thick', 'thickness', 'pcs_month', 'qty_unit', 'weight', 'width',
            'length', 'height', 'header', 'parent', 'customer', 'model'
        ];

        foreach ($parentKeywords as $kw) {
            if (stripos($fieldKey, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper to check if array has any non-empty value
     */
    private function hasAnyNonEmptyValue(array $data): bool
    {
        foreach ($data as $val) {
            if ($val !== null && trim((string)$val) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Cell Value handling Merged Cells safely
     */
    private function getCellValue(Worksheet $sheet, string $cellCoordinate)
    {
        $cell = $sheet->getCell($cellCoordinate);
        
        foreach ($sheet->getMergeCells() as $mergeRange) {
            if ($cell->isInRange($mergeRange)) {
                $topRow = explode(':', $mergeRange)[0];
                return $sheet->getCell($topRow)->getFormattedValue();
            }
        }

        return $cell->getFormattedValue();
    }

    /**
     * Check if dynamic table reader should terminate at currentRow
     */
    private function shouldStopLoop(Worksheet $sheet, int $currentRow, array $stopCondition): bool
    {
        if (empty($stopCondition['type'])) {
            return false;
        }

        $type = $stopCondition['type'];
        $column = $stopCondition['column'] ?? 'B';
        $targetValue = $stopCondition['value'] ?? '';

        $cellCoordinate = $column . $currentRow;
        $currentVal = (string)$this->getCellValue($sheet, $cellCoordinate);

        switch ($type) {
            case 'cell_value_contains':
                return stripos($currentVal, (string)$targetValue) !== false;

            case 'cell_value_equals':
                return trim(strtolower($currentVal)) === trim(strtolower((string)$targetValue));

            case 'is_empty':
                return trim($currentVal) === '';

            default:
                return false;
        }
    }
}
