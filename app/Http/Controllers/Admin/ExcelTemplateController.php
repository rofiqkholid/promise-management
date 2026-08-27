<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MngCfgSystemField;
use App\Models\MngCfgTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelTemplateController extends Controller
{
    public function index()
    {
        $templates = MngCfgTemplate::with('customer')->latest()->paginate(10);
        $customers = Customer::orderBy('name', 'asc')->get();
        return view('management.excel-templates.index', compact('templates', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
            'template_type' => 'required|string',
            'direction'     => 'nullable|string|in:export,import',
            'customer_id'   => 'nullable|exists:customers,id',
            'revision'      => 'nullable|string|max:20',
            'file'          => 'required|file|mimes:xlsx',
        ]);

        $filePath = $request->file('file')->store('templates', 'public');
        $direction = $request->direction ?? 'export';

        $template = MngCfgTemplate::create([
            'template_name' => $request->template_name,
            'template_type' => $request->template_type,
            'direction'     => $direction,
            'revision'      => $request->revision ?? '0',
            'customer_id'   => $request->customer_id,
            'file_path'     => $filePath,
            'is_active'     => true,
            'mapping_config' => [
                'template_type' => $request->template_type,
                'direction'     => $direction,
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
            'direction'     => 'nullable|string|in:export,import',
            'customer_id'   => 'nullable|exists:customers,id',
            'revision'      => 'nullable|string|max:20',
            'file'          => 'nullable|file|mimes:xlsx',
        ]);

        $data = [
            'template_name' => $request->template_name,
            'template_type' => $request->template_type,
            'direction'     => $request->direction ?? $template->direction ?? 'export',
            'revision'      => $request->revision ?? $template->revision ?? '0',
            'customer_id'   => $request->has('customer_id') ? ($request->filled('customer_id') ? $request->customer_id : null) : $template->customer_id,
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

    public function duplicate($id)
    {
        $original = MngCfgTemplate::findOrFail($id);

        $newFilePath = null;
        if ($original->file_path && Storage::disk('public')->exists($original->file_path)) {
            $ext = pathinfo($original->file_path, PATHINFO_EXTENSION);
            $newFilePath = 'templates/' . \Illuminate\Support\Str::uuid() . '.' . $ext;
            Storage::disk('public')->copy($original->file_path, $newFilePath);
        }

        $duplicate = MngCfgTemplate::create([
            'template_name'  => $original->template_name . ' (Copy)',
            'template_type'  => $original->template_type,
            'direction'      => $original->direction,
            'revision'       => $original->revision ? $original->revision . '.copy' : '0.copy',
            'customer_id'    => $original->customer_id,
            'file_path'      => $newFilePath,
            'is_active'      => false,
            'mapping_config' => $original->mapping_config,
        ]);

        return redirect()->route('management.excel-templates.index')
            ->with('success', "Template '{$original->template_name}' successfully duplicated!");
    }

    public function toggleStatus($id)
    {
        $template = MngCfgTemplate::findOrFail($id);
        $template->is_active = !$template->is_active;
        $template->save();

        $statusLabel = $template->is_active ? 'activated' : 'deactivated';
        return redirect()->route('management.excel-templates.index')
            ->with('success', "Template '{$template->template_name}' successfully {$statusLabel}!");
    }

    public function builder(Request $request, $id)
    {
        $template = MngCfgTemplate::findOrFail($id);
        $systemFields = Cache::remember('excel_builder_system_fields', 3600, function() {
            return MngCfgSystemField::orderBy('group')->orderBy('label')->get();
        });
        $groupedFields = $systemFields->groupBy('group');
        $filePath = Storage::disk('public')->path($template->file_path);
        
        $sheetIndex = (int) $request->get('sheet', 0);
        $fileMtime = file_exists($filePath) ? filemtime($filePath) : 0;
        $cacheKey = "excel_builder_sheet_{$template->id}_{$sheetIndex}_{$fileMtime}";

        $sheetData = Cache::remember($cacheKey, 1800, function () use ($filePath, $sheetIndex) {
            return $this->parseExcelSheetWithStyles($filePath, $sheetIndex);
        });

        return view('management.excel-templates.builder', array_merge(
            compact('template', 'systemFields', 'groupedFields'),
            $sheetData
        ));
    }

    public function preview(Request $request, $id)
    {
        $template = MngCfgTemplate::findOrFail($id);
        $filePath = Storage::disk('public')->path($template->file_path);
        $sheetIndex = (int) $request->get('sheet', 0);
        $fileMtime = file_exists($filePath) ? filemtime($filePath) : 0;
        $cacheKey = "excel_builder_sheet_{$template->id}_{$sheetIndex}_{$fileMtime}";

        $sheetData = Cache::remember($cacheKey, 1800, function () use ($filePath, $sheetIndex) {
            return $this->parseExcelSheetWithStyles($filePath, $sheetIndex);
        });

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
            // Store merge range metadata for border post-processing
            $mergeRangeMeta = []; // keyed by firstCell => [firstRow, lastRow, firstCol, lastCol, lastColLetter, lastRowLetter]
            foreach ($sheet->getMergeCells() as $mergeRange) {
                $mergedCells[] = $mergeRange;
                $range = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::splitRange($mergeRange);
                $firstCell = $range[0][0];
                $lastCell = $range[0][1];

                $firstColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace('/[0-9]/', '', $firstCell));
                $firstRow = (int)preg_replace('/[A-Z]/', '', $firstCell);

                $lastColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(preg_replace('/[0-9]/', '', $lastCell));
                $lastRow = (int)preg_replace('/[A-Z]/', '', $lastCell);

                $colSpan = $lastColIdx - $firstColIdx + 1;
                $rowSpan = $lastRow - $firstRow + 1;

                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIdx);
                $firstColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($firstColIdx);

                $mergeMap[$firstCell] = [
                    'colspan' => $colSpan,
                    'rowspan' => $rowSpan,
                ];

                $mergeRangeMeta[$firstCell] = [
                    'firstRow'       => $firstRow,
                    'lastRow'        => $lastRow,
                    'firstColIdx'    => $firstColIdx,
                    'lastColIdx'     => $lastColIdx,
                    'firstColLetter' => $firstColLetter,
                    'lastColLetter'  => $lastColLetter,
                ];

                // Mark covered slave cells so they can be hidden in table HTML
                for ($r = $firstRow; $r <= $lastRow; $r++) {
                    for ($c = $firstColIdx; $c <= $lastColIdx; $c++) {
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

            // 3. Drawings / Embedded Images (PhpSpreadsheet collection & Dynamic XLSX XML drawing parser)
            try {
                $drawingCollection = $sheet->getDrawingCollection();
                foreach ($drawingCollection as $drawing) {
                    $coordinates = method_exists($drawing, 'getCoordinates') ? $drawing->getCoordinates() : 'A1';
                    $width = method_exists($drawing, 'getWidth') ? $drawing->getWidth() : 100;
                    $height = method_exists($drawing, 'getHeight') ? $drawing->getHeight() : 100;
                    $offsetX = method_exists($drawing, 'getOffsetX') ? $drawing->getOffsetX() : 0;
                    $offsetY = method_exists($drawing, 'getOffsetY') ? $drawing->getOffsetY() : 0;
                    $src = null;

                    if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
                        $path = $drawing->getPath();
                        if (!empty($path) && file_exists($path)) {
                            $imageData = base64_encode(file_get_contents($path));
                            $mime = @mime_content_type($path) ?: 'image/png';
                            $src = "data:{$mime};base64,{$imageData}";
                        }
                    } elseif ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                        $imageRes = $drawing->getImageResource();
                        if ($imageRes) {
                            ob_start();
                            switch ($drawing->getRenderingFunction()) {
                                case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::RENDERING_JPEG:
                                    imagejpeg($imageRes);
                                    $mime = 'image/jpeg';
                                    break;
                                case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::RENDERING_GIF:
                                    imagegif($imageRes);
                                    $mime = 'image/gif';
                                    break;
                                case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::RENDERING_PNG:
                                default:
                                    imagepng($imageRes);
                                    $mime = 'image/png';
                                    break;
                            }
                            $imageData = base64_encode(ob_get_clean());
                            $src = "data:{$mime};base64,{$imageData}";
                        }
                    } elseif (method_exists($drawing, 'getPath')) {
                        $path = $drawing->getPath();
                        if (!empty($path) && file_exists($path)) {
                            $imageData = base64_encode(file_get_contents($path));
                            $mime = @mime_content_type($path) ?: 'image/png';
                            $src = "data:{$mime};base64,{$imageData}";
                        }
                    }

                    if ($src) {
                        $images[] = [
                            'cell'     => $coordinates,
                            'src'      => $src,
                            'width'    => $width,
                            'height'   => $height,
                            'offsetX'  => $offsetX,
                            'offsetY'  => $offsetY,
                        ];
                    }
                }

                // If getDrawingCollection returned no images, parse XLSX drawings XML files dynamically
                if (empty($images) && class_exists('\ZipArchive')) {
                    $zip = new \ZipArchive();
                    if ($zip->open($filePath) === true) {
                        // 1. Collect all media files (e.g. xl/media/image1.png => rId mapping or path)
                        $mediaStreams = [];
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $name = $zip->getNameIndex($i);
                            if (str_starts_with($name, 'xl/media/')) {
                                $stream = $zip->getStream($name);
                                if ($stream) {
                                    $data = stream_get_contents($stream);
                                    fclose($stream);
                                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                                    $mime = match (strtolower($ext)) {
                                        'png'  => 'image/png',
                                        'jpg', 'jpeg' => 'image/jpeg',
                                        'gif'  => 'image/gif',
                                        'svg'  => 'image/svg+xml',
                                        default => 'image/png',
                                    };
                                    $mediaStreams[basename($name)] = "data:{$mime};base64," . base64_encode($data);
                                }
                            }
                        }

                        // 2. Iterate all xl/drawings/drawing*.xml files inside the zip
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            if (preg_match('#^xl/drawings/drawing\d+\.xml$#i', $filename)) {
                                $drawingXml = $zip->getFromName($filename);
                                $dir = dirname($filename);
                                $baseName = basename($filename);
                                $relsPath = $dir . '/_rels/' . $baseName . '.rels';
                                
                                $relsMap = [];
                                $relsContent = $zip->getFromName($relsPath);
                                if ($relsContent) {
                                    $sxmlRels = @simplexml_load_string($relsContent);
                                    if ($sxmlRels) {
                                        foreach ($sxmlRels->Relationship as $rel) {
                                            $rId = (string)$rel['Id'];
                                            $target = (string)$rel['Target'];
                                            $relsMap[$rId] = basename($target);
                                        }
                                    }
                                }

                                if ($drawingXml) {
                                    // Remove XML namespaces to simplify SimpleXML parsing across versions
                                    $cleanXmlStr = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $drawingXml);
                                    $cleanXmlStr = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $cleanXmlStr);
                                    
                                    $sxml = @simplexml_load_string($cleanXmlStr);
                                    if ($sxml) {
                                        $anchors = array_merge(
                                            $sxml->xpath('//twoCellAnchor') ?: [],
                                            $sxml->xpath('//oneCellAnchor') ?: []
                                        );

                                        foreach ($anchors as $anchor) {
                                            if (isset($anchor->from)) {
                                                $col = (int)$anchor->from->col + 1; // 0-indexed to 1-indexed
                                                $row = (int)$anchor->from->row + 1;
                                                $colOff = (int)($anchor->from->colOff ?? 0);
                                                $rowOff = (int)($anchor->from->rowOff ?? 0);

                                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                                                $cellCoord = $colLetter . $row;

                                                // Find blip embed Id
                                                $blips = $anchor->xpath('.//blip');
                                                if (!empty($blips)) {
                                                    $embedId = null;
                                                    foreach ($blips[0]->attributes() as $attrName => $attrVal) {
                                                        if (str_contains(strtolower($attrName), 'embed') || $attrName === 'id') {
                                                            $embedId = (string)$attrVal;
                                                            break;
                                                        }
                                                    }

                                                    $imageFilename = $embedId ? ($relsMap[$embedId] ?? null) : null;
                                                    // Fallback if rels map direct match or single media stream
                                                    if (!$imageFilename && !empty($mediaStreams)) {
                                                        $imageFilename = array_key_first($mediaStreams);
                                                    }

                                                    if ($imageFilename && isset($mediaStreams[$imageFilename])) {
                                                        $widthPx = 120;
                                                        $heightPx = 45;

                                                        $exts = $anchor->xpath('.//ext');
                                                        if (!empty($exts)) {
                                                            foreach ($exts as $extNode) {
                                                                $cx = (int)($extNode['cx'] ?? 0);
                                                                $cy = (int)($extNode['cy'] ?? 0);
                                                                if ($cx > 0) $widthPx = round($cx / 9525);
                                                                if ($cy > 0) $heightPx = round($cy / 9525);
                                                                if ($cx > 0 && $cy > 0) break;
                                                            }
                                                        }

                                                        $offsetX = ($colOff > 0) ? round($colOff / 9525) : 0;
                                                        $offsetY = ($rowOff > 0) ? round($rowOff / 9525) : 0;

                                                        $images[] = [
                                                            'cell'    => $cellCoord,
                                                            'src'     => $mediaStreams[$imageFilename],
                                                            'width'   => $widthPx,
                                                            'height'  => $heightPx,
                                                            'offsetX' => $offsetX,
                                                            'offsetY' => $offsetY,
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Ultimate fallback: if drawing XML wasn't matched but xl/media/ has images
                        if (empty($images) && !empty($mediaStreams)) {
                            $fallbackCells = ['A1', 'J1', 'M1'];
                            $idx = 0;
                            foreach ($mediaStreams as $fileName => $srcData) {
                                $targetCell = $fallbackCells[$idx] ?? 'A1';
                                $images[] = [
                                    'cell'    => $targetCell,
                                    'src'     => $srcData,
                                    'width'   => 120,
                                    'height'  => 45,
                                    'offsetX' => 5,
                                    'offsetY' => 2,
                                ];
                                $idx++;
                            }
                        }

                        $zip->close();
                    }
                }
            } catch (\Throwable $e) {
                // Ignore drawing parse errors gracefully
            }

            // ── Border helper (defined once, outside the cell loop) ────────────────
            $parseSideBorder = function($borderObject) {
                $styleType = $borderObject->getBorderStyle();
                if (!$styleType || $styleType === \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                    return ['active' => false, 'css' => ''];
                }
                
                $colorObj = $borderObject->getColor();
                $rgb = $colorObj ? $colorObj->getRGB() : null;
                $argb = $colorObj ? $colorObj->getARGB() : null;

                // Handle ARGB color or default dark border
                if ($rgb && strtoupper($rgb) !== 'FFFFFF' && strtoupper($rgb) !== '000000') {
                    $color = '#' . $rgb;
                } elseif ($argb && strlen($argb) === 8 && substr($argb, 2) !== 'FFFFFF') {
                    $color = '#' . substr($argb, 2);
                } else {
                    // Default border color matching Excel's standard gridlines/borders
                    $color = '#000000';
                }

                switch ($styleType) {
                    case \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_HAIR:
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
                    $font       = $style->getFont();
                    $fontColor  = $font->getColor()->getRGB() ? '#' . $font->getColor()->getRGB() : null;
                    $fontFamily = $font->getName() ?: 'Segoe UI, Calibri, sans-serif';
                    $isBold     = $font->getBold();
                    $isItalic   = $font->getItalic();
                    $fontSize   = $font->getSize() ? round($font->getSize(), 1) : 11;
                    $alignment  = $style->getAlignment();
                    $align      = $alignment->getHorizontal();
                    $valign     = $alignment->getVertical();
                    $wrapText   = $alignment->getWrapText();
                    $textRotation = $alignment->getTextRotation();

                    // Own borders
                    $borders = [
                        'top'    => $parseSideBorder($style->getBorders()->getTop()),
                        'bottom' => $parseSideBorder($style->getBorders()->getBottom()),
                        'left'   => $parseSideBorder($style->getBorders()->getLeft()),
                        'right'  => $parseSideBorder($style->getBorders()->getRight()),
                    ];

                    $rowCells[] = [
                        'col'           => $colLetter,
                        'row'           => $r,
                        'cell'          => $coordinate,
                        'value'         => $formattedValue,
                        'is_formula'    => $isFormula,
                        'raw_formula'   => $isFormula ? $rawValue : null,
                        'fill_color'    => $fillColor,
                        'font_color'    => $fontColor,
                        'font_family'   => $fontFamily,
                        'font_size'     => $fontSize,
                        'is_bold'       => $isBold,
                        'is_italic'     => $isItalic,
                        'align'         => $align,
                        'valign'        => $valign,
                        'wrap_text'     => $wrapText,
                        'text_rotation' => $textRotation,
                        'borders'       => $borders,
                        'merge'         => $mergeMap[$coordinate] ?? null,
                    ];
                }
                $gridData[] = $rowCells;
            }

            // ── Pure-PHP second pass: propagate borders ──────────────────────────────
            // Build a flat map: coordinate → [ri, ci] index for O(1) lookups
            $coordIndex = [];
            foreach ($gridData as $ri => $row) {
                foreach ($row as $ci => $cellData) {
                    $coordIndex[$cellData['cell']] = [$ri, $ci];
                }
            }

            // A. For each merged range, read REAL borders from all 4 outer edges
            //    (left of firstCol, right of lastCol, top of firstRow, bottom of lastRow)
            //    and assign them to the master cell so it renders with the correct borders.
            foreach ($mergeRangeMeta as $masterCoord => $meta) {
                if (!isset($coordIndex[$masterCoord])) continue;
                [$mri, $mci] = $coordIndex[$masterCoord];

                $fRow = $meta['firstRow'];
                $lRow = $meta['lastRow'];
                $fColIdx = $meta['firstColIdx'];
                $lColIdx = $meta['lastColIdx'];
                $fColLetter = $meta['firstColLetter'];
                $lColLetter = $meta['lastColLetter'];

                // TOP border: read from master cell itself (firstRow, firstCol)
                // — already in master, no action needed

                // LEFT border: read from firstCol cells of the range (master cell handles this)
                // — already in master, no action needed

                // RIGHT border: read from lastCol cells of the range — get from last slave col
                // Look at the actual style from PhpSpreadsheet for each right-edge cell
                $rightBorder = $gridData[$mri][$mci]['borders']['right'];
                for ($r2 = $fRow; $r2 <= $lRow; $r2++) {
                    $edgeCoord = $lColLetter . $r2;
                    $edgeStyle = $sheet->getStyle($edgeCoord)->getBorders()->getRight();
                    $parsed = $parseSideBorder($edgeStyle);
                    if ($parsed['active']) {
                        $rightBorder = $parsed;
                        break;
                    }
                }
                $gridData[$mri][$mci]['borders']['right'] = $rightBorder;

                // BOTTOM border: read from lastRow cells of the range
                $bottomBorder = $gridData[$mri][$mci]['borders']['bottom'];
                for ($c2 = $fColIdx; $c2 <= $lColIdx; $c2++) {
                    $edgeCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c2) . $lRow;
                    $edgeStyle = $sheet->getStyle($edgeCoord)->getBorders()->getBottom();
                    $parsed = $parseSideBorder($edgeStyle);
                    if ($parsed['active']) {
                        $bottomBorder = $parsed;
                        break;
                    }
                }
                $gridData[$mri][$mci]['borders']['bottom'] = $bottomBorder;
            }

            // B. Propagate borders from adjacent neighboring cells (for non-merged cells that
            //    only define border on one side)
            foreach ($gridData as $ri => &$row) {
                foreach ($row as $ci => &$cellData) {
                    // Skip hidden slave merge cells
                    if (!empty($cellData['merge']['hidden'])) continue;

                    $col = $cellData['col'];
                    $rowNum = $cellData['row'];

                    // TOP: inherit from bottom of the cell above
                    if (!$cellData['borders']['top']['active'] && $rowNum > 1) {
                        $aboveKey = $col . ($rowNum - 1);
                        if (isset($coordIndex[$aboveKey])) {
                            [$ari, $aci] = $coordIndex[$aboveKey];
                            $aboveCell = $gridData[$ari][$aci];
                            // If above cell is slave, use its master
                            if (!empty($aboveCell['merge']['hidden']) && isset($coordIndex[$aboveCell['merge']['master']])) {
                                [$ari, $aci] = $coordIndex[$aboveCell['merge']['master']];
                            }
                            if ($gridData[$ari][$aci]['borders']['bottom']['active']) {
                                $cellData['borders']['top'] = $gridData[$ari][$aci]['borders']['bottom'];
                            }
                        }
                    }

                    // LEFT: inherit from right of the cell to the left
                    if (!$cellData['borders']['left']['active'] && $ci > 0) {
                        // Walk left to find nearest non-hidden cell
                        for ($li = $ci - 1; $li >= 0; $li--) {
                            $leftCell = $row[$li];
                            if (!empty($leftCell['merge']['hidden'])) {
                                // Use master
                                if (isset($coordIndex[$leftCell['merge']['master']])) {
                                    [$lri, $lci] = $coordIndex[$leftCell['merge']['master']];
                                    $leftCell = $gridData[$lri][$lci];
                                }
                            }
                            if ($leftCell['borders']['right']['active']) {
                                $cellData['borders']['left'] = $leftCell['borders']['right'];
                            }
                            break;
                        }
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
                        for ($ri2 = $ci + 1; $ri2 < count($row); $ri2++) {
                            $rightCell = $row[$ri2];
                            if (!empty($rightCell['merge']['hidden'])) continue;
                            if ($rightCell['borders']['left']['active']) {
                                $cellData['borders']['right'] = $rightCell['borders']['left'];
                            }
                            break;
                        }
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
        try {
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
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error: ' . implode(', ', $ve->validator->errors()->all()),
                'errors' => $ve->validator->errors()
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to save mapping for template {$id}: " . $e->getMessage(), [
                'exception' => $e
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save mapping: ' . $e->getMessage()
            ], 500);
        }
    }
}
