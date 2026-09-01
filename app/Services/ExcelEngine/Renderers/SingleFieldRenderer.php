<?php

namespace App\Services\ExcelEngine\Renderers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SingleFieldRenderer
{
    /**
     * Render single fields into the spreadsheet
     *
     * @param Spreadsheet $spreadsheet
     * @param array $fields List of field definitions
     * @param array $payload Resolved data payload
     * @param array $sheetNameMap Key => sheet name map
     */
    public function render(Spreadsheet $spreadsheet, array $fields, array $payload, array $sheetNameMap = [], array $conditionalRules = []): void
    {
        foreach ($fields as $fieldDef) {
            $fieldKey = $fieldDef['key'] ?? $fieldDef['field_key'] ?? null;
            $cellCoordinate = $fieldDef['cell'] ?? null;

            if (!$cellCoordinate) {
                continue;
            }

            // Determine target worksheet
            $targetSheet = $this->resolveWorksheet($spreadsheet, $fieldDef, $sheetNameMap);
            if (!$targetSheet) {
                continue;
            }

            // Determine value
            $value = null;
            if (($fieldDef['type'] ?? '') === 'static' || ($fieldDef['value_type'] ?? '') === 'static' || isset($fieldDef['static_value'])) {
                $value = $fieldDef['static_value'] ?? '';
            } elseif ($fieldKey) {
                $value = $this->resolveValueFromPayload($fieldKey, $payload);
            }

            // Apply fallback if value is null
            if ($value === null && isset($fieldDef['fallback'])) {
                $value = $fieldDef['fallback'];
            }

            if ($value === null && !isset($fieldDef['fallback'])) {
                continue;
            }

            // Apply formatting and inject into cell
            $format = $fieldDef['format'] ?? $fieldDef['render_type'] ?? 'default';
            $this->applyValueAndFormat($targetSheet, $cellCoordinate, $value, $format, $fieldDef);
        }

        // Evaluate single-cell conditional rules
        if (!empty($conditionalRules)) {
            $this->renderConditionalRules($spreadsheet, $conditionalRules, $payload, $sheetNameMap);
        }
    }

    /**
     * Render single fields directly onto a specific worksheet instance
     */
    public function renderOnSheet(Worksheet $sheet, array $fields, array $payload, array $conditionalRules = []): void
    {
        foreach ($fields as $fieldDef) {
            $fieldKey = $fieldDef['key'] ?? $fieldDef['field_key'] ?? null;
            $cellCoordinate = $fieldDef['cell'] ?? null;

            if (!$cellCoordinate) {
                continue;
            }

            $value = null;
            if (($fieldDef['type'] ?? '') === 'static' || ($fieldDef['value_type'] ?? '') === 'static' || isset($fieldDef['static_value'])) {
                $value = $fieldDef['static_value'] ?? '';
            } elseif ($fieldKey) {
                $value = $this->resolveValueFromPayload($fieldKey, $payload);
            }

            if ($value === null && isset($fieldDef['fallback'])) {
                $value = $fieldDef['fallback'];
            }

            if ($value === null && !isset($fieldDef['fallback'])) {
                continue;
            }

            $format = $fieldDef['format'] ?? $fieldDef['render_type'] ?? 'default';
            $this->applyValueAndFormat($sheet, $cellCoordinate, $value, $format, $fieldDef);
        }

        if (!empty($conditionalRules)) {
            $this->renderConditionalRulesOnSheet($sheet, $conditionalRules, $payload);
        }
    }

    /**
     * Evaluate conditional rules for single cells across workbook
     */
    protected function renderConditionalRules(Spreadsheet $spreadsheet, array $conditionalRules, array $payload, array $sheetNameMap): void
    {
        foreach ($conditionalRules as $rule) {
            $targetSheet = $this->resolveWorksheet($spreadsheet, $rule, $sheetNameMap);
            if (!$targetSheet) {
                continue;
            }
            $targetCell = $rule['target_cell'] ?? null;
            if (!$targetCell || !preg_match('/^[A-Z]+[0-9]+$/i', $targetCell)) {
                continue;
            }
            $this->evaluateRuleOnSheet($targetSheet, $rule, $payload);
        }
    }

