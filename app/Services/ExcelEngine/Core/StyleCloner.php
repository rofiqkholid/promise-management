<?php

namespace App\Services\ExcelEngine\Core;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StyleCloner
{
    /**
     * Clone complete row styling (borders, fills, fonts, alignments, formats, row height, merged cells)
     * from a template row to a destination row.
     *
     * @param Worksheet $sheet
     * @param int $sourceRow
     * @param int $destRow
     * @param int $maxColumnIndex Optional limit for column index (e.g. 50)
     */
    public function cloneRowStyle(Worksheet $sheet, int $sourceRow, int $destRow, int $maxColumnIndex = 60): void
    {
        // 1. Copy Row Dimensions / Height
        $sourceRowDimension = $sheet->getRowDimension($sourceRow);
        if ($sourceRowDimension && $sourceRowDimension->getRowHeight() > 0) {
            $destRowDimension = $sheet->getRowDimension($destRow);
            $destRowDimension->setRowHeight($sourceRowDimension->getRowHeight());
        }

        // 2. Clone cell styles across columns in the row
        $highestColumn = $sheet->getHighestDataColumn($sourceRow);
        $highestColIndex = min(Coordinate::columnIndexFromString($highestColumn), $maxColumnIndex);

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $sourceCoordinate = $colLetter . $sourceRow;
            $destCoordinate = $colLetter . $destRow;

            // Duplicate style array
            $sourceStyle = $sheet->getStyle($sourceCoordinate);
            if ($sourceStyle) {
                $sheet->duplicateStyle($sourceStyle, $destCoordinate);
            }
        }

        // 3. Clone Merged Cells if any exist in the source row
        $this->cloneMergedCellsInRow($sheet, $sourceRow, $destRow);
    }

    /**
     * Check if the template row has merged cell ranges and clone them to the destination row
     *
     * @param Worksheet $sheet
     * @param int $sourceRow
     * @param int $destRow
     */
    public function cloneMergedCellsInRow(Worksheet $sheet, int $sourceRow, int $destRow): void
    {
        $mergeRanges = $sheet->getMergeCells();
        foreach ($mergeRanges as $range) {
            if (preg_match('/^([A-Za-z]+)' . $sourceRow . ':([A-Za-z]+)' . $sourceRow . '$/', $range, $matches)) {
                $startCol = $matches[1];
                $endCol = $matches[2];
                $newRange = "{$startCol}{$destRow}:{$endCol}{$destRow}";
                
                // Only merge if not already merged
                if (!isset($mergeRanges[$newRange])) {
                    $sheet->mergeCells($newRange);
                }
            }
        }
    }
}
