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
                    if (str_starts_with($staticVal, '=')) {
                        $targetSheet->setCellValue($cellCoordinate, $staticVal);
                    } else {
                        $targetSheet->setCellValueExplicit($cellCoordinate, $staticVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                } else if ($fieldKey && $cellCoordinate && array_key_exists($fieldKey, $payloadData)) {
                    $targetSheet->setCellValue($cellCoordinate, $payloadData[$fieldKey]);
                }
            }
        }

        // 2. Inject Table Loops (Dynamic Multiline Tables & Nested Block Repeaters)
        if (!empty($mappingConfig['table_loops'])) {

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
            // Find the first parent item that has a non-empty child array to use as reference
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

            // For each loop config, determine ONCE if it writes child (process) fields or parent fields.
            // A loop is classified as "child" if ANY of its field_keys exist in the child row data
            // but NOT in the parent row data (using the reference item with actual processes).
            $loopClassifications = [];
            foreach ($mappingConfig['table_loops'] as $idx => $loopConfig) {
                $columns = $loopConfig['columns'] ?? [];
                $isChildLoop = false;

                if (!empty($referenceChildren)) {
                    $refChild = $referenceChildren[0];
                    foreach (array_keys($columns) as $fieldKey) {
                        // Field is child-level if it exists in child data but NOT in parent data
                        if (array_key_exists($fieldKey, $refChild) && !array_key_exists($fieldKey, $referenceParent ?? [])) {
                            $isChildLoop = true;
                            break;
                        }
                    }
                }

                $loopClassifications[$idx] = $isChildLoop;
            }

            // ── STEP C: Compute row stride per parent (based on template block height & child count)
            $startRows = array_map(fn($l) => (int)($l['start_row'] ?? 0), $mappingConfig['table_loops']);
            $minStartRow = !empty($startRows) ? min($startRows) : 0;
            $maxStartRow = !empty($startRows) ? max($startRows) : 0;
            $templateBlockHeight = max(1, ($maxStartRow - $minStartRow + 1));

            $rowStrides = [];
            foreach ($sourceItems as $parentIdx => $parentData) {
                $childCount = $templateBlockHeight; // Minimum stride is template block height (e.g. 2 rows)
                foreach ($parentData as $val) {
                    if (is_array($val) && count($val) > 0) {
                        $childCount = max($templateBlockHeight, count($val));
                        break;
                    }
                }
                $rowStrides[$parentIdx] = $childCount;
            }

            // ── STEP D: Master pass — handle direction & insert_behavior ────────────
            $primaryLoopConfig = $mappingConfig['table_loops'][0] ?? [];
            $direction = $primaryLoopConfig['direction'] ?? 'down';
            $insertBehavior = $primaryLoopConfig['insert_behavior'] ?? 'insert_duplicate';

            if ($direction === 'right') {
                // ── HORIZONTAL LOOP (RIGHT / COLUMNS) ──────────────────────────
                foreach ($sourceItems as $itemIdx => $itemData) {
                    foreach ($mappingConfig['table_loops'] as $loopConfig) {
                        $columns = $loopConfig['columns'] ?? [];
                        foreach ($columns as $fieldKey => $baseColumnLetter) {
                            $baseColNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($baseColumnLetter);
                            $targetColNum = $baseColNum + $itemIdx;
                            $targetColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($targetColNum);
                            $cellCoordinate = $targetColLetter . $baseStartRow;

                            if ($insertBehavior === 'insert_duplicate' && $itemIdx > 0) {
                                if (array_key_first($columns) === $fieldKey) {
                                    $sheet->insertNewColumnBefore($targetColLetter, 1);
                                    $sheet->duplicateStyle($sheet->getStyle($baseColumnLetter . $baseStartRow), $cellCoordinate);
                                }
                            }

                            if (array_key_exists($fieldKey, $itemData)) {
                                $sheet->setCellValue($cellCoordinate, $itemData[$fieldKey]);
                            }
                        }
                    }
                }
            } else {
                // ── VERTICAL LOOP (DOWN / ROWS) ─────────────────────────────────
                // Pre-collect parent mapped columns to prevent parent values repeating on child process lines
                $parentMappedColumns = [];
                foreach ($mappingConfig['table_loops'] as $idx => $loopConfig) {
                    if (empty($loopClassifications[$idx])) {
                        foreach ($loopConfig['columns'] ?? [] as $colLetter) {
                            $parentMappedColumns[$colLetter] = true;
                        }
                    }
                }

                $currentRow = $baseStartRow;

                foreach ($sourceItems as $parentIdx => $parentData) {
                    $childCount = $rowStrides[$parentIdx];

                    // Extract the child array (processes) for this parent
                    $children = [];
                    foreach ($parentData as $val) {
                        if (is_array($val)) {
                            $children = $val;
                            break;
                        }
                    }

                    // Handle Row Insertion vs Overwrite
                    if ($insertBehavior === 'insert_duplicate') {
                        // If not the very first parent item, insert base rows for this parent block
                        if ($parentIdx > 0) {
                            $sheet->insertNewRowBefore($currentRow, 1);
                            $sourceRowForTarget = $baseStartRow + (($currentRow - $baseStartRow) % $templateBlockHeight);
                            $this->copyRowStylesAndFormulas($sheet, $sourceRowForTarget, $currentRow, $parentMappedColumns, false);
                        }

                        // Insert extra blank rows for additional process lines (if process count > 1)
                        if ($childCount > 1) {
                            $sheet->insertNewRowBefore($currentRow + 1, $childCount - 1);
                            for ($k = 1; $k < $childCount; $k++) {
                                $targetR = $currentRow + $k;
                                $sourceRowForTarget = $baseStartRow + (($targetR - $baseStartRow) % $templateBlockHeight);
                                $this->copyRowStylesAndFormulas($sheet, $sourceRowForTarget, $targetR, $parentMappedColumns, true);
                            }
                        }
                    }

                    // Write all loops for this parent block
                    foreach ($mappingConfig['table_loops'] as $idx => $loopConfig) {
                        $columns = $loopConfig['columns'] ?? [];
                        if (empty($columns)) {
                            continue;
                        }

                        $isChildLoop = $loopClassifications[$idx];
                        $staticValues = $loopConfig['static_values'] ?? [];

                        if ($isChildLoop) {
                            // ── Child Loop: write one row per process ─────────────────
                            for ($i = 0; $i < $childCount; $i++) {
                                $blockRow = $currentRow + $i;
                                $childData = $children[$i] ?? [];

                                foreach ($columns as $fieldKey => $columnLetter) {
                                    $cellCoordinate = $columnLetter . $blockRow;
                                    if (isset($staticValues[$fieldKey])) {
                                        $evalVal = str_replace('{row}', $blockRow, $staticValues[$fieldKey]);
                                        if (str_starts_with($evalVal, '=')) {
                                            $sheet->setCellValue($cellCoordinate, $evalVal);
                                        } else {
                                            $sheet->setCellValueExplicit($cellCoordinate, $evalVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                                        }
                                    } else if (array_key_exists($fieldKey, $childData)) {
                                        $sheet->setCellValue($cellCoordinate, $childData[$fieldKey]);
                                    }
                                }
                            }
                        } else {
                            // ── Parent Loop: write at offset row corresponding to this loop's start_row ─
                            $loopStartRow = (int)($loopConfig['start_row'] ?? $baseStartRow);
                            $loopRowOffset = max(0, $loopStartRow - $baseStartRow);
                            $targetParentRow = $currentRow + $loopRowOffset;

                            foreach ($columns as $fieldKey => $columnLetter) {
                                $cellCoordinate = $columnLetter . $targetParentRow;
                                if (isset($staticValues[$fieldKey])) {
                                    $evalVal = str_replace('{row}', $targetParentRow, $staticValues[$fieldKey]);
                                    if (str_starts_with($evalVal, '=')) {
                                        $sheet->setCellValue($cellCoordinate, $evalVal);
                                    } else {
                                        $sheet->setCellValueExplicit($cellCoordinate, $evalVal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                                    }
                                } else if (array_key_exists($fieldKey, $parentData)) {
                                    $sheet->setCellValue($cellCoordinate, $parentData[$fieldKey]);
                                }
                            }
                        }
                    }

                    // Advance row pointer by this parent's block stride (children count or 1)
                    $currentRow += $childCount;
                }
            }
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
        $writer->setPreCalculateFormulas(false); // Preserve existing formula intact
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Copy cell formatting, formulas, and static values from source template row to newly inserted target row.
     */
    private function copyRowStylesAndFormulas($sheet, int $sourceRow, int $targetRow, array $parentMappedColumns = [], bool $isExtraChildRow = false): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Copy row height if defined
        if ($sheet->getRowDimension($sourceRow)->getRowHeight() > 0) {
            $sheet->getRowDimension($targetRow)->setRowHeight($sheet->getRowDimension($sourceRow)->getRowHeight());
        }

        for ($c = 1; $c <= $highestColumnIndex; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sourceCellCoord = $colLetter . $sourceRow;
            $targetCellCoord = $colLetter . $targetRow;

            // 1. Copy Style (Borders, Backgrounds, Alignments, Number Formats)
            $sheet->duplicateStyle($sheet->getStyle($sourceCellCoord), $targetCellCoord);

            // 2. Copy Formula or Static Content
            $sourceCell = $sheet->getCell($sourceCellCoord);
            if ($isExtraChildRow && isset($parentMappedColumns[$colLetter])) {
                // For extra child process rows, do NOT copy parent formulas OR static values (keep clean & blank)
                $sheet->setCellValue($targetCellCoord, null);
            } else if ($sourceCell->isFormula()) {
                $formula = $sourceCell->getValue();
                // Dynamically replace source row index references with target row index
                $updatedFormula = preg_replace_callback('/(\$?[A-Z]{1,3}\$?)(' . $sourceRow . ')\b/i', function($matches) use ($targetRow) {
                    return $matches[1] . $targetRow;
                }, $formula);

                $sheet->setCellValue($targetCellCoord, $updatedFormula);
            } else if ($sourceCell->getValue() !== null && $sourceCell->getValue() !== '') {
                $sheet->setCellValue($targetCellCoord, $sourceCell->getValue());
            }
        }
    }
}
