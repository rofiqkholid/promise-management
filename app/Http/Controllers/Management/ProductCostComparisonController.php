<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProjectModel;
use App\Models\MngEbdHeader;
use App\Services\ProductCostComparisonService;
use Illuminate\Http\Request;

class ProductCostComparisonController extends Controller
{
    protected $comparisonService;

    public function __construct(ProductCostComparisonService $comparisonService)
    {
        $this->comparisonService = $comparisonService;
    }

    public function index(Request $request)
    {
        // 1. Only fetch Customers that have actual EBD records
        $customerIdsWithEbd = MngEbdHeader::whereNotNull('customer_id')->distinct()->pluck('customer_id');
        $customers = Customer::whereIn('id', $customerIdsWithEbd)->orderBy('name', 'asc')->get();

        $selectedCustomerId = $request->input('customer_id');
        $selectedModelId = $request->input('model_id');

        // 2. Only fetch Project Models that have actual EBD records (and matching customer if selected)
        $modelIdsWithEbd = MngEbdHeader::whereNotNull('model_id');
        if ($selectedCustomerId) {
            $modelIdsWithEbd->where('customer_id', $selectedCustomerId);
        }
        $modelIds = $modelIdsWithEbd->distinct()->pluck('model_id');
        $models = ProjectModel::whereIn('id', $modelIds)->orderBy('name', 'asc')->get();

        // Query EBD Headers
        $ebdQuery = MngEbdHeader::with(['customer', 'projectModel', 'items.toolingProcesses', 'items.addProcesses'])->orderByDesc('id');
        if ($selectedCustomerId) {
            $ebdQuery->where('customer_id', $selectedCustomerId);
        }
        if ($selectedModelId) {
            $ebdQuery->where('model_id', $selectedModelId);
        }
        $ebdHeaders = $ebdQuery->get();

        // Compute summary metrics for each EBD Header
        $comparisonSummaries = [];
        $overallKpi = [
            'total_projects' => $ebdHeaders->count(),
            'total_cogs_eng' => 0.0,
            'total_cogs_sales' => 0.0,
            'total_margin_idr' => 0.0,
        ];

        foreach ($ebdHeaders as $ebd) {
            $calc = $this->comparisonService->calculateForEbdHeader($ebd->id);
            $comparisonSummaries[] = [
                'ebd' => $ebd,
                'customer' => $ebd->customer,
                'model' => $ebd->projectModel,
                'parts_count' => count($calc['items']),
                'cogs_eng' => $calc['totals']['cogs_eng'],
                'cogs_sales' => $calc['totals']['cogs_sales'],
                'margin_idr' => $calc['margin_idr'],
                'margin_pct' => $calc['margin_pct'],
                'status' => $calc['status'],
                'status_badge' => $calc['status_badge'],
                'status_text' => $calc['status_text'],
            ];

            $overallKpi['total_cogs_eng'] += $calc['totals']['cogs_eng'];
            $overallKpi['total_cogs_sales'] += $calc['totals']['cogs_sales'];
            $overallKpi['total_margin_idr'] += $calc['margin_idr'];
        }

        $overallKpi['avg_margin_pct'] = $overallKpi['total_cogs_sales'] > 0
            ? ($overallKpi['total_margin_idr'] / $overallKpi['total_cogs_sales']) * 100
            : 0.0;

        $exportTemplates = \App\Models\MngCfgTemplate::where('direction', 'export')
            ->where('is_active', true)
            ->with('customer')
            ->orderBy('template_name')
            ->get();

        return view('management.cost-comparison.index', compact(
            'customers',
            'models',
            'selectedCustomerId',
            'selectedModelId',
            'comparisonSummaries',
            'overallKpi',
            'exportTemplates'
        ));
    }