    /**
     * Evaluate conditional rules for single cells on specific worksheet
     */
    protected function renderConditionalRulesOnSheet(Worksheet $sheet, array $conditionalRules, array $payload): void
    {
        foreach ($conditionalRules as $rule) {
            $targetCell = $rule['target_cell'] ?? null;
            if (!$targetCell || !preg_match('/^[A-Z]+[0-9]+$/i', $targetCell)) {
                continue;
            }
            $this->evaluateRuleOnSheet($sheet, $rule, $payload);
        }
    }

    /**
     * Evaluate a single multi-branch rule on a worksheet
     */
    protected function evaluateRuleOnSheet(Worksheet $sheet, array $rule, array $payload): void
    {
        $targetCell = $rule['target_cell'];
        $format = $rule['render_type'] ?? 'default';

        $branches = [];
        $branches[] = [
            'field_key' => $rule['field_key'] ?? '',
            'operator' => $rule['operator'] ?? 'equals',
            'value' => $rule['value'] ?? '',
            'conditions' => $rule['conditions'] ?? [],
            'output_type' => $rule['output_type'] ?? 'field_value',
            'output_field_key' => $rule['output_field_key'] ?? '',
            'output_static_value' => $rule['output_static_value'] ?? '',
        ];
        if (!empty($rule['branches']) && is_array($rule['branches'])) {
            foreach ($rule['branches'] as $b) {
                $branches[] = [
                    'field_key' => $b['field_key'] ?? ($rule['field_key'] ?? ''),
                    'operator' => $b['operator'] ?? 'equals',
                    'value' => $b['value'] ?? '',
                    'conditions' => $b['conditions'] ?? [],
                    'output_type' => $b['output_type'] ?? 'field_value',
                    'output_field_key' => $b['output_field_key'] ?? '',
                    'output_static_value' => $b['output_static_value'] ?? '',
                ];
            }
        }

        $matched = false;
        foreach ($branches as $branch) {
            $srcKey = $branch['field_key'] ?? '';
            $actualVal = $srcKey ? $this->resolveValueFromPayload($srcKey, $payload) : null;
            $op = $branch['operator'] ?? 'equals';
            $expectedVal = $branch['value'] ?? '';

            $branchMatches = $this->evaluateCondition($actualVal, $op, $expectedVal);

            if (!empty($branch['conditions']) && is_array($branch['conditions'])) {
                foreach ($branch['conditions'] as $sub) {
                    $gate = strtoupper($sub['logic_gate'] ?? 'AND');
                    $subKey = $sub['field_key'] ?? '';
                    $subOp = $sub['operator'] ?? 'equals';
                    $subExpected = $sub['value'] ?? '';

                    $subActual = $subKey ? $this->resolveValueFromPayload($subKey, $payload) : null;
                    $subMatched = $this->evaluateCondition($subActual, $subOp, $subExpected);

                    if ($gate === 'OR') {
                        $branchMatches = ($branchMatches || $subMatched);
                    } else {
                        $branchMatches = ($branchMatches && $subMatched);
                    }
                }
            }

            if ($branchMatches) {
                $matched = true;
                $outputType = $branch['output_type'] ?? 'field_value';
                if ($outputType === 'static_value') {
                    $outVal = $branch['output_static_value'] ?? '';
                } else {
                    $outField = $branch['output_field_key'] ?? '';
                    $outVal = $outField ? $this->resolveValueFromPayload($outField, $payload) : null;
                }

                if ($outVal !== null) {
                    $this->applyValueAndFormat($sheet, $targetCell, $outVal, $format, $rule);
                }
                break;
            }
        }

        if (!$matched && isset($rule['else_static_value']) && $rule['else_static_value'] !== '') {
            $this->applyValueAndFormat($sheet, $targetCell, $rule['else_static_value'], $format, $rule);
        }
    }

    /**
     * Evaluate comparison condition
     */
    protected function evaluateCondition($sourceVal, string $operator, $targetVal): bool
    {
        $src = (string)($sourceVal ?? '');
        $tgt = (string)($targetVal ?? '');

        return match ($operator) {
            'equals', '==' => strcasecmp($src, $tgt) === 0,
            'not_equals', '!=' => strcasecmp($src, $tgt) !== 0,
            'contains' => stripos($src, $tgt) !== false,
            'starts_with' => str_starts_with(strtolower($src), strtolower($tgt)),
            'ends_with' => str_ends_with(strtolower($src), strtolower($tgt)),
            'greater_than', '>' => (float)$src > (float)$tgt,
            'greater_equal', '>=' => (float)$src >= (float)$tgt,
            'less_than', '<' => (float)$src < (float)$tgt,
            'less_equal', '<=' => (float)$src <= (float)$tgt,
            'is_empty' => empty($sourceVal) && $sourceVal !== '0' && $sourceVal !== 0,
            'is_not_empty' => !empty($sourceVal) || $sourceVal === '0' || $sourceVal === 0,
            default => strcasecmp($src, $tgt) === 0,
        };
    }

