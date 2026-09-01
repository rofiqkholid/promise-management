<?php

namespace App\Services\ExcelEngine\Resolvers;

use App\Models\ToolingQuotation;
use App\Models\WorkOrder;
use App\Models\MngEbdHeader;
use App\Models\MfgProcessStpCost;
use App\Models\MfgProcessCost;
use App\Models\MaterialCost;
use App\Services\ExcelEngine\Contracts\DataResolverInterface;

class ToolingQuotationDataResolver implements DataResolverInterface
{
    /**
     * Resolve ToolingQuotation, WorkOrder, or MngEbdHeader entity into normalized array payload for Dynamic Excel Engine
     *
     * @param int|string|ToolingQuotation|WorkOrder|MngEbdHeader $entity
     * @param array $options
     * @return array
     */
    public function resolve($entity, array $options = []): array
    {
        $workOrder = null;
        $ebdHeader = null;
        $quotation = null;

        if ($entity instanceof WorkOrder) {
            $workOrder = $entity;
            $ebdHeader = $workOrder->ebdHeader;
        } elseif ($entity instanceof MngEbdHeader) {
            $ebdHeader = $entity;
            $workOrder = $ebdHeader->workOrder;
        } elseif ($entity instanceof ToolingQuotation) {
            $quotation = $entity;
            $ebdHeader = $quotation->ebdHeader;
            $workOrder = $ebdHeader ? $ebdHeader->workOrder : null;
        } elseif (is_numeric($entity) || is_string($entity)) {
            $workOrder = WorkOrder::with(['ebdHeader', 'inquiry.customer'])->find($entity);
            if ($workOrder) {
                $ebdHeader = $workOrder->ebdHeader;
            } else {
                $ebdHeader = MngEbdHeader::with(['customer', 'projectModel', 'workOrder', 'items.toolingProcesses', 'items.addProcesses'])->find($entity);
                if ($ebdHeader) {
                    $workOrder = $ebdHeader->workOrder;
                } else {
                    $quotation = ToolingQuotation::with(['customer', 'supplier', 'ebdHeader', 'details.ebdItem', 'details.ebdToolingProcess'])->find($entity);
                    if ($quotation) {
                        $ebdHeader = $quotation->ebdHeader;
                        $workOrder = $ebdHeader ? $ebdHeader->workOrder : null;
                    }
                }
            }
        }

        // 1. Resolve Single / Scalar Fields (Header data)
        $quotationNo = $workOrder ? ('QT-' . $workOrder->wo_number) : ($quotation ? $quotation->quotation_no : ('QT-EBD-' . ($ebdHeader->revision ?? '0')));
        $customerObj = optional($workOrder ? optional($workOrder->inquiry)->customer : optional($ebdHeader)->customer);
        $customerName = $customerObj ? ($customerObj->name ?? '') : ($quotation->customer ? $quotation->customer->name : '-');
        $customerCode = $customerObj ? ($customerObj->code ?? '') : ($quotation->customer ? $quotation->customer->code : '');
        $customerId = $customerObj ? ($customerObj->id ?? null) : ($quotation ? $quotation->customer_id : null);

        $fields = [
            'quotation_no'       => $quotationNo,
            'revision'           => $ebdHeader ? ($ebdHeader->revision ?? '0') : ($quotation ? ($quotation->revision ?? '0') : '0'),
            'quotation_date'     => now()->format('Y-m-d'),
            'quote_date'         => now()->format('Y-m-d'),
            'ebd_date'           => $ebdHeader && $ebdHeader->date ? $ebdHeader->date->format('Y-m-d') : now()->format('Y-m-d'),
            'customer_name'      => $customerName,
            'customer_code'      => $customerCode,
            'supplier_name'      => $quotation ? ($quotation->supplier_name ?? optional($quotation->supplier)->name ?? 'Supplier Name') : 'Supplier Name',
            'currency_code'      => $quotation ? ($quotation->currency_code ?? 'IDR') : 'IDR',
            'currency_symbol'    => $quotation ? ($quotation->currency_symbol ?? 'Rp') : 'Rp',
            'exchange_rate'      => $quotation ? (float)($quotation->exchange_rate ?? 1.0) : 1.0,
            'model_name'         => $ebdHeader && $ebdHeader->projectModel ? ($ebdHeader->projectModel->name ?? '') : ($ebdHeader->model_name ?? ''),
            'project_name'       => $ebdHeader && $ebdHeader->projectModel ? ($ebdHeader->projectModel->name ?? '') : ($ebdHeader->project_name ?? ''),
            'ebd_status'         => $ebdHeader->status ?? 'Active',
            'total_cost_idr'     => $quotation ? (float)($quotation->total_cost_idr ?? 0) : 0.0,
            'total_cost_foreign' => $quotation ? (float)($quotation->total_cost_foreign ?? 0) : 0.0,
        ];

        // 2. Load Master Rates for Process & Material Matching
        $stampingRates = MfgProcessStpCost::all();
        $generalMfgRates = MfgProcessCost::all();
        $materials = MaterialCost::all();

        // 3. Resolve EBD Items with Tooling & Assembly Processes
        $ebdItems = collect();
        if ($ebdHeader) {
            $ebdItems = $ebdHeader->items()->with(['toolingProcesses', 'addProcesses'])->get();
        }

        $itemsList = [];
        $flatDetails = [];

        foreach ($ebdItems as $item) {
            // A. Resolve Stamping / Tooling Processes
            $processes = [];
            if ($item->toolingProcesses && $item->toolingProcesses->count() > 0) {
                foreach ($item->toolingProcesses as $tp) {
                    $tonnage = $tp->tonnage ? intval($tp->tonnage) : null;
                    $machineCode = \App\Helpers\MachineTypeHelper::toCode($tp->machine_type);
                    $machineName = \App\Helpers\MachineTypeHelper::toName($tp->machine_type);
                    $machineFullName = \App\Helpers\MachineTypeHelper::toUpperName($tp->machine_type);

                    $stroke = ($tp->stroke !== null && $tp->stroke !== '') ? floatval($tp->stroke) : 1.0;
                    $partRank = strtoupper(trim((string)($item->part_rank ?? '')));
                    $category = strtoupper(trim((string)($tp->category ?? '')));

                    $stpRate = null;
                    // Exclude non-press investment tooling like CF or JIG
                    if ($tonnage && !in_array($category, ['CF', 'JIG'])) {
                        // 1. Customer-Specific Exact Match
                        if (!empty($customerId)) {
                            $stpRate = $stampingRates->first(function ($r) use ($machineName, $tonnage, $partRank, $customerId) {
                                if (empty($r->customer_id) || (string)$r->customer_id !== (string)$customerId) return false;
                                $mMatch = \App\Helpers\MachineTypeHelper::isMatch($r->machine_type, $machineName);
                                $tMatch = abs(intval($r->tonnage) - $tonnage) <= 25 || intval($r->tonnage) === $tonnage;
                                $rMatch = empty($partRank) || strcasecmp($r->complexity_alias ?? '', $partRank) === 0 || stripos($r->process_complexity ?? '', $partRank) !== false;
                                return $mMatch && $tMatch && $rMatch;
                            });
                        }

                        // 2. Global Rate Exact Match (Machine + Tonnage + Rank)
                        if (!$stpRate) {
                            $stpRate = $stampingRates->first(function ($r) use ($machineName, $tonnage, $partRank) {
                                if (!empty($r->customer_id)) return false;
                                $mMatch = \App\Helpers\MachineTypeHelper::isMatch($r->machine_type, $machineName);
                                $tMatch = abs(intval($r->tonnage) - $tonnage) <= 25 || intval($r->tonnage) === $tonnage;
                                $rMatch = empty($partRank) || strcasecmp($r->complexity_alias ?? '', $partRank) === 0 || stripos($r->process_complexity ?? '', $partRank) !== false;
                                return $mMatch && $tMatch && $rMatch;
                            });
                        }

                        // 3. Fallback: Global by Tonnage & Rank (e.g. Hydraulic fallback to Tandem rate)
                        if (!$stpRate) {
                            $stpRate = $stampingRates->first(function ($r) use ($tonnage, $partRank) {
                                if (!empty($r->customer_id)) return false;
                                $tMatch = abs(intval($r->tonnage) - $tonnage) <= 25 || intval($r->tonnage) === $tonnage;
                                $rMatch = empty($partRank) || strcasecmp($r->complexity_alias ?? '', $partRank) === 0 || stripos($r->process_complexity ?? '', $partRank) !== false;
                                return $tMatch && $rMatch;
                            });
                        }

                        // 4. Fallback: Global by Tonnage only
                        if (!$stpRate) {
                            $stpRate = $stampingRates->first(function ($r) use ($tonnage) {
                                if (!empty($r->customer_id)) return false;
                                return abs(intval($r->tonnage) - $tonnage) <= 25 || intval($r->tonnage) === $tonnage;
                            });
                        }
                    }

                    $costRateVal = $stpRate ? floatval($stpRate->std_cost_rate) : null;
                    $minCostRateVal = $stpRate ? floatval($stpRate->min_cost_rate) : null;
                    $stampingCostVal = $stpRate ? (($costRateVal * $stroke) / max(1, intval($tp->output ?? 1))) : null;

                    $processes[] = [
                        'ebd_tool_op'                => $tp->op,
                        'op'                         => $tp->op,
                        'ebd_tool_process_name'      => $tp->process_name,
                        'process_name'               => $tp->process_name,
                        'tooling_process_name'       => $tp->process_name,
                        'ebd_tool_category'          => $tp->category,
                        'category'                   => $tp->category,
                        'tooling_type'               => $tp->category,
                        'ebd_tool_homeline'          => $tp->prod_homeline,
                        'homeline'                   => $tp->prod_homeline,
                        'ebd_tool_tonnage'           => $tp->tonnage,
                        'tonnage'                    => $tp->tonnage,
                        'machine_type'               => $tp->machine_type,
                        'ebd_tool_machine_type'      => $tp->machine_type,
                        'machine_code'               => $machineCode,
                        'ebd_tool_machine_code'      => $machineCode,
                        'machine_name'               => $machineName,
                        'ebd_tool_machine_name'      => $machineName,
                        'machine_full_name'          => $machineFullName,
                        'ebd_tool_machine_full_name' => $machineFullName,
                        'stroke'                     => $tp->stroke ?? $stroke,
                        'ebd_tool_die_height'        => $tp->die_height,
                        'die_height'                 => $tp->die_height,
                        'ebd_tool_output'            => $tp->output,
                        'output'                     => $tp->output,
                        'ebd_tool_price_idr'         => $tp->price_idr,
                        'price_idr'                  => $tp->price_idr,
                        'cost_idr'                   => $tp->price_idr,
                        'cost_rate'                  => $costRateVal,
                        'std_cost_rate'              => $costRateVal,
                        'min_cost_rate'              => $minCostRateVal,
                        'stamping_cost'              => $stampingCostVal,
                        'ebd_tool_status'            => $tp->tooling_status,
                        'supplier_status'            => $tp->tooling_status,
                        'ebd_tool_information'       => $tp->information,
                        'remarks'                    => $tp->information,
                    ];
                }
            }

            // B. Resolve Additional / Assembly Processes
            $additionalProcesses = [];
            if ($item->addProcesses && $item->addProcesses->count() > 0) {
                foreach ($item->addProcesses as $ap) {
                    $procName = trim($ap->process_name ?? '');
                    $rawQty = floatval($ap->qty ?? 0.0);
                    $qtyMultiplier = $rawQty > 0 ? $rawQty : 1.0;

                    $apRate = null;
                    if (!empty($customerId)) {
                        // 1. Try Customer-Specific Process Rate Match
                        $apRate = $generalMfgRates->first(function ($r) use ($procName, $customerId) {
                            if (empty($procName) || empty($r->customer_id) || $r->customer_id != $customerId) return false;
                            $mfgName = trim($r->process_name);
                            return strcasecmp($mfgName, $procName) === 0 ||
                                   stripos($mfgName, $procName) !== false ||
                                   stripos($procName, $mfgName) !== false;
                        });
                    }

                    // 2. Fallback to Global Process Rate Match (customer_id is null)
                    if (!$apRate) {
                        $apRate = $generalMfgRates->first(function ($r) use ($procName) {
                            if (empty($procName) || !empty($r->customer_id)) return false;
                            $mfgName = trim($r->process_name);
                            return strcasecmp($mfgName, $procName) === 0 ||
                                   stripos($mfgName, $procName) !== false ||
                                   stripos($procName, $mfgName) !== false;
                        }) ?? $generalMfgRates->first(function ($r) use ($procName) {
                            if (empty($procName)) return false;
                            $mfgName = trim($r->process_name);
                            return strcasecmp($mfgName, $procName) === 0 ||
                                   stripos($mfgName, $procName) !== false ||
                                   stripos($procName, $mfgName) !== false;
                        });
                    }

                    $rateStd = $apRate ? floatval($apRate->std_cost_rate) : null;
                    $valCalc = $rateStd !== null ? ($rateStd * $qtyMultiplier) : null;

                    $additionalProcesses[] = [
                        'add_process_name'         => $procName,
                        'ebd_add_process_name'     => $procName,
                        'process_name'             => $procName,
                        'add_qty'                  => $rawQty > 0 ? $rawQty : null,
                        'ebd_add_process_qty'      => $rawQty > 0 ? $rawQty : null,
                        'qty'                      => $rawQty > 0 ? $rawQty : null,
                        'add_unit'                 => $ap->unit ?? 'PCS',
                        'ebd_add_process_unit'     => $ap->unit ?? 'PCS',
                        'unit'                     => $ap->unit ?? 'PCS',
                        'add_proc_sales'           => $valCalc,
                        'add_proc_eng'             => $valCalc,
                        'add_proc_cost'            => $valCalc,
                        'add_cost_idr'             => $valCalc,
                        'ebd_add_process_cost_idr' => $valCalc,
                        'cost_idr'                 => $valCalc,
                        'price_idr'                => $valCalc,
                        'cost_rate'                => $rateStd,
                        'std_cost_rate'            => $rateStd,
                    ];
                }
            }

            // C. Match Material Cost from Master Data
            $matSpec = trim($item->mat_spec ?? '');
            $matThick = ($item->mat_thick !== null && $item->mat_thick !== '') ? floatval($item->mat_thick) : null;
            $rateMat = null;
            if (!empty($matSpec) || !empty($matThick)) {
                $cleanSpec = preg_replace('/[^A-Za-z0-9]/', '', strtolower($matSpec));

                // 1. Customer-Specific Match
                if (!empty($customerId)) {
                    // 1a. Customer + Exact Spec & Thickness
                    $rateMat = $materials->first(function ($m) use ($matSpec, $matThick, $customerId, $cleanSpec) {
                        if (empty($m->customer_id) || (string)$m->customer_id !== (string)$customerId) return false;
                        $mClean = preg_replace('/[^A-Za-z0-9]/', '', strtolower($m->material_spec ?? ''));
                        $specMatch = !empty($matSpec) && (
                            strcasecmp($m->material_spec, $matSpec) === 0 ||
                            stripos($m->material_spec, $matSpec) !== false ||
                            stripos($matSpec, $m->material_spec) !== false ||
                            (!empty($cleanSpec) && $mClean === $cleanSpec)
                        );
                        $thickMatch = empty($matThick) || empty($m->thickness) || abs(floatval($m->thickness) - $matThick) < 0.05;
                        return $specMatch && $thickMatch;
                    });
                }

                // 2. Global Rate Match (customer_id is null)
                if (!$rateMat) {
                    $rateMat = $materials->first(function ($m) use ($matSpec, $matThick, $cleanSpec) {
                        if (!empty($m->customer_id)) return false;
                        $mClean = preg_replace('/[^A-Za-z0-9]/', '', strtolower($m->material_spec ?? ''));
                        $specMatch = !empty($matSpec) && (
                            strcasecmp($m->material_spec, $matSpec) === 0 ||
                            stripos($m->material_spec, $matSpec) !== false ||
                            stripos($matSpec, $m->material_spec) !== false ||
                            (!empty($cleanSpec) && $mClean === $cleanSpec)
                        );
                        $thickMatch = empty($matThick) || empty($m->thickness) || abs(floatval($m->thickness) - $matThick) < 0.05;
                        return $specMatch && $thickMatch;
                    });
                }

                // 3. Fallback Match by Spec Only (Global)
                if (!$rateMat && !empty($matSpec)) {
                    $rateMat = $materials->first(function ($m) use ($matSpec, $cleanSpec) {
                        if (!empty($m->customer_id)) return false;
                        $mClean = preg_replace('/[^A-Za-z0-9]/', '', strtolower($m->material_spec ?? ''));
                        return stripos($m->material_spec, $matSpec) !== false ||
                               stripos($matSpec, $m->material_spec) !== false ||
                               (!empty($cleanSpec) && $mClean === $cleanSpec);
                    }) ?? $materials->first(function ($m) use ($matSpec, $cleanSpec) {
                        $mClean = preg_replace('/[^A-Za-z0-9]/', '', strtolower($m->material_spec ?? ''));
                        return stripos($m->material_spec, $matSpec) !== false ||
                               stripos($matSpec, $m->material_spec) !== false ||
                               (!empty($cleanSpec) && $mClean === $cleanSpec);
                    });
                }
            }

            $matPricePerKg = $rateMat ? floatval($rateMat->price_per_kg) : null;
            $scrapPricePerKg = $rateMat ? floatval($rateMat->scrap_price_per_kg) : null;
            $matCostVal = ($rateMat && $item->weight) ? (floatval($item->weight) * $matPricePerKg) : null;

            // D. Build Normalized Item Object
            $itemData = [
                'ebd_part_no'          => $item->part_no,
                'part_no'              => $item->part_no,
                'part_number'          => $item->part_no,
                'ebd_part_name'        => $item->part_name,
                'part_name'            => $item->part_name,
                'ebd_part_rank'        => $item->part_rank,
                'part_rank'            => $item->part_rank,
                'ebd_active_level'     => $item->active_level,
                'active_level'         => $item->active_level,
                'ebd_pcs_month'        => $item->pcs_month,
                'pcs_month'            => $item->pcs_month,
                'ebd_qty_unit'         => $item->qty_unit,
                'qty_unit'             => $item->qty_unit,

                // Dimensions
                'ebd_part_width'       => $item->width,
                'width'                => $item->width,
                'ebd_part_length'      => $item->length,
                'length'               => $item->length,
                'ebd_part_height'      => $item->height,
                'height'               => $item->height,
                'ebd_part_weight'      => $item->weight,
                'weight'               => $item->weight,

                // Material Specifications & Dimensions
                'ebd_mat_spec'         => $item->mat_spec ?: null,
                'mat_spec'             => $item->mat_spec ?: null,
                'material_spec'        => $item->mat_spec ?: null,
                'ebd_mat_thick'        => $item->mat_thick ?: null,
                'mat_thick'            => $item->mat_thick ?: null,
                'material_thick'       => $item->mat_thick ?: null,
                'ebd_mat_width'        => $item->mat_width ?: null,
                'mat_width'            => $item->mat_width ?: null,
                'material_width'       => $item->mat_width ?: null,
                'ebd_mat_length'       => $item->mat_length ?: null,
                'mat_length'           => $item->mat_length ?: null,
                'material_length'      => $item->mat_length ?: null,
                'ebd_mat_pcs_sheet'    => $item->mat_pcs_sheet ?: null,
                'mat_pcs_sheet'        => $item->mat_pcs_sheet ?: null,
                'material_pcs_sheet'   => $item->mat_pcs_sheet ?: null,
                'pcs_sheet'            => $item->mat_pcs_sheet ?: null,
                'ebd_mat_weight_pcs'   => $item->mat_weight_pcs ?: null,
                'mat_weight_pcs'       => $item->mat_weight_pcs ?: null,
                'material_weight_pcs'  => $item->mat_weight_pcs ?: null,
                'ebd_mat_yield_ratio'  => $item->mat_yield_ratio ?: null,
                'mat_yield_ratio'      => $item->mat_yield_ratio ?: null,
                'material_yield_ratio' => $item->mat_yield_ratio ?: null,
                'yield_ratio'          => $item->mat_yield_ratio ?: null,

                // Master Matched Material & Scrap Prices
                'mat_price_per_kg'     => $matPricePerKg,
                'mat_price_sales'      => $matPricePerKg,
                'mat_price_eng'        => $matPricePerKg,
                'material_price'       => $matPricePerKg,
                'material_rate'        => $matPricePerKg,
                'scrap_price_per_kg'   => $scrapPricePerKg,
                'scrap_price_sales'    => $scrapPricePerKg,
                'scrap_price_eng'      => $scrapPricePerKg,
                'scrap_price'          => $scrapPricePerKg,
                'scrap_rate'           => $scrapPricePerKg,
                'material_cost'        => $matCostVal,
                'material_sales'       => $matCostVal,
                'material_eng'         => $matCostVal,

                // Standard Parts
                'ebd_std_part_no'      => $item->std_part_no,
                'std_part_no'          => $item->std_part_no,
                'ebd_std_part_name'    => $item->std_part_name,
                'std_part_name'        => $item->std_part_name,
                'ebd_std_qty'          => $item->std_qty,
                'std_qty'              => $item->std_qty,
                'ebd_std_uom'          => $item->std_uom,
                'std_uom'              => $item->std_uom,

                'processes'            => $processes,
                'additional_processes' => $additionalProcesses,
                'add_processes'        => $additionalProcesses,
                'tooling_processes'    => $processes,
            ];

            $itemsList[] = $itemData;

            // Flat process detail for simple single-row table loops
            foreach ($processes as $p) {
                $flatDetails[] = array_merge($itemData, $p);
            }
        }

        return [
            'fields'                => $fields,
            'items'                 => $itemsList,
            'ebd_items'             => $itemsList,
            'cost_comparison_items' => $itemsList,
            'details'               => $flatDetails,
            'sections'             => [
                'items'            => $itemsList,
                'ebd_items'        => $itemsList,
                'details'          => $flatDetails,
            ],
            // Backward compatibility aliases
            'single_fields'        => $fields,
            'table_loops'          => [
                'items'            => $itemsList,
                'ebd_items'        => $itemsList,
                'details'          => $flatDetails,
            ]
        ];
    }
}
