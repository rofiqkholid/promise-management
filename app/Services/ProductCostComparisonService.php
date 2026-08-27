<?php

namespace App\Services;

use App\Models\MngEbdHeader;
use App\Models\MngEbdItem;
use App\Models\MaterialCost;
use App\Models\MfgProcessCost;
use App\Models\MfgProcessStpCost;
use App\Models\CustomerCostPolicy;

class ProductCostComparisonService
{
    /**
     * Calculate comprehensive Product Cost Comparison for a given EBD Header or Customer + Model.
     */
    public function calculateForEbdHeader($ebdHeaderId)
    {
        $ebdHeader = MngEbdHeader::with([
            'customer',
            'projectModel',
            'items.toolingProcesses',
            'items.addProcesses',
        ])->findOrFail($ebdHeaderId);

        $customerId = $ebdHeader->customer_id;

        // 1. Fetch Cost Policies (Strict: 0.0 if not defined in master data)
        $policyEng = CustomerCostPolicy::where('rate_source', 'Engineering')
            ->whereNull('customer_id')
            ->first() ?? (object)[
                'admin_matrl_pct' => 0.00,
                'admin_mfg_pct' => 0.00,
                'oh_profit_pct' => 0.00,
                'min_std_margin_pct' => 0.00,
                'tooling_oh_profit_pct' => 0.00,
                'tooling_min_std_margin_pct' => 0.00,
            ];

        $policySales = null;
        if ($customerId) {
            $policySales = CustomerCostPolicy::where('rate_source', 'Sales')
                ->where('customer_id', $customerId)
                ->first();
        }
        if (!$policySales) {
            $policySales = CustomerCostPolicy::where('rate_source', 'Sales')
                ->whereNull('customer_id')
                ->first() ?? (object)[
                    'admin_matrl_pct' => 0.00,
                    'admin_mfg_pct' => 0.00,
                    'oh_profit_pct' => 0.00,
                    'min_std_margin_pct' => 0.00,
                    'tooling_oh_profit_pct' => 20.00,
                    'tooling_min_std_margin_pct' => 20.00,
                ];
        }

        // 2. Fetch all materials and process rates for lookup
        $materials = MaterialCost::active()->get();
        $stampingRates = MfgProcessStpCost::active()->get();
        $generalMfgRates = MfgProcessCost::active()->get();

        $itemsCalculations = [];
        $totals = [
            'material_eng' => 0.0,
            'material_sales' => 0.0,
            'stamping_eng' => 0.0,
            'stamping_sales' => 0.0,
            'add_proc_eng' => 0.0,
            'add_proc_sales' => 0.0,
            'mfg_eng' => 0.0,
            'mfg_sales' => 0.0,
            'cogm_eng' => 0.0,
            'cogm_sales' => 0.0,
            'admin_matrl_eng' => 0.0,
            'admin_matrl_sales' => 0.0,
            'admin_mfg_eng' => 0.0,
            'admin_mfg_sales' => 0.0,
            'oh_profit_eng' => 0.0,
            'oh_profit_sales' => 0.0,
            'cogs_eng' => 0.0,
            'cogs_sales' => 0.0,
        ];

        foreach ($ebdHeader->items as $item) {
            $itemResult = $this->calculateItemCost(
                $item,
                $customerId,
                $policyEng,
                $policySales,
                $materials,
                $stampingRates,
                $generalMfgRates
            );

            $itemsCalculations[] = $itemResult;

            // Accumulate
            $qty = max(1, $item->qty_unit ?? 1);
            $totals['material_eng'] += ($itemResult['eng']['material_cost'] * $qty);
            $totals['material_sales'] += ($itemResult['sales']['material_cost'] * $qty);
            $totals['stamping_eng'] += ($itemResult['eng']['stamping_cost'] * $qty);
            $totals['stamping_sales'] += ($itemResult['sales']['stamping_cost'] * $qty);
            $totals['add_proc_eng'] += ($itemResult['eng']['add_proc_cost'] * $qty);
            $totals['add_proc_sales'] += ($itemResult['sales']['add_proc_cost'] * $qty);
            $totals['mfg_eng'] += ($itemResult['eng']['mfg_cost'] * $qty);
            $totals['mfg_sales'] += ($itemResult['sales']['mfg_cost'] * $qty);
            $totals['cogm_eng'] += ($itemResult['eng']['cogm'] * $qty);
            $totals['cogm_sales'] += ($itemResult['sales']['cogm'] * $qty);
            $totals['admin_matrl_eng'] += ($itemResult['eng']['admin_matrl'] * $qty);
            $totals['admin_matrl_sales'] += ($itemResult['sales']['admin_matrl'] * $qty);
            $totals['admin_mfg_eng'] += ($itemResult['eng']['admin_mfg'] * $qty);
            $totals['admin_mfg_sales'] += ($itemResult['sales']['admin_mfg'] * $qty);
            $totals['oh_profit_eng'] += ($itemResult['eng']['oh_profit'] * $qty);
            $totals['oh_profit_sales'] += ($itemResult['sales']['oh_profit'] * $qty);
            $totals['cogs_eng'] += ($itemResult['eng']['cogs'] * $qty);
            $totals['cogs_sales'] += ($itemResult['sales']['cogs'] * $qty);
        }

        // Calculate Overall Product Margins
        $marginIdr = $totals['cogs_sales'] - $totals['cogs_eng'];
        $marginPct = $totals['cogs_sales'] > 0 ? ($marginIdr / $totals['cogs_sales']) * 100 : 0.0;

        $targetMarginSales = $policySales->min_std_margin_pct ?? 0.0;
        $targetMarginEng = $policyEng->min_std_margin_pct ?? 0.0;

        $status = 'PASSED';
        $statusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
        $statusText = 'TARGET ACHIEVED (PASSED)';

        if ($targetMarginEng > 0 && $marginPct < $targetMarginEng) {
            $status = 'ALERT';
            $statusBadge = 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300';
            $statusText = 'BELOW MINIMUM MARGIN (CRITICAL)';
        } elseif ($targetMarginSales > 0 && $marginPct < $targetMarginSales) {
            $status = 'WARNING';
            $statusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300';
            $statusText = 'MARGINAL (BELOW SALES TARGET)';
        }

        // -------------------------------------------------------------
        // Calculate Tooling Cost Comparison (Exact Match to Reference Formula)
        // -------------------------------------------------------------
        $toolingItems = [];
        $totalToolingCostEng = 0.0;
        $totalToolingCostSales = 0.0;
        $toolingOhProfitEngPct = floatval($policyEng->tooling_oh_profit_pct ?? 0.0);
        $toolingOhProfitSalesPct = floatval($policySales->tooling_oh_profit_pct ?? 20.0);
        $toolingTargetStdMargin = floatval($policySales->tooling_min_std_margin_pct ?? 20.0);

        foreach ($ebdHeader->items as $item) {
            if ($item->toolingProcesses && $item->toolingProcesses->count() > 0) {
                foreach ($item->toolingProcesses as $tp) {
                    $toolPrice = floatval($tp->price_idr ?? 0.0);
                    $toolQty = max(1, intval($tp->qty ?? 1));
                    $costEng = $toolPrice * $toolQty;
                    $ohSalesVal = $costEng * ($toolingOhProfitSalesPct / 100);
                    $costSales = $costEng + $ohSalesVal;

                    $totalToolingCostEng += $costEng;
                    $totalToolingCostSales += $costSales;

                    $toolingItems[] = [
                        'ebd_item_id'      => $item->id,
                        'part_no'          => $item->part_no,
                        'part_name'        => $item->part_name,
                        'tool_rank'        => $tp->tool_rank ?? '-',
                        'category'         => $tp->category ?? 'DIE',
                        'op'               => $tp->op ?? '-',
                        'process_name'     => $tp->process_name ?? '-',
                        'machine_type'     => $tp->machine_type ?? '-',
                        'prod_homeline'    => $tp->prod_homeline ?? '-',
                        'tonnage'          => $tp->tonnage ?? 0,
                        'die_height'       => $tp->die_height ?? 0,
                        'output'           => $tp->output ?? 1,
                        'stroke'           => $tp->stroke ?? 1,
                        'qty'              => $toolQty,
                        'price_unit_eng'   => $toolPrice,
                        'total_cost_eng'   => $costEng,
                        'oh_profit_pct'    => $toolingOhProfitSalesPct,
                        'oh_profit_val'    => $ohSalesVal,
                        'total_cost_sales' => $costSales,
                        'margin_idr'       => $costSales - $costEng,
                        'margin_pct'       => $costSales > 0 ? (($costSales - $costEng) / $costSales) * 100 : 0.0,
                    ];
                }
            }
        }

        $toolingCogmEng = $totalToolingCostEng;
        $toolingCogmSales = $totalToolingCostEng;
        $toolingOhProfitEngVal = $toolingCogmEng * ($toolingOhProfitEngPct / 100);
        $toolingOhProfitSalesVal = $toolingCogmSales * ($toolingOhProfitSalesPct / 100);
        $toolingCogsEng = $toolingCogmEng + $toolingOhProfitEngVal;
        $toolingCogsSales = $toolingCogmSales + $toolingOhProfitSalesVal;

        $toolingMarginIdr = $toolingCogsSales - $toolingCogsEng;
        $toolingMarginPct = $toolingCogsSales > 0 ? ($toolingMarginIdr / $toolingCogsSales) * 100 : 0.0;

        $toolingStatus = 'PASSED';
        $toolingStatusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
        $toolingStatusText = 'TARGET ACHIEVED (PASSED)';

        if ($toolingMarginPct < $toolingTargetStdMargin) {
            $toolingStatus = 'WARNING';
            $toolingStatusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300';
            $toolingStatusText = 'BELOW TARGET MARGIN (< ' . number_format($toolingTargetStdMargin, 1) . '%)';
        }

        $toolingComparison = [
            'items'                 => $toolingItems,
            'total_items_count'     => count($toolingItems),
            'cogm_eng'              => $toolingCogmEng,
            'cogm_sales'            => $toolingCogmSales,
            'oh_profit_eng_pct'     => $toolingOhProfitEngPct,
            'oh_profit_eng_val'     => $toolingOhProfitEngVal,
            'oh_profit_sales_pct'   => $toolingOhProfitSalesPct,
            'oh_profit_sales_val'   => $toolingOhProfitSalesVal,
            'cogs_eng'              => $toolingCogsEng,
            'cogs_sales'            => $toolingCogsSales,
            'margin_idr'            => $toolingMarginIdr,
            'margin_pct'            => $toolingMarginPct,
            'target_std_margin_pct' => $toolingTargetStdMargin,
            'status'                => $toolingStatus,
            'status_badge'          => $toolingStatusBadge,
            'status_text'           => $toolingStatusText,
        ];

        return [
            'ebd_header'          => $ebdHeader,
            'customer'            => $ebdHeader->customer,
            'project_model'       => $ebdHeader->projectModel,
            'policy_eng'          => $policyEng,
            'policy_sales'        => $policySales,
            'totals'              => $totals,
            'margin_idr'          => $marginIdr,
            'margin_pct'          => $marginPct,
            'target_margin_sales' => $targetMarginSales,
            'target_margin_eng'   => $targetMarginEng,
            'status'              => $status,
            'status_badge'        => $statusBadge,
            'status_text'         => $statusText,
            'items'               => $itemsCalculations,
            'tooling'             => $toolingComparison,
        ];
    }

