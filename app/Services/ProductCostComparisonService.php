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

        // 1. Fetch Cost Policies
        $policyEng = CustomerCostPolicy::where('rate_source', 'Engineering')
            ->whereNull('customer_id')
            ->first() ?? (object)[
                'admin_matrl_pct' => 2.00,
                'admin_mfg_pct' => 4.00,
                'oh_profit_pct' => 0.00,
                'min_std_margin_pct' => 12.00,
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
                    'admin_matrl_pct' => 3.00,
                    'admin_mfg_pct' => 5.00,
                    'oh_profit_pct' => 10.00,
                    'min_std_margin_pct' => 12.00,
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

        // Calculate Overall Margins
        $marginIdr = $totals['cogs_sales'] - $totals['cogs_eng'];
        $marginPct = $totals['cogs_sales'] > 0 ? ($marginIdr / $totals['cogs_sales']) * 100 : 0.0;

        $targetMarginSales = $policySales->min_std_margin_pct ?? 12.0;
        $targetMarginEng = $policyEng->min_std_margin_pct ?? 12.0;

        $status = 'PASSED';
        $statusBadge = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
        $statusText = 'TARGET ACHIEVED (PASSED)';

        if ($marginPct < $targetMarginEng) {
            $status = 'ALERT';
            $statusBadge = 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300';
            $statusText = 'BELOW MINIMUM MARGIN (CRITICAL)';
        } elseif ($marginPct < $targetMarginSales) {
            $status = 'WARNING';
            $statusBadge = 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300';
            $statusText = 'MARGINAL (BELOW SALES TARGET)';
        }

        return [
            'ebd_header' => $ebdHeader,
            'customer' => $ebdHeader->customer,
            'project_model' => $ebdHeader->projectModel,
            'policy_eng' => $policyEng,
            'policy_sales' => $policySales,
            'totals' => $totals,
            'margin_idr' => $marginIdr,
            'margin_pct' => $marginPct,
            'target_margin_sales' => $targetMarginSales,
            'target_margin_eng' => $targetMarginEng,
            'status' => $status,
            'status_badge' => $statusBadge,
            'status_text' => $statusText,
            'items' => $itemsCalculations,
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

        // 1. Fetch Cost Policies
        $policyEng = CustomerCostPolicy::where('rate_source', 'Engineering')
            ->whereNull('customer_id')
            ->first() ?? (object)[
                'admin_matrl_pct' => 2.00,
                'admin_mfg_pct' => 4.00,
                'oh_profit_pct' => 0.00,
                'min_std_margin_pct' => 12.00,
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
                    'admin_matrl_pct' => 3.00,
                    'admin_mfg_pct' => 5.00,
                    'oh_profit_pct' => 10.00,
                    'min_std_margin_pct' => 12.00,
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
                'part_rank' => $item->part_rank ?? '-',
                'mat_spec' => $item->mat_spec ?? '-',
                'total_process' => $totalProcesses,
                'tp_count' => $tpCount,
                'ap_count' => $apCount,
                'eng_mat_cost' => $calc['eng']['material_cost'],
                'eng_mfg_cost' => $calc['eng']['mfg_cost'],
                'eng_cogs' => $calc['eng']['cogs'],
                'sales_mat_cost' => $calc['sales']['material_cost'],
                'sales_mfg_cost' => $calc['sales']['mfg_cost'],
                'sales_cogs' => $calc['sales']['cogs'],
                'margin_idr' => $calc['margin_idr'],
                'margin_pct' => $calc['margin_pct'],
            ];
        }

        // Sorting mapping across all columns
        $orderColumnIndex = $request->input('order.0.column', 1);
        $orderDir = strtolower($request->input('order.0.dir', 'asc'));
        $columnsMap = [
            0 => 'id',
            1 => 'part_no',
            2 => 'part_rank',
            3 => 'mat_spec',
            4 => 'total_process',
            5 => 'eng_mat_cost',
            6 => 'eng_mfg_cost',
            7 => 'eng_cogs',
            8 => 'sales_mat_cost',
            9 => 'sales_mfg_cost',
            10 => 'sales_cogs',
            11 => 'margin_idr',
            12 => 'margin_pct',
        ];

        $sortField = $columnsMap[$orderColumnIndex] ?? 'part_no';

        usort($allRows, function ($a, $b) use ($sortField, $orderDir) {
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
        // 1. Match Material Cost
        // -------------------------------------------------------------
        $rateMatEng = $this->matchMaterialRate($materials, $matSpec, $matThick, 'Engineering', null);
        $rateMatSales = $this->matchMaterialRate($materials, $matSpec, $matThick, 'Sales', $customerId);

        $matPriceEng = $rateMatEng ? $rateMatEng->price_per_kg : 15000.0;
        $matPriceSales = $rateMatSales ? $rateMatSales->price_per_kg : 16500.0;

        $matCostEng = $weight * $matPriceEng;
        $matCostSales = $weight * $matPriceSales;

        // -------------------------------------------------------------
        // 2. Match Manufacturing Cost (Stamping Processes & Additional Processes)
        // -------------------------------------------------------------
        $mfgCostEng = 0.0;
        $mfgCostSales = 0.0;

        if ($item->toolingProcesses && $item->toolingProcesses->count() > 0) {
            foreach ($item->toolingProcesses as $tp) {
                $tonnage = $tp->tonnage ? intval($tp->tonnage) : null;
                $machineType = $tp->machine_type ?: null;
                $stroke = ($tp->stroke !== null && $tp->stroke !== '') ? floatval($tp->stroke) : null;
                $outputQty = max(1, intval($tp->output ?? 1));

                $stpEng = $this->matchStampingRate($stampingRates, $machineType, $tonnage, $stroke, $partRank, 'Engineering');
                $stpSales = $this->matchStampingRate($stampingRates, $machineType, $tonnage, $stroke, $partRank, 'Sales');

                $strokeMultiplier = $stroke !== null ? $stroke : 1.0;
                $rateValEng = $stpEng ? ($stpEng->std_cost_rate * $strokeMultiplier) : 0.0;
                $rateValSales = $stpSales ? ($stpSales->std_cost_rate * $strokeMultiplier) : 0.0;

                $mfgCostEng += ($rateValEng / $outputQty);
                $mfgCostSales += ($rateValSales / $outputQty);
            }
        } else {
            // Default single stamping process estimation based on part rank
            $stpEng = $this->matchStampingRate($stampingRates, null, null, null, $partRank, 'Engineering');
            $stpSales = $this->matchStampingRate($stampingRates, null, null, null, $partRank, 'Sales');

            $mfgCostEng += ($stpEng ? $stpEng->std_cost_rate : 0.0);
            $mfgCostSales += ($stpSales ? $stpSales->std_cost_rate : 0.0);
        }

        // Additional processes if any (ED Painting, PSW, RSW, etc.)
        if ($item->addProcesses && $item->addProcesses->count() > 0) {
            foreach ($item->addProcesses as $ap) {
                $apCost = floatval($ap->cost_idr ?? 0.0);
                $mfgCostEng += $apCost;
                $mfgCostSales += ($apCost * 1.10); // 10% markup for sales
            }
        }

        // -------------------------------------------------------------
        // 3. COGM Subtotals
        // -------------------------------------------------------------
        $cogmEng = $matCostEng + $mfgCostEng;
        $cogmSales = $matCostSales + $mfgCostSales;

        // -------------------------------------------------------------
        // 4. Others (Admin Matrl, Admin Mfg, OH + Profit)
        // -------------------------------------------------------------
        $adminMatrlEng = $matCostEng * (floatval($policyEng->admin_matrl_pct) / 100);
        $adminMatrlSales = $matCostSales * (floatval($policySales->admin_matrl_pct) / 100);

        $adminMfgEng = $mfgCostEng * (floatval($policyEng->admin_mfg_pct) / 100);
        $adminMfgSales = $mfgCostSales * (floatval($policySales->admin_mfg_pct) / 100);

        $ohProfitEng = 0.0; // Engineering = 0%
        $ohProfitSales = ($cogmSales + $adminMatrlSales + $adminMfgSales) * (floatval($policySales->oh_profit_pct) / 100);

        // -------------------------------------------------------------
        // 5. COGS Totals
        // -------------------------------------------------------------
        $cogsEng = $cogmEng + $adminMatrlEng + $adminMfgEng;
        $cogsSales = $cogmSales + $adminMatrlSales + $adminMfgSales + $ohProfitSales;

        $itemMarginIdr = $cogsSales - $cogsEng;
        $itemMarginPct = $cogsSales > 0 ? ($itemMarginIdr / $cogsSales) * 100 : 0.0;

        return [
            'item' => $item,
            'eng' => [
                'material_rate' => $matPriceEng,
                'material_cost' => $matCostEng,
                'mfg_cost' => $mfgCostEng,
                'cogm' => $cogmEng,
                'admin_matrl' => $adminMatrlEng,
                'admin_matrl_pct' => $policyEng->admin_matrl_pct,
                'admin_mfg' => $adminMfgEng,
                'admin_mfg_pct' => $policyEng->admin_mfg_pct,
                'oh_profit' => $ohProfitEng,
                'oh_profit_pct' => $policyEng->oh_profit_pct,
                'cogs' => $cogsEng,
            ],
            'sales' => [
                'material_rate' => $matPriceSales,
                'material_cost' => $matCostSales,
                'mfg_cost' => $mfgCostSales,
                'cogm' => $cogmSales,
                'admin_matrl' => $adminMatrlSales,
                'admin_matrl_pct' => $policySales->admin_matrl_pct,
                'admin_mfg' => $adminMfgSales,
                'admin_mfg_pct' => $policySales->admin_mfg_pct,
                'oh_profit' => $ohProfitSales,
                'oh_profit_pct' => $policySales->oh_profit_pct,
                'cogs' => $cogsSales,
            ],
            'margin_idr' => $itemMarginIdr,
            'margin_pct' => $itemMarginPct,
        ];
    }

    /**
     * Match closest Material Rate from master data.
     */
    protected function matchMaterialRate($materials, $spec, $thick, $rateSource, $customerId = null)
    {
        // 1. Try exact Customer + Spec + Thickness + RateSource
        if ($customerId) {
            $match = $materials->where('rate_source', $rateSource)
                ->where('customer_id', $customerId)
                ->filter(function($m) use ($spec, $thick) {
                    $specMatch = empty($spec) || stripos($m->material_spec, $spec) !== false || stripos($spec, $m->material_spec) !== false;
                    $thickMatch = empty($thick) || empty($m->thickness) || abs($m->thickness - $thick) < 0.05;
                    return $specMatch && $thickMatch;
                })->first();

            if ($match) return $match;
        }

        // 2. Try Global Rate with Spec & Thickness
        $match = $materials->where('rate_source', $rateSource)
            ->whereNull('customer_id')
            ->filter(function($m) use ($spec, $thick) {
                $specMatch = empty($spec) || stripos($m->material_spec, $spec) !== false || stripos($spec, $m->material_spec) !== false;
                $thickMatch = empty($thick) || empty($m->thickness) || abs($m->thickness - $thick) < 0.05;
                return $specMatch && $thickMatch;
            })->first();

        if ($match) return $match;

        // 3. Fallback to any active rate with same rateSource
        return $materials->where('rate_source', $rateSource)->first() ?? $materials->first();
    }

    /**
     * Match closest Stamping Process Rate from master data.
     */
    protected function matchStampingRate($rates, $machineType, $tonnage, $stroke, $partRank, $rateSource)
    {
        // 1. Try exact match: Machine Type + Tonnage (closest) + Complexity Alias/Rank + RateSource
        $match = $rates->where('rate_source', $rateSource)
            ->filter(function($r) use ($machineType, $partRank, $tonnage, $stroke) {
                $machineMatch = empty($machineType) || strcasecmp($r->machine_type, $machineType) === 0;
                $rankMatch = empty($partRank) ||
                    strcasecmp($r->complexity_alias, $partRank) === 0 ||
                    stripos($r->process_complexity, $partRank) !== false;
                $tonnageMatch = empty($tonnage) || $r->tonnage >= ($tonnage - 50);
                return $machineMatch && $rankMatch && $tonnageMatch;
            })->first();

        if ($match) return $match;

        // 2. Match by Complexity Alias & Tonnage
        $match = $rates->where('rate_source', $rateSource)
            ->filter(function($r) use ($partRank, $tonnage) {
                $rankMatch = empty($partRank) ||
                    strcasecmp($r->complexity_alias, $partRank) === 0 ||
                    stripos($r->process_complexity, $partRank) !== false;
                $tonnageMatch = empty($tonnage) || $r->tonnage >= ($tonnage - 50);
                return $rankMatch && $tonnageMatch;
            })->first();

        if ($match) return $match;

        // 3. Fallback to same rateSource
        return $rates->where('rate_source', $rateSource)->first() ?? $rates->first();
    }
}
