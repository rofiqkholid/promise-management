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
    public function calculateForEbdHeader($ebdHeaderId, $request = null)
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

        // 3. Fetch all Quotations for this EBD Header (Sales revisions, Customer quotes, Supplier quotes)
        $allQuotations = \App\Models\ToolingQuotation::with([
            'details',
            'productDetails',
            'supplier',
            'customer',
            'importer'
        ])->where('ebd_header_id', $ebdHeaderId)
          ->orderBy('revision', 'desc')
          ->orderBy('id', 'desc')
          ->get();

        // Separate by source_type
        $salesRevisions = $allQuotations->filter(fn($q) => $q->source_type === 'sales')->values();
        $customerQuotes = $allQuotations->filter(fn($q) => $q->source_type === 'customer' || (!empty($q->customer_id) && empty($q->supplier_id)))->values();
        $supplierQuotes = $allQuotations->filter(fn($q) => $q->source_type === 'supplier' || !empty($q->supplier_id))->values();

        // Determine Active Revisi Sales
        $activeSalesRev = null;
        if ($salesRevisions->isNotEmpty()) {
            $selectedSalesRevId = $request ? $request->input('sales_rev_id') : null;
            $activeSalesRev = $selectedSalesRevId ? ($salesRevisions->firstWhere('id', $selectedSalesRevId) ?? $salesRevisions->first()) : $salesRevisions->first();
            $activeSalesRev->all_revisions = $salesRevisions;
        }

        // Determine Active Customer Quote
        $activeCustomerQuote = null;
        if ($customerQuotes->isNotEmpty()) {
            $selectedCustQuoteId = $request ? $request->input('cust_quote_id') : null;
            $activeCustomerQuote = $selectedCustQuoteId ? ($customerQuotes->firstWhere('id', $selectedCustQuoteId) ?? $customerQuotes->first()) : $customerQuotes->first();
            $activeCustomerQuote->all_revisions = $customerQuotes;
        }

        // Determine Active Supplier Quote & Group suppliers
        $rankedSuppliers = collect();
        $groupedBySupplier = $supplierQuotes->groupBy('supplier_id');
        foreach ($groupedBySupplier as $supplierId => $sQuotes) {
            $selectedSuppRevId = $request ? $request->input("supp_quote_{$supplierId}") : null;
            $activeSupp = $selectedSuppRevId ? ($sQuotes->firstWhere('id', $selectedSuppRevId) ?? $sQuotes->first()) : $sQuotes->first();
            $activeSupp->all_revisions = $sQuotes;
            $rankedSuppliers->push($activeSupp);
        }
        $rankedSuppliers = $rankedSuppliers->sortBy('total_cost_idr')->values();
        foreach ($rankedSuppliers as $rIdx => $sq) {
            $sq->worth_rank = $rIdx + 1;
        }

        $selectedSupplierId = $request ? $request->input('supp_quote_id') : null;
        $activeSupplierQuote = $selectedSupplierId 
            ? ($rankedSuppliers->firstWhere('id', $selectedSupplierId) ?? $rankedSuppliers->first())
            : $rankedSuppliers->first();

        // 4. Calculate Product Cost Comparison per item & totals
        $salesRevProductDetails = $activeSalesRev ? $activeSalesRev->productDetails->keyBy('ebd_item_id') : collect();
        $custProductDetails = $activeCustomerQuote ? $activeCustomerQuote->productDetails->keyBy('ebd_item_id') : collect();

        $itemsCalculations = [];
        $totals = [
            'material_eng' => 0.0,
            'material_sales' => 0.0,
            'material_sales_rev' => 0.0,
            'material_customer' => 0.0,

            'stamping_eng' => 0.0,
            'stamping_sales' => 0.0,
            'stamping_sales_rev' => 0.0,
            'stamping_customer' => 0.0,

            'add_proc_eng' => 0.0,
            'add_proc_sales' => 0.0,
            'add_proc_sales_rev' => 0.0,
            'add_proc_customer' => 0.0,

            'mfg_eng' => 0.0,
            'mfg_sales' => 0.0,
            'mfg_sales_rev' => 0.0,
            'mfg_customer' => 0.0,

            'cogm_eng' => 0.0,
            'cogm_sales' => 0.0,
            'cogm_sales_rev' => 0.0,
            'cogm_customer' => 0.0,

            'admin_matrl_eng' => 0.0,
            'admin_matrl_sales' => 0.0,
            'admin_matrl_sales_rev' => 0.0,
            'admin_matrl_customer' => 0.0,

            'admin_mfg_eng' => 0.0,
            'admin_mfg_sales' => 0.0,
            'admin_mfg_sales_rev' => 0.0,
            'admin_mfg_customer' => 0.0,

            'oh_profit_eng' => 0.0,
            'oh_profit_sales' => 0.0,
            'oh_profit_sales_rev' => 0.0,
            'oh_profit_customer' => 0.0,

            'cogs_eng' => 0.0,
            'cogs_sales' => 0.0,
            'cogs_sales_rev' => 0.0,
            'cogs_customer' => 0.0,
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

            $qty = max(1, $item->qty_unit ?? 1);

            // Revisi Sales mapping
            if ($activeSalesRev) {
                $sRevDetail = $salesRevProductDetails->get($item->id);
                $salesRevMat = $sRevDetail ? floatval($sRevDetail->material_cost) : $itemResult['sales']['material_cost'];
                $salesRevStp = $sRevDetail ? floatval($sRevDetail->stamping_cost) : $itemResult['sales']['stamping_cost'];
                $salesRevAdd = $sRevDetail ? floatval($sRevDetail->add_proc_cost) : $itemResult['sales']['add_proc_cost'];
                $salesRevMfg = $sRevDetail ? (floatval($sRevDetail->mfg_process_cost) ?: ($salesRevStp + $salesRevAdd)) : $itemResult['sales']['mfg_cost'];
                $salesRevCogm = $sRevDetail ? (floatval($sRevDetail->cogm) ?: ($salesRevMat + $salesRevMfg)) : $itemResult['sales']['cogm'];
                $salesRevCogs = $sRevDetail ? floatval($sRevDetail->cogs) : $itemResult['sales']['cogs'];
            } else {
                $salesRevMat = null;
                $salesRevStp = null;
                $salesRevAdd = null;
                $salesRevMfg = null;
                $salesRevCogm = null;
                $salesRevCogs = null;
            }

            // Customer Target mapping
            $custDetail = $custProductDetails->get($item->id);
            $custMat = $custDetail ? floatval($custDetail->material_cost) : null;
            $custStp = $custDetail ? floatval($custDetail->stamping_cost) : null;
            $custAdd = $custDetail ? floatval($custDetail->add_proc_cost) : null;
            $custMfg = $custDetail ? (floatval($custDetail->mfg_process_cost) ?: (($custStp ?? 0) + ($custAdd ?? 0))) : null;
            $custCogm = $custDetail ? (floatval($custDetail->cogm) ?: (($custMat ?? 0) + ($custMfg ?? 0))) : null;
            $custCogs = $custDetail ? floatval($custDetail->cogs) : null;

            $itemResult['sales_rev'] = [
                'material_cost' => $salesRevMat,
                'stamping_cost' => $salesRevStp,
                'add_proc_cost' => $salesRevAdd,
                'mfg_cost' => $salesRevMfg,
                'cogm' => $salesRevCogm,
                'cogs' => $salesRevCogs,
            ];

            $itemResult['customer'] = [
                'material_cost' => $custMat,
                'stamping_cost' => $custStp,
                'add_proc_cost' => $custAdd,
                'mfg_cost' => $custMfg,
                'cogm' => $custCogm,
                'cogs' => $custCogs,
            ];

            $itemsCalculations[] = $itemResult;

            // Accumulate Eng & Sales
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

            // Accumulate Revisi Sales
            if ($activeSalesRev && $salesRevCogs !== null) {
                $totals['material_sales_rev'] += ($salesRevMat * $qty);
                $totals['stamping_sales_rev'] += ($salesRevStp * $qty);
                $totals['add_proc_sales_rev'] += ($salesRevAdd * $qty);
                $totals['mfg_sales_rev'] += ($salesRevMfg * $qty);
                $totals['cogm_sales_rev'] += ($salesRevCogm * $qty);
                $totals['cogs_sales_rev'] += ($salesRevCogs * $qty);
            }

            // Accumulate Customer Target
            if ($custMat !== null) $totals['material_customer'] += ($custMat * $qty);
            if ($custMfg !== null) $totals['mfg_customer'] += ($custMfg * $qty);
            if ($custCogm !== null) $totals['cogm_customer'] += ($custCogm * $qty);
            if ($custCogs !== null) $totals['cogs_customer'] += ($custCogs * $qty);
        }

        // Policies for Revisi Sales & Customer
        $adminMatrlRevPct = $activeSalesRev && $activeSalesRev->admin_matrl_pct !== null ? floatval($activeSalesRev->admin_matrl_pct) : floatval($policySales->admin_matrl_pct ?? 0);
        $adminMfgRevPct = $activeSalesRev && $activeSalesRev->admin_mfg_pct !== null ? floatval($activeSalesRev->admin_mfg_pct) : floatval($policySales->admin_mfg_pct ?? 0);
        $ohProfitRevPct = $activeSalesRev && $activeSalesRev->oh_profit_pct !== null ? floatval($activeSalesRev->oh_profit_pct) : floatval($policySales->oh_profit_pct ?? 0);

        $totals['admin_matrl_sales_rev'] = $totals['cogm_sales_rev'] * ($adminMatrlRevPct / 100);
        $totals['admin_mfg_sales_rev'] = $totals['cogm_sales_rev'] * ($adminMfgRevPct / 100);
        $totals['oh_profit_sales_rev'] = $totals['cogm_sales_rev'] * ($ohProfitRevPct / 100);
        if ($activeSalesRev && $activeSalesRev->total_product_cogs > 0) {
            $totals['cogs_sales_rev'] = floatval($activeSalesRev->total_product_cogs);
        }

        $adminMatrlCustPct = $activeCustomerQuote && $activeCustomerQuote->admin_matrl_pct !== null ? floatval($activeCustomerQuote->admin_matrl_pct) : 0.0;
        $adminMfgCustPct = $activeCustomerQuote && $activeCustomerQuote->admin_mfg_pct !== null ? floatval($activeCustomerQuote->admin_mfg_pct) : 0.0;
        $ohProfitCustPct = $activeCustomerQuote && $activeCustomerQuote->oh_profit_pct !== null ? floatval($activeCustomerQuote->oh_profit_pct) : 0.0;

        $totals['admin_matrl_customer'] = $totals['cogm_customer'] * ($adminMatrlCustPct / 100);
        $totals['admin_mfg_customer'] = $totals['cogm_customer'] * ($adminMfgCustPct / 100);
        $totals['oh_profit_customer'] = $totals['cogm_customer'] * ($ohProfitCustPct / 100);
        if ($activeCustomerQuote && $activeCustomerQuote->total_product_cogs > 0) {
            $totals['cogs_customer'] = floatval($activeCustomerQuote->total_product_cogs);
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
        // Calculate Tooling Cost Comparison (Multi-Source Comparison)
        // -------------------------------------------------------------
        $salesRevToolingDetails = $activeSalesRev ? $activeSalesRev->details : collect();
        $custToolingDetails = $activeCustomerQuote ? $activeCustomerQuote->details : collect();
        $suppToolingDetails = $activeSupplierQuote ? $activeSupplierQuote->details : collect();

        $toolingItems = [];
        $totalToolingCostEng = 0.0;
        $totalToolingCostSales = 0.0;
        $totalToolingCostSalesRev = 0.0;
        $totalToolingCostCust = 0.0;
        $totalToolingCostSupp = 0.0;

        $toolingOhProfitEngPct = floatval($policyEng->tooling_oh_profit_pct ?? 0.0);
        $toolingOhProfitSalesPct = floatval($policySales->tooling_oh_profit_pct ?? 20.0);
        $toolingOhProfitSalesRevPct = $activeSalesRev && $activeSalesRev->oh_profit_pct !== null ? floatval($activeSalesRev->oh_profit_pct) : $toolingOhProfitSalesPct;
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

                    // Match Revisi Sales Tooling detail
                    if ($activeSalesRev) {
                        $revTd = $salesRevToolingDetails->first(fn($d) => $d->ebd_tooling_process_id == $tp->id || ($d->ebd_item_id == $item->id && (int)$d->op === (int)$tp->op));
                        $costSalesRev = $revTd ? floatval($revTd->cost_idr) : $costSales;
                        $totalToolingCostSalesRev += $costSalesRev;
                    } else {
                        $costSalesRev = null;
                    }

                    // Match Customer Tooling detail
                    $custTd = $custToolingDetails->first(fn($d) => $d->ebd_tooling_process_id == $tp->id || ($d->ebd_item_id == $item->id && (int)$d->op === (int)$tp->op));
                    $costCustomer = $custTd ? floatval($custTd->cost_idr) : null;
                    if ($costCustomer !== null) $totalToolingCostCust += $costCustomer;

                    // Match Supplier Tooling detail
                    $suppTd = $suppToolingDetails->first(fn($d) => $d->ebd_tooling_process_id == $tp->id || ($d->ebd_item_id == $item->id && (int)$d->op === (int)$tp->op));
                    $costSupplier = $suppTd ? floatval($suppTd->cost_idr) : null;
                    if ($costSupplier !== null) $totalToolingCostSupp += $costSupplier;

                    $toolingItems[] = [
                        'ebd_item_id'          => $item->id,
                        'part_no'              => $item->part_no,
                        'part_name'            => $item->part_name,
                        'tool_rank'            => $tp->tool_rank ?? '-',
                        'category'             => $tp->category ?? 'DIE',
                        'op'                   => $tp->op ?? '-',
                        'process_name'         => $tp->process_name ?? '-',
                        'machine_type'         => $tp->machine_type ?? '-',
                        'prod_homeline'        => $tp->prod_homeline ?? '-',
                        'tonnage'              => $tp->tonnage ?? 0,
                        'die_height'           => $tp->die_height ?? 0,
                        'output'               => $tp->output ?? 1,
                        'stroke'               => $tp->stroke ?? 1,
                        'qty'                  => $toolQty,
                        'price_unit_eng'       => $toolPrice,
                        'total_cost_eng'       => $costEng,
                        'oh_profit_pct'        => $toolingOhProfitSalesPct,
                        'oh_profit_val'        => $ohSalesVal,
                        'total_cost_sales'     => $costSales,
                        'total_cost_sales_rev' => $costSalesRev,
                        'total_cost_customer'  => $costCustomer,
                        'total_cost_supplier'  => $costSupplier,
                        'margin_idr'           => $costSales - $costEng,
                        'margin_pct'           => $costSales > 0 ? (($costSales - $costEng) / $costSales) * 100 : 0.0,
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

        // Tooling Sales Revision
        $toolingCogmSalesRev = $activeSalesRev && $activeSalesRev->total_cost_idr > 0 ? floatval($activeSalesRev->total_cost_idr) : $totalToolingCostSalesRev;
        $toolingOhProfitSalesRevVal = $toolingCogmSalesRev * ($toolingOhProfitSalesRevPct / 100);
        $toolingCogsSalesRev = $toolingCogmSalesRev + $toolingOhProfitSalesRevVal;

        // Tooling Customer Target
        $toolingCogmCust = $activeCustomerQuote && $activeCustomerQuote->total_cost_idr > 0 ? floatval($activeCustomerQuote->total_cost_idr) : $totalToolingCostCust;
        $toolingOhProfitCustPct = $activeCustomerQuote && $activeCustomerQuote->oh_profit_pct !== null ? floatval($activeCustomerQuote->oh_profit_pct) : 0.0;
        $toolingOhProfitCustVal = $toolingCogmCust * ($toolingOhProfitCustPct / 100);
        $toolingCogsCust = $toolingCogmCust + $toolingOhProfitCustVal;

        // Tooling Supplier Best Price
        $toolingCogmSupp = $activeSupplierQuote && $activeSupplierQuote->total_cost_idr > 0 ? floatval($activeSupplierQuote->total_cost_idr) : $totalToolingCostSupp;
        $toolingCogsSupp = $toolingCogmSupp;

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
            'cogm_sales_rev'        => $toolingCogmSalesRev,
            'cogm_customer'         => $toolingCogmCust,
            'cogm_supplier'         => $toolingCogmSupp,
            'oh_profit_eng_pct'     => $toolingOhProfitEngPct,
            'oh_profit_eng_val'     => $toolingOhProfitEngVal,
            'oh_profit_sales_pct'   => $toolingOhProfitSalesPct,
            'oh_profit_sales_val'   => $toolingOhProfitSalesVal,
            'oh_profit_sales_rev_pct' => $toolingOhProfitSalesRevPct,
            'oh_profit_sales_rev_val' => $toolingOhProfitSalesRevVal,
            'oh_profit_customer_pct' => $toolingOhProfitCustPct,
            'oh_profit_customer_val' => $toolingOhProfitCustVal,
            'cogs_eng'              => $toolingCogsEng,
            'cogs_sales'            => $toolingCogsSales,
            'cogs_sales_rev'        => $toolingCogsSalesRev,
            'cogs_customer'         => $toolingCogsCust,
            'cogs_supplier'         => $toolingCogsSupp,
            'margin_idr'            => $toolingMarginIdr,
            'margin_pct'            => $toolingMarginPct,
            'target_std_margin_pct' => $toolingTargetStdMargin,
            'status'                => $toolingStatus,
            'status_badge'          => $toolingStatusBadge,
            'status_text'           => $toolingStatusText,
        ];

        $ebdRevisions = $ebdHeader->getAllRevisions();
        if ($ebdRevisions->count() <= 1 && $ebdHeader->wo_id) {
            $ebdRevisions = MngEbdHeader::where('wo_id', $ebdHeader->wo_id)
                ->with(['workOrder', 'customer', 'projectModel'])
                ->orderBy('revision', 'asc')
                ->get();
        }

        return [
            'ebd_header'            => $ebdHeader,
            'ebd_revisions'         => $ebdRevisions,
            'customer'              => $ebdHeader->customer,
            'project_model'         => $ebdHeader->projectModel,
            'policy_eng'            => $policyEng,
            'policy_sales'          => $policySales,
            'totals'                => $totals,
            'margin_idr'            => $marginIdr,
            'margin_pct'            => $marginPct,
            'target_margin_sales'   => $targetMarginSales,
            'target_margin_eng'     => $targetMarginEng,
            'status'                => $status,
            'status_badge'          => $statusBadge,
            'status_text'           => $statusText,
            'items'                 => $itemsCalculations,
            'tooling'               => $toolingComparison,
            'sales_revisions'       => $salesRevisions,
            'active_sales_rev'      => $activeSalesRev,
            'customer_quotations'   => $customerQuotes,
            'active_customer_quote' => $activeCustomerQuote,
            'supplier_quotations'   => $rankedSuppliers,
            'active_supplier_quote' => $activeSupplierQuote,
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

        // 3. Fetch Quotations for this EBD Header
        $allQuotations = \App\Models\ToolingQuotation::with(['productDetails'])
            ->where('ebd_header_id', $ebdHeaderId)
            ->orderBy('revision', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $salesRevisions = $allQuotations->filter(fn($q) => $q->source_type === 'sales')->values();
        $customerQuotes = $allQuotations->filter(fn($q) => $q->source_type === 'customer' || (!empty($q->customer_id) && empty($q->supplier_id)))->values();

        $selectedSalesRevId = $request->input('sales_rev_id');
        $activeSalesRev = $selectedSalesRevId ? ($salesRevisions->firstWhere('id', $selectedSalesRevId) ?? $salesRevisions->first()) : $salesRevisions->first();

        $selectedCustQuoteId = $request->input('cust_quote_id');
        $activeCustomerQuote = $selectedCustQuoteId ? ($customerQuotes->firstWhere('id', $selectedCustQuoteId) ?? $customerQuotes->first()) : $customerQuotes->first();

        $salesRevDetails = $activeSalesRev ? $activeSalesRev->productDetails->keyBy('ebd_item_id') : collect();
        $custDetails = $activeCustomerQuote ? $activeCustomerQuote->productDetails->keyBy('ebd_item_id') : collect();

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

            // Revisi Sales values
            if ($activeSalesRev) {
                $sRevD = $salesRevDetails->get($item->id);
                $salesRevMat = $sRevD ? floatval($sRevD->material_cost) : $calc['sales']['material_cost'];
                $salesRevStp = $sRevD ? floatval($sRevD->stamping_cost) : $calc['sales']['stamping_cost'];
                $salesRevAdd = $sRevD ? floatval($sRevD->add_proc_cost) : $calc['sales']['add_proc_cost'];
                $salesRevMfg = $sRevD ? (floatval($sRevD->mfg_process_cost) ?: ($salesRevStp + $salesRevAdd)) : $calc['sales']['mfg_cost'];
                $salesRevCogs = $sRevD ? floatval($sRevD->cogs) : $calc['sales']['cogs'];
            } else {
                $salesRevMat = null;
                $salesRevStp = null;
                $salesRevAdd = null;
                $salesRevMfg = null;
                $salesRevCogs = null;
            }

            // Customer values
            $custD = $custDetails->get($item->id);
            $custMat = $custD ? floatval($custD->material_cost) : null;
            $custStp = $custD ? floatval($custD->stamping_cost) : null;
            $custAdd = $custD ? floatval($custD->add_proc_cost) : null;
            $custMfg = $custD ? (floatval($custD->mfg_process_cost) ?: (($custStp ?? 0) + ($custAdd ?? 0))) : null;
            $custCogs = $custD ? floatval($custD->cogs) : null;

            // Collect process details
            $processList = [];
            if ($item->toolingProcesses) {
                foreach ($item->toolingProcesses as $tp) {
                    $opStr = $tp->op ? "OP {$tp->op}" : "ST";
                    $pName = trim($tp->process_name ?? '');
                    if ($pName) {
                        $processList[] = [
                            'type' => 'tooling',
                            'op'   => $opStr,
                            'name' => $pName,
                            'machine' => $tp->machine_type ? trim($tp->machine_type . ($tp->tonnage ? " ({$tp->tonnage}T)" : '')) : null,
                        ];
                    }
                }
            }
            if ($item->addProcesses) {
                foreach ($item->addProcesses as $ap) {
                    $pName = trim($ap->process_name ?? '');
                    if ($pName) {
                        $processList[] = [
                            'type' => 'add',
                            'op'   => 'ADD',
                            'name' => $pName,
                            'machine' => null,
                        ];
                    }
                }
            }

            $matSpec = trim($item->mat_spec ?? '');
            $matThick = $item->mat_thick ? floatval($item->mat_thick) : null;
            $isAssy = (empty($matSpec) || $matSpec === '-') && ($totalProcesses > 0 || $item->active_level == 1);
            if (!empty($matSpec) && $matSpec !== '-') {
                $matFull = $matSpec . ($matThick ? ' (t=' . rtrim(rtrim(number_format($matThick, 2, '.', ''), '0'), '.') . 'mm)' : '');
            } else {
                $matFull = $isAssy ? 'Assembly' : '-';
            }

            $allRows[] = [
                'id'                   => $item->id,
                'part_no'              => $item->part_no ?? '-',
                'part_name'            => $item->part_name ?? '-',
                'active_level'         => intval($item->active_level ?? 1),
                'parent_id'            => $item->parent_id,
                'mat_spec'             => $matSpec ?: '-',
                'mat_thick'            => $matThick,
                'mat_full'             => $matFull,
                'is_assy'              => $isAssy,
                'process_list'         => $processList,
                'total_process'        => $totalProcesses,
                'tp_count'             => $tpCount,
                'ap_count'             => $apCount,
                'eng_mat_cost'         => $calc['eng']['material_cost'],
                'eng_stamping_cost'    => $calc['eng']['stamping_cost'],
                'eng_add_proc_cost'    => $calc['eng']['add_proc_cost'],
                'eng_mfg_cost'         => $calc['eng']['mfg_cost'],
                'eng_cogs'             => $calc['eng']['cogs'],
                'sales_mat_cost'       => $calc['sales']['material_cost'],
                'sales_stamping_cost'  => $calc['sales']['stamping_cost'],
                'sales_add_proc_cost'  => $calc['sales']['add_proc_cost'],
                'sales_mfg_cost'       => $calc['sales']['mfg_cost'],
                'sales_cogs'           => $calc['sales']['cogs'],
                'sales_rev_mat_cost'   => $salesRevMat,
                'sales_rev_mfg_cost'   => $salesRevMfg,
                'sales_rev_cogs'       => $salesRevCogs,
                'cust_mat_cost'        => $custMat,
                'cust_mfg_cost'        => $custMfg,
                'cust_cogs'            => $custCogs,
                'margin_idr'           => $calc['margin_idr'],
                'margin_pct'           => $calc['margin_pct'],
            ];
        }

        // Sorting mapping across all columns
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = strtolower($request->input('order.0.dir', 'asc'));
        $columnsMap = [
            0  => 'id',
            1  => 'part_no',
            2  => 'eng_mat_cost',
            3  => 'eng_mfg_cost',
            4  => 'eng_cogs',
            5  => 'sales_mat_cost',
            6  => 'sales_mfg_cost',
            7  => 'sales_cogs',
            8  => 'sales_rev_mat_cost',
            9  => 'sales_rev_mfg_cost',
            10 => 'sales_rev_cogs',
            11 => 'cust_mat_cost',
            12 => 'cust_mfg_cost',
            13 => 'cust_cogs',
            14 => 'margin_idr',
            15 => 'margin_pct',
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
                    $machineMatch = empty($machineType) || \App\Helpers\MachineTypeHelper::isMatch($r->machine_type, $machineType);
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
                $machineMatch = empty($machineType) || \App\Helpers\MachineTypeHelper::isMatch($r->machine_type, $machineType);
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