    /**
     * Display Detailed Comparison Matrix & Per-Part Breakdown for a specific EBD.
     */
    public function show($id)
    {
        $comparisonResult = $this->comparisonService->calculateForEbdHeader($id);
        $customerId = $comparisonResult['customer']->id ?? null;

        $exportTemplates = \App\Models\MngCfgTemplate::where('direction', 'export')
            ->where('is_active', true)
            ->where(function($q) use ($customerId) {
                $q->whereNull('customer_id');
                if ($customerId) {
                    $q->orWhere('customer_id', $customerId);
                }
            })
            ->with('customer')
            ->orderBy('template_name')
            ->get();

        $defaultTemplateId = null;
        if ($customerId) {
            $defaultTemplate = $exportTemplates->firstWhere('customer_id', $customerId);
            if ($defaultTemplate) {
                $defaultTemplateId = $defaultTemplate->id;
            }
        }
        if (!$defaultTemplateId && $exportTemplates->isNotEmpty()) {
            $defaultTemplateId = $exportTemplates->first()->id;
        }

        return view('management.cost-comparison.show', compact('comparisonResult', 'exportTemplates', 'defaultTemplateId'));
    }

    /**
     * Export Cost Comparison to Customer Quotation Excel via Dynamic Template Engine or Default Excel Generator.
     */
    public function exportQuotation(Request $request, $id)
    {
        try {
            $ebdHeaderId = $this->resolveId($id);
            $comparison = $this->comparisonService->calculateForEbdHeader($ebdHeaderId);
            $ebdHeader = $comparison['ebd_header'];
            $customer = $comparison['customer'];
            $model = $comparison['project_model'];
            $templateId = $request->input('template_id');

            $sanitizedCust = str_replace(['/', '\\', ' '], '_', $customer->code ?? $customer->name ?? 'Customer');
            $sanitizedModel = str_replace(['/', '\\', ' '], '_', $model->name ?? 'Model');
            $filename = 'Quotation_' . $sanitizedCust . '_' . $sanitizedModel . '_Rev_' . ($ebdHeader->revision ?? '0') . '.xlsx';

            // 1. If user selected a configured dynamic template from MngCfgTemplate
            if ($templateId) {
                $templateConfig = \App\Models\MngCfgTemplate::find($templateId);

                if ($templateConfig && $templateConfig->file_path) {
                    $templatePath = \Illuminate\Support\Facades\Storage::disk('public')->path($templateConfig->file_path);

                    if (file_exists($templatePath)) {
                        $resolver = app(\App\Services\ExcelEngine\Resolvers\ToolingQuotationDataResolver::class);
                        $payloadData = $resolver->resolve($ebdHeader);

                        // Merge cost comparison header totals
                        $payloadData['fields'] = array_merge($payloadData['fields'] ?? [], [
                            'quotation_no'         => 'QT-' . ($customer->code ?? 'CUST') . '-' . ($model->name ?? 'MOD') . '-R' . ($ebdHeader->revision ?? '0'),
                            'quote_date'           => now()->format('Y-m-d'),
                            'customer_code'        => $customer->code ?? '',
                            'customer_name'        => $customer->name ?? '',
                            'model_name'           => $model->name ?? '',
                            'ebd_revision'         => $ebdHeader->revision ?? '0',
                            'revision'             => $ebdHeader->revision ?? '0',
                            'ebd_date'             => $ebdHeader->date ? $ebdHeader->date->format('Y-m-d') : now()->format('Y-m-d'),
                            'currency_code'        => 'IDR',
                            'exchange_rate'        => 1.0,
                            'total_parts_count'    => count($comparison['items'] ?? []),
                            'cogs_eng'             => $comparison['totals']['cogs_eng'] ?? 0,
                            'cogs_sales'           => $comparison['totals']['cogs_sales'] ?? 0,
                            'margin_idr'           => $comparison['margin_idr'] ?? 0,
                            'margin_pct'           => $comparison['margin_pct'] ?? 0,
                            'total_material_eng'   => $comparison['totals']['material_eng'] ?? 0,
                            'total_material_sales' => $comparison['totals']['material_sales'] ?? 0,
                            'total_mfg_eng'        => $comparison['totals']['mfg_eng'] ?? 0,
                            'total_mfg_sales'      => $comparison['totals']['mfg_sales'] ?? 0,
                            'total_cogm_eng'       => $comparison['totals']['cogm_eng'] ?? 0,
                            'total_cogm_sales'     => $comparison['totals']['cogm_sales'] ?? 0,
                        ]);

                        $engine = new \App\Services\ExcelEngine\ExcelExportEngineService();
                        $generatedFile = $engine->export($templatePath, $templateConfig->mapping_config ?? [], $payloadData);

                        return response()->download($generatedFile, $filename)->deleteFileAfterSend(true);
                    }
                }
            }

            // 2. Default Standard Excel Export if no template chosen or template file not found
            return $this->generateStandardQuotationExcel($comparison, $filename);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to export Quotation', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Failed to export Quotation: ' . $e->getMessage());
        }
    }

    /**
     * Generate Default Formatted Excel Spreadsheet for Quotation.
     */
    protected function generateStandardQuotationExcel(array $comparison, string $filename)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Quotation Summary');

        $header = $comparison['ebd_header'];
        $customer = $comparison['customer'];
        $model = $comparison['project_model'];
        $totals = $comparison['totals'];

        // Title
        $sheet->setCellValue('A1', 'CUSTOMER PART QUOTATION BREAKDOWN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Metadata Block
        $sheet->setCellValue('A3', 'Customer:');
        $sheet->setCellValue('B3', ($customer->name ?? 'Customer') . ' (' . ($customer->code ?? '-') . ')');
        $sheet->setCellValue('A4', 'Project Model:');
        $sheet->setCellValue('B4', $model->name ?? '-');
        $sheet->setCellValue('A5', 'Quotation Date:');
        $sheet->setCellValue('B5', now()->format('d/m/Y'));
        $sheet->setCellValue('A6', 'EBD Revision:');
        $sheet->setCellValue('B6', 'Rev ' . ($header->revision ?? '0'));
        $sheet->getStyle('A3:A6')->getFont()->setBold(true);

        // Summary Matrix Table
        $sheet->setCellValue('A8', 'COST STAGE');
        $sheet->setCellValue('B8', 'COST CRITERIA');
        $sheet->setCellValue('C8', 'ENGINEERING (HPP)');
        $sheet->setCellValue('D8', 'SALES (QUOTATION)');
        $sheet->setCellValue('E8', 'VARIANCE (IDR)');
        $sheet->setCellValue('F8', 'MARGIN (%)');
        $sheet->getStyle('A8:F8')->getFont()->setBold(true);
        $sheet->getStyle('A8:F8')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

        $row = 9;
        $stages = [
            ['COGM', 'Material Cost', $totals['material_eng'], $totals['material_sales']],
            ['COGM', 'Manufacturing Process Cost', $totals['mfg_eng'], $totals['mfg_sales']],
            ['COGM TOTAL', 'Subtotal COGM', $totals['cogm_eng'], $totals['cogm_sales']],
            ['Others', 'Admin Material', $totals['admin_matrl_eng'], $totals['admin_matrl_sales']],
            ['Others', 'Admin Manufacturing', $totals['admin_mfg_eng'], $totals['admin_mfg_sales']],
            ['Others', 'Overhead & Profit', $totals['oh_profit_eng'], $totals['oh_profit_sales']],
            ['TOTAL COGS', 'Final Quotation Selling Price', $totals['cogs_eng'], $totals['cogs_sales']],
        ];

        foreach ($stages as $s) {
            $diff = $s[3] - $s[2];
            $pct = $s[3] > 0 ? ($diff / $s[3]) * 100 : 0;

            $sheet->setCellValue('A' . $row, $s[0]);
            $sheet->setCellValue('B' . $row, $s[1]);
            $sheet->setCellValue('C' . $row, $s[2]);
            $sheet->setCellValue('D' . $row, $s[3]);
            $sheet->setCellValue('E' . $row, $diff);
            $sheet->setCellValue('F' . $row, number_format($pct, 2) . '%');

            $sheet->getStyle('C' . $row . ':E' . $row)->getNumberFormat()->setFormatCode('#,##0');

            if ($s[0] === 'COGM TOTAL' || $s[0] === 'TOTAL COGS') {
                $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
            }
            $row++;
        }

        // Sheet 2: Part Detail List
        $partSheet = $spreadsheet->createSheet();
        $partSheet->setTitle('Part Details');

        $headers = ['No', 'Part Number', 'Part Name', 'Rank', 'Material Spec', 'Thick (mm)', 'Qty/Unit', 'Mat. Cost (Sales)', 'Stamping (Sales)', 'Add. Proc (Sales)', 'COGM (Sales)', 'Unit Selling Price'];
        $col = 'A';
        foreach ($headers as $h) {
            $partSheet->setCellValue($col . '1', $h);
            $col++;
        }
        $partSheet->getStyle('A1:L1')->getFont()->setBold(true);
        $partSheet->getStyle('A1:L1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

        $pRow = 2;
        foreach ($comparison['items'] as $idx => $it) {
            $item = $it['item'];
            $sales = $it['sales'] ?? [];

            $partSheet->setCellValue('A' . $pRow, $idx + 1);
            $partSheet->setCellValue('B' . $pRow, $item->part_no ?? '-');
            $partSheet->setCellValue('C' . $pRow, $item->part_name ?? '-');
            $partSheet->setCellValue('D' . $pRow, $item->part_rank ?? '-');
            $partSheet->setCellValue('E' . $pRow, $item->mat_spec ?? '-');
            $partSheet->setCellValue('F' . $pRow, $item->mat_thick ?? 0);
            $partSheet->setCellValue('G' . $pRow, $item->qty_unit ?? 1);
            $partSheet->setCellValue('H' . $pRow, $sales['material_cost'] ?? 0);
            $partSheet->setCellValue('I' . $pRow, $sales['stamping_cost'] ?? 0);
            $partSheet->setCellValue('J' . $pRow, $sales['add_proc_cost'] ?? 0);
            $partSheet->setCellValue('K' . $pRow, $sales['cogm'] ?? 0);
            $partSheet->setCellValue('L' . $pRow, $sales['cogs'] ?? 0);

            $partSheet->getStyle('H' . $pRow . ':L' . $pRow)->getNumberFormat()->setFormatCode('#,##0');
            $pRow++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        foreach (range('A', 'L') as $col) {
            $partSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $tempPath = tempnam(sys_get_temp_dir(), 'CC_QT_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function resolveId($id)
    {
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($id);
        } catch (\Throwable $e) {
            return $id;
        }
    }

    /**
     * Server-side DataTables endpoint for detailed per-part cost breakdown.
     */
    public function itemsData($id, Request $request)
    {
        $data = $this->comparisonService->getPaginatedItemsData($id, $request);
        return response()->json($data);
    }

    /**
     * AJAX endpoint to get models by customer from existing EBD records.
     */
    public function getModelsByCustomer(Request $request)
    {
        $customerId = $request->input('customer_id');
        $modelIdsQuery = MngEbdHeader::whereNotNull('model_id');
        if ($customerId) {
            $modelIdsQuery->where('customer_id', $customerId);
        }
        $modelIds = $modelIdsQuery->distinct()->pluck('model_id');
        $models = ProjectModel::whereIn('id', $modelIds)->orderBy('name', 'asc')->get(['id', 'name']);
        return response()->json($models);
    }

    /**
     * AJAX endpoint to get EBD headers by customer & model.
     */
    public function getEbdsByModel(Request $request)
    {
        $customerId = $request->input('customer_id');
        $modelId = $request->input('model_id');

        $query = MngEbdHeader::query();
        if ($customerId) $query->where('customer_id', $customerId);
        if ($modelId) $query->where('model_id', $modelId);

        $ebds = $query->orderByDesc('id')->get(['id', 'revision', 'date', 'status']);
        return response()->json($ebds);
    }

    /**
     * Export Cost Comparison to CSV.
     */
    public function export(Request $request)
    {
        $ebdHeaderId = $request->input('ebd_header_id');
        if (!$ebdHeaderId) {
            return redirect()->back()->with('error', 'Please select an EBD to export.');
        }

        $comparison = $this->comparisonService->calculateForEbdHeader($ebdHeaderId);
        $header = $comparison['ebd_header'];
        $custName = $comparison['customer']->name ?? 'Customer';
        $modelName = $comparison['project_model']->name ?? 'Model';

        $fileName = 'Product_Cost_Comparison_' . str_replace(' ', '_', $custName) . '_' . str_replace(' ', '_', $modelName) . '_' . date('Ymd_His') . '.csv';

        $headers = array(
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function () use ($comparison, $header, $custName, $modelName) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Title block
            fputcsv($file, ['PRODUCT COST COMPARISON MATRIX (ENGINEERING VS SALES)']);
            fputcsv($file, ['Customer:', $custName]);
            fputcsv($file, ['Project Model:', $modelName]);
            fputcsv($file, ['EBD Date:', $header->date ? $header->date->format('Y-m-d') : '-']);
            fputcsv($file, ['Revision:', $header->revision ?? '0']);
            fputcsv($file, ['Evaluation Status:', $comparison['status_text']]);
            fputcsv($file, []);

            // Summary Matrix
            fputcsv($file, ['COST STAGE', 'CRITERIA', 'ENGINEERING (HPP)', 'SALES (QUOTATION)', 'VARIANCE / NOTES']);
            fputcsv($file, [
                'COGM',
                'Material Cost',
                number_format($comparison['totals']['material_eng'], 2, '.', ''),
                number_format($comparison['totals']['material_sales'], 2, '.', ''),
                'Eng Rate vs Sales Rate'
            ]);
            fputcsv($file, [
                'COGM',
                'Mfg Cost',
                number_format($comparison['totals']['mfg_eng'], 2, '.', ''),
                number_format($comparison['totals']['mfg_sales'], 2, '.', ''),
                'Eng Process Rate vs Sales Rate'
            ]);
            fputcsv($file, [
                'COGM TOTAL',
                'Subtotal COGM',
                number_format($comparison['totals']['cogm_eng'], 2, '.', ''),
                number_format($comparison['totals']['cogm_sales'], 2, '.', ''),
                '-'
            ]);
            fputcsv($file, [
                'Others',
                'Admin matrl. (' . $comparison['policy_eng']->admin_matrl_pct . '% vs ' . $comparison['policy_sales']->admin_matrl_pct . '%)',
                number_format($comparison['totals']['admin_matrl_eng'], 2, '.', ''),
                number_format($comparison['totals']['admin_matrl_sales'], 2, '.', ''),
                'Follow Cust Rate'
            ]);
            fputcsv($file, [
                'Others',
                'Admin mfg. (' . $comparison['policy_eng']->admin_mfg_pct . '% vs ' . $comparison['policy_sales']->admin_mfg_pct . '%)',
                number_format($comparison['totals']['admin_mfg_eng'], 2, '.', ''),
                number_format($comparison['totals']['admin_mfg_sales'], 2, '.', ''),
                'Follow Cust Rate'
            ]);
            fputcsv($file, [
                'Others',
                'O/H + Profit (' . $comparison['policy_eng']->oh_profit_pct . '% vs ' . $comparison['policy_sales']->oh_profit_pct . '%)',
                number_format($comparison['totals']['oh_profit_eng'], 2, '.', ''),
                number_format($comparison['totals']['oh_profit_sales'], 2, '.', ''),
                'Follow Sales Strategy'
            ]);
            fputcsv($file, [
                'COGS TOTAL',
                'Total COGS',
                number_format($comparison['totals']['cogs_eng'], 2, '.', ''),
                number_format($comparison['totals']['cogs_sales'], 2, '.', ''),
                '-'
            ]);
            fputcsv($file, [
                'PROFITABILITY',
                'Margin (IDR)',
                '-',
                number_format($comparison['margin_idr'], 2, '.', ''),
                '= COGS Sales - COGS Eng'
            ]);
            fputcsv($file, [
                'PROFITABILITY',
                'Margin (%)',
                '-',
                number_format($comparison['margin_pct'], 2, '.', '') . '%',
                'Target Std Margin: Min ' . $comparison['target_margin_sales'] . '%'
            ]);
            fputcsv($file, []);

            // Detail Per-Part Breakdown
            fputcsv($file, ['DETAILED PER-PART BREAKDOWN']);
            fputcsv($file, [
                'No', 'Part No', 'Part Name', 'Rank', 'Spec', 'Thick (mm)', 'Weight (kg)', 'Qty/Unit',
                'Material (Eng)', 'Mfg (Eng)', 'COGS (Eng)',
                'Material (Sales)', 'Mfg (Sales)', 'COGS (Sales)',
                'Margin (IDR)', 'Margin (%)'
            ]);

            $no = 1;
            foreach ($comparison['items'] as $it) {
                $item = $it['item'];
                fputcsv($file, [
                    $no++,
                    $item->part_no ?? '-',
                    $item->part_name ?? '-',
                    $item->part_rank ?? '-',
                    $item->mat_spec ?? '-',
                    $item->mat_thick ?? '-',
                    $item->mat_weight_pcs ?? $item->weight ?? 0,
                    $item->qty_unit ?? 1,
                    number_format($it['eng']['material_cost'], 2, '.', ''),
                    number_format($it['eng']['mfg_cost'], 2, '.', ''),
                    number_format($it['eng']['cogs'], 2, '.', ''),
                    number_format($it['sales']['material_cost'], 2, '.', ''),
                    number_format($it['sales']['mfg_cost'], 2, '.', ''),
                    number_format($it['sales']['cogs'], 2, '.', ''),
                    number_format($it['margin_idr'], 2, '.', ''),
                    number_format($it['margin_pct'], 2, '.', '') . '%'
                ]);
            }

            // Tooling Cost Comparison Section
            if (!empty($comparison['tooling'])) {
                $tooling = $comparison['tooling'];
                fputcsv($file, []);
                fputcsv($file, ['========================================================================']);
                fputcsv($file, ['TOOLING COST COMPARISON MATRIX (ENGINEERING VS SALES)']);
                fputcsv($file, ['Total Tooling Items:', $tooling['total_items_count']]);
                fputcsv($file, ['Tooling Evaluation Status:', $tooling['status_text']]);
                fputcsv($file, []);

                fputcsv($file, ['COST STAGE', 'CRITERIA', 'ENGINEERING (HPP)', 'SALES (COMMERCIAL)', 'VARIANCE / NOTES']);
                fputcsv($file, [
                    'COGM',
                    'Tooling Cost',
                    number_format($tooling['cogm_eng'], 2, '.', ''),
                    number_format($tooling['cogm_sales'], 2, '.', ''),
                    'Total Tooling Investment'
                ]);
                fputcsv($file, [
                    'Others',
                    'O/H + Profit (0% vs ' . $tooling['oh_profit_sales_pct'] . '%)',
                    number_format($tooling['oh_profit_eng_val'], 2, '.', ''),
                    number_format($tooling['oh_profit_sales_val'], 2, '.', ''),
                    'Follow Sales Strategy'
                ]);
                fputcsv($file, [
                    'COGS TOTAL',
                    'Total Tooling COGS',
                    number_format($tooling['cogs_eng'], 2, '.', ''),
                    number_format($tooling['cogs_sales'], 2, '.', ''),
                    '( COGM + O/H Profit )'
                ]);
                fputcsv($file, [
                    'PROFITABILITY',
                    'Margin (IDR)',
                    '-',
                    number_format($tooling['margin_idr'], 2, '.', ''),
                    '= COGS Sales - COGS Eng'
                ]);
                fputcsv($file, [
                    'PROFITABILITY',
                    'Std Margin (%)',
                    '-',
                    number_format($tooling['margin_pct'], 2, '.', '') . '%',
                    'Std Margin: Min ' . $tooling['target_std_margin_pct'] . '%'
                ]);
                fputcsv($file, []);

                // Detail Tooling Breakdown
                fputcsv($file, ['DETAILED TOOLING PROCESS BREAKDOWN']);
                fputcsv($file, [
                    'No', 'Part No', 'Part Name', 'Rank', 'Category', 'OP', 'Process Name', 'Machine Type', 'Tonnage', 'Qty',
                    'Tooling Cost (Eng)', 'O/H (%)', 'Tooling Cost (Sales)', 'Margin (IDR)', 'Margin (%)'
                ]);

                $tNo = 1;
                foreach ($tooling['items'] as $tItem) {
                    fputcsv($file, [
                        $tNo++,
                        $tItem['part_no'] ?? '-',
                        $tItem['part_name'] ?? '-',
                        $tItem['tool_rank'] ?? '-',
                        $tItem['category'] ?? '-',
                        $tItem['op'] ?? '-',
                        $tItem['process_name'] ?? '-',
                        $tItem['machine_type'] ?? '-',
                        $tItem['tonnage'] ?? 0,
                        $tItem['qty'] ?? 1,
                        number_format($tItem['total_cost_eng'], 2, '.', ''),
                        number_format($tItem['oh_profit_pct'], 2, '.', '') . '%',
                        number_format($tItem['total_cost_sales'], 2, '.', ''),
                        number_format($tItem['margin_idr'], 2, '.', ''),
                        number_format($tItem['margin_pct'], 2, '.', '') . '%'
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
