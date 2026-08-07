<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MngCfgSystemField;
use App\Models\MngCfgTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelTemplateController extends Controller
{
    public function index()
    {
        $templates = MngCfgTemplate::latest()->paginate(10);
        return view('management.excel-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('management.excel-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
            'template_type' => 'required|string',
            'revision'      => 'nullable|string|max:20',
            'file'          => 'required|file|mimes:xlsx',
        ]);

        $filePath = $request->file('file')->store('templates', 'public');

        $template = MngCfgTemplate::create([
            'template_name' => $request->template_name,
            'template_type' => $request->template_type,
            'revision'      => $request->revision ?? '0',
            'customer_id'   => $request->customer_id,
            'file_path'     => $filePath,
            'is_active'     => true,
            'mapping_config' => [
                'template_type' => $request->template_type,
                'single_fields' => [],
                'table_loops'   => []
            ]
        ]);

        return redirect()->route('management.excel-templates.builder', $template->id)
            ->with('success', 'Template uploaded! Proceed with visual mapping.');
    }

    public function update(Request $request, $id)
    {
        $template = MngCfgTemplate::findOrFail($id);

        $request->validate([
            'template_name' => 'required|string|max:255',
            'template_type' => 'required|string',
            'revision'      => 'nullable|string|max:20',
            'file'          => 'nullable|file|mimes:xlsx',
        ]);

        $data = [
            'template_name' => $request->template_name,
            'template_type' => $request->template_type,
            'revision'      => $request->revision ?? $template->revision ?? '0',
            'customer_id'   => $request->customer_id ?? $template->customer_id,
        ];

        // If a new master file is uploaded, delete old file from storage & save new file
        if ($request->hasFile('file')) {
            if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
                Storage::disk('public')->delete($template->file_path);
            }
            $data['file_path'] = $request->file('file')->store('templates', 'public');
        }

        $template->update($data);

        return redirect()->route('management.excel-templates.index')
            ->with('success', 'Template metadata & master file updated successfully!');
    }

    public function destroy($id)
    {
        $template = MngCfgTemplate::findOrFail($id);

        // Delete physical master excel file
        if ($template->file_path && Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        return redirect()->route('management.excel-templates.index')
            ->with('success', 'Template deleted successfully!');
    }

    public function builder(Request $request, $id)
    {
        $template = MngCfgTemplate::findOrFail($id);
        $systemFields = MngCfgSystemField::orderBy('group')->orderBy('label')->get();
        $filePath = Storage::disk('public')->path($template->file_path);
        
        $sheetIndex = (int) $request->get('sheet', 0);
        $sheetData = $this->parseExcelSheetWithStyles($filePath, $sheetIndex);

        return view('management.excel-templates.builder', array_merge(
            compact('template', 'systemFields'),
            $sheetData
        ));
    }

    public function preview(Request $request, $id)
    {
        $template = MngCfgTemplate::findOrFail($id);
        $filePath = Storage::disk('public')->path($template->file_path);
        $sheetIndex = (int) $request->get('sheet', 0);
        $sheetData = $this->parseExcelSheetWithStyles($filePath, $sheetIndex);

        return response()->json([
            'template' => $template,
            'sheetNames' => $sheetData['sheetNames'],
            'activeSheetIndex' => $sheetData['activeSheetIndex'],
            'gridData' => $sheetData['gridData'],
            'colWidths' => $sheetData['colWidths'],
            'rowHeights' => $sheetData['rowHeights'],
            'mergedCells' => $sheetData['mergedCells'],
            'images' => $sheetData['images'],
        ]);
    }

    /**
     * Parse Excel Spreadsheet with Full Styles, Widths, Heights, Merged Ranges, and Images
     */
    private function parseExcelSheetWithStyles(string $filePath, int $sheetIndex = 0): array
    {
        $gridData = [];
        $colWidths = [];
        $rowHeights = [];
        $mergedCells = [];
        $images = [];
        $sheetNames = [];
        $activeSheetIndex = $sheetIndex;

        if (file_exists($filePath)) {
            $spreadsheet = IOFactory::load($filePath);
            $sheetNames = $spreadsheet->getSheetNames();

            if (!isset($sheetNames[$sheetIndex])) {
                $sheetIndex = 0;
            }
            $activeSheetIndex = $sheetIndex;
            $sheet = $spreadsheet->getSheet($sheetIndex);
            
            $highestRow = min($sheet->getHighestRow(), 500);
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            // Allow up to 500 columns for ultra-wide future templates
            $highestColumnIndex = min($highestColumnIndex, 500);

            // 1. Column Widths
            for ($c = 1; $c <= $highestColumnIndex; $c++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $dim = $sheet->getColumnDimension($colLetter);
                $width = $dim->getWidth();
                // Default width conversion
                $colWidths[$colLetter] = ($width > 0) ? round($width * 8.5) : 85;
            }

            // 2. Merged Cells details (colspan & rowspan)
            $mergeMap = [];
            foreach ($sheet->getMergeCells() as $mergeRange) {
                $mergedCells[] = $mergeRange;
                $range = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::splitRange($mergeRange);
                $firstCell = $range[0][0];
                $lastCell = $range[0][1];

                $firstCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace('/[0-9]/', '', $firstCell));
                $firstRow = (int)preg_replace('/[A-Z]/', '', $firstCell);

                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace('/[0-9]/', '', $lastCell));
                $lastRow = (int)preg_replace('/[A-Z]/', '', $lastCell);

                $colSpan = $lastCol - $firstCol + 1;
                $rowSpan = $lastRow - $firstRow + 1;

                $mergeMap[$firstCell] = [
                    'colspan' => $colSpan,
                    'rowspan' => $rowSpan,
                ];

                // Mark covered slave cells so they can be hidden in table HTML, but save master cell reference
                for ($r = $firstRow; $r <= $lastRow; $r++) {
                    for ($c = $firstCol; $c <= $lastCol; $c++) {
                        $cellCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c) . $r;
                        if ($cellCoord !== $firstCell) {
                            $mergeMap[$cellCoord] = [
                                'hidden' => true,
                                'master' => $firstCell
                            ];
                        }
                    }
                }
            }

            // 3. Drawings / Embedded Images
            foreach ($sheet->getDrawingCollection() as $drawing) {
                if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
                    $coordinates = $drawing->getCoordinates();
                    $path = $drawing->getPath();
                    if (file_exists($path)) {
                        $imageData = base64_encode(file_get_contents($path));
                        $mime = mime_content_type($path) ?: 'image/png';
                        $images[] = [
                            'cell' => $coordinates,
                            'src' => "data:{$mime};base64,{$imageData}",
                            'width' => $drawing->getWidth(),
                            'height' => $drawing->getHeight(),
                        ];
                    }
                }
            }

            // ── Border helper (defined once, outside the cell loop) ────────────────
            $parseSideBorder = function($borderObject) {
                $styleType = $borderObject->getBorderStyle();
                if (!$styleType || $styleType === \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                    return ['active' => false, 'css' => ''];
                }
                if ($styleType === \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR) {
                    return ['active' => false, 'css' => ''];
                }
                $rgb = $borderObject->getColor()->getRGB();
                $color = ($rgb) ? '#' . $rgb : '#000000';
                switch ($styleType) {
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN:        $w = '1px'; $s = 'solid';  break;
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED:      $w = '1px'; $s = 'dotted'; break;
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DASHED:      $w = '1px'; $s = 'dashed'; break;
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM:
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUMDASHED: $w = '2px'; $s = 'solid'; break;
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK:        $w = '3px'; $s = 'solid'; break;
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE:       $w = '3px'; $s = 'double'; break;
                    default:                                                           $w = '1px'; $s = 'solid'; break;
                }
                return ['active' => true, 'css' => "{$w} {$s} {$color}"];
            };

            // 4. Grid Cells — read own data & borders only (no neighbor calls here)
            for ($r = 1; $r <= $highestRow; $r++) {
                $rowDim = $sheet->getRowDimension($r);
                $height = $rowDim->getRowHeight();
                $rowHeights[$r] = ($height > 0) ? round($height * 1.3) : 26;

                $rowCells = [];
                for ($c = 1; $c <= $highestColumnIndex; $c++) {
                    $colLetter  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $coordinate = $colLetter . $r;

                    // Read cell value FIRST before any style/collection changes
                    $cell           = $sheet->getCell($coordinate);
                    $rawValue       = (string)$cell->getValue();
                    $isFormula      = str_starts_with($rawValue, '=');
                    $formattedValue = $rawValue;
                    try { $formattedValue = (string)$cell->getFormattedValue(); } catch (\Throwable $e) {}
                    unset($cell); // release before getStyle to avoid stale reference

                    $style = $sheet->getStyle($coordinate);

                    // Fill color
                    $fillColor = null;
                    $fill = $style->getFill();
                    if ($fill->getFillType() !== \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE) {
                        $fc = $fill->getStartColor()->getRGB();
                        if ($fc && $fc !== 'FFFFFF' && $fc !== '000000') $fillColor = '#' . $fc;
                    }

                    // Font
                    $font      = $style->getFont();
                    $fontColor = $font->getColor()->getRGB() ? '#' . $font->getColor()->getRGB() : null;
                    $isBold    = $font->getBold();
                    $isItalic  = $font->getItalic();
                    $fontSize  = $font->getSize() ? round($font->getSize()) : 11;
                    $align     = $style->getAlignment()->getHorizontal();

                    // Own borders
                    $borders = [
                        'top'    => $parseSideBorder($style->getBorders()->getTop()),
                        'bottom' => $parseSideBorder($style->getBorders()->getBottom()),
                        'left'   => $parseSideBorder($style->getBorders()->getLeft()),
                        'right'  => $parseSideBorder($style->getBorders()->getRight()),
                    ];

                    $rowCells[] = [
                        'col'        => $colLetter,
                        'row'        => $r,
                        'cell'       => $coordinate,
                        'value'      => $formattedValue,
                        'is_formula' => $isFormula,
                        'fill_color' => $fillColor,
                        'font_color' => $fontColor,
                        'is_bold'    => $isBold,
                        'is_italic'  => $isItalic,
                        'font_size'  => $fontSize,
                        'align'      => $align,
                        'borders'    => $borders,
                        'merge'      => $mergeMap[$coordinate] ?? null,
                    ];
                }
                $gridData[] = $rowCells;
            }

            // ── Pure-PHP second pass: propagate borders from adjacent cells ──────────
            // Build a flat map: coordinate → [ri, ci] index for O(1) lookups
            $coordIndex = [];
            foreach ($gridData as $ri => $row) {
                foreach ($row as $ci => $cellData) {
                    $coordIndex[$cellData['cell']] = [$ri, $ci];
                }
            }

            $Coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::class;

            foreach ($gridData as $ri => &$row) {
                foreach ($row as $ci => &$cellData) {
                    $col = $cellData['col'];
                    $rowNum = $cellData['row'];

                    // TOP: inherit from bottom of the cell above
                    if (!$cellData['borders']['top']['active'] && $rowNum > 1) {
                        $aboveKey = $col . ($rowNum - 1);
                        if (isset($coordIndex[$aboveKey])) {
                            [$ari, $aci] = $coordIndex[$aboveKey];
                            if ($gridData[$ari][$aci]['borders']['bottom']['active']) {
                                $cellData['borders']['top'] = $gridData[$ari][$aci]['borders']['bottom'];
                            }
                        }
                    }

                    // LEFT: inherit from right of the cell to the left
                    if (!$cellData['borders']['left']['active'] && $ci > 0) {
                        $nb = $row[$ci - 1]['borders']['right'] ?? null;
                        if ($nb && $nb['active']) $cellData['borders']['left'] = $nb;
                    }

                    // BOTTOM: inherit from top of the cell below
                    if (!$cellData['borders']['bottom']['active'] && $rowNum < $highestRow) {
                        $belowKey = $col . ($rowNum + 1);
                        if (isset($coordIndex[$belowKey])) {
                            [$bri, $bci] = $coordIndex[$belowKey];
                            if ($gridData[$bri][$bci]['borders']['top']['active']) {
                                $cellData['borders']['bottom'] = $gridData[$bri][$bci]['borders']['top'];
                            }
                        }
                    }

                    // RIGHT: inherit from left of the cell to the right
                    if (!$cellData['borders']['right']['active'] && $ci < count($row) - 1) {
                        $nb = $row[$ci + 1]['borders']['left'] ?? null;
                        if ($nb && $nb['active']) $cellData['borders']['right'] = $nb;
                    }
                }
            }
            unset($row, $cellData);
        }


        return [
            'sheetNames' => $sheetNames,
            'activeSheetIndex' => $activeSheetIndex,
            'gridData' => $gridData,
            'colWidths' => $colWidths,
            'rowHeights' => $rowHeights,
            'mergedCells' => $mergedCells,
            'images' => $images,
        ];
    }

    public function saveMapping(Request $request, $id)
    {
        $template = MngCfgTemplate::findOrFail($id);
        
        $request->validate([
            'mapping_config' => 'required|array'
        ]);

        $template->mapping_config = $request->mapping_config;
        $template->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Visual Mapping Configuration saved successfully!'
        ]);
    }
}
