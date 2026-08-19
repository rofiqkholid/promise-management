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

    /**
     * Display Header List of all EBD Projects for Cost Comparison.
     */
    public function index(Request $request)
    {
        $customers = Customer::orderBy('name', 'asc')->get();

        $selectedCustomerId = $request->input('customer_id');
        $selectedModelId = $request->input('model_id');

        // Models list based on customer
        $models = collect();
        if ($selectedCustomerId) {
            $models = ProjectModel::where('customer_id', $selectedCustomerId)->orderBy('name', 'asc')->get();
        }

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

        return view('management.cost-comparison.index', compact(
            'customers',
            'models',
            'selectedCustomerId',
            'selectedModelId',
            'comparisonSummaries',
            'overallKpi'
        ));
    }

    /**
     * Display Detailed Comparison Matrix & Per-Part Breakdown for a specific EBD.
     */
    public function show($id)
    {
        $comparisonResult = $this->comparisonService->calculateForEbdHeader($id);

        return view('management.cost-comparison.show', compact('comparisonResult'));
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
     * AJAX endpoint to get models by customer.
     */
    public function getModelsByCustomer(Request $request)
    {
        $customerId = $request->input('customer_id');
        $models = ProjectModel::where('customer_id', $customerId)->orderBy('name', 'asc')->get(['id', 'name']);
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