    /**
     * Resolve worksheet from sheet name, key, or index
     */
    protected function resolveWorksheet(Spreadsheet $spreadsheet, array $fieldDef, array $sheetNameMap): ?Worksheet
    {
        // 1. By explicit sheet name or sheet key
        if (!empty($fieldDef['sheet'])) {
            $sheetIdentifier = $fieldDef['sheet'];
            $actualSheetName = $sheetNameMap[$sheetIdentifier] ?? $sheetIdentifier;
            $sheet = $spreadsheet->getSheetByName($actualSheetName);
            if ($sheet) {
                return $sheet;
            }
        }

        // 2. By sheet index
        if (isset($fieldDef['sheet_index'])) {
            $sheet = $spreadsheet->getSheet((int)$fieldDef['sheet_index']);
            if ($sheet) {
                return $sheet;
            }
        }

        return $spreadsheet->getActiveSheet();
    }

    /**
     * Extract value from payload by key or dot-notation
     */
    public function resolveValueFromPayload(string $fieldKey, array $payload)
    {
        // Direct root key
        if (array_key_exists($fieldKey, $payload)) {
            return $payload[$fieldKey];
        }

        // Inside 'fields' sub-array
        if (isset($payload['fields']) && is_array($payload['fields']) && array_key_exists($fieldKey, $payload['fields'])) {
            return $payload['fields'][$fieldKey];
        }

        // Inside 'single_fields' sub-array (backward compatibility)
        if (isset($payload['single_fields']) && is_array($payload['single_fields']) && array_key_exists($fieldKey, $payload['single_fields'])) {
            return $payload['single_fields'][$fieldKey];
        }

        // Nested dot notation (e.g. customer.name)
        if (str_contains($fieldKey, '.')) {
            $segments = explode('.', $fieldKey);
            $curr = $payload;
            foreach ($segments as $seg) {
                if (is_array($curr) && array_key_exists($seg, $curr)) {
                    $curr = $curr[$seg];
                } else {
                    return null;
                }
            }
            return $curr;
        }

        // Fallback: If not found at root, check first item of items / ebd_items / details array
        $listKeys = ['items', 'ebd_items', 'cost_comparison_items', 'details'];
        foreach ($listKeys as $listKey) {
            if (!empty($payload[$listKey]) && is_array($payload[$listKey])) {
                $firstItem = reset($payload[$listKey]);
                if (is_array($firstItem) && array_key_exists($fieldKey, $firstItem)) {
                    return $firstItem[$fieldKey];
                }
            }
        }

        return null;
    }

