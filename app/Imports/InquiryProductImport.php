<?php

namespace App\Imports;

use ZipArchive;
use SimpleXMLElement;
use Carbon\Carbon;
use App\Models\InquiryProduct;
use Illuminate\Support\Facades\Validator;

class InquiryProductImport
{
    protected $inquiryId;
    protected $importedCount = 0;
    protected $errors = [];

    public function __construct($inquiryId)
    {
        $this->inquiryId = $inquiryId;
    }

    /**
     * Parse the XLSX file using native PHP ZipArchive and XML Parser.
     */
    public function import($filePath)
    {
        $rows = $this->parseXlsx($filePath);
        if (empty($rows)) {
            $this->errors[] = [
                'row' => 0,
                'errors' => ['Failed to read Excel file or file is empty.']
            ];
            return;
        }

        // Find header row (1-indexed)
        $headerRowIndex = 0;
        $headers = [];
        foreach ($rows as $index => $row) {
            $cols = array_map('strtolower', array_map('trim', $row));
            if (in_array('part num', $cols) || in_array('part no', $cols) || in_array('part name', $cols)) {
                $headerRowIndex = $index;
                $headers = $row;
                break;
            }
        }

        if ($headerRowIndex === 0) {
            // Fallback to row 5 if not found
            $headerRowIndex = min(5, count($rows));
            $headers = $rows[$headerRowIndex - 1] ?? [];
        }

        // Clean headers: convert to slug-like keys
        $cleanHeaders = [];
        foreach ($headers as $colIdx => $h) {
            $hClean = strtolower(trim($h));
            // Replace non-alphanumeric with underscores
            $hClean = preg_replace('/[^a-z0-9]/', '_', $hClean);
            $hClean = preg_replace('/_+/', '_', $hClean);
            $hClean = trim($hClean, '_');

            // Alias mapping
            if ($hClean === 'part_num' || $hClean === 'part_no') {
                $hClean = 'part_num';
            } elseif ($hClean === 'sop_date' || $hClean === 'sop') {
                $hClean = 'sop';
            } elseif ($hClean === 'eol_date' || $hClean === 'eol') {
                $hClean = 'eol';
            } elseif ($hClean === 'volume_y' || $hClean === 'volumey' || $hClean === 'volume' || $hClean === 'annual_volume') {
                $hClean = 'volumey';
            }
            $cleanHeaders[$colIdx] = $hClean;
        }

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $rowIndex = $i + 1;
            $rawRow = $rows[$i];
            
            // Skip empty rows
            if (empty(array_filter($rawRow))) {
                continue;
            }

            // Map row to headers
            $row = [];
            foreach ($cleanHeaders as $colIdx => $headerKey) {
                if ($headerKey !== '') {
                    $row[$headerKey] = $rawRow[$colIdx] ?? null;
                }
            }

            $partNo   = $row['part_num'] ?? null;
            $partName = $row['part_name'] ?? null;
            
            $validator = Validator::make($row, [
                'part_num'  => 'required',
                'part_name' => 'required',
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $rowIndex,
                    'errors' => $validator->errors()->all()
                ];
                continue;
            }

            // Parse Dates
            $sopDate = $this->parseDate($row['sop'] ?? null);
            $eolDate = $this->parseDate($row['eol'] ?? null);

            // Boolean Conversion
            $has2d = $this->parseBoolean($row['2d_data'] ?? null);
            $has3d = $this->parseBoolean($row['3d_data'] ?? null);
            $hasTech = $this->parseBoolean($row['tech_doc'] ?? null);

            // Duplicate Check in the same Inquiry (Part Number and Variant)
            $variant = isset($row['variant']) ? trim($row['variant']) : null;
            $duplicate = InquiryProduct::where('inquiry_id', $this->inquiryId)
                ->where('customer_part_no', $partNo)
                ->where('variant', $variant)
                ->exists();

            if ($duplicate) {
                $this->errors[] = [
                    'row' => $rowIndex,
                    'errors' => ["Duplicate Part Number '{$partNo}' with Variant '{$variant}' found in this Inquiry."]
                ];
                continue;
            }

            // Save Product
            $product = InquiryProduct::create([
                'inquiry_id' => $this->inquiryId,
                'customer_part_no' => $partNo,
                'customer_part_name' => $partName,
                'part_category' => $row['part_category'] ?? null,
                'destination' => $row['destination'] ?? null,
                'sop_date' => $sopDate,
                'eol_date' => $eolDate,
                'model_life' => !empty($row['model_life']) ? (int) $row['model_life'] : null,
                'annual_volume' => !empty($row['volumey']) ? (int) $row['volumey'] : null,
                'has_2d_data' => $has2d,
                'has_3d_data' => $has3d,
                'has_tech_doc' => $hasTech,
                'variant' => $variant,
                'remarks' => $row['remarks'] ?? null,
            ]);

            // Auto assessment from Excel if scoring columns are populated
            $this->autoAssess($product, $row);

            $this->importedCount++;
        }
    }

    private function parseXlsx($filePath)
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Get Shared Strings
        $sharedStrings = [];
        $stringsEntry = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsEntry) {
            $xml = simplexml_load_string($stringsEntry);
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string)$si->t;
                } elseif (isset($si->r)) {
                    // Rich text parts
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string)$r->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }

        // 2. Parse Sheet1
        $sheetEntry = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetEntry) {
            $zip->close();
            return [];
        }

        $xml = simplexml_load_string($sheetEntry);
        $rows = [];

        foreach ($xml->sheetData->row as $rowNode) {
            $rowIndex = (int)$rowNode['r'] - 1;
            $rowCells = [];

            foreach ($rowNode->c as $cell) {
                $ref = (string)$cell['r'];
                // Get column index from cell reference e.g. "A1" -> 0, "B3" -> 1
                preg_match('/^[A-Z]+/', $ref, $matches);
                $colStr = $matches[0];
                $colIdx = $this->columnLetterToIndex($colStr);

                $val = (string)$cell->v;
                $type = (string)$cell['t'];

                if ($type === 's' && isset($sharedStrings[(int)$val])) {
                    $value = $sharedStrings[(int)$val];
                } else {
                    $value = $val;
                }

                $rowCells[$colIdx] = $value;
            }

            // Fill missing columns in between
            $maxIdx = count($rowCells) > 0 ? max(array_keys($rowCells)) : 0;
            for ($c = 0; $c <= $maxIdx; $c++) {
                if (!isset($rowCells[$c])) {
                    $rowCells[$c] = '';
                }
            }
            ksort($rowCells);
            $rows[$rowIndex] = $rowCells;
        }

        // Fill empty rows in between
        $maxRowIdx = count($rows) > 0 ? max(array_keys($rows)) : 0;
        for ($r = 0; $r <= $maxRowIdx; $r++) {
            if (!isset($rows[$r])) {
                $rows[$r] = [];
            }
        }
        ksort($rows);

        $zip->close();
        return $rows;
    }

    private function columnLetterToIndex($letter)
    {
        $len = strlen($letter);
        $idx = 0;
        for ($i = 0; $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letter[$i]) - 64);
        }
        return $idx - 1;
    }

    private function autoAssess($product, $row)
    {
        $categoryMappings = [
            'customer_priority' => $row['customer_priority'] ?? null,
            'volume_potential' => $row['volume_potential'] ?? null,
            'product_type' => $row['type_product'] ?? null,
            'technical_capability' => $row['technical_capability'] ?? null,
            'investment_requirement' => $row['investment'] ?? null,
        ];

        $activeMappings = array_filter($categoryMappings);
        if (empty($activeMappings)) {
            return;
        }

        $optionIds = [];
        $totalScore = 0;

        foreach ($activeMappings as $categoryCode => $optionValue) {
            $category = \App\Models\ScoreCategory::where('category_code', $categoryCode)->first();
            if (!$category) continue;

            $option = \App\Models\ScoreOption::where('category_id', $category->id)
                ->where(function($query) use ($optionValue) {
                    $query->where('option_name', 'like', $optionValue)
                          ->orWhere('description', 'like', $optionValue);
                })
                ->first();

            if ($option) {
                $optionIds[] = $option;
                $totalScore += $option->score_value;
            }
        }

        if (empty($optionIds)) {
            return;
        }

        $ranking = \App\Models\AssessmentRanking::where('min_score', '<=', $totalScore)
            ->where('max_score', '>=', $totalScore)
            ->where('is_active', true)
            ->first();

        $assessment = \App\Models\PriorityAssessment::create([
            'inquiry_product_id' => $product->id,
            'total_score' => $totalScore,
            'ranking_id' => $ranking ? $ranking->id : null,
            'action' => $row['action'] ?? 'Accept',
            'assessed_by' => 'Excel Import',
            'assessed_at' => now(),
        ]);

        foreach ($optionIds as $option) {
            \App\Models\PriorityAssessmentDetail::create([
                'assessment_id' => $assessment->id,
                'category_id' => $option->category_id,
                'option_id' => $option->id,
                'score_snapshot' => $option->score_value,
            ]);
        }
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;
        if (is_numeric($value)) {
            try {
                // Excel base date is 1900-01-01
                $utc_days = $value - 25569;
                $utc_value = $utc_days * 86400;
                return date('Y-m-d', $utc_value);
            } catch (\Exception $e) {
                return null;
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseBoolean($value)
    {
        if (empty($value)) return false;
        $val = strtolower(trim($value));
        return in_array($val, ['yes', 'y', '1', 'true', 'ya']);
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
