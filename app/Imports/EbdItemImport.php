<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\MngEbdItem;
use App\Models\MngEbdToolingProcess;
use App\Models\MngEbdAddProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EbdItemImport
{
    protected $ebdHeaderId;
    protected $errors = [];

    public function __construct($ebdHeaderId)
    {
        $this->ebdHeaderId = $ebdHeaderId;
    }

    /**
     * Main function to process EBD XLSX file into the database.
     *
     * @param  string  $filePath  Absolute path to the XLSX file
     */
    public function import($filePath)
    {
        try {
            // Load the spreadsheet using PhpSpreadsheet
            $spreadsheet = IOFactory::load($filePath);
            
            // Loop through all sheets and find the one that has data (e.g. contains part_no / level columns)
            $sheet = null;
            foreach ($spreadsheet->getAllSheets() as $currentSheet) {
                // Check if row 15 has data by reading row 15 values
                $hasHeaders = false;
                for ($col = 1; $col <= 20; $col++) {
                    $val = trim((string)$currentSheet->getCell([$col, 15])->getValue());
                    if (str_starts_with(strtolower($val), 'level_') || strtolower($val) === 'part_no') {
                        $hasHeaders = true;
                        break;
                    }
                }
                if ($hasHeaders) {
                    $sheet = $currentSheet;
                    Log::info("EBD Import: Selected sheet '" . $sheet->getTitle() . "' because EBD headers were detected at row 15.");
                    break;
                }
            }

            // Fallback to active sheet if no sheet specifically matched the row 15 headers
            if (!$sheet) {
                $sheet = $spreadsheet->getActiveSheet();
                Log::info("EBD Import: Fallback to active sheet '" . $sheet->getTitle() . "'.");
            }

            // Convert worksheet data to array format (1-based index)
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

            // Read all sheet data into a matrix (0-indexed to match previous logic)
            $rows = [];
            for ($r = 1; $r <= $highestRow; $r++) {
                $rowCells = [];
                for ($c = 1; $c <= $highestColIdx; $c++) {
                    $cell = $sheet->getCell([$c, $r]);
                    try {
                        $val = $cell->getCalculatedValue();
                    } catch (\Exception $ex) {
                        $val = $cell->getOldCalculatedValue() ?? $cell->getValue();
                    }
                    $rowCells[$c - 1] = $val !== null ? (string)$val : '';
                }
                $rows[$r - 1] = $rowCells;
            }

            // --- INITIALIZE BOM STATE ---
            $map          = [];
            $levelColumns = [];
            $activeIds    = [];
            $activePartId = null;
            $activeLevel  = null;

            // Header variable row is Excel row 15 (0-indexed: 14)
            $mappingRowIndex   = 14;
            // Data rows begin at Excel row 16 (0-indexed: 15)
            $startDataRowIndex = 15;

            // Build map of header columns
            $rawMapRow = $rows[$mappingRowIndex] ?? [];
            if (empty(array_filter($rawMapRow))) {
                throw new \Exception("Row 15 does not contain EBD column headers.");
            }

            foreach ($rawMapRow as $index => $value) {
                $value = trim((string)$value);
                if ($value === '') continue;

                $key = strtolower(str_replace(' ', '_', $value));

                // Detect level columns dynamically: level_1, level_2, ...
                if (str_starts_with($key, 'level_')) {
                    $levelNumber                = (int) filter_var($key, FILTER_SANITIZE_NUMBER_INT);
                    $levelColumns[$levelNumber] = $index;
                } else {
                    $map[$key] = $index;
                }
            }
            ksort($levelColumns);

            if (empty($levelColumns)) {
                throw new \Exception("Could not find any level columns (e.g. level_1, level_2) at row 15.");
            }

            DB::beginTransaction();

            // Loop data rows (starting Excel row 16)
            for ($i = $startDataRowIndex; $i < count($rows); $i++) {
                $rawRow = $rows[$i];

                // Skip entirely empty rows
                if (empty(array_filter($rawRow, fn($v) => $v !== '' && $v !== null))) {
                    continue;
                }

                // Detect which level column is filled in this row
                $detectedLevel = null;
                foreach ($levelColumns as $levelNum => $colIndex) {
                    $cellVal = trim((string)($rawRow[$colIndex] ?? ''));
                    if ($cellVal !== '') {
                        $detectedLevel = $levelNum;
                        break;
                    }
                }

                // =============================================================
                // CONDITION A: NEW LEVEL — first row of a BOM block
                // =============================================================
                if ($detectedLevel !== null) {
                    $activeLevel = $detectedLevel;

                    $parentId = null;
                    if ($detectedLevel > 1) {
                        $parentId = $activeIds[$detectedLevel - 1] ?? null;
                    }

                    $rawYield = $this->val($rawRow, $map, 'mat_yield_ratio');
                    $yieldRatio = 0;
                    if ($rawYield !== null && $rawYield !== '') {
                        $vf = (float) str_replace(['%', ' '], '', $rawYield);
                        $yieldRatio = ($vf > 0 && $vf <= 1.0) ? $vf * 100 : $vf;
                        $yieldRatio = (float) round($yieldRatio);
                    }

                    $partData = [
                        'ebd_header_id'   => $this->ebdHeaderId,
                        'parent_id'       => $parentId,
                        'active_level'    => $activeLevel,
                        'part_no'         => $this->val($rawRow, $map, 'part_no'),
                        'part_name'       => $this->val($rawRow, $map, 'part_name'),
                        'pcs_month'       => (int) ($this->val($rawRow, $map, 'pcs_month') ?? 0),
                        'sketch'          => $this->val($rawRow, $map, 'sketch'),

                        // Part Dimensions
                        'qty_unit'        => (int) ($this->val($rawRow, $map, 'qty_unit') ?? 1),
                        'width'           => (float) ($this->val($rawRow, $map, 'part_width') ?? 0),
                        'length'          => (float) ($this->val($rawRow, $map, 'part_length') ?? 0),
                        'height'          => (float) ($this->val($rawRow, $map, 'part_height') ?? 0),
                        'weight'          => (float) ($this->val($rawRow, $map, 'part_weight') ?? 0),
                        'status'          => $this->val($rawRow, $map, 'part_status'),
                        'part_rank'       => $this->val($rawRow, $map, 'part_rank'),

                        // Material Spec
                        'mat_spec'        => $this->val($rawRow, $map, 'mat_spec'),
                        'mat_thick'       => (float) ($this->val($rawRow, $map, 'mat_thick') ?? 0),
                        'mat_width'       => (float) ($this->val($rawRow, $map, 'mat_width') ?? 0),
                        'mat_length'      => (float) ($this->val($rawRow, $map, 'mat_length') ?? 0),
                        'mat_pcs_sheet'   => (int) ($this->val($rawRow, $map, 'mat_pcs_sheet') ?? 0),
                        'mat_weight_pcs'  => (float) ($this->val($rawRow, $map, 'mat_weight_pcs') ?? 0),
                        'mat_yield_ratio' => $yieldRatio,

                        // Standard Part
                        'std_part_no'     => $this->val($rawRow, $map, 'std_part_no'),
                        'std_part_name'   => $this->val($rawRow, $map, 'std_part_name'),
                        'std_qty'         => (int) ($this->val($rawRow, $map, 'std_qty') ?? 0),
                        'std_uom'         => $this->val($rawRow, $map, 'std_uom'),

                        // Packing & Transport
                        'packing_type'    => $this->val($rawRow, $map, 'packing_type'),
                        'pcs_packing'     => (int) ($this->val($rawRow, $map, 'pcs_packing') ?? 0),
                        'part_vol_m2'     => (float) ($this->val($rawRow, $map, 'part_vol_m2') ?? 0),
                        'truck_vol_m2'    => (float) ($this->val($rawRow, $map, 'truck_vol_m2') ?? 0),
                    ];

                    $item = MngEbdItem::create($partData);

                    $activeIds[$detectedLevel] = $item->id;
                    $activePartId              = $item->id;

                    // Clear deeper levels to prevent parent-ID leakage
                    foreach ($activeIds as $lvl => $id) {
                        if ($lvl > $detectedLevel) unset($activeIds[$lvl]);
                    }

                } else {
                    // =============================================================
                    // CONDITION B: MERGED / CONTINUATION ROW
                    // =============================================================
                    $activePartId = !empty($activeIds) ? end($activeIds) : null;

                    // If this continuation row has part-level data that was not on the
                    // first row (e.g. std_part_no, sketch, dimensions on a later line),
                    // merge it into the already-created active item.
                    if ($activePartId) {
                        $updateData = [];

                        // Standard Part — may appear on a separate sub-row
                        $stdPartNo   = $this->val($rawRow, $map, 'std_part_no');
                        $stdPartName = $this->val($rawRow, $map, 'std_part_name');
                        $stdQty      = $this->val($rawRow, $map, 'std_qty');
                        $stdUom      = $this->val($rawRow, $map, 'std_uom');
                        if ($stdPartNo   !== null && $stdPartNo   !== '') $updateData['std_part_no']   = $stdPartNo;
                        if ($stdPartName !== null && $stdPartName !== '') $updateData['std_part_name'] = $stdPartName;
                        if ($stdQty      !== null && $stdQty      !== '') $updateData['std_qty']       = (int) $stdQty;
                        if ($stdUom      !== null && $stdUom      !== '') $updateData['std_uom']       = $stdUom;

                        // Sketch — may appear on a continuation row
                        $sketch = $this->val($rawRow, $map, 'sketch');
                        if ($sketch !== null && $sketch !== '') $updateData['sketch'] = $sketch;

                        // Part dimensions / material if on continuation row
                        $partNo = $this->val($rawRow, $map, 'part_no');
                        if ($partNo !== null && $partNo !== '') $updateData['part_no'] = $partNo;

                        if (!empty($updateData)) {
                            MngEbdItem::where('id', $activePartId)->update($updateData);
                        }
                    }
                }

                if (!$activePartId) continue;

                // =============================================================
                // CONDITION C: SAVE MULTI-ROW PROCESS DATA
                // =============================================================

                // Tooling Process
                $toolProcessName = $this->val($rawRow, $map, 'tool_process_name');
                if (!empty($toolProcessName)) {

                    $isLevel1  = ($activeLevel === 1);
                    $toolRank = $this->val($rawRow, $map, 'tool_rank') ?? '';
                    $toolCategory = $this->val($rawRow, $map, 'tool_category') ?? '';
                    $combinedToolType = strtoupper("{$toolRank} {$toolCategory} {$toolProcessName}");
                    $isCfOrJig = (bool) preg_match('/(cf|jig)/i', $combinedToolType);

                    MngEbdToolingProcess::create([
                        'ebd_item_id'    => $activePartId,
                        'tool_rank'      => $this->val($rawRow, $map, 'tool_rank'),
                        'category'       => $this->val($rawRow, $map, 'tool_category'),
                        'op'             => $this->val($rawRow, $map, 'tool_op') !== null ? (int) $this->val($rawRow, $map, 'tool_op') : null,
                        'process_name'   => $toolProcessName,
                        'prod_homeline'  => $this->val($rawRow, $map, 'tool_prod_homeline'),
                        'tonnage'        => ($isLevel1 || $isCfOrJig) ? null : (int) ($this->val($rawRow, $map, 'tool_tonnage') ?? 0),
                        'die_height'     => ($isLevel1 || $isCfOrJig) ? null : (float) ($this->val($rawRow, $map, 'tool_die_height') ?? 0),
                        'output'         => $this->val($rawRow, $map, 'tool_output') !== null ? (int) $this->val($rawRow, $map, 'tool_output') : null,
                        'output_type'    => $this->val($rawRow, $map, 'tool_output_type'),
                        'qty'            => (int) ($this->val($rawRow, $map, 'tool_qty') ?? 1),
                        'price_idr'      => (float) ($this->val($rawRow, $map, 'tool_price_idr') ?? 0),
                        'tooling_status' => $this->val($rawRow, $map, 'tooling_status'),
                        'information'    => $this->val($rawRow, $map, 'tool_information'),
                    ]);
                }

                // Add Process
                $addProcessName = $this->val($rawRow, $map, 'add_process_name');
                if (!empty($addProcessName)) {
                    MngEbdAddProcess::create([
                        'ebd_item_id'  => $activePartId,
                        'process_name' => $addProcessName,
                        'qty'          => (int) ($this->val($rawRow, $map, 'add_qty') ?? 0),
                        'unit'         => $this->val($rawRow, $map, 'add_unit') ?? 'pcs',
                        'cost_idr'     => (float) ($this->val($rawRow, $map, 'add_cost_idr') ?? 0),
                    ]);
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('EBD Import failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $this->errors[] = [
                'row'    => isset($i) ? ($i + 1) : 0,
                'errors' => [$e->getMessage()]
            ];
            return false;
        }
    }

    /**
     * Safe column value getter — returns null when key missing or value empty.
     */
    private function val(array $row, array $map, string $key): ?string
    {
        $colIdx = $map[$key] ?? -1;
        if ($colIdx < 0) return null;
        $v = trim((string)($row[$colIdx] ?? ''));
        return $v === '' ? null : $v;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}