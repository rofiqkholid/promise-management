<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ExcelEngine\ExcelExportEngineService;
use App\Services\ExcelEngine\ExcelImportEngineService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DynamicExcelEngineTest extends TestCase
{
    public function test_export_and_import_flow()
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

        $tempTemplate = sys_get_temp_dir() . '/test_master.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempTemplate);

        // 2. Sample Mapping Config
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
        $exportedFilePath = sys_get_temp_dir() . '/test_output.xlsx';
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
}
