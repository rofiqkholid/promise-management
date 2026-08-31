<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\MngEbdItem;
use App\Models\MngEbdToolingProcess;
use App\Models\MngEbdAddProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                    break;
                }
            }

            // Fallback to active sheet if no sheet specifically matched the row 15 headers
            if (!$sheet) {
                $sheet = $spreadsheet->getActiveSheet();
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

                $rawKey = strtolower($value);
                $keyUnder = strtolower(str_replace(' ', '_', $value));
                $cleanKey = strtolower(preg_replace('/[^a-z0-9]+/', '_', $rawKey));
                $cleanKey = trim($cleanKey, '_');

                // Detect level columns dynamically: level_1, level_2, ...
                if (str_starts_with($keyUnder, 'level_') || str_starts_with($cleanKey, 'level_')) {
                    $levelNumber                = (int) filter_var($cleanKey, FILTER_SANITIZE_NUMBER_INT);
                    $levelColumns[$levelNumber] = $index;
                } else {
                    $map[$rawKey]   = $index;
                    $map[$keyUnder] = $index;
                    $map[$cleanKey] = $index;
                }
            }
            ksort($levelColumns);

            if (empty($levelColumns)) {
                throw new \Exception("Could not find any level columns (e.g. level_1, level_2) at row 15.");
            }

            // Extract all embedded images (sketch & material layout) anchored to cells
            $extractedImages = $this->extractEmbeddedImages($sheet, $map, $filePath);

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

                    $partNo   = $this->val($rawRow, $map, 'part_no');
                    $partName = $this->val($rawRow, $map, 'part_name');

                    // Skip summary / total rows at item level
                    if (($partNo && $this->isTotalOrSummaryRow($partNo)) || ($partName && $this->isTotalOrSummaryRow($partName))) {
                        continue;
                    }

                    $sketchVal    = $extractedImages[$i]['sketch'] ?? $this->val($rawRow, $map, 'sketch');
                    $matLayoutVal = $extractedImages[$i]['material_layout'] ?? $this->val($rawRow, $map, 'material_layout');

                    $partData = [
                        'ebd_header_id'   => $this->ebdHeaderId,
                        'parent_id'       => $parentId,
                        'active_level'    => $activeLevel,
                        'part_no'         => $partNo,
                        'part_name'       => $partName,
                        'pcs_month'       => (int) ($this->val($rawRow, $map, 'pcs_month') ?? 0),
                        'sketch'          => $sketchVal,
                        'material_layout' => $matLayoutVal,

                        // Part Dimensions
                        'qty_unit'        => (int) ($this->val($rawRow, $map, 'qty_unit') ?? 1),
                        'width'           => (float) ($this->val($rawRow, $map, 'part_width', 'width') ?? 0),
                        'length'          => (float) ($this->val($rawRow, $map, 'part_length', 'length') ?? 0),
                        'height'          => (float) ($this->val($rawRow, $map, 'part_height', 'height') ?? 0),
                        'weight'          => (float) ($this->val($rawRow, $map, 'part_weight', 'weight') ?? 0),
                        'status'          => $this->val($rawRow, $map, 'part_status', 'status'),
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
                        if ($stdPartNo   !== null && $stdPartNo   !== '' && !$this->isTotalOrSummaryRow($stdPartNo)) $updateData['std_part_no']   = $stdPartNo;
                        if ($stdPartName !== null && $stdPartName !== '' && !$this->isTotalOrSummaryRow($stdPartName)) $updateData['std_part_name'] = $stdPartName;
                        if ($stdQty      !== null && $stdQty      !== '') $updateData['std_qty']       = (int) $stdQty;
                        if ($stdUom      !== null && $stdUom      !== '') $updateData['std_uom']       = $stdUom;

                        // Sketch — may appear on a continuation row or embedded image
                        $sketch = $extractedImages[$i]['sketch'] ?? $this->val($rawRow, $map, 'sketch');
                        if ($sketch !== null && $sketch !== '') $updateData['sketch'] = $sketch;

                        // Material layout — may appear on a continuation row or embedded image
                        $matLayout = $extractedImages[$i]['material_layout'] ?? $this->val($rawRow, $map, 'material_layout');
                        if ($matLayout !== null && $matLayout !== '') $updateData['material_layout'] = $matLayout;

                        // Part dimensions / material if on continuation row
                        $partNo = $this->val($rawRow, $map, 'part_no');
                        if ($partNo !== null && $partNo !== '' && !$this->isTotalOrSummaryRow($partNo)) $updateData['part_no'] = $partNo;

                        if (!empty($updateData)) {
                            MngEbdItem::where('id', $activePartId)->update($updateData);
                        }
                    }
                }

                if (!$activePartId) continue;

                // =============================================================
                // CONDITION C: SAVE MULTI-ROW PROCESS DATA
                // =============================================================

                // Tooling Process — ignore rows marked as TOTAL, SUBTOTAL, etc.
                $toolProcessName = $this->val($rawRow, $map, 'tool_process_name', 'process_name');
                $toolOp          = $this->val($rawRow, $map, 'tool_op', 'op');
                $toolRank        = $this->val($rawRow, $map, 'tool_rank', 'rank');
                $toolCategory    = $this->val($rawRow, $map, 'tool_category', 'category');

                $isTotalProcess = $this->isTotalOrSummaryRow($toolProcessName ?? '')
                    || $this->isTotalOrSummaryRow($toolOp ?? '')
                    || $this->isTotalOrSummaryRow($toolRank ?? '')
                    || $this->isTotalOrSummaryRow($toolCategory ?? '');

                if (!empty($toolProcessName) && !$isTotalProcess) {

                    $isLevel1  = ($activeLevel === 1);
                    $combinedToolType = strtoupper("{$toolRank} {$toolCategory} {$toolProcessName}");
                    $isCfOrJig = (bool) preg_match('/(cf|jig)/i', $combinedToolType);

                    $toolMachineType = $this->val($rawRow, $map, 'tool_machine_type', 'machine_type', 'tool_machine', 'machine', 'mach_type', 'mach');
                    $toolHomeline    = $this->val($rawRow, $map, 'tool_prod_homeline', 'prod_homeline', 'homeline');
                    $toolTonRaw      = $this->val($rawRow, $map, 'tool_tonnage', 'tonnage');
                    $toolDhRaw       = $this->val($rawRow, $map, 'tool_die_height', 'die_height');
                    $toolOutputRaw   = $this->val($rawRow, $map, 'tool_output', 'output');
                    $toolOutputType  = $this->val($rawRow, $map, 'tool_output_type', 'output_type');
                    $toolStrokeRaw   = $this->val($rawRow, $map, 'tool_stroke', 'stroke', 'spm');
                    $toolJphRaw      = $this->val($rawRow, $map, 'tool_jph_gsph', 'jph_gsph', 'tool_jph', 'jph', 'gsph');
                    $toolMpRaw       = $this->val($rawRow, $map, 'tool_man_power', 'man_power', 'tool_manpower', 'manpower', 'tool_mp', 'mp');
                    $toolQtyRaw      = $this->val($rawRow, $map, 'tool_qty', 'qty');
                    $toolPriceRaw    = $this->val($rawRow, $map, 'tool_price_idr', 'price_idr', 'price');
                    $toolStatus      = $this->val($rawRow, $map, 'tooling_status', 'tool_status', 'status');
                    $toolInfo        = $this->val($rawRow, $map, 'tool_information', 'information', 'info');

                    MngEbdToolingProcess::create([
                        'ebd_item_id'    => $activePartId,
                        'tool_rank'      => $toolRank ?: null,
                        'category'       => $toolCategory ?: null,
                        'op'             => ($toolOp !== null && trim((string)$toolOp) !== '') ? (int) $toolOp : null,
                        'process_name'   => $toolProcessName,
                        'machine_type'   => ($toolMachineType !== null && trim((string)$toolMachineType) !== '') ? $toolMachineType : null,
                        'prod_homeline'  => ($toolHomeline !== null && trim((string)$toolHomeline) !== '') ? $toolHomeline : null,
                        'tonnage'        => ($isLevel1 || $isCfOrJig || $toolTonRaw === null || trim((string)$toolTonRaw) === '') ? null : (int) $toolTonRaw,
                        'die_height'     => ($isLevel1 || $isCfOrJig || $toolDhRaw === null || trim((string)$toolDhRaw) === '') ? null : (float) str_replace(',', '.', $toolDhRaw),
                        'output'         => ($toolOutputRaw !== null && trim((string)$toolOutputRaw) !== '') ? (int) $toolOutputRaw : null,
                        'output_type'    => ($toolOutputType !== null && trim((string)$toolOutputType) !== '') ? $toolOutputType : null,
                        'stroke'         => ($toolStrokeRaw !== null && trim((string)$toolStrokeRaw) !== '') ? (float) str_replace(',', '.', $toolStrokeRaw) : null,
                        'jph_gsph'       => ($toolJphRaw !== null && trim((string)$toolJphRaw) !== '') ? (float) str_replace(',', '.', $toolJphRaw) : null,
                        'man_power'      => ($toolMpRaw !== null && trim((string)$toolMpRaw) !== '') ? (float) str_replace(',', '.', $toolMpRaw) : null,
                        'qty'            => ($toolQtyRaw !== null && trim((string)$toolQtyRaw) !== '') ? (int) $toolQtyRaw : null,
                        'price_idr'      => ($toolPriceRaw !== null && trim((string)$toolPriceRaw) !== '') ? (float) str_replace(',', '.', $toolPriceRaw) : null,
                        'tooling_status' => $toolStatus ?: null,
                        'information'    => $toolInfo ?: null,
                    ]);
                }

                // Add Process — ignore rows marked as TOTAL, SUBTOTAL, etc.
                $addProcessName = $this->val($rawRow, $map, 'add_process_name', 'process_name');
                if (!empty($addProcessName) && !$this->isTotalOrSummaryRow($addProcessName)) {
                    $addQtyRaw  = $this->val($rawRow, $map, 'add_qty', 'qty');
                    $addCostRaw = $this->val($rawRow, $map, 'add_cost_idr', 'cost_idr', 'price_idr');

                    MngEbdAddProcess::create([
                        'ebd_item_id'  => $activePartId,
                        'process_name' => $addProcessName,
                        'qty'          => ($addQtyRaw !== null && trim((string)$addQtyRaw) !== '') ? (int) $addQtyRaw : null,
                        'unit'         => $this->val($rawRow, $map, 'add_unit', 'unit') ?: null,
                        'cost_idr'     => ($addCostRaw !== null && trim((string)$addCostRaw) !== '') ? (float) str_replace(',', '.', $addCostRaw) : null,
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
    private function val(array $row, array $map, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $colIdx = $map[$key] ?? -1;
            if ($colIdx >= 0) {
                $v = trim((string)($row[$colIdx] ?? ''));
                if ($v !== '') return $v;
            }
        }
        return null;
    }

    /**
     * Extract embedded drawings (sketch & material_layout) anchored to cells from spreadsheet.
     * Uses ZipArchive to read directly from the XLSX file (which is a ZIP).
     *
     * @param  \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet  $sheet
     * @param  array  $map
     * @param  string  $filePath
     * @return array [ 0-indexed row => [ 'sketch' => string, 'material_layout' => string ] ]
     */
    protected function extractEmbeddedImages($sheet, array $map, string $filePath): array
    {
        $images = [];

        $sketchColIdx    = $map['sketch'] ?? null;
        $matLayoutColIdx = $map['material_layout'] ?? null;

        if ($sketchColIdx === null && $matLayoutColIdx === null) {
            return $images;
        }

        // Build set of target column indices (0-based) expanding merged cells
        $sketchCols    = ($sketchColIdx !== null) ? [$sketchColIdx] : [];
        $matLayoutCols = ($matLayoutColIdx !== null) ? [$matLayoutColIdx] : [];

        try {
            foreach ($sheet->getMergeCells() as $rangeStr) {
                $range       = Coordinate::splitRange($rangeStr);
                $firstCell   = $range[0][0];
                $lastCell    = $range[0][1];
                $firstColIdx = Coordinate::columnIndexFromString(preg_replace('/[0-9]/', '', $firstCell)) - 1;
                $firstRow    = (int) preg_replace('/[A-Z]/i', '', $firstCell);
                $lastColIdx  = Coordinate::columnIndexFromString(preg_replace('/[0-9]/', '', $lastCell)) - 1;
                $lastRow     = (int) preg_replace('/[A-Z]/i', '', $lastCell);

                if ($firstRow <= 15 && $lastRow >= 15) {
                    if ($sketchColIdx !== null && $sketchColIdx >= $firstColIdx && $sketchColIdx <= $lastColIdx) {
                        for ($c = $firstColIdx; $c <= $lastColIdx; $c++) $sketchCols[] = $c;
                    }
                    if ($matLayoutColIdx !== null && $matLayoutColIdx >= $firstColIdx && $matLayoutColIdx <= $lastColIdx) {
                        for ($c = $firstColIdx; $c <= $lastColIdx; $c++) $matLayoutCols[] = $c;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("EBD Import: Merge cell processing error: " . $e->getMessage());
        }

        $sketchCols    = array_values(array_unique($sketchCols));
        $matLayoutCols = array_values(array_unique($matLayoutCols));

        if (!file_exists($filePath)) {
            Log::warning("EBD Import: XLSX file not found at: $filePath");
            return $images;
        }

        try {
            Storage::disk('public')->makeDirectory('ebd/sketches');
            Storage::disk('public')->makeDirectory('ebd/material_layouts');
        } catch (\Exception $e) {
            Log::warning("EBD Import: Failed creating storage directories: " . $e->getMessage());
        }

        // Determine which sheet corresponds to the selected sheet (to find correct drawing XML)
        $sheetName = $sheet->getTitle();

        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                Log::warning("EBD Import: Cannot open xlsx as zip: $filePath");
                return $images;
            }

            // --- Load all media files into memory ---
            $mediaStreams = [];
            for ($idx = 0; $idx < $zip->numFiles; $idx++) {
                $name = $zip->getNameIndex($idx);
                if (str_starts_with($name, 'xl/media/')) {
                    $data = $zip->getFromIndex($idx);
                    if ($data !== false) {
                        $mediaStreams[basename($name)] = $data;
                    }
                }
            }

            // --- Find which sheet rId corresponds to our sheet by name ---
            // Read xl/workbook.xml to get sheet names and rId list
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            $sheetRId    = null;
            if ($workbookXml) {
                $wb = @simplexml_load_string($workbookXml);
                if ($wb) {
                    $wb->registerXPathNamespace('ss', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $sheets = $wb->xpath('//ss:sheet');
                    foreach ($sheets as $s) {
                        if ((string)$s['name'] === $sheetName) {
                            $attrs = $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                            $sheetRId = (string)($attrs['id'] ?? '');
                            break;
                        }
                    }
                }
            }

            // --- Resolve rId to actual sheet filename via xl/_rels/workbook.xml.rels ---
            $sheetFile = null;
            if ($sheetRId) {
                $wbRels = $zip->getFromName('xl/_rels/workbook.xml.rels');
                if ($wbRels) {
                    $rels = @simplexml_load_string($wbRels);
                    if ($rels) {
                        foreach ($rels->Relationship as $rel) {
                            if ((string)$rel['Id'] === $sheetRId) {
                                $sheetFile = 'xl/' . ltrim((string)$rel['Target'], '/');
                                break;
                            }
                        }
                    }
                }
            }

            // --- Resolve the drawing XML for this sheet ---
            // Sheet file is e.g. xl/worksheets/sheet2.xml
            // Its drawing rel is at xl/worksheets/_rels/sheet2.xml.rels
            $drawingXmlFiles = [];
            if ($sheetFile) {
                $sheetBaseName = basename($sheetFile);
                $sheetDir      = dirname($sheetFile);
                $sheetRelsPath = $sheetDir . '/_rels/' . $sheetBaseName . '.rels';
                $sheetRelsXml  = $zip->getFromName($sheetRelsPath);
                if ($sheetRelsXml) {
                    $sheetRels = @simplexml_load_string($sheetRelsXml);
                    if ($sheetRels) {
                        foreach ($sheetRels->Relationship as $rel) {
                            $type   = (string)$rel['Type'];
                            $target = (string)$rel['Target'];
                            if (str_ends_with($type, '/drawing')) {
                                // Normalize path: xl/worksheets/../drawings/drawingN.xml => xl/drawings/drawingN.xml
                                $drawingPath = $sheetDir . '/' . $target;
                                // Resolve ..
                                $parts = explode('/', $drawingPath);
                                $resolved = [];
                                foreach ($parts as $p) {
                                    if ($p === '..') array_pop($resolved);
                                    elseif ($p !== '.') $resolved[] = $p;
                                }
                                $drawingXmlFiles[] = implode('/', $resolved);
                            }
                        }
                    }
                }
            }

            // Fallback: use all drawing XMLs in xl/drawings/
            if (empty($drawingXmlFiles)) {
                for ($idx = 0; $idx < $zip->numFiles; $idx++) {
                    $name = $zip->getNameIndex($idx);
                    if (preg_match('#^xl/drawings/drawing\d+\.xml$#i', $name)) {
                        $drawingXmlFiles[] = $name;
                    }
                }
            }

            // --- Parse each drawing XML and extract images ---
            foreach ($drawingXmlFiles as $drawingFile) {
                $drawingXml = $zip->getFromName($drawingFile);
                if (!$drawingXml) continue;

                $drawingDir  = dirname($drawingFile);
                $drawingBase = basename($drawingFile);
                $relsPath    = $drawingDir . '/_rels/' . $drawingBase . '.rels';
                $relsMap     = [];
                $relsContent = $zip->getFromName($relsPath);
                if ($relsContent) {
                    $sxmlRels = @simplexml_load_string($relsContent);
                    if ($sxmlRels) {
                        foreach ($sxmlRels->Relationship as $rel) {
                            $relsMap[(string)$rel['Id']] = basename((string)$rel['Target']);
                        }
                    }
                }

                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $parsed = $dom->loadXML($drawingXml, LIBXML_NOERROR | LIBXML_NOWARNING);
                libxml_clear_errors();

                if (!$parsed) {
                    Log::warning("EBD Import: Could not parse drawing XML '$drawingFile'.");
                    continue;
                }

                $xpathDom = new \DOMXPath($dom);
                $xpathDom->registerNamespace('xdr', 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing');
                $xpathDom->registerNamespace('a',   'http://schemas.openxmlformats.org/drawingml/2006/main');
                $xpathDom->registerNamespace('r',   'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

                $anchors = $xpathDom->query('//*[local-name()="twoCellAnchor"] | //*[local-name()="oneCellAnchor"]');

                foreach ($anchors as $anchor) {
                    $fromNodes = $xpathDom->query('.//*[local-name()="from"]', $anchor);
                    $blipNodes = $xpathDom->query('.//*[local-name()="blip"]', $anchor);

                    if ($fromNodes->length === 0 || $blipNodes->length === 0) {
                        continue; // shape/group with no image blip
                    }

                    $fromNode = $fromNodes->item(0);
                    $colNodes = $xpathDom->query('.//*[local-name()="col"]', $fromNode);
                    $rowNodes = $xpathDom->query('.//*[local-name()="row"]', $fromNode);

                    $col = $colNodes->length > 0 ? (int)$colNodes->item(0)->textContent : -1;
                    $row = $rowNodes->length > 0 ? (int)$rowNodes->item(0)->textContent : -1;

                    $colLetter = ($col >= 0) ? Coordinate::stringFromColumnIndex($col + 1) : '?';

                    $blipNode = $blipNodes->item(0);
                    $embed = $blipNode->getAttributeNS(
                        'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                        'embed'
                    );
                    if (!$embed) {
                        $embed = $blipNode->getAttribute('r:embed');
                    }

                    $imageFile = $relsMap[$embed] ?? null;

                    if (!$imageFile || !isset($mediaStreams[$imageFile])) {
                        continue;
                    }

                    $imgBinary = $mediaStreams[$imageFile];
                    $ext       = strtolower(pathinfo($imageFile, PATHINFO_EXTENSION)) ?: 'png';

                    if (!empty($sketchCols) && in_array($col, $sketchCols)) {
                        $fileName = 'ebd/sketches/' . uniqid() . '_r' . ($row + 1) . '.' . $ext;
                        Storage::disk('public')->put($fileName, $imgBinary);
                        $images[$row]['sketch'] = $fileName;
                    } elseif (!empty($matLayoutCols) && in_array($col, $matLayoutCols)) {
                        $fileName = 'ebd/material_layouts/' . uniqid() . '_r' . ($row + 1) . '.' . $ext;
                        Storage::disk('public')->put($fileName, $imgBinary);
                        $images[$row]['material_layout'] = $fileName;
                    }
                }
            }

            $zip->close();

        } catch (\Exception $e) {
            Log::error("EBD Import: Image extraction error: " . $e->getMessage());
        }

        return $images;
    }

    /**
     * Check if a given string represents a summary/total row that should not be saved as a process/item.
     *
     * @param  string  $text
     * @return bool
     */
    protected function isTotalOrSummaryRow(string $text): bool
    {
        $clean = strtoupper(trim($text));
        if ($clean === '') return false;

        $keywords = [
            'TOTAL',
            'SUB TOTAL',
            'SUBTOTAL',
            'GRAND TOTAL',
            'GRANDTOTAL',
            'TOTAL COST',
            'TOTAL PRICE',
            'TOTAL PROCESS',
            'TOTAL TOOLING',
            'TOTAL DIE',
            'SUMMARY',
            'JUMLAH',
            'TOTAL JUMLAH'
        ];

        if (in_array($clean, $keywords)) {
            return true;
        }

        foreach ($keywords as $kw) {
            if (str_starts_with($clean, $kw . ' ') || str_starts_with($clean, $kw . ':') || str_starts_with($clean, $kw . ' -') || str_starts_with($clean, $kw . '_')) {
                return true;
            }
        }

        return false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}