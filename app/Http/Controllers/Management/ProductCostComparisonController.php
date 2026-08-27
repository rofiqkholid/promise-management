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
                        $stampingRates = \App\Models\MfgProcessStpCost::all();
                        $generalMfgRates = \App\Models\MfgProcessCost::all();

                        // Build comprehensive items loop payload with exact correct keys
                        $itemsPayload = [];
                        foreach ($comparison['items'] as $it) {
                            $mItem = $it['item'];
                            $eng = $it['eng'] ?? [];
                            $sales = $it['sales'] ?? [];

                            $processes = [];
                            if ($mItem->toolingProcesses && $mItem->toolingProcesses->count() > 0) {
                                foreach ($mItem->toolingProcesses as $tp) {
                                    $tonnage = $tp->tonnage ? intval($tp->tonnage) : null;
                                    $machineType = ($tp->machine_type && !in_array($tp->machine_type, ['M', 'JW'])) ? $tp->machine_type : 'Tandem';
                                    $stroke = ($tp->stroke !== null && $tp->stroke !== '') ? floatval($tp->stroke) : 1.0;
                                    $partRank = trim($mItem->part_rank ?? '');

                                    $stpSales = $tonnage ? $this->comparisonService->matchStampingRate($stampingRates, $machineType, $tonnage, $stroke, $partRank, 'Sales', $customer->id ?? null) : null;
                                    $stpEng = $tonnage ? $this->comparisonService->matchStampingRate($stampingRates, $machineType, $tonnage, $stroke, $partRank, 'Engineering', null) : null;

                                    $costRateSales = $stpSales ? floatval($stpSales->std_cost_rate) : ($stpEng ? floatval($stpEng->std_cost_rate) : 0.0);
                                    $minCostRateSales = $stpSales ? floatval($stpSales->min_cost_rate) : ($stpEng ? floatval($stpEng->min_cost_rate) : 0.0);

                                    $processes[] = [
                                        'op'                    => $tp->op,
                                        'ebd_tool_op'           => $tp->op,
                                        'process_name'          => $tp->process_name,
                                        'ebd_tool_process_name' => $tp->process_name,
                                        'tooling_process_name'  => $tp->process_name,
                                        'category'              => $tp->category,
                                        'ebd_tool_category'     => $tp->category,
                                        'tooling_type'          => $tp->category,
                                        'homeline'              => $tp->prod_homeline,
                                        'ebd_tool_homeline'     => $tp->prod_homeline,
                                        'tonnage'               => $tp->tonnage,
                                        'ebd_tool_tonnage'      => $tp->tonnage,
                                        'machine_type'          => $tp->machine_type,
                                        'stroke'                => $tp->stroke ?? $stroke,
                                        'die_height'            => $tp->die_height,
                                        'ebd_tool_die_height'   => $tp->die_height,
                                        'output'                => $tp->output,
                                        'ebd_tool_output'       => $tp->output,
                                        'price_idr'             => $tp->price_idr,
                                        'ebd_tool_price_idr'    => $tp->price_idr,
                                        'cost_idr'              => $tp->price_idr,
                                        'cost_rate'             => $costRateSales,
                                        'std_cost_rate'         => $costRateSales,
                                        'min_cost_rate'         => $minCostRateSales,
                                        'stamping_cost'         => ($costRateSales * $stroke) / max(1, intval($tp->output ?? 1)),
                                        'ebd_tool_status'       => $tp->tooling_status,
                                        'supplier_status'       => $tp->tooling_status,
                                        'remarks'               => $tp->information,
                                        'ebd_tool_information'  => $tp->information,
                                    ];
                                }
                            }

                            // Extract Additional / Subcon Processes
                            $additionalProcesses = [];
                            if ($mItem->addProcesses && $mItem->addProcesses->count() > 0) {
                                foreach ($mItem->addProcesses as $ap) {
                                    $procName = trim($ap->process_name ?? '');
                                    $rawQty = floatval($ap->qty ?? 0.0);
                                    $qtyMultiplier = $rawQty > 0 ? $rawQty : 1.0;

                                    $apEng = $generalMfgRates->where('rate_source', 'Engineering')
                                        ->first(function($r) use ($procName) {
                                            if (empty($procName)) return false;
                                            $mfgName = trim($r->process_name);
                                            return strcasecmp($mfgName, $procName) === 0 ||
                                                   stripos($mfgName, $procName) !== false ||
                                                   stripos($procName, $mfgName) !== false;
                                        });

                                    $apSales = $generalMfgRates->where('rate_source', 'Sales')
                                        ->first(function($r) use ($procName) {
                                            if (empty($procName)) return false;
                                            $mfgName = trim($r->process_name);
                                            return strcasecmp($mfgName, $procName) === 0 ||
                                                   stripos($mfgName, $procName) !== false ||
                                                   stripos($procName, $mfgName) !== false;
                                        });

                                    $valEng = $apEng ? (floatval($apEng->std_cost_rate) * $qtyMultiplier) : floatval($ap->cost_idr ?? 0.0);
                                    $valSales = $apSales ? (floatval($apSales->std_cost_rate) * $qtyMultiplier) : floatval($ap->cost_idr ?? 0.0);
                                    $rateStd = $apSales ? floatval($apSales->std_cost_rate) : ($apEng ? floatval($apEng->std_cost_rate) : floatval($ap->cost_idr ?? 0.0));

                                    $additionalProcesses[] = [
                                        'add_process_name'         => $procName,
                                        'ebd_add_process_name'     => $procName,
                                        'process_name'             => $procName,
                                        'add_qty'                  => $rawQty,
                                        'ebd_add_process_qty'      => $rawQty,
                                        'qty'                      => $rawQty,
                                        'add_unit'                 => $ap->unit ?? 'PCS',
                                        'ebd_add_process_unit'     => $ap->unit ?? 'PCS',
                                        'unit'                     => $ap->unit ?? 'PCS',
                                        'add_proc_sales'           => $valSales,
                                        'add_proc_eng'             => $valEng,
                                        'add_proc_cost'            => $valSales,
                                        'add_cost_idr'             => $valSales,
                                        'ebd_add_process_cost_idr' => $valSales,
                                        'cost_idr'                 => $valSales,
                                        'price_idr'                => $valSales,
                                        'cost_rate'                => $rateStd,
                                        'std_cost_rate'            => $rateStd,
                                    ];
                                }
                            }

                            $itemsPayload[] = [
                                'part_no'              => $mItem->part_no ?? '-',
                                'part_number'          => $mItem->part_no ?? '-',
                                'ebd_part_no'          => $mItem->part_no ?? '-',
                                'part_name'            => $mItem->part_name ?? '-',
                                'ebd_part_name'        => $mItem->part_name ?? '-',
                                'part_rank'            => $mItem->part_rank ?? '-',
                                'ebd_part_rank'        => $mItem->part_rank ?? '-',
                                'active_level'         => $mItem->active_level ?? 1,
                                'ebd_active_level'     => $mItem->active_level ?? 1,
                                'qty_unit'             => $mItem->qty_unit ?? 1,
                                'ebd_qty_unit'         => $mItem->qty_unit ?? 1,
                                'pcs_month'            => $mItem->pcs_month ?? 0,
                                'ebd_pcs_month'        => $mItem->pcs_month ?? 0,

                                // Part Dimensions
                                'width'                => $mItem->width ?? 0,
                                'ebd_part_width'       => $mItem->width ?? 0,
                                'length'               => $mItem->length ?? 0,
                                'ebd_part_length'      => $mItem->length ?? 0,
                                'height'               => $mItem->height ?? 0,
                                'ebd_part_height'      => $mItem->height ?? 0,
                                'weight'               => $mItem->weight ?? 0,
                                'ebd_part_weight'      => $mItem->weight ?? 0,

                                // Material Specification & Dimensions
                                'mat_spec'             => $mItem->mat_spec ?? '-',
                                'material_name'        => $mItem->mat_spec ?? '-',
                                'material_spec'        => $mItem->mat_spec ?? '-',
                                'ebd_mat_spec'         => $mItem->mat_spec ?? '-',

                                'mat_thick'            => $mItem->mat_thick ?? 0,
                                'material_thick'       => $mItem->mat_thick ?? 0,
                                'ebd_mat_thick'        => $mItem->mat_thick ?? 0,

                                'mat_width'            => $mItem->mat_width ?? 0,
                                'material_width'       => $mItem->mat_width ?? 0,
                                'ebd_mat_width'        => $mItem->mat_width ?? 0,

                                'mat_length'           => $mItem->mat_length ?? 0,
                                'material_length'      => $mItem->mat_length ?? 0,
                                'ebd_mat_length'       => $mItem->mat_length ?? 0,

                                'mat_pcs_sheet'        => $mItem->mat_pcs_sheet ?? 0,
                                'material_pcs_sheet'   => $mItem->mat_pcs_sheet ?? 0,
                                'pcs_sheet'            => $mItem->mat_pcs_sheet ?? 0,
                                'ebd_mat_pcs_sheet'    => $mItem->mat_pcs_sheet ?? 0,

                                'mat_weight_pcs'       => $mItem->mat_weight_pcs ?? 0,
                                'material_weight_pcs'  => $mItem->mat_weight_pcs ?? 0,
                                'ebd_mat_weight_pcs'   => $mItem->mat_weight_pcs ?? 0,

                                'mat_yield_ratio'      => $mItem->mat_yield_ratio ?? 0,
                                'material_yield_ratio' => $mItem->mat_yield_ratio ?? 0,
                                'yield_ratio'          => $mItem->mat_yield_ratio ?? 0,
                                'ebd_mat_yield_ratio'  => $mItem->mat_yield_ratio ?? 0,

                                // Standard Part Components
                                'std_part_no'          => $mItem->std_part_no ?? '-',
                                'ebd_std_part_no'      => $mItem->std_part_no ?? '-',
                                'std_part_name'        => $mItem->std_part_name ?? '-',
                                'ebd_std_part_name'    => $mItem->std_part_name ?? '-',
                                'std_qty'              => $mItem->std_qty ?? 0,
                                'ebd_std_qty'          => $mItem->std_qty ?? 0,
                                'std_uom'              => $mItem->std_uom ?? '-',
                                'ebd_std_uom'          => $mItem->std_uom ?? '-',

                                // Costs Breakdown
                                'material_cost'        => $sales['material_cost'] ?? 0.0,
                                'material_eng'         => $eng['material_cost'] ?? 0.0,
                                'material_sales'       => $sales['material_cost'] ?? 0.0,
                                'mat_price_eng'        => $eng['mat_price_per_kg'] ?? ($eng['material_price'] ?? 0.0),
                                'mat_price_sales'      => $sales['mat_price_per_kg'] ?? ($sales['material_price'] ?? 0.0),
                                'mat_price_per_kg'     => $sales['mat_price_per_kg'] ?? ($sales['material_price'] ?? 0.0),
                                'material_price'       => $sales['mat_price_per_kg'] ?? ($sales['material_price'] ?? 0.0),
                                'material_rate'        => $sales['mat_price_per_kg'] ?? ($sales['material_price'] ?? 0.0),
                                'scrap_price_eng'      => $eng['scrap_price_per_kg'] ?? 0.0,
                                'scrap_price_sales'    => $sales['scrap_price_per_kg'] ?? 0.0,
                                'scrap_price_per_kg'   => $sales['scrap_price_per_kg'] ?? 0.0,
                                'scrap_price'          => $sales['scrap_price_per_kg'] ?? 0.0,
                                'scrap_rate'           => $sales['scrap_price_per_kg'] ?? 0.0,
                                'stamping_eng'         => $eng['stamping_cost'] ?? 0.0,
                                'stamping_sales'       => $sales['stamping_cost'] ?? 0.0,
                                'add_proc_eng'         => $eng['add_proc_cost'] ?? 0.0,
                                'add_proc_sales'       => $sales['add_proc_cost'] ?? 0.0,
                                'mfg_eng'              => $eng['mfg_cost'] ?? 0.0,
                                'mfg_sales'            => $sales['mfg_cost'] ?? 0.0,
                                'cogm_eng'             => $eng['cogm'] ?? 0.0,
                                'cogm_sales'           => $sales['cogm'] ?? 0.0,
                                'admin_matrl_eng'      => $eng['admin_matrl'] ?? 0.0,
                                'admin_matrl_sales'    => $sales['admin_matrl'] ?? 0.0,
                                'admin_mfg_eng'        => $eng['admin_mfg'] ?? 0.0,
                                'admin_mfg_sales'      => $sales['admin_mfg'] ?? 0.0,
                                'oh_profit_eng'        => $eng['oh_profit'] ?? 0.0,
                                'oh_profit_sales'      => $sales['oh_profit'] ?? 0.0,
                                'item_cogs_eng'        => $eng['cogs'] ?? 0.0,
                                'item_cogs_sales'      => $sales['cogs'] ?? 0.0,
                                'cogs_eng'             => $eng['cogs'] ?? 0.0,
                                'cogs_sales'           => $sales['cogs'] ?? 0.0,
                                'selling_price'        => $sales['cogs'] ?? 0.0,
                                'item_margin_idr'      => ($sales['cogs'] ?? 0.0) - ($eng['cogs'] ?? 0.0),
                                'item_margin_pct'      => ($sales['cogs'] ?? 0.0) > 0 ? ((($sales['cogs'] ?? 0.0) - ($eng['cogs'] ?? 0.0)) / ($sales['cogs'] ?? 0.0)) * 100 : 0.0,
                                'processes'            => $processes,
                                'additional_processes' => $additionalProcesses,
                                'add_processes'        => $additionalProcesses,
                                'tooling_processes'    => $processes,
                            ];
                        }

                        $payloadData = [
                            // Single Header Fields
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
                            'total_parts_count'    => count($comparison['items']),
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

                            // Loop Tables
                            'items'                => $itemsPayload,
                            'ebd_items'            => $itemsPayload,
                            'cost_comparison_items'=> $itemsPayload,
                        ];

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
