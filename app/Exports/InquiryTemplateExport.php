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
            'Customer',
            'Model',
            'Part Num',
            'Part Category',
            'Part Name',
            'Destination',
            'SOP',
            'EOL',
            'Model Life',
            'Volume/y',
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
                'MMK',
                'Toyota Yaris',
                '89661-0D310',
                'Engine',
                'Computer, Engine Control',
                'Domestic',
                '2026-07-01',
                '2031-06-30',
                '5',
                '12000',
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
            ],
            [
                '2',
                'MMK',
                'Mitsubishi Xpander',
                '1860B888',
                'Engine',
                'ECU Engine Control',
                'Domestic',
                '2026-08-01',
                '2032-07-31',
                '6',
                '8000',
                'Yes',
                'Yes',
                'Yes',
                'Existing',
                'Sedang',
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
            ],
            [
                '3',
                'MMK',
                'Honda Brio',
                '37820-55A-Z71',
                'Body',
                'Control Unit, FI/Engine',
                'Export',
                '2026-10-01',
                '2031-09-30',
                '5',
                '4000',
                'Yes',
                'No',
                'Yes',
                'Existing',
                'Rendah',
                'Minor Modification',
                'Available',
                'Rendah',
                '',
                '',
                '',
                '',
                '',
                '',
                'Accept'
            ],
            [
                '4',
                'MMK',
                'Suzuki Ertiga',
                '33910-61R00',
                'Engine',
                'Control Unit, FI',
                'Domestic',
                '2027-01-01',
                '2032-12-31',
                '6',
                '15000',
                'No',
                'Yes',
                'No',
                'Existing',
                'Tinggi',
                'New Product',
                'Not Available',
                'Tinggi',
                '',
                '',
                '',
                '',
                '',
                '',
                'Accept'
            ],
            [
                '5',
                'HP',
                'Hyundai Creta',
                '39110-03400',
                'Chassis',
                'ECU Unit Creta',
                'Export',
                '2026-07-01',
                '2031-06-30',
                '5',
                '3000',
                'Yes',
                'Yes',
                'Yes',
                'Existing',
                'Rendah',
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
            ],
            [
                '6',
                'TOY',
                'Toyota Avanza',
                '89661-BZ310',
                'Body',
                'Engine Control Unit Avanza',
                'Domestic',
                '2026-07-01',
                '2031-06-30',
                '5',
                '24000',
                'Yes',
                'Yes',
                'Yes',
                'Existing',
                'Tinggi',
                'Similar',
                'Available',
                'Sedang',
                '',
                '',
                '',
                '',
                '',
                '',
                'Accept'
            ],
            [
                '7',
                'ISUZ',
                'Isuzu D-Max',
                '89800-12340',
                'Engine',
                'Engine Control Module D-Max',
                'Domestic',
                '2026-07-01',
                '2031-06-30',
                '5',
                '2000',
                'Yes',
                'Yes',
                'Yes',
                'Existing',
                'Rendah',
                'Similar',
                'Minor Gap',
                'Sedang',
                '',
                '',
                '',
                '',
                '',
                '',
                'Accept'
            ],
            [
                '8',
                'BY',
                'BYD Atto 3',
                'BYD-1029381',
                'Electrical',
                'Battery Management System',
                'Export',
                '2027-03-01',
                '2032-02-28',
                '5',
                '30000',
                'Yes',
                'Yes',
                'Yes',
                'Strategis',
                'Tinggi',
                'New Product',
                'Not Available',
                'Tinggi',
                '',
                '',
                '',
                '',
                '',
                '',
                'Accept'
            ],
            [
                '9',
                'VIN',
                'VinFast VF5',
                'VF5-882910',
                'Chassis',
                'Main Controller VF5',
                'Export',
                '2026-11-01',
                '2031-10-31',
                '5',
                '18000',
                'Yes',
                'Yes',
                'Yes',
                'Baru',
                'Tinggi',
                'New Product',
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
        $sheet->mergeCells('A2:Z2');
        $sheet->setCellValue('A2', 'PROJECT INQUIRY PRODUCTS IMPORT TEMPLATE');
        
        $sheet->mergeCells('A3:Z3');
        $sheet->setCellValue('A3', 'Fill your data starting on row 6. Headers are on row 5. Values for scoring categories (cols O-S) must match system options.');

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