    /**
     * Get Server-Side Paginated & Filtered Items Data for DataTables.
     */
    public function getPaginatedItemsData($ebdHeaderId, $request)
    {
        $draw = intval($request->input('draw', 1));
        $start = intval($request->input('start', 0));
        $length = intval($request->input('length', 25));

        $ebdHeader = MngEbdHeader::findOrFail($ebdHeaderId);
        $customerId = $ebdHeader->customer_id;

        // 1. Fetch Cost Policies (Strict: 0.0 if not defined)
        $policyEng = CustomerCostPolicy::where('rate_source', 'Engineering')
            ->whereNull('customer_id')
            ->first() ?? (object)[
                'admin_matrl_pct' => 0.00,
                'admin_mfg_pct' => 0.00,
                'oh_profit_pct' => 0.00,
                'min_std_margin_pct' => 0.00,
            ];

        $policySales = null;
        if ($customerId) {
            $policySales = CustomerCostPolicy::where('rate_source', 'Sales')
                ->where('customer_id', $customerId)
                ->first();
        }
        if (!$policySales) {
            $policySales = CustomerCostPolicy::where('rate_source', 'Sales')
                ->whereNull('customer_id')
                ->first() ?? (object)[
                    'admin_matrl_pct' => 0.00,
                    'admin_mfg_pct' => 0.00,
                    'oh_profit_pct' => 0.00,
                    'min_std_margin_pct' => 0.00,
                ];
        }

        // 2. Fetch all materials and process rates for lookup
        $materials = MaterialCost::active()->get();
        $stampingRates = MfgProcessStpCost::active()->get();
        $generalMfgRates = MfgProcessCost::active()->get();

        $query = MngEbdItem::with(['toolingProcesses', 'addProcesses'])
            ->where('ebd_header_id', $ebdHeaderId);

        $totalRecords = (clone $query)->count();

        // Search
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $query->where(function($q) use ($searchValue) {
                $q->where('part_no', 'like', "%{$searchValue}%")
                  ->orWhere('part_name', 'like', "%{$searchValue}%")
                  ->orWhere('part_rank', 'like', "%{$searchValue}%")
                  ->orWhere('mat_spec', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = (clone $query)->count();
        $items = $query->get();

        // Calculate cost structure for each item
        $allRows = [];
        foreach ($items as $idx => $item) {
            $calc = $this->calculateItemCost(
                $item,
                $customerId,
                $policyEng,
                $policySales,
                $materials,
                $stampingRates,
                $generalMfgRates
            );

            $tpCount = $item->toolingProcesses ? $item->toolingProcesses->count() : 0;
            $apCount = $item->addProcesses ? $item->addProcesses->count() : 0;
            $totalProcesses = $tpCount + $apCount;

            $allRows[] = [
                'id' => $item->id,
                'part_no' => $item->part_no ?? '-',
                'part_name' => $item->part_name ?? '-',
                'active_level' => intval($item->active_level ?? 1),
                'parent_id' => $item->parent_id,
                'part_rank' => $item->part_rank ?? '-',
                'mat_spec' => $item->mat_spec ?? '-',
                'total_process' => $totalProcesses,
                'tp_count' => $tpCount,
                'ap_count' => $apCount,
                'eng_mat_cost' => $calc['eng']['material_cost'],
                'eng_stamping_cost' => $calc['eng']['stamping_cost'],
                'eng_add_proc_cost' => $calc['eng']['add_proc_cost'],
                'eng_mfg_cost' => $calc['eng']['mfg_cost'],
                'eng_cogs' => $calc['eng']['cogs'],
                'sales_mat_cost' => $calc['sales']['material_cost'],
                'sales_stamping_cost' => $calc['sales']['stamping_cost'],
                'sales_add_proc_cost' => $calc['sales']['add_proc_cost'],
                'sales_mfg_cost' => $calc['sales']['mfg_cost'],
                'sales_cogs' => $calc['sales']['cogs'],
                'margin_idr' => $calc['margin_idr'],
                'margin_pct' => $calc['margin_pct'],
            ];
        }

        // Sorting mapping across all columns
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = strtolower($request->input('order.0.dir', 'asc'));
        $columnsMap = [
            0 => 'id',
            1 => 'part_no',
            2 => 'part_rank',
            3 => 'mat_spec',
            4 => 'total_process',
            5 => 'eng_mat_cost',
            6 => 'eng_stamping_cost',
            7 => 'eng_add_proc_cost',
            8 => 'eng_cogs',
            9 => 'sales_mat_cost',
            10 => 'sales_stamping_cost',
            11 => 'sales_add_proc_cost',
            12 => 'sales_cogs',
            13 => 'margin_idr',
            14 => 'margin_pct',
        ];

        $sortField = $columnsMap[$orderColumnIndex] ?? 'id';

        usort($allRows, function ($a, $b) use ($sortField, $orderDir) {
            if ($sortField === 'id' && $orderDir === 'asc') {
                // Natural BOM sequence: Level 1 parent first, then Level 2 children
                $lvlA = $a['active_level'] ?? 1;
                $lvlB = $b['active_level'] ?? 1;
                if ($lvlA !== $lvlB) {
                    return $lvlA <=> $lvlB;
                }
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            }

            $valA = $a[$sortField] ?? 0;
            $valB = $b[$sortField] ?? 0;

            if (is_numeric($valA) && is_numeric($valB)) {
                $cmp = $valA <=> $valB;
            } else {
                $cmp = strcasecmp((string)$valA, (string)$valB);
            }

            return $orderDir === 'desc' ? -$cmp : $cmp;
        });

        // Paginate slice
        $pagedRows = ($length > 0) ? array_slice($allRows, $start, $length) : $allRows;

        // Assign index number
        foreach ($pagedRows as $i => &$row) {
            $row['index'] = $start + $i + 1;
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $pagedRows
        ];
    }

    /**
     * Calculate Cost Structure for a single EBD Item.
     */
    protected function calculateItemCost(
        MngEbdItem $item,
        $customerId,
        $policyEng,
        $policySales,
        $materials,
        $stampingRates,
        $generalMfgRates
    ) {
        $weight = floatval($item->mat_weight_pcs ?? $item->weight ?? 0.0);
        $matSpec = trim($item->mat_spec ?? '');
        $matThick = floatval($item->mat_thick ?? 0.0);
        $partRank = trim($item->part_rank ?? '');

        // -------------------------------------------------------------
        // 1. Match Material Cost (Strict: 0.0 if not matched in master data)
        // -------------------------------------------------------------
        $rateMatEng = $this->matchMaterialRate($materials, $matSpec, $matThick, 'Engineering', null);
        $rateMatSales = $this->matchMaterialRate($materials, $matSpec, $matThick, 'Sales', $customerId);

        $matPriceEng = $rateMatEng ? floatval($rateMatEng->price_per_kg) : 0.0;
        $matPriceSales = $rateMatSales ? floatval($rateMatSales->price_per_kg) : 0.0;

        $matCostEng = $weight * $matPriceEng;
        $matCostSales = $weight * $matPriceSales;

        // -------------------------------------------------------------
        // 2. Match Manufacturing Cost: Stamping Process vs Assembly & Add. Process
        // -------------------------------------------------------------
        $stampingCostEng = 0.0;
        $stampingCostSales = 0.0;

        if ($item->toolingProcesses && $item->toolingProcesses->count() > 0) {
            foreach ($item->toolingProcesses as $tp) {
                // CF and JIG are checking fixture / welding jigs (tooling investment), DIE is stamping press
                if ($tp->category && strtoupper(trim($tp->category)) !== 'DIE') {
                    continue;
                }

                $tonnage = $tp->tonnage ? intval($tp->tonnage) : null;
                if (!$tonnage) {
                    continue;
                }

                $machineType = ($tp->machine_type && !in_array($tp->machine_type, ['M', 'JW'])) ? $tp->machine_type : 'Tandem';
                $stroke = ($tp->stroke !== null && $tp->stroke !== '') ? floatval($tp->stroke) : null;
                $outputQty = max(1, intval($tp->output ?? 1));

                $stpEng = $this->matchStampingRate($stampingRates, $machineType, $tonnage, $stroke, $partRank, 'Engineering', null);
                $stpSales = $this->matchStampingRate($stampingRates, $machineType, $tonnage, $stroke, $partRank, 'Sales', $customerId);

                $strokeMultiplier = $stroke !== null ? $stroke : 1.0;
                $rateValEng = $stpEng ? ($stpEng->std_cost_rate * $strokeMultiplier) : 0.0;
                $rateValSales = $stpSales ? ($stpSales->std_cost_rate * $strokeMultiplier) : 0.0;

                $stampingCostEng += ($rateValEng / $outputQty);
                $stampingCostSales += ($rateValSales / $outputQty);
            }
        }

        // Additional processes (Welding: Spot/CO2, Painting, QC Check, Rivet, etc. from mng_mfg_process_costs)
        $addProcCostEng = 0.0;
        $addProcCostSales = 0.0;

        if ($item->addProcesses && $item->addProcesses->count() > 0) {
            foreach ($item->addProcesses as $ap) {
                $procName = trim($ap->process_name ?? '');
                $rawQty = floatval($ap->qty ?? 0.0);
                $qtyMultiplier = $rawQty > 0 ? $rawQty : 1.0;

                // Match in master data by process_name (case-insensitive & bidirectional)
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

                $addProcCostEng += $valEng;
                $addProcCostSales += $valSales;
            }
        }

        $mfgCostEng = $stampingCostEng + $addProcCostEng;
        $mfgCostSales = $stampingCostSales + $addProcCostSales;

        // -------------------------------------------------------------
        // 3. COGM Subtotals
        // -------------------------------------------------------------
        $cogmEng = $matCostEng + $mfgCostEng;
        $cogmSales = $matCostSales + $mfgCostSales;

        // -------------------------------------------------------------
        // 4. Others (Admin Matrl, Admin Mfg, OH + Profit)
        // -------------------------------------------------------------
        $adminMatrlEng = $matCostEng * (floatval($policyEng->admin_matrl_pct ?? 0.0) / 100);
        $adminMatrlSales = $matCostSales * (floatval($policySales->admin_matrl_pct ?? 0.0) / 100);

        $adminMfgEng = $mfgCostEng * (floatval($policyEng->admin_mfg_pct ?? 0.0) / 100);
        $adminMfgSales = $mfgCostSales * (floatval($policySales->admin_mfg_pct ?? 0.0) / 100);

        $ohProfitEng = 0.0; // Engineering = 0%
        $ohProfitSales = ($cogmSales + $adminMatrlSales + $adminMfgSales) * (floatval($policySales->oh_profit_pct ?? 0.0) / 100);

        // -------------------------------------------------------------
        // 5. COGS Totals
        // -------------------------------------------------------------
        $cogsEng = $cogmEng + $adminMatrlEng + $adminMfgEng;
        $cogsSales = $cogmSales + $adminMatrlSales + $adminMfgSales + $ohProfitSales;

        $itemMarginIdr = $cogsSales - $cogsEng;
        $itemMarginPct = $cogsSales > 0 ? ($itemMarginIdr / $cogsSales) * 100 : 0.0;

        $scrapPriceEng = $rateMatEng ? floatval($rateMatEng->scrap_price_per_kg) : 0.0;
        $scrapPriceSales = $rateMatSales ? floatval($rateMatSales->scrap_price_per_kg) : 0.0;

        return [
            'item' => $item,
            'eng' => [
                'material_rate' => $matPriceEng,
                'material_price' => $matPriceEng,
                'mat_price_per_kg' => $matPriceEng,
                'scrap_rate' => $scrapPriceEng,
                'scrap_price' => $scrapPriceEng,
                'scrap_price_per_kg' => $scrapPriceEng,
                'material_cost' => $matCostEng,
                'stamping_cost' => $stampingCostEng,
                'add_proc_cost' => $addProcCostEng,
                'mfg_cost' => $mfgCostEng,
                'cogm' => $cogmEng,
                'admin_matrl' => $adminMatrlEng,
                'admin_matrl_pct' => $policyEng->admin_matrl_pct ?? 0.0,
                'admin_mfg' => $adminMfgEng,
                'admin_mfg_pct' => $policyEng->admin_mfg_pct ?? 0.0,
                'oh_profit' => $ohProfitEng,
                'oh_profit_pct' => $policyEng->oh_profit_pct ?? 0.0,
                'cogs' => $cogsEng,
            ],
            'sales' => [
                'material_rate' => $matPriceSales,
                'material_price' => $matPriceSales,
                'mat_price_per_kg' => $matPriceSales,
                'scrap_rate' => $scrapPriceSales,
                'scrap_price' => $scrapPriceSales,
                'scrap_price_per_kg' => $scrapPriceSales,
                'material_cost' => $matCostSales,
                'stamping_cost' => $stampingCostSales,
                'add_proc_cost' => $addProcCostSales,
                'mfg_cost' => $mfgCostSales,
                'cogm' => $cogmSales,
                'admin_matrl' => $adminMatrlSales,
                'admin_matrl_pct' => $policySales->admin_matrl_pct ?? 0.0,
                'admin_mfg' => $adminMfgSales,
                'admin_mfg_pct' => $policySales->admin_mfg_pct ?? 0.0,
                'oh_profit' => $ohProfitSales,
                'oh_profit_pct' => $policySales->oh_profit_pct ?? 0.0,
                'cogs' => $cogsSales,
            ],
            'margin_idr' => $itemMarginIdr,
            'margin_pct' => $itemMarginPct,
        ];
    }

    /**
     * Match closest Material Rate from master data.
     * Returns null if no match found.
     */
    public function matchMaterialRate($materials, $spec, $thick, $rateSource, $customerId = null)
    {
        if (empty($spec) && empty($thick)) {
            return null;
        }

        // 1. Try Customer + Spec + Thickness + RateSource
        if ($customerId) {
            $match = $materials->where('rate_source', $rateSource)
                ->where('customer_id', $customerId)
                ->filter(function($m) use ($spec, $thick) {
                    $specMatch = !empty($spec) && (stripos($m->material_spec, $spec) !== false || stripos($spec, $m->material_spec) !== false);
                    $thickMatch = empty($thick) || empty($m->thickness) || abs($m->thickness - $thick) < 0.05;
                    return $specMatch && $thickMatch;
                })->first();

            if ($match) return $match;
        }

        // 2. Try Global Rate with Spec & Thickness
        $match = $materials->where('rate_source', $rateSource)
            ->whereNull('customer_id')
            ->filter(function($m) use ($spec, $thick) {
                $specMatch = !empty($spec) && (stripos($m->material_spec, $spec) !== false || stripos($spec, $m->material_spec) !== false);
                $thickMatch = empty($thick) || empty($m->thickness) || abs($m->thickness - $thick) < 0.05;
                return $specMatch && $thickMatch;
            })->first();

        if ($match) return $match;

        // 3. Try Global Rate with Spec only
        if (!empty($spec)) {
            $match = $materials->where('rate_source', $rateSource)
                ->whereNull('customer_id')
                ->filter(function($m) use ($spec) {
                    return stripos($m->material_spec, $spec) !== false || stripos($spec, $m->material_spec) !== false;
                })->first();

            if ($match) return $match;
        }

        return null;
    }

    /**
     * Match Stamping Process Rate from master data.
     * Prioritizes Customer-Specific rate first, then falls back to Global rate.
     * Returns null if no match found.
     */
    public function matchStampingRate($rates, $machineType, $tonnage, $stroke, $partRank, $rateSource, $customerId = null)
    {
        if (empty($rates) || $rates->isEmpty()) {
            return null;
        }

        // 1. Try Customer-Specific Exact Match: Customer + Machine Type + Tonnage + Rank + RateSource
        if (!empty($customerId)) {
            $match = $rates->where('rate_source', $rateSource)
                ->where('customer_id', $customerId)
                ->filter(function($r) use ($machineType, $partRank, $tonnage) {
                    $machineMatch = empty($machineType) || strcasecmp($r->machine_type, $machineType) === 0;
                    $rankMatch = empty($partRank) ||
                        strcasecmp($r->complexity_alias, $partRank) === 0 ||
                        stripos($r->process_complexity, $partRank) !== false;
                    $tonnageMatch = empty($tonnage) || $r->tonnage >= ($tonnage - 50);
                    return $machineMatch && $rankMatch && $tonnageMatch;
                })->first();

            if ($match) return $match;

            // 1b. Try Customer-Specific Match by Rank & Tonnage
            if (!empty($partRank)) {
                $match = $rates->where('rate_source', $rateSource)
                    ->where('customer_id', $customerId)
                    ->filter(function($r) use ($partRank, $tonnage) {
                        $rankMatch = strcasecmp($r->complexity_alias, $partRank) === 0 ||
                            stripos($r->process_complexity, $partRank) !== false;
                        $tonnageMatch = empty($tonnage) || $r->tonnage >= ($tonnage - 50);
                        return $rankMatch && $tonnageMatch;
                    })->first();

                if ($match) return $match;
            }
        }

        // 2. Global Rate Exact Match: Global (customer_id null) + Machine Type + Tonnage Range + Complexity Alias/Rank + RateSource
        $match = $rates->where('rate_source', $rateSource)
            ->whereNull('customer_id')
            ->filter(function($r) use ($machineType, $partRank, $tonnage) {
                $machineMatch = empty($machineType) || strcasecmp($r->machine_type, $machineType) === 0;
                $rankMatch = empty($partRank) ||
                    strcasecmp($r->complexity_alias, $partRank) === 0 ||
                    stripos($r->process_complexity, $partRank) !== false;
                $tonnageMatch = empty($tonnage) || $r->tonnage >= ($tonnage - 50);
                return $machineMatch && $rankMatch && $tonnageMatch;
            })->first();

        if ($match) return $match;

        // 3. Global Match by Complexity Alias & Tonnage
        if (!empty($partRank)) {
            $match = $rates->where('rate_source', $rateSource)
                ->whereNull('customer_id')
                ->filter(function($r) use ($partRank, $tonnage) {
                    $rankMatch = strcasecmp($r->complexity_alias, $partRank) === 0 ||
                        stripos($r->process_complexity, $partRank) !== false;
                    $tonnageMatch = empty($tonnage) || $r->tonnage >= ($tonnage - 50);
                    return $rankMatch && $tonnageMatch;
                })->first();

            if ($match) return $match;
        }

        return null;
    }
}
