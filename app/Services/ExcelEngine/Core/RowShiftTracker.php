<?php

namespace App\Services\ExcelEngine\Core;

class RowShiftTracker
{
    /**
     * Map of sheet identifier (name or index) => list of shift checkpoints
     * [
     *   sheet_id => [
     *      ['inserted_at_row' => 14, 'count' => 5],
     *      ...
     *   ]
     * ]
     * @var array<string|int, array<int, array{inserted_at_row: int, count: int}>>
     */
    protected array $shifts = [];

    /**
     * Record a row insertion on a specific sheet
     *
     * @param string|int $sheetKey Sheet name or index
     * @param int $insertedAtRow Base row index where new rows were inserted before
     * @param int $count Number of rows inserted
     */
    public function recordShift($sheetKey, int $insertedAtRow, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        if (!isset($this->shifts[$sheetKey])) {
            $this->shifts[$sheetKey] = [];
        }

        $this->shifts[$sheetKey][] = [
            'inserted_at_row' => $insertedAtRow,
            'count' => $count
        ];
    }

    /**
     * Calculate the shifted row index for an original template row coordinate
     *
     * @param string|int $sheetKey
     * @param int $originalRow
     * @return int Shifted row index
     */
    public function getShiftedRow($sheetKey, int $originalRow): int
    {
        if (!isset($this->shifts[$sheetKey])) {
            return $originalRow;
        }

        $shiftedRow = $originalRow;
        foreach ($this->shifts[$sheetKey] as $shift) {
            if ($originalRow > $shift['inserted_at_row']) {
                $shiftedRow += $shift['count'];
            }
        }

        return $shiftedRow;
    }

    /**
     * Adjust a cell coordinate (e.g. "E15" or "AB20") by current sheet shifts
     *
     * @param string|int $sheetKey
     * @param string $cellCoordinate
     * @return string Shifted cell coordinate (e.g. "E20")
     */
    public function adjustCellCoordinate($sheetKey, string $cellCoordinate): string
    {
        if (preg_match('/^([A-Za-z]+)(\d+)$/', $cellCoordinate, $matches)) {
            $columnLetter = $matches[1];
            $originalRow = (int)$matches[2];
            $shiftedRow = $this->getShiftedRow($sheetKey, $originalRow);
            return $columnLetter . $shiftedRow;
        }

        return $cellCoordinate;
    }

    /**
     * Reset shifts
     */
    public function reset(): void
    {
        $this->shifts = [];
    }
}
