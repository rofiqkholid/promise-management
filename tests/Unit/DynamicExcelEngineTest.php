<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ExcelEngine\ExcelExportEngineService;
use App\Services\ExcelEngine\ExcelImportEngineService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DynamicExcelEngineTest extends TestCase
{
    public function test_legacy_export_and_import_flow()
    {
        // 1. Create dummy sample master template spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'QUOTATION TEMPLATE');
        $sheet->setCellValue('B2', 'Part Number');
        $sheet->setCellValue('B3', 'Part Name');

        // Table headers
        $sheet->setCellValue('B10', 'Material Name');
        $sheet->setCellValue('C10', 'Weight (kg)');
        $sheet->setCellValue('B15', 'MATERIAL COST TOTAL');

        $tempTemplate = sys_get_temp_dir() . '/test_master_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempTemplate);

        // 2. Sample Legacy Mapping Config
        $mappingConfig = [
            'template_type' => 'quotation',
            'single_fields' => [
                ['field_key' => 'part_number', 'cell' => 'C2'],
                ['field_key' => 'part_name', 'cell' => 'C3']
            ],
            'table_loops' => [
                [
                    'group' => 'material',
                    'start_row' => 11,
                    'stop_condition' => [
                        'type' => 'cell_value_contains',
                        'column' => 'B',
                        'value' => 'MATERIAL COST TOTAL'
                    ],
                    'columns' => [
                        'material_name' => 'B',
                        'input_wt' => 'C'
                    ]
                ]
            ]
        ];

        // 3. Payload Data for Export Engine
        $payloadData = [
            'part_number' => 'PN-99081',
            'part_name'   => 'Bracket Engine Mount',
            'material'    => [
                ['material_name' => 'SPCC Steel 2.0mm', 'input_wt' => 3.5],
                ['material_name' => 'SUS304 Bolt M8', 'input_wt' => 0.2]
            ]
        ];

        // Test Export Engine
        $exportEngine = new ExcelExportEngineService();
        $exportedFilePath = sys_get_temp_dir() . '/test_output_' . uniqid() . '.xlsx';
        $exportEngine->export($tempTemplate, $mappingConfig, $payloadData, $exportedFilePath);

        $this->assertFileExists($exportedFilePath);

        // Test Import Engine
        $importEngine = new ExcelImportEngineService();
        $extractedPayload = $importEngine->import($exportedFilePath, $mappingConfig);

        $this->assertEquals('PN-99081', $extractedPayload['single_fields']['part_number']);
        $this->assertEquals('Bracket Engine Mount', $extractedPayload['single_fields']['part_name']);
        $this->assertCount(2, $extractedPayload['table_loops']['material']);
        $this->assertEquals('SPCC Steel 2.0mm', $extractedPayload['table_loops']['material'][0]['material_name']);

        // Clean up
        @unlink($tempTemplate);
        @unlink($exportedFilePath);
    }

    public function test_dsl_v2_with_formulas_and_footer_aggregates()
    {
        // 1. Create dummy master template spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('1. MMKI');

        $sheet->setCellValue('B2', 'Quotation No:');
        $sheet->setCellValue('B3', 'Part Number:');

        // Table header on row 12
        $sheet->setCellValue('B12', 'No');
        $sheet->setCellValue('C12', 'Part No');
        $sheet->setCellValue('D12', 'Qty');
        $sheet->setCellValue('E12', 'Unit Price');
        $sheet->setCellValue('F12', 'Total Price');

        // Template row on row 13
        $sheet->setCellValue('B13', '');
        $sheet->setCellValue('C13', '');
        $sheet->setCellValue('D13', '');
        $sheet->setCellValue('E13', '');
        $sheet->setCellValue('F13', '');

        $tempTemplate = sys_get_temp_dir() . '/test_dsl_v2_master_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempTemplate);

        // 2. DSL v2.0 Mapping Configuration
        $mappingConfig = [
            'version' => '2.0',
            'template_type' => 'tooling_quotation',
            'sheets' => [
                ['key' => 'main', 'name' => '1. MMKI']
            ],
            'fields' => [
                ['key' => 'quotation_no', 'sheet' => '1. MMKI', 'cell' => 'C2', 'format' => 'string'],
                ['key' => 'part_no', 'sheet' => '1. MMKI', 'cell' => 'C3', 'format' => 'string']
            ],
            'sections' => [
                [
                    'key' => 'ebd_items',
                    'sheet' => '1. MMKI',
                    'type' => 'repeat',
                    'data_source' => 'ebd_items',
                    'start_row' => 13,
                    'mappings' => [
                        'idx' => ['column' => 'B', 'type' => 'auto_increment'],
                        'part_no' => ['column' => 'C', 'type' => 'variable', 'format' => 'string'],
                        'qty' => ['column' => 'D', 'type' => 'variable', 'format' => 'number'],
                        'unit_price' => ['column' => 'E', 'type' => 'variable', 'format' => 'currency'],
                    ],
                    'row_formulas' => [
                        'F' => [
                            'formula' => '=D{row}*E{row}',
                            'format' => 'currency'
                        ]
                    ],
                    'footer_formulas' => [
                        'grand_total' => [
                            'target_cell' => 'F{end_row+1}',
                            'formula' => '=SUM(F{start_row}:F{end_row})',
                            'format' => 'currency'
                        ]
                    ]
                ]
            ]
        ];

        // 3. Payload with 3 items
        $payloadData = [
            'fields' => [
                'quotation_no' => 'QUO-2026-001',
                'part_no' => '51000-K01-000'
            ],
            'ebd_items' => [
                ['part_no' => 'PART-A', 'qty' => 2, 'unit_price' => 50000],
                ['part_no' => 'PART-B', 'qty' => 1, 'unit_price' => 75000],
                ['part_no' => 'PART-C', 'qty' => 4, 'unit_price' => 20000]
            ]
        ];

        $exportEngine = new ExcelExportEngineService();
        $exportedFilePath = sys_get_temp_dir() . '/test_dsl_v2_output_' . uniqid() . '.xlsx';
        $exportEngine->export($tempTemplate, $mappingConfig, $payloadData, $exportedFilePath);

        $this->assertFileExists($exportedFilePath);

        // Load the generated spreadsheet and inspect values & formulas
        $resSpreadsheet = IOFactory::load($exportedFilePath);
        $resSheet = $resSpreadsheet->getSheetByName('1. MMKI');

        // Assert single fields
        $this->assertEquals('QUO-2026-001', $resSheet->getCell('C2')->getValue());
        $this->assertEquals('51000-K01-000', $resSheet->getCell('C3')->getValue());

        // Assert row 13 (Item 1)
        $this->assertEquals(1, $resSheet->getCell('B13')->getValue());
        $this->assertEquals('PART-A', $resSheet->getCell('C13')->getValue());
        $this->assertEquals(2, $resSheet->getCell('D13')->getValue());
        $this->assertEquals(50000, $resSheet->getCell('E13')->getValue());
        $this->assertEquals('=D13*E13', $resSheet->getCell('F13')->getValue());

        // Assert row 14 (Item 2)
        $this->assertEquals(2, $resSheet->getCell('B14')->getValue());
        $this->assertEquals('PART-B', $resSheet->getCell('C14')->getValue());
        $this->assertEquals(1, $resSheet->getCell('D14')->getValue());
        $this->assertEquals(75000, $resSheet->getCell('E14')->getValue());
        $this->assertEquals('=D14*E14', $resSheet->getCell('F14')->getValue());

        // Assert row 15 (Item 3)
        $this->assertEquals(3, $resSheet->getCell('B15')->getValue());
        $this->assertEquals('PART-C', $resSheet->getCell('C15')->getValue());
        $this->assertEquals(4, $resSheet->getCell('D15')->getValue());
        $this->assertEquals(20000, $resSheet->getCell('E15')->getValue());
        $this->assertEquals('=D15*E15', $resSheet->getCell('F15')->getValue());

        // Assert Footer Aggregate Formula on row 16 (F{end_row+1} => F16)
        $this->assertEquals('=SUM(F13:F15)', $resSheet->getCell('F16')->getValue());

        // Clean up
        $resSpreadsheet->disconnectWorksheets();
        unset($resSpreadsheet);
        @unlink($tempTemplate);
        @unlink($exportedFilePath);
    }

    public function test_multiple_sections_with_row_shift_tracking()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MultiSection');

        // Section 1: rows 5-6
        $sheet->setCellValue('B4', 'Section 1: Materials');
        $sheet->setCellValue('B5', '');
        
        // Section 2: row 10
        $sheet->setCellValue('B9', 'Section 2: Labor');
        $sheet->setCellValue('B10', '');

        $tempTemplate = sys_get_temp_dir() . '/test_multi_' . uniqid() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempTemplate);

        $mappingConfig = [
            'version' => '2.0',
            'sections' => [
                [
                    'key' => 'materials',
                    'sheet' => 'MultiSection',
                    'start_row' => 5,
                    'mappings' => [
                        'name' => ['column' => 'B', 'type' => 'variable'],
                        'cost' => ['column' => 'C', 'type' => 'variable', 'format' => 'currency']
                    ]
                ],
                [
                    'key' => 'labor',
                    'sheet' => 'MultiSection',
                    'start_row' => 10,
                    'mappings' => [
                        'job'  => ['column' => 'B', 'type' => 'variable'],
                        'rate' => ['column' => 'C', 'type' => 'variable', 'format' => 'currency']
                    ]
                ]
            ]
        ];

        // Section 1 has 3 items (adds 2 extra rows, pushing section 2 from row 10 to row 12)
        $payloadData = [
            'materials' => [
                ['name' => 'Mat A', 'cost' => 100],
                ['name' => 'Mat B', 'cost' => 200],
                ['name' => 'Mat C', 'cost' => 300],
            ],
            'labor' => [
                ['job' => 'Machinist', 'rate' => 500],
                ['job' => 'Welder', 'rate' => 600]
            ]
        ];

        $exportEngine = new ExcelExportEngineService();
        $exportedFilePath = sys_get_temp_dir() . '/test_multi_out_' . uniqid() . '.xlsx';
        $exportEngine->export($tempTemplate, $mappingConfig, $payloadData, $exportedFilePath);

        $resSpreadsheet = IOFactory::load($exportedFilePath);
        $resSheet = $resSpreadsheet->getSheetByName('MultiSection');

        // Section 1 items at rows 5, 6, 7
        $this->assertEquals('Mat A', $resSheet->getCell('B5')->getValue());
        $this->assertEquals('Mat B', $resSheet->getCell('B6')->getValue());
        $this->assertEquals('Mat C', $resSheet->getCell('B7')->getValue());

        // Section 2 items shifted down to rows 12, 13
        $this->assertEquals('Machinist', $resSheet->getCell('B12')->getValue());
        $this->assertEquals('Welder', $resSheet->getCell('B13')->getValue());

        $resSpreadsheet->disconnectWorksheets();
        unset($resSpreadsheet);
        @unlink($tempTemplate);
        @unlink($exportedFilePath);
    }
}
