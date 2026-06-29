<?php

namespace App\Imports;

use App\Models\InquiryProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class InquiryProductImport implements ToCollection, WithHeadingRow
{
    protected $inquiryId;
    protected $importedCount = 0;
    protected $errors = [];
    protected $headingRow = 5;

    public function __construct($inquiryId, $file = null)
    {
        $this->inquiryId = $inquiryId;
        if ($file) {
            $this->headingRow = $this->detectHeadingRow($file);
        }
    }

    private function detectHeadingRow($file)
    {
        try {
            $path = is_string($file) ? $file : $file->getRealPath();
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Check first 10 rows
            for ($row = 1; $row <= 10; $row++) {
                $cols = [];
                for ($col = 1; $col <= 15; $col++) {
                    $val = $worksheet->getCell([$col, $row])->getValue();
                    if ($val !== null && $val !== '') {
                        $cols[] = strtolower(trim($val));
                    }
                }
                
                // Check if this row looks like the header row
                if (in_array('part num', $cols) || in_array('part no', $cols) || in_array('part name', $cols)) {
                    return $row;
                }
            }
        } catch (\Exception $e) {
            // Fallback to default
        }
        return 5;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function collection(Collection $rows)
    {
        $rowIndex = $this->headingRow;
        
        foreach ($rows as $row) {
            $rowIndex++;
            
            // Skip empty rows
            if (empty(array_filter($row->toArray()))) {
                continue;
            }

            // Normalization
            $partNo   = $row['part_num'] ?? null;
            $partName = $row['part_name'] ?? null;
            
            $validator = Validator::make($row->toArray(), [
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
                'annual_volume' => !empty($row['volumey']) ? (int) $row['volumey'] : (!empty($row['volume_y']) ? (int) $row['volume_y'] : null),
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

    private function autoAssess($product, $row)
    {
        $categoryMappings = [
            'customer_priority' => $row['customer_priority'] ?? null,
            'volume_potential' => $row['volume_potential'] ?? null,
            'product_type' => $row['type_product'] ?? null,
            'technical_capability' => $row['technical_capability'] ?? null,
            'investment_requirement' => $row['investment'] ?? null,
        ];

        // Filter out empty category values
        $activeMappings = array_filter($categoryMappings);
        if (empty($activeMappings)) {
            return;
        }

        $optionIds = [];
        $totalScore = 0;

        foreach ($activeMappings as $categoryCode => $optionValue) {
            $category = \App\Models\ScoreCategory::where('category_code', $categoryCode)->first();
            if (!$category) continue;

            // Look up corresponding option
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

        // Find ranking mapping
        $ranking = \App\Models\AssessmentRanking::where('min_score', '<=', $totalScore)
            ->where('max_score', '>=', $totalScore)
            ->where('is_active', true)
            ->first();

        // Create the assessment
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
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
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
