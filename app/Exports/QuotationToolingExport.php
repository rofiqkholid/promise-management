<?php

namespace App\Exports;

use App\Models\WorkOrder;
use App\Models\MngEbdItem;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class QuotationToolingExport implements FromView, WithTitle, WithEvents
{
    protected $workOrder;

    public function __construct(WorkOrder $workOrder)
    {
        $this->workOrder = $workOrder;
    }

    protected function getItems()
    {
        $ebdHeader = $this->workOrder->ebdHeader;
        $ebdItemIds = $this->workOrder->products->pluck('ebd_item_id')->filter()->unique()->toArray();
        
        if (!empty($ebdItemIds)) {
            return MngEbdItem::with(['toolingProcesses', 'addProcesses'])
                ->whereIn('id', $ebdItemIds)
                ->orderBy('id', 'asc')
                ->get();
        } else if ($ebdHeader) {
            return $ebdHeader->items()->with(['toolingProcesses', 'addProcesses'])->orderBy('id', 'asc')->get();
        }
        
        return collect();
    }

    public function view(): View
    {
        $workOrder = $this->workOrder->load([
            'ebdHeader.customer', 
            'ebdHeader.projectModel', 
            'ebdHeader.items.toolingProcesses', 
            'ebdHeader.items.addProcesses',
            'products.ebdItem.toolingProcesses',
            'products.ebdItem.addProcesses',
            'inquiry.customer',
            'inquiry.projectModel'
        ]);

        $ebdHeader = $workOrder->ebdHeader;
        $items = $this->getItems();
        $supplierName = $ebdHeader->customer->name ?? $workOrder->inquiry->customer->name ?? 'PT. SUMMIT ADYAWINSA INDONESIA';

        return view('management.work-order.wo2-tooling.quotation', [
            'workOrder' => $workOrder,
            'ebdHeader' => $ebdHeader,
            'items' => $items,
            'supplierName' => $supplierName
        ]);
    }

    public function title(): string
    {
        return 'Quotation Tooling';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                
                // Wrap text for header range (Row 6 - Row 10)
                $sheet->getStyle('A6:Y10')->getAlignment()->setWrapText(true);
                
                // Rotate text in header row 8 for Tonage (S), Width (T), Length (U), Height (V) by 90 degrees
                $sheet->getStyle('S8:V8')->getAlignment()->setTextRotation(90);
                
                // Set row height for row 8
                $sheet->getRowDimension(8)->setRowHeight(55);

                // Medium borders for all cells in Header section A6:Y10
                $sheet->getStyle('A6:Y10')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Thicker outer border for the entire active table range A1:Y{highestRow}
                $sheet->getStyle("A1:Y{$highestRow}")->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Medium bottom border for Row 5 and Row 10 (headers)
                $sheet->getStyle('A5:Y5')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
                $sheet->getStyle('A10:Y10')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
                
                // Dynamically calculate and set BORDER_MEDIUM for bottom row of each item
                $items = $this->getItems();
                $currentRow = 11; // Data starts at row 11 (after filter row 10)
                foreach ($items as $item) {
                    $toolingProcs = $item->toolingProcesses ?? collect();
                    $rowCount = max($toolingProcs->count(), 1);
                    $endRow = $currentRow + $rowCount - 1;
                    
                    // Apply medium bottom border to row $endRow across columns A to Y
                    $sheet->getStyle("A{$endRow}:Y{$endRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
                    
                    $currentRow = $endRow + 1;
                }

                // Explicit Column Widths
                $sheet->getColumnDimension('A')->setWidth(6);   // No
                $sheet->getColumnDimension('B')->setWidth(3);   // NEW/COMMON/MODIFY col 1
                $sheet->getColumnDimension('C')->setWidth(3);   // col 2
                $sheet->getColumnDimension('D')->setWidth(3);   // col 3
                $sheet->getColumnDimension('E')->setWidth(3);   // col 4
                $sheet->getColumnDimension('F')->setWidth(3);   // col 5
                $sheet->getColumnDimension('G')->setWidth(3);   // col 6
                $sheet->getColumnDimension('H')->setWidth(22);  // Part No.
                $sheet->getColumnDimension('I')->setWidth(32);  // Part Name
                $sheet->getColumnDimension('J')->setWidth(18);  // Process Name
                $sheet->getColumnDimension('K')->setWidth(12);  // Process
                $sheet->getColumnDimension('L')->setWidth(15);  // NEW DIES/MODIF/COMMON
                $sheet->getColumnDimension('M')->setWidth(8);   // OP
                $sheet->getColumnDimension('N')->setWidth(18);  // Process Name
                $sheet->getColumnDimension('O')->setWidth(12);  // TOOLING
                $sheet->getColumnDimension('P')->setWidth(8);   // Dies
                $sheet->getColumnDimension('Q')->setWidth(8);   // Jig
                $sheet->getColumnDimension('R')->setWidth(8);   // CF
                $sheet->getColumnDimension('S')->setWidth(9);   // Tonage (T)
                $sheet->getColumnDimension('T')->setWidth(9);   // Width
                $sheet->getColumnDimension('U')->setWidth(9);   // Length
                $sheet->getColumnDimension('V')->setWidth(9);   // Height
                $sheet->getColumnDimension('W')->setWidth(12);  // Category
                $sheet->getColumnDimension('X')->setWidth(20);  // Currency
                $sheet->getColumnDimension('Y')->setWidth(20);  // IDR
            },
        ];
    }
}
