<?php

namespace App\Services\ExcelEngine;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelImportEngineService
{
    /**
     * Extract structured payload from uploaded .xlsx file based on mapping_config
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
        $sheet = $spreadsheet->getActiveSheet();

        $extractedData = [
            'single_fields' => [],
            'table_loops' => []
        ];

        // 1. Parse Single Fields
        if (!empty($mappingConfig['single_fields'])) {
            foreach ($mappingConfig['single_fields'] as $fieldMapping) {
                $fieldKey = $fieldMapping['field_key'] ?? null;
                $cellCoordinate = $fieldMapping['cell'] ?? null;

                if ($fieldKey && $cellCoordinate) {
                    $val = $this->getCellValue($sheet, $cellCoordinate);
                    $extractedData['single_fields'][$fieldKey] = $val;
                }
            }
        }

        // 2. Parse Table Loops (Flat Tables & Nested Parent-Child Blocks)
        if (!empty($mappingConfig['table_loops'])) {
            foreach ($mappingConfig['table_loops'] as $loopConfig) {
                $group = $loopConfig['group'] ?? null;
                $loopMode = $loopConfig['loop_mode'] ?? 'flat';
                $startRow = (int)($loopConfig['start_row'] ?? 0);
                $stopCondition = $loopConfig['stop_condition'] ?? [];
                $columns = $loopConfig['columns'] ?? [];

                if (!$group || !$startRow || empty($columns)) {
                    continue;
                }

                $extractedData['table_loops'][$group] = [];

                $currentRow = $startRow;
                $maxRowGuard = $startRow + 1000; // Safety threshold
                $currentParentBlock = null;

                while ($currentRow < $maxRowGuard) {
                    if ($this->shouldStopLoop($sheet, $currentRow, $stopCondition)) {
                        break;
                    }

                    $rowPayload = [];
                    $hasAnyData = false;

                    foreach ($columns as $fieldKey => $columnLetter) {
                        $cellCoordinate = $columnLetter . $currentRow;
                        $cellValue = $this->getCellValue($sheet, $cellCoordinate);

                        if ($cellValue !== null && $cellValue !== '') {
                            $hasAnyData = true;
                        }
                        $rowPayload[$fieldKey] = $cellValue;
                    }

                    if (!$hasAnyData) {
                        break;
                    }

                    if ($loopMode === 'nested_block') {
                        // ── NESTED PARENT-CHILD FORWARD-FILL TRACKING ───────────
                        // If row contains parent identifier (e.g. part_number), start new parent block
                        $hasParentKey = false;
                        foreach ($rowPayload as $k => $v) {
                            if (str_contains($k, 'part_number') || str_contains($k, 'parent') || str_contains($k, 'header')) {
                                if (!empty(trim((string)$v))) {
                                    $hasParentKey = true;
                                    break;
                                }
                            }
                        }

                        if ($hasParentKey || $currentParentBlock === null) {
                            if ($currentParentBlock !== null) {
                                $extractedData['table_loops'][$group][] = $currentParentBlock;
                            }
                            $currentParentBlock = [
                                'parent' => $rowPayload,
                                'children' => [$rowPayload]
                            ];
                        } else {
                            $currentParentBlock['children'][] = $rowPayload;
                        }
                    } else {
                        $extractedData['table_loops'][$group][] = $rowPayload;
                    }

                    $currentRow++;
                }

                if ($loopMode === 'nested_block' && $currentParentBlock !== null) {
                    $extractedData['table_loops'][$group][] = $currentParentBlock;
                }
            }
        }

        return $extractedData;
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
