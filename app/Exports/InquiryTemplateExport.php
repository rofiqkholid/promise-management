<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InquiryTemplateExport implements FromArray, WithHeadings, WithCustomStartCell, WithStyles
{
    public function headings(): array
    {
        return [
            'No',
            'Part Num',
            'Part Category',
            'Part Name',
            'Destination',
            'SOP',
            'EOL',
            'Model Life',
            'Volume/y',
            'Variant',
            '2d data',
            '3d data',
            'tech doc',
            'customer priority',
            'volume potential',
            'type product',
            'technical capability',
            'investment',
            'customer score',
            'volume score',
            'capability score',
            'investment score',
            'total score',
            'rank',
            'action'
        ];
    }

    public function array(): array
    {
        return [
            [
                '1',
                '89661-0D310',
                'Engine',
                'Computer, Engine Control',
                'Domestic',
                '2026-07-01',
                '2031-06-30',
                '5',
                '12000',
                'RHD',
                'Yes',
                'Yes',
                'Yes',
                'Existing',
                'Tinggi',
                'Similar',
                'Available',
                'Rendah',
                '',
                '',
                '',
                '',
                '',
                '',
                'Accept'
            ]
        ];
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function styles(Worksheet $sheet)
    {
        // Add title on Row 2
        $sheet->mergeCells('A2:Y2');
        $sheet->setCellValue('A2', 'PROJECT INQUIRY PRODUCTS IMPORT TEMPLATE');
        
        $sheet->mergeCells('A3:Y3');
        $sheet->setCellValue('A3', 'Fill your data starting on row 6. Headers are on row 5. Values for scoring categories (cols P-T) must match system options.');

        return [
            2 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            3 => [
                'font' => ['italic' => true, 'size' => 10],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ],
            5 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD']
                ]
            ]
        ];
    }
}
