<?php

namespace App\Services\ExcelEngine;

use App\Services\ExcelEngine\Core\RowShiftTracker;
use App\Services\ExcelEngine\Core\StyleCloner;
use App\Services\ExcelEngine\Renderers\FormulaCompiler;
use App\Services\ExcelEngine\Renderers\SingleFieldRenderer;
use App\Services\ExcelEngine\Renderers\TableLoopRenderer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExportEngineService
{
    protected SingleFieldRenderer $singleFieldRenderer;
    protected TableLoopRenderer $tableLoopRenderer;
    protected FormulaCompiler $formulaCompiler;
    protected StyleCloner $styleCloner;
    protected RowShiftTracker $shiftTracker;

    public function __construct(
        ?SingleFieldRenderer $singleFieldRenderer = null,
        ?TableLoopRenderer $tableLoopRenderer = null,
        ?FormulaCompiler $formulaCompiler = null,
        ?StyleCloner $styleCloner = null,
        ?RowShiftTracker $shiftTracker = null
    ) {
        $this->singleFieldRenderer = $singleFieldRenderer ?? new SingleFieldRenderer();
        $this->formulaCompiler = $formulaCompiler ?? new FormulaCompiler();
        $this->styleCloner = $styleCloner ?? new StyleCloner();
        $this->shiftTracker = $shiftTracker ?? new RowShiftTracker();
        $this->tableLoopRenderer = $tableLoopRenderer ?? new TableLoopRenderer(
            $this->styleCloner,
            $this->formulaCompiler,
            $this->singleFieldRenderer
        );
    }

    /**
     * Inject data array into master .xlsx template and return file output path
     *
     * @param string $templatePath Full path to master .xlsx template
     * @param array $mappingConfig Dynamic mapping JSON structure from database
     * @param array $payloadData Data payload (contains fields/single_fields and sections/table_loops data)
     * @param string|null $outputPath Optional destination save path
     * @return string Path to generated file
     */
    public function export(string $templatePath, array $mappingConfig, array $payloadData, ?string $outputPath = null): string
    {
        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Master template file not found: {$templatePath}");
        }

        $spreadsheet = IOFactory::load($templatePath);
        $this->shiftTracker->reset();

        // 1. Build Sheet Name Map (Key => Name)
        $sheetNameMap = [];
        if (!empty($mappingConfig['sheets']) && is_array($mappingConfig['sheets'])) {
            foreach ($mappingConfig['sheets'] as $s) {
                if (isset($s['key']) && isset($s['name'])) {
                    $sheetNameMap[$s['key']] = $s['name'];
                }
            }
        }

        $singleFields = $mappingConfig['single_fields'] ?? $mappingConfig['fields'] ?? [];
        $sections = $mappingConfig['table_loops'] ?? $mappingConfig['sections'] ?? [];
        $conditionalRules = $mappingConfig['conditional_rules'] ?? $mappingConfig['conditions'] ?? [];
        $conditions = $mappingConfig['conditions'] ?? $mappingConfig['conditional_renders'] ?? [];

        // Check if 1 Sheet Tab Per Parent Item is enabled
        $isSplitSheet = false;
        $sheetLoopSection = null;
        foreach ($sections as $sec) {
            if (!empty($sec['split_sheet_per_parent']) || !empty($sec['sheet_loop'])) {
                $isSplitSheet = true;
                $sheetLoopSection = $sec;
                break;
            }
        }

        $items = $payloadData['items'] ?? $payloadData['ebd_items'] ?? $payloadData['cost_comparison_items'] ?? [];

        if ($isSplitSheet && !empty($items) && is_array($items) && count($items) > 0) {
            // Mode: 1 Sheet Tab Per Parent Item
            $this->exportSplitSheetPerParent(
                $spreadsheet,
                $items,
                $sheetLoopSection,
                $mappingConfig,
                $payloadData,
                $singleFields,
                $sections,
                $conditionalRules,
                $conditions
            );
        } else {
            // Mode: Standard Single/Multi Section Export
            if (!empty($singleFields) && is_array($singleFields)) {
                $this->singleFieldRenderer->render($spreadsheet, $singleFields, $payloadData, $sheetNameMap, $conditionalRules);
            }

            if (!empty($sections) && is_array($sections)) {
                $this->tableLoopRenderer->render($spreadsheet, $sections, $payloadData, $this->shiftTracker, $sheetNameMap, $conditionalRules);
            }

            if (!empty($conditions) && is_array($conditions)) {
                $this->evaluateConditions($spreadsheet, $conditions, $payloadData, $sheetNameMap);
            }
        }

        // Generate Output File
        if (empty($outputPath)) {
            $tempDir = sys_get_temp_dir();
            $prefix = 'export_' . ($mappingConfig['template_type'] ?? 'doc') . '_' . date('YmdHis') . '_';
            $outputPath = tempnam($tempDir, $prefix) . '.xlsx';
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);

        // Free memory
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $outputPath;
    }

    /**
     * Export with 1 Sheet Tab per Parent Item
     */
    protected function exportSplitSheetPerParent(
        Spreadsheet $spreadsheet,
        array $items,
        array $sheetLoopSection,
        array $mappingConfig,
        array $payloadData,
        array $singleFields,
        array $sections,
        array $conditionalRules,
        array $conditions
    ): void {
        $targetSheetIndex = $sheetLoopSection['sheet_index'] ?? null;
        $targetSheetName = $sheetLoopSection['sheet_name'] ?? null;

        $masterSheet = null;
        if (!empty($targetSheetName) && $spreadsheet->sheetNameExists($targetSheetName)) {
            $masterSheet = $spreadsheet->getSheetByName($targetSheetName);
        } elseif ($targetSheetIndex !== null && is_numeric($targetSheetIndex) && (int)$targetSheetIndex < $spreadsheet->getSheetCount()) {
            $masterSheet = $spreadsheet->getSheet((int)$targetSheetIndex);
        } else {
            $masterSheet = $spreadsheet->getSheet(0);
        }

        $masterSheetTitle = $masterSheet->getTitle();
        $masterSheetIdx = $spreadsheet->getIndex($masterSheet);

        // Helper to check if a rule/section/field belongs to the split master sheet
        $belongsToSplitSheet = function ($item) use ($masterSheetTitle, $masterSheetIdx) {
            if (!empty($item['split_sheet_per_parent']) || !empty($item['sheet_loop'])) {
                return true;
            }
            $sName = $item['sheet_name'] ?? null;
            if ($sName !== null && $sName === $masterSheetTitle) {
                return true;
            }
            $sIdx = $item['sheet_index'] ?? $item['sheet'] ?? null;
            if ($sIdx !== null && is_numeric($sIdx) && (int)$sIdx === $masterSheetIdx) {
                return true;
            }
            return false;
        };

        // Partition sections
        $splitSections = [];
        $nonSplitSections = [];
        foreach ($sections as $sec) {
            if ($belongsToSplitSheet($sec)) {
                $splitSections[] = $sec;
            } else {
                $nonSplitSections[] = $sec;
            }
        }

        // Partition single fields
        $splitSingleFields = [];
        $nonSplitSingleFields = [];
        foreach ($singleFields as $field) {
            if ($belongsToSplitSheet($field)) {
                $splitSingleFields[] = $field;
            } else {
                $nonSplitSingleFields[] = $field;
            }
        }

        // Partition conditional rules
        $splitConditionalRules = [];
        $nonSplitConditionalRules = [];
        foreach ($conditionalRules as $rule) {
            if ($belongsToSplitSheet($rule)) {
                $splitConditionalRules[] = $rule;
            } else {
                $nonSplitConditionalRules[] = $rule;
            }
        }

        $sheetNameMap = [];
        if (!empty($mappingConfig['sheets']) && is_array($mappingConfig['sheets'])) {
            foreach ($mappingConfig['sheets'] as $s) {
                if (isset($s['key']) && isset($s['name'])) {
                    $sheetNameMap[$s['key']] = $s['name'];
                }
            }
        }

        // 1. Render all non-split sheets first using global payload
        if (!empty($nonSplitSingleFields)) {
            $this->singleFieldRenderer->render($spreadsheet, $nonSplitSingleFields, $payloadData, $sheetNameMap, $nonSplitConditionalRules);
        }
        if (!empty($nonSplitSections)) {
            $this->tableLoopRenderer->render($spreadsheet, $nonSplitSections, $payloadData, $this->shiftTracker, $sheetNameMap, $nonSplitConditionalRules);
        }

        // 2. Clone master sheet for each parent item and render split sections
        $clonedTemplate = clone $masterSheet;
        $nameField = $sheetLoopSection['sheet_name_field'] ?? 'part_no';
        $items = array_values($items);

        $usedTitles = [];
        // Reserve existing sheet names in spreadsheet to prevent collisions
        foreach ($spreadsheet->getSheetNames() as $sn) {
            if ($sn !== $masterSheetTitle) {
                $usedTitles[strtolower($sn)] = true;
            }
        }

        foreach ($items as $p => $parentItem) {
            // Parent specific payload
            $parentPayload = array_merge($payloadData, $parentItem, [
                'items' => [$parentItem],
                'ebd_items' => [$parentItem],
                'cost_comparison_items' => [$parentItem]
            ]);

            // Determine unique clean sheet title (max 26 chars, no forbidden chars)
            $rawTitle = (string)($parentItem[$nameField] ?? $parentItem['part_no'] ?? $parentItem['part_name'] ?? ('Item ' . ($p + 1)));
            $cleanTitle = substr(preg_replace('/[\/\\\\\\?\\*:\\[\\]]/', '_', trim($rawTitle)), 0, 26);
            if (empty($cleanTitle)) $cleanTitle = 'Item ' . ($p + 1);

            $uniqueTitle = $cleanTitle;
            $counter = 1;
            while (isset($usedTitles[strtolower($uniqueTitle)])) {
                $uniqueTitle = $cleanTitle . ' (' . $counter++ . ')';
            }
            $usedTitles[strtolower($uniqueTitle)] = true;

            if ($p === 0) {
                $currentSheet = $masterSheet;
                $currentSheet->setTitle($uniqueTitle);
            } else {
                $currentSheet = clone $clonedTemplate;
                $currentSheet->setTitle($uniqueTitle);
                $spreadsheet->addSheet($currentSheet);
            }

            // Render single fields for this sheet
            if (!empty($splitSingleFields)) {
                $this->singleFieldRenderer->renderOnSheet($currentSheet, $splitSingleFields, $parentPayload);
            }

            // Render loop for this single parent item
            $sheetTracker = new RowShiftTracker();
            $this->tableLoopRenderer->renderOnSheet($currentSheet, $splitSections, $parentPayload, $sheetTracker, $splitConditionalRules);
        }
    }

    /**
     * Backward-compatible helper to resolve single field value
     */
    public function resolveSingleFieldValue(string $fieldKey, array $payloadData)
    {
        return $this->singleFieldRenderer->resolveValueFromPayload($fieldKey, $payloadData);
    }

    /**
     * Evaluate conditional render rules
     */
    protected function evaluateConditions(Spreadsheet $spreadsheet, array $conditions, array $payload, array $sheetNameMap): void
    {
        foreach ($conditions as $cond) {
            $type = $cond['type'] ?? '';
            $sheetIdentifier = $cond['sheet'] ?? null;

            if ($type === 'hide_sheet' && $sheetIdentifier) {
                $actualName = $sheetNameMap[$sheetIdentifier] ?? $sheetIdentifier;
                $sheet = $spreadsheet->getSheetByName($actualName);
                if ($sheet && $spreadsheet->getSheetCount() > 1) {
                    $sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
                }
            }
        }
    }
}
