<?php

namespace App\Services\ExcelEngine;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelExportEngineService
{
    /**
     * Inject data array into master .xlsx template and return file output path or stream
     *
     * @param string $templatePath Full path to master .xlsx template
     * @param array $mappingConfig Dynamic mapping JSON structure from database
     * @param array $payloadData Data payload (contains single_fields and table_loops data)
     * @param string|null $outputPath Optional destination save path
     * @return string Path to generated file
     */
    public function export(string $templatePath, array $mappingConfig, array $payloadData, ?string $outputPath = null): string
    {
        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Master template file not found: {$templatePath}");
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Inject Single Fields (Header / Master Data)
        if (!empty($mappingConfig['single_fields'])) {
            foreach ($mappingConfig['single_fields'] as $fieldMapping) {
                $fieldKey = $fieldMapping['field_key'] ?? null;
                $cellCoordinate = $fieldMapping['cell'] ?? null;
                $sheetIdx = $fieldMapping['sheet_index'] ?? 0;
                $targetSheet = $spreadsheet->getSheet($sheetIdx) ?? $spreadsheet->getActiveSheet();

                if (($fieldMapping['value_type'] ?? '') === 'static' || !empty($fieldMapping['static_value'])) {
                    $staticVal = $fieldMapping['static_value'] ?? '';
                    $this->applyCellFormatAndValue($targetSheet, $cellCoordinate, $staticVal, 'default');
                } else if ($fieldKey && $cellCoordinate) {
                    $resolvedVal = $this->resolveSingleFieldValue($fieldKey, $payloadData);
                    if ($resolvedVal !== null) {
                        $rType = $fieldMapping['render_type'] ?? 'default';
                        $imgSize = $fieldMapping['image_size'] ?? '100x40';
                        $this->applyCellFormatAndValue($targetSheet, $cellCoordinate, $resolvedVal, $rType, $imgSize);
                    }
                }
            }
        }

        // 2. Inject Table Loops (Dynamic Multiline Tables & Nested Block Repeaters)
        if (!empty($mappingConfig['table_loops'])) {

            // Collect all mapped loop columns across table_loops configs
            $allMappedLoopColumns = [];
            foreach ($mappingConfig['table_loops'] as $lc) {
                foreach ($lc['columns'] ?? [] as $colLetter) {
                    $allMappedLoopColumns[$colLetter] = true;
                }
            }

            // ── STEP A: Resolve source items & base start row ─────────────
            $sourceItems = null;
            $baseStartRow = null;

            foreach ($mappingConfig['table_loops'] as $lc) {
                $g = $lc['group'] ?? null;
                $items = null;
                if ($g && !empty($payloadData[$g]) && is_array($payloadData[$g])) {
                    $items = $payloadData[$g];
                } else {
                    foreach ($payloadData as $val) {
                        if (is_array($val) && !empty($val) && is_array(reset($val))) {
                            $items = $val;
                            break;
                        }
                    }
                }

                if (!empty($items) && is_array($items)) {
                    $sourceItems = $items;
                    $baseStartRow = (int)($lc['start_row'] ?? 0);
                    $targetSheetIdx = $lc['sheet_index'] ?? 0;
                    $sheet = $spreadsheet->getSheet($targetSheetIdx) ?? $spreadsheet->getActiveSheet();
                    break;
                }
            }

            if (empty($sourceItems) || !$baseStartRow) {
                goto writeFile;
            }

            // ── STEP B: Pre-classify each loop config as "child" or "parent" ──────────
            $referenceParent = null;
            $referenceChildren = [];
            foreach ($sourceItems as $parentData) {
                foreach ($parentData as $val) {
                    if (is_array($val) && count($val) > 0) {
                        $referenceParent = $parentData;
                        $referenceChildren = $val;
                        break 2;
                    }
                }
            }

            $loopClassifications = [];
            foreach ($mappingConfig['table_loops'] as $idx => $loopConfig) {
                $columns = $loopConfig['columns'] ?? [];
                $isChildLoop = false;

                if (!empty($referenceChildren)) {
                    $refChild = $referenceChildren[0];
                    foreach (array_keys($columns) as $fieldKey) {
                        if (array_key_exists($fieldKey, $refChild) && !array_key_exists($fieldKey, $referenceParent ?? [])) {
                            $isChildLoop = true;
                            break;
                        }
                    }
                }

                $loopClassifications[$idx] = $isChildLoop;
            }

            // ── STEP C: Compute row stride per parent ────────────
            $startRows = array_map(fn($l) => (int)($l['start_row'] ?? 0), $mappingConfig['table_loops']);
            $minStartRow = !empty($startRows) ? min($startRows) : 0;
            $maxStartRow = !empty($startRows) ? max($startRows) : 0;
            $templateBlockHeight = max(1, ($maxStartRow - $minStartRow + 1));

            $rowStrides = [];
            foreach ($sourceItems as $parentIdx => $parentData) {
                $childCount = $templateBlockHeight;
                foreach ($parentData as $val) {
                    if (is_array($val) && count($val) > 0) {
                        $childCount = max($templateBlockHeight, count($val));
                        break;
                    }
                }
                $rowStrides[$parentIdx] = $childCount;
            }

            // ── STEP D: Master pass ────────────
            $primaryLoopConfig = $mappingConfig['table_loops'][0] ?? [];
            $direction = $primaryLoopConfig['direction'] ?? 'down';
            $insertBehavior = $primaryLoopConfig['insert_behavior'] ?? 'insert_duplicate';

            if ($direction === 'right') {
                // ── HORIZONTAL LOOP (RIGHT / COLUMNS) ──────────────────────────
                foreach ($sourceItems as $itemIdx => $itemData) {
                    foreach ($mappingConfig['table_loops'] as $loopConfig) {
                        $columns = $loopConfig['columns'] ?? [];
                        if (empty($columns)) continue;

                        $baseColumnLetter = reset($columns);
                        $baseColumnNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($baseColumnLetter);
                        $targetColNum = $baseColumnNum + $itemIdx;
                        $targetColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($targetColNum);
                        $renderTypes = $loopConfig['render_types'] ?? [];

                        foreach ($columns as $fieldKey => $colLet) {
                            $cellCoordinate = $targetColLetter . $baseStartRow;

                            if ($insertBehavior === 'insert_duplicate' && $itemIdx > 0) {
                                if (array_key_first($columns) === $fieldKey) {
                                    $sheet->insertNewColumnBefore($targetColLetter, 1);
                                    $sheet->duplicateStyle($sheet->getStyle($baseColumnLetter . $baseStartRow), $cellCoordinate);
                                }
                            }

                            if (array_key_exists($fieldKey, $itemData)) {
                                $rType = $renderTypes[$fieldKey] ?? 'default';
                                $this->applyCellFormatAndValue($sheet, $cellCoordinate, $itemData[$fieldKey], $rType);
                            }
                        }
                    }
                }
            } else {
                // ── VERTICAL LOOP (DOWN / ROWS) ─────────────────────────────────
                $parentMappedColumns = [];
                foreach ($mappingConfig['table_loops'] as $idx => $loopConfig) {
                    if (empty($loopClassifications[$idx])) {
                        foreach ($loopConfig['columns'] ?? [] as $colLetter) {
                            $parentMappedColumns[$colLetter] = true;
                        }
                    }
                }

                $isSheetLoopMode = false;
                $sheetNamingField = null;
                $targetSheetIdx = $primaryLoopConfig['sheet_index'] ?? 0;
                foreach ($mappingConfig['table_loops'] as $lc) {
                    $lcSheetIdx = $lc['sheet_index'] ?? 0;
                    if ($lcSheetIdx == $targetSheetIdx && (!empty($lc['split_sheet_per_parent']) || (!empty($lc['sheet_loop']) && $lc['sheet_loop'] === true))) {
                        $isSheetLoopMode = true;
                        $sheetNamingField = $lc['sheet_name_field'] ?? null;
                        break;
                    }
                }

                $evaluateFormulaVal = function($rawVal, $targetRow) {
                    if (empty($rawVal)) return '';
                    $evalVal = (string)$rawVal;
                    if (str_contains($evalVal, '{row')) {
                        $evalVal = preg_replace_callback('/\{row(?:([+-]\d+))?\}/i', function($m) use ($targetRow) {
                            $offset = isset($m[1]) ? (int)$m[1] : 0;
                            $calcRow = $targetRow + $offset;
                            return (string)max(1, $calcRow);
                        }, $evalVal);
                    }
                    return $evalVal;
                };

                if ($isSheetLoopMode) {
                    $masterSheet = $spreadsheet->getSheet($targetSheetIdx) ?? $spreadsheet->getActiveSheet();
                    $templateCleanSheet = clone $masterSheet;

                    foreach ($sourceItems as $parentIdx => $parentData) {
                        $sheetTitle = null;
                        if ($sheetNamingField && !empty($parentData[$sheetNamingField])) {
                            $sheetTitle = (string)$parentData[$sheetNamingField];
                        } elseif (!empty($parentData['part_no'])) {
                            $sheetTitle = (string)$parentData['part_no'];
                        } elseif (!empty($parentData['part_name'])) {
                            $sheetTitle = (string)$parentData['part_name'];
                        } else {
                            $sheetTitle = 'Item ' . ($parentIdx + 1);
                        }
                        $sheetTitle = preg_replace('~[\\\\/*?:\[\]]~', '', $sheetTitle);
                        $sheetTitle = mb_substr(trim($sheetTitle), 0, 30);
                        if (empty($sheetTitle)) $sheetTitle = 'Sheet ' . ($parentIdx + 1);

                        $existingTitles = array_map(fn($s) => $s->getTitle(), $spreadsheet->getAllSheets());
                        if (in_array($sheetTitle, $existingTitles)) {
                            $sheetTitle = mb_substr($sheetTitle, 0, 25) . '_' . ($parentIdx + 1);
                        }

                        if ($parentIdx === 0) {
                            $currentSheet = $masterSheet;
                            $currentSheet->setTitle($sheetTitle);
                        } else {
                            $currentSheet = clone $templateCleanSheet;
                            $currentSheet->setTitle($sheetTitle);
                            $spreadsheet->addSheet($currentSheet);
                        }

                        if (!empty($mappingConfig['single_fields'])) {
                            foreach ($mappingConfig['single_fields'] as $fieldMapping) {
                                $fieldKey = $fieldMapping['field_key'] ?? null;
                                $cellCoordinate = $fieldMapping['cell'] ?? null;

                                if (($fieldMapping['value_type'] ?? '') === 'static' || !empty($fieldMapping['static_value'])) {
                                    $staticVal = $fieldMapping['static_value'] ?? '';
                                    $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, $staticVal, 'default');
                                } else if ($fieldKey && $cellCoordinate) {
                                    $resolvedVal = $this->resolveSingleFieldValue($fieldKey, $payloadData, $parentData);
                                    if ($resolvedVal !== null) {
                                        $rType = $fieldMapping['render_type'] ?? 'default';
                                        $imgSize = $fieldMapping['image_size'] ?? '100x40';
                                        $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, $resolvedVal, $rType, $imgSize);
                                    }
                                }
                            }
                        }

                        $currentRow = $baseStartRow;
                        $children = [];
                        foreach ($parentData as $val) {
                            if (is_array($val)) {
                                $children = $val;
                                break;
                            }
                        }

                        $childCount = max(1, count($children));

                        if ($insertBehavior === 'insert_duplicate' && $childCount > 1) {
                            $currentSheet->insertNewRowBefore($currentRow + 1, $childCount - 1);
                            for ($k = 1; $k < $childCount; $k++) {
                                $targetR = $currentRow + $k;
                                $sourceRowForTarget = $baseStartRow + (($targetR - $baseStartRow) % $templateBlockHeight);
                                $this->copyRowStylesAndFormulas($currentSheet, $sourceRowForTarget, $targetR, $parentMappedColumns, true, $allMappedLoopColumns);
                            }
                        }

                        foreach ($mappingConfig['table_loops'] as $idx => $loopConfig) {
                            $columns = $loopConfig['columns'] ?? [];
                            if (empty($columns)) continue;

                            $isChildLoop = $loopClassifications[$idx];
                            $staticValues = $loopConfig['static_values'] ?? [];
                            $renderTypes = $loopConfig['render_types'] ?? [];

                            if ($isChildLoop) {
                                for ($i = 0; $i < $childCount; $i++) {
                                    $blockRow = $currentRow + $i;
                                    $childData = $children[$i] ?? [];

                                    foreach ($columns as $fieldKey => $columnLetter) {
                                        $cellCoordinate = $columnLetter . $blockRow;
                                        $rType = $renderTypes[$fieldKey] ?? 'default';
                                        if (isset($staticValues[$fieldKey])) {
                                            $evalVal = $evaluateFormulaVal($staticValues[$fieldKey], $blockRow);
                                            $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, $evalVal, 'default');
                                        } else if ($fieldKey === 'auto_number' || $fieldKey === 'no' || $fieldKey === 'row_index') {
                                            $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, ($i + 1), $rType);
                                        } else if (array_key_exists($fieldKey, $childData)) {
                                            $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, $childData[$fieldKey], $rType);
                                        }
                                    }
                                }
                            } else {
                                $loopStartRow = (int)($loopConfig['start_row'] ?? $baseStartRow);
                                $loopRowOffset = max(0, $loopStartRow - $baseStartRow);
                                $targetParentRow = $currentRow + $loopRowOffset;

                                foreach ($columns as $fieldKey => $columnLetter) {
                                    $cellCoordinate = $columnLetter . $targetParentRow;
                                    $rType = $renderTypes[$fieldKey] ?? 'default';
                                    if (isset($staticValues[$fieldKey])) {
                                        $evalVal = $evaluateFormulaVal($staticValues[$fieldKey], $targetParentRow);
                                        $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, $evalVal, 'default');
                                    } else if ($fieldKey === 'auto_number' || $fieldKey === 'no' || $fieldKey === 'row_index') {
                                        $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, ($parentIdx + 1), $rType);
                                    } else if (array_key_exists($fieldKey, $parentData)) {
                                        $this->applyCellFormatAndValue($currentSheet, $cellCoordinate, $parentData[$fieldKey], $rType);
                                    }
                                }
                            }
                        }
                    }

                } else {
                    $currentRow = $baseStartRow;
                    $blankRowsAfter = 0;
                    foreach ($mappingConfig['table_loops'] as $lc) {
                        if (!empty($lc['blank_rows_after']) && (int)$lc['blank_rows_after'] > 0) {
                            $blankRowsAfter = (int)$lc['blank_rows_after'];
                            break;
                        }
                    }

                    foreach ($sourceItems as $parentIdx => $parentData) {
                        $childCount = $rowStrides[$parentIdx];
                        $children = [];
                        foreach ($parentData as $val) {
                            if (is_array($val)) {
                                $children = $val;
                                break;
                            }
                        }

                        if ($insertBehavior === 'insert_duplicate') {
                            if ($parentIdx > 0) {
                                $sheet->insertNewRowBefore($currentRow, 1);
                                $sourceRowForTarget = $baseStartRow + (($currentRow - $baseStartRow) % $templateBlockHeight);
                                $this->copyRowStylesAndFormulas($sheet, $sourceRowForTarget, $currentRow, $parentMappedColumns, false, $allMappedLoopColumns);
                            }

                            if ($childCount > 1) {
                                $sheet->insertNewRowBefore($currentRow + 1, $childCount - 1);
                                for ($k = 1; $k < $childCount; $k++) {
                                    $targetR = $currentRow + $k;
                                    $sourceRowForTarget = $baseStartRow + (($targetR - $baseStartRow) % $templateBlockHeight);
                                    $this->copyRowStylesAndFormulas($sheet, $sourceRowForTarget, $targetR, $parentMappedColumns, true, $allMappedLoopColumns);
                                }
                            }
                        }

                        foreach ($mappingConfig['table_loops'] as $idx => $loopConfig) {
                            $columns = $loopConfig['columns'] ?? [];
                            if (empty($columns)) continue;

                            $isChildLoop = $loopClassifications[$idx];
                            $staticValues = $loopConfig['static_values'] ?? [];
                            $renderTypes = $loopConfig['render_types'] ?? [];

                            if ($isChildLoop) {
                                for ($i = 0; $i < $childCount; $i++) {
                                    $blockRow = $currentRow + $i;
                                    $childData = $children[$i] ?? [];

                                    foreach ($columns as $fieldKey => $columnLetter) {
                                        $cellCoordinate = $columnLetter . $blockRow;
                                        $rType = $renderTypes[$fieldKey] ?? 'default';
                                        if (isset($staticValues[$fieldKey])) {
                                            $evalVal = $evaluateFormulaVal($staticValues[$fieldKey], $blockRow);
                                            $this->applyCellFormatAndValue($sheet, $cellCoordinate, $evalVal, 'default');
                                        } else if ($fieldKey === 'auto_number' || $fieldKey === 'no' || $fieldKey === 'row_index') {
                                            $this->applyCellFormatAndValue($sheet, $cellCoordinate, ($i + 1), $rType);
                                        } else if (array_key_exists($fieldKey, $childData)) {
                                            $this->applyCellFormatAndValue($sheet, $cellCoordinate, $childData[$fieldKey], $rType);
                                        }
                                    }
                                }
                            } else {
                                $loopStartRow = (int)($loopConfig['start_row'] ?? $baseStartRow);
                                $loopRowOffset = max(0, $loopStartRow - $baseStartRow);
                                $targetParentRow = $currentRow + $loopRowOffset;

                                foreach ($columns as $fieldKey => $columnLetter) {
                                    $cellCoordinate = $columnLetter . $targetParentRow;
                                    $rType = $renderTypes[$fieldKey] ?? 'default';
                                    if (isset($staticValues[$fieldKey])) {
                                        $evalVal = $evaluateFormulaVal($staticValues[$fieldKey], $targetParentRow);
                                        $this->applyCellFormatAndValue($sheet, $cellCoordinate, $evalVal, 'default');
                                    } else if ($fieldKey === 'auto_number' || $fieldKey === 'no' || $fieldKey === 'row_index') {
                                        $this->applyCellFormatAndValue($sheet, $cellCoordinate, ($parentIdx + 1), $rType);
                                    } else if (array_key_exists($fieldKey, $parentData)) {
                                        $this->applyCellFormatAndValue($sheet, $cellCoordinate, $parentData[$fieldKey], $rType);
                                    }
                                }
                            }
                        }

                        if ($blankRowsAfter > 0 && $parentIdx < count($sourceItems) - 1) {
                            if ($insertBehavior === 'insert_duplicate') {
                                $sheet->insertNewRowBefore($currentRow + $childCount, $blankRowsAfter);
                            }
                            $currentRow += $blankRowsAfter;
                        }

                        $currentRow += $childCount;
                    }
                }
            }
        }

        // 3. Process Conditional Cell Rules (IF-THEN dynamic rules)
        if (!empty($mappingConfig['conditional_rules'])) {
            $this->processConditionalRules($spreadsheet, $mappingConfig['conditional_rules'], $payloadData);
        }

        writeFile:
        if (!$outputPath) {
            $tempDir = storage_path('app/exports');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $outputPath = $tempDir . '/exported_' . time() . '_' . uniqid() . '.xlsx';
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Copy cell formatting, formulas, and static values from source template row to newly inserted target row.
     */
    private function copyRowStylesAndFormulas($sheet, int $sourceRow, int $targetRow, array $parentMappedColumns = [], bool $isExtraChildRow = false, array $allMappedLoopColumns = []): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        if ($sheet->getRowDimension($sourceRow)->getRowHeight() > 0) {
            $sheet->getRowDimension($targetRow)->setRowHeight($sheet->getRowDimension($sourceRow)->getRowHeight());
        }

        for ($c = 1; $c <= $highestColumnIndex; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sourceCellCoord = $colLetter . $sourceRow;
            $targetCellCoord = $colLetter . $targetRow;

            $sheet->duplicateStyle($sheet->getStyle($sourceCellCoord), $targetCellCoord);

            $sourceCell = $sheet->getCell($sourceCellCoord);
            if ($isExtraChildRow && isset($parentMappedColumns[$colLetter])) {
                $sheet->setCellValue($targetCellCoord, null);
            } else if (isset($allMappedLoopColumns[$colLetter]) && !$sourceCell->isFormula()) {
                $sheet->setCellValue($targetCellCoord, null);
            } else if ($sourceCell->isFormula()) {
                $formula = $sourceCell->getValue();
                $rowOffset = $targetRow - $sourceRow;
                $updatedFormula = preg_replace_callback('/(\$?)([A-Z]{1,3})(\$?)(\d+)\b/i', function($matches) use ($sourceRow, $targetRow, $rowOffset) {
                    $colPrefix = $matches[1];
                    $colLetter = $matches[2];
                    $rowLock   = $matches[3];
                    $rowNum    = (int)$matches[4];

                    if ($rowLock === '$') {
                        return $matches[0];
                    }
                    if ($rowNum === $sourceRow) {
                        return $colPrefix . $colLetter . $targetRow;
                    }
                    return $colPrefix . $colLetter . max(1, $rowNum + $rowOffset);
                }, $formula);

                $sheet->setCellValue($targetCellCoord, $updatedFormula);
            } else if ($sourceCell->getValue() !== null && $sourceCell->getValue() !== '') {
                $sheet->setCellValue($targetCellCoord, $sourceCell->getValue());
            }
        }
    }

    /**
     * Set cell value and apply appropriate PhpSpreadsheet number format or image drawing based on render_type.
     */
    private function applyCellFormatAndValue($sheet, string $cellCoord, $val, ?string $renderType = 'default', ?string $imageSize = null): void
    {
        if ($val === null || $val === '') {
            $sheet->setCellValue($cellCoord, null);
            return;
        }

        $renderType = strtolower(trim((string)$renderType));
        if (empty($renderType)) {
            $renderType = 'default';
        }

        // Evaluate formula values starting with '='
        if (is_string($val) && str_starts_with($val, '=')) {
            $sheet->setCellValue($cellCoord, $val);
            switch ($renderType) {
                case 'number':
                case 'numeric':
                case 'decimal':
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('#,##0.00');
                    break;
                case 'currency':
                case 'rupiah':
                case 'rp':
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('"Rp "#,##0');
                    break;
                case 'percentage':
                case 'percent':
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('0.00%');
                    break;
                case 'date':
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
                    break;
                case 'long_date':
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('dd mmmm yyyy');
                    break;
                case 'text':
                case 'general':
                    $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL);
                    break;
                case 'default':
                default:
                    // Inherit template default format: DO NOT touch or override number format!
                    break;
            }
            return;
        }

        switch ($renderType) {
            case 'number':
            case 'numeric':
            case 'decimal':
                $numericVal = is_numeric($val) ? (float)$val : (float)preg_replace('/[^0-9.-]/', '', (string)$val);
                $sheet->setCellValueExplicit($cellCoord, $numericVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $formatCode = (floor($numericVal) == $numericVal) ? '#,##0' : '#,##0.00';
                $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode($formatCode);
                break;

            case 'currency':
            case 'rupiah':
            case 'rp':
                $numericVal = is_numeric($val) ? (float)$val : (float)preg_replace('/[^0-9.-]/', '', (string)$val);
                $sheet->setCellValueExplicit($cellCoord, $numericVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('"Rp "#,##0');
                break;

            case 'percentage':
            case 'percent':
                $numericVal = is_numeric($val) ? (float)$val : (float)preg_replace('/[^0-9.-]/', '', (string)$val);
                if ($numericVal > 1) {
                    $numericVal = $numericVal / 100;
                }
                $sheet->setCellValueExplicit($cellCoord, $numericVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('0.00%');
                break;

            case 'date':
                if (is_numeric($val)) {
                    $sheet->setCellValueExplicit($cellCoord, (float)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                } else {
                    $timestamp = strtotime((string)$val);
                    if ($timestamp !== false) {
                        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::timestampToExcel($timestamp);
                        $sheet->setCellValueExplicit($cellCoord, $excelDate, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
                $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
                break;

            case 'long_date':
                if (is_numeric($val)) {
                    $sheet->setCellValueExplicit($cellCoord, (float)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                } else {
                    $timestamp = strtotime((string)$val);
                    if ($timestamp !== false) {
                        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::timestampToExcel($timestamp);
                        $sheet->setCellValueExplicit($cellCoord, $excelDate, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    } else {
                        $sheet->setCellValueExplicit($cellCoord, (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
                $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode('dd mmmm yyyy');
                break;

            case 'image':
            case 'qr':
                $imgPath = (string)$val;
                if ($renderType === 'qr') {
                    $qrText = urlencode($imgPath);
                    $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={$qrText}";
                    $tempQrFile = sys_get_temp_dir() . '/qr_' . md5($imgPath) . '.png';
                    if (!file_exists($tempQrFile)) {
                        @file_put_contents($tempQrFile, @file_get_contents($qrApiUrl));
                    }
                    if (file_exists($tempQrFile)) {
                        $imgPath = $tempQrFile;
                    }
                }

                if (!empty($imgPath) && (file_exists($imgPath) || str_starts_with($imgPath, 'http'))) {
                    if (str_starts_with($imgPath, 'http')) {
                        $tempImgFile = sys_get_temp_dir() . '/img_' . md5($imgPath) . '.png';
                        if (!file_exists($tempImgFile)) {
                            @file_put_contents($tempImgFile, @file_get_contents($imgPath));
                        }
                        if (file_exists($tempImgFile)) {
                            $imgPath = $tempImgFile;
                        }
                    }

                    if (file_exists($imgPath)) {
                        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                        $drawing->setPath($imgPath);
                        $drawing->setCoordinates($cellCoord);

                        // Parse target image dimensions (width x height px)
                        $width = 100;
                        $height = 40;
                        if (!empty($imageSize) && str_contains(strtolower($imageSize), 'x')) {
                            [$w, $h] = explode('x', strtolower($imageSize));
                            if (is_numeric($w) && (int)$w > 0) $width = (int)$w;
                            if (is_numeric($h) && (int)$h > 0) $height = (int)$h;
                        } elseif ($renderType === 'qr') {
                            $width = 80;
                            $height = 80;
                        }

                        $drawing->setWidth($width);
                        $drawing->setHeight($height);

                        // Set cell alignment to Center
                        $sheet->getStyle($cellCoord)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle($cellCoord)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                        // Auto-adjust Row Height (convert px height to pt points + padding)
                        $rowNum = (int)preg_replace('/[^0-9]/', '', $cellCoord);
                        $colLetter = preg_replace('/[0-9]/', '', $cellCoord);

                        $requiredRowHeightPt = ($height * 0.75) + 8;
                        $currentRowHeightPt = $sheet->getRowDimension($rowNum)->getRowHeight();
                        if ($currentRowHeightPt < $requiredRowHeightPt) {
                            $sheet->getRowDimension($rowNum)->setRowHeight($requiredRowHeightPt);
                            $currentRowHeightPt = $requiredRowHeightPt;
                        }

                        // Auto-adjust Column Width (convert px width to char width + padding)
                        $requiredColWidthChar = ($width / 7.5) + 3;
                        $currentColWidthChar = $sheet->getColumnDimension($colLetter)->getWidth();
                        if ($currentColWidthChar < $requiredColWidthChar) {
                            $sheet->getColumnDimension($colLetter)->setWidth($requiredColWidthChar);
                            $currentColWidthChar = $requiredColWidthChar;
                        }

                        // Center Drawing image inside cell bounds via OffsetX and OffsetY
                        $cellHeightPx = $currentRowHeightPt / 0.75;
                        $offsetY = max(2, (int)(($cellHeightPx - $height) / 2));
                        $drawing->setOffsetY($offsetY);

                        $cellWidthPx = ($currentColWidthChar > 0 ? $currentColWidthChar : 12) * 7.5;
                        $offsetX = max(2, (int)(($cellWidthPx - $width) / 2));
                        $drawing->setOffsetX($offsetX);

                        $drawing->setWorksheet($sheet);
                        break;
                    }
                }
                $sheet->setCellValueExplicit($cellCoord, (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                break;

            case 'text':
            case 'general':
                // General format without special numeric code (overwrite template format)
                if (is_numeric($val) && (!str_starts_with((string)$val, '0') || (string)$val === '0')) {
                    $sheet->setCellValueExplicit($cellCoord, $val + 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueExplicit($cellCoord, (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $sheet->getStyle($cellCoord)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL);
                break;

            case 'default':
            default:
                // Format Bawaan: Inherit existing cell format and DataType from Excel template!
                // Do NOT perform $val + 0 type casting! If template cell was Text (TYPE_STRING), keep as Text.
                $existingDataType = $sheet->getCell($cellCoord)->getDataType();
                if ($existingDataType === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING) {
                    $sheet->setCellValueExplicit($cellCoord, (string)$val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cellCoord, $val);
                }
                break;
        }
    }

    /**
     * Resolve a single field value from parentData, payloadData root, aliases, or first item of sub-arrays.
     */
    private function resolveSingleFieldValue(string $fieldKey, array $payloadData, ?array $parentData = null)
    {
        // 1. Check parentData if provided
        if ($parentData && is_array($parentData)) {
            if (array_key_exists($fieldKey, $parentData) && $parentData[$fieldKey] !== null) {
                return $parentData[$fieldKey];
            }
        }

        // 2. Direct root key in payloadData
        if (array_key_exists($fieldKey, $payloadData) && $payloadData[$fieldKey] !== null) {
            return $payloadData[$fieldKey];
        }

        // 3. Known Aliases
        $aliases = [
            'ebd_part_no' => ['part_number', 'part_no', 'part_num'],
            'part_number' => ['ebd_part_no', 'part_no'],
            'ebd_part_name' => ['part_name', 'product_name'],
            'part_name' => ['ebd_part_name', 'product_name'],
            'customer_name' => ['ebd_customer', 'customer'],
            'model_name' => ['ebd_model', 'model', 'project_model']
        ];

        if (isset($aliases[$fieldKey])) {
            foreach ($aliases[$fieldKey] as $aliasKey) {
                if ($parentData && is_array($parentData) && array_key_exists($aliasKey, $parentData) && $parentData[$aliasKey] !== null) {
                    return $parentData[$aliasKey];
                }
                if (array_key_exists($aliasKey, $payloadData) && $payloadData[$aliasKey] !== null) {
                    return $payloadData[$aliasKey];
                }
            }
        }

        // 4. Fallback to first item of any list array in payloadData
        foreach ($payloadData as $val) {
            if (is_array($val) && !empty($val)) {
                $firstItem = reset($val);
                if (is_array($firstItem)) {
                    if (array_key_exists($fieldKey, $firstItem) && $firstItem[$fieldKey] !== null) {
                        return $firstItem[$fieldKey];
                    }
                    if (isset($aliases[$fieldKey])) {
                        foreach ($aliases[$fieldKey] as $aliasKey) {
                            if (array_key_exists($aliasKey, $firstItem) && $firstItem[$aliasKey] !== null) {
                                return $firstItem[$aliasKey];
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Evaluate dynamic conditional rules and inject resulting values into target cells/columns.
     */
    private function processConditionalRules($spreadsheet, array $rules, array $payloadData): void
    {
        foreach ($rules as $rule) {
            $fieldKey = $rule['field_key'] ?? null;
            $operator = $rule['operator'] ?? 'equals';
            $matchVal = $rule['value'] ?? '';
            $targetCell = $rule['target_cell'] ?? null;
            $outputType = $rule['output_type'] ?? 'field_value';
            $outputFieldKey = $rule['output_field_key'] ?? null;
            $outputStaticVal = $rule['output_static_value'] ?? '';
            $sheetIdx = $rule['sheet_index'] ?? 0;
            $renderType = $rule['render_type'] ?? 'default';

            if (!$fieldKey || !$targetCell) continue;

            $targetSheet = $spreadsheet->getSheet($sheetIdx) ?? $spreadsheet->getActiveSheet();

            $sourceItems = [];
            foreach ($payloadData as $val) {
                if (is_array($val) && !empty($val) && is_array(reset($val))) {
                    $sourceItems = $val;
                    break;
                }
            }

            if (empty($sourceItems)) {
                $sourceItems = [$payloadData];
            }

            preg_match('/^([A-Z]+)(\d+)?$/i', trim($targetCell), $matches);
            $targetCol = strtoupper($matches[1] ?? 'A');
            $targetStartRow = isset($matches[2]) ? (int)$matches[2] : 13;
            $isRowSpecific = isset($matches[2]);

            $elseStaticVal = $rule['else_static_value'] ?? null;

            foreach ($sourceItems as $idx => $itemData) {
                $cellCoord = $isRowSpecific ? ($targetCol . $targetStartRow) : ($targetCol . ($targetStartRow + $idx));

                $valToEval = $this->resolveSingleFieldValue($fieldKey, $payloadData, $itemData);

                $isMatched = false;
                $strVal = (string)($valToEval ?? '');
                $cleanMatch = (string)$matchVal;

                switch ($operator) {
                    case 'equals':
                        $isMatched = strtolower(trim($strVal)) === strtolower(trim($cleanMatch));
                        break;
                    case 'not_equals':
                        $isMatched = strtolower(trim($strVal)) !== strtolower(trim($cleanMatch));
                        break;
                    case 'contains':
                        $isMatched = stripos($strVal, $cleanMatch) !== false;
                        break;
                    case 'starts_with':
                        $isMatched = str_starts_with(strtolower(trim($strVal)), strtolower(trim($cleanMatch)));
                        break;
                    case 'ends_with':
                        $isMatched = str_ends_with(strtolower(trim($strVal)), strtolower(trim($cleanMatch)));
                        break;
                    case 'greater_than':
                        $isMatched = is_numeric($strVal) && is_numeric($cleanMatch) && ((float)$strVal > (float)$cleanMatch);
                        break;
                    case 'greater_equal':
                        $isMatched = is_numeric($strVal) && is_numeric($cleanMatch) && ((float)$strVal >= (float)$cleanMatch);
                        break;
                    case 'less_than':
                        $isMatched = is_numeric($strVal) && is_numeric($cleanMatch) && ((float)$strVal < (float)$cleanMatch);
                        break;
                    case 'less_equal':
                        $isMatched = is_numeric($strVal) && is_numeric($cleanMatch) && ((float)$strVal <= (float)$cleanMatch);
                        break;
                    case 'is_empty':
                        $isMatched = trim($strVal) === '';
                        break;
                    case 'is_not_empty':
                        $isMatched = trim($strVal) !== '';
                        break;
                    default:
                        $isMatched = strtolower(trim($strVal)) === strtolower(trim($cleanMatch));
                        break;
                }

                if ($isMatched) {
                    $outVal = null;
                    if ($outputType === 'static_value') {
                        $outVal = $outputStaticVal;
                    } else if ($outputFieldKey) {
                        $outVal = $this->resolveSingleFieldValue($outputFieldKey, $payloadData, $itemData);
                    }

                    if ($outVal !== null) {
                        $this->applyCellFormatAndValue($targetSheet, $cellCoord, $outVal, $renderType);
                    }
                } else if ($elseStaticVal !== null && $elseStaticVal !== '') {
                    $this->applyCellFormatAndValue($targetSheet, $cellCoord, $elseStaticVal, $renderType);
                }
            }
        }
    }
}