    /**
     * Apply value and number format / drawing to target cell
     */
    public function applyValueAndFormat(Worksheet $sheet, string $cellCoordinate, $value, string $format, array $options = []): void
    {
        // Image / Signature rendering
        if ($format === 'image' || $format === 'signature') {
            if (!empty($value) && is_string($value) && (file_exists($value) || file_exists(public_path($value)) || file_exists(storage_path('app/public/' . $value)))) {
                $realPath = file_exists($value) ? $value : (file_exists(public_path($value)) ? public_path($value) : storage_path('app/public/' . $value));

                $drawing = new Drawing();
                $drawing->setName('EmbeddedImage');
                $drawing->setDescription('Image');
                $drawing->setPath($realPath);
                $drawing->setCoordinates($cellCoordinate);

                $imgSize = $options['image_size'] ?? '120x50';
                $dims = explode('x', strtolower($imgSize));
                if (count($dims) === 2) {
                    $drawing->setWidth((int)$dims[0]);
                    $drawing->setHeight((int)$dims[1]);
                } else {
                    $drawing->setHeight(50);
                }

                $drawing->setWorksheet($sheet);
                return;
            }
        }

        // Standard value setter with formatting
        $cell = $sheet->getCell($cellCoordinate);

        if ($value === null || $value === '') {
            $cell->setValueExplicit('', DataType::TYPE_STRING);
            return;
        }

        switch (strtolower($format)) {
            case 'currency':
                $numericVal = is_numeric($value) ? (float)$value : (float)str_replace([',', ' '], '', $value);
                $cell->setValueExplicit($numericVal, DataType::TYPE_NUMERIC);
                $currencyFormat = $options['currency_format'] ?? '_($* #,##0_);_($* (#,##0);_($* "-"_);_(@_)';
                $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode($currencyFormat);
                break;

            case 'number':
            case 'numeric':
                if ($value !== null) {
                    if (is_numeric($value)) {
                        $num = (float)$value;
                        $cell->setValueExplicit($num, DataType::TYPE_NUMERIC);
                        if (isset($options['decimal_places'])) {
                            $decimals = (int)$options['decimal_places'];
                            $numFormat = $decimals > 0 ? '#,##0.' . str_repeat('0', $decimals) : '#,##0';
                        } else {
                            $numFormat = (floor($num) == $num) ? '#,##0' : '#,##0.##';
                        }
                        $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode($numFormat);
                    } else {
                        $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
                    }
                }
                break;

            case 'percentage':
            case 'percent':
                if ($value !== null) {
                    $numericVal = is_numeric($value) ? (float)$value : (float)str_replace(['%', ' '], '', $value);
                    if ($numericVal > 1) {
                        $numericVal = $numericVal / 100;
                    }
                    $cell->setValueExplicit($numericVal, DataType::TYPE_NUMERIC);
                    if (isset($options['decimal_places'])) {
                        $decimals = (int)$options['decimal_places'];
                        $pctFormat = $decimals > 0 ? '0.' . str_repeat('0', $decimals) . '%' : '0%';
                    } else {
                        $pctVal100 = $numericVal * 100;
                        $pctFormat = (floor($pctVal100) == $pctVal100) ? '0%' : '0.##%';
                    }
                    $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode($pctFormat);
                }
                break;

            case 'date':
                if ($value !== null) {
                    if (!empty($value)) {
                        $timestamp = is_numeric($value) ? (int)$value : strtotime((string)$value);
                        if ($timestamp !== false) {
                            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timestamp);
                            $cell->setValueExplicit($excelDate, DataType::TYPE_NUMERIC);
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
                            break;
                        }
                    }
                    $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
                }
                $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
                break;

            case 'string':
            case 'text':
                if ($value !== null) {
                    $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
                }
                break;

            default:
                if ($value !== null) {
                    if (is_numeric($value)) {
                        $num = (float)$value;
                        $cell->setValueExplicit($num, DataType::TYPE_NUMERIC);
                        if (floor($num) == $num) {
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode('#,##0');
                        } else {
                            $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode('#,##0.##');
                        }
                    } elseif (is_bool($value)) {
                        $cell->setValueExplicit($value, DataType::TYPE_BOOL);
                    } else {
                        $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
                    }
                }
                break;
        }
    }

    /**
     * Apply number format / style without modifying the existing cell formula or value
     */
    public function applyFormatOnly(Worksheet $sheet, string $cellCoordinate, string $format, array $options = []): void
    {
        switch (strtolower($format)) {
            case 'currency':
                $currencyFormat = $options['currency_format'] ?? '_($* #,##0_);_($* (#,##0);_($* "-"_);_(@_)';
                $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode($currencyFormat);
                break;

            case 'number':
            case 'numeric':
                if (isset($options['decimal_places'])) {
                    $decimals = (int)$options['decimal_places'];
                    $numFormat = $decimals > 0 ? '#,##0.' . str_repeat('0', $decimals) : '#,##0';
                } else {
                    $numFormat = '#,##0.##';
                }
                $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode($numFormat);
                break;

            case 'percentage':
            case 'percent':
                if (isset($options['decimal_places'])) {
                    $decimals = (int)$options['decimal_places'];
                    $pctFormat = $decimals > 0 ? '0.' . str_repeat('0', $decimals) . '%' : '0%';
                } else {
                    $pctFormat = '0.##%';
                }
                $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode($pctFormat);
                break;

            case 'date':
                $sheet->getStyle($cellCoordinate)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
                break;
        }
    }
}
