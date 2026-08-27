<?php

namespace App\Services\ExcelEngine\Resolvers;

use App\Models\ToolingQuotation;
use App\Services\ExcelEngine\Contracts\DataResolverInterface;

class ToolingQuotationDataResolver implements DataResolverInterface
{
    /**
     * Resolve ToolingQuotation entity into normalized array payload for Dynamic Excel Engine
     *
     * @param int|string|ToolingQuotation $entity
     * @param array $options
     * @return array
     */
    public function resolve($entity, array $options = []): array
    {
        $quotation = $entity instanceof ToolingQuotation
            ? $entity
            : ToolingQuotation::with([
                'customer',
                'supplier',
                'ebdHeader',
                'details.ebdItem',
                'details.ebdToolingProcess'
            ])->findOrFail($entity);

        // Ensure relations loaded
        if (!$quotation->relationLoaded('details')) {
            $quotation->load([
                'customer',
                'supplier',
                'ebdHeader',
                'details.ebdItem',
                'details.ebdToolingProcess'
            ]);
        }

        // 1. Resolve Single / Scalar Fields (Header data)
        $fields = [
            'quotation_no'         => $quotation->quotation_no,
            'revision'             => $quotation->revision ?? '0',
            'quotation_date'       => $quotation->created_at ? $quotation->created_at->format('Y-m-d') : date('Y-m-d'),
            'customer_name'        => $quotation->customer ? $quotation->customer->name : ($quotation->supplier_name ?? '-'),
            'customer_code'        => $quotation->customer ? $quotation->customer->code : '',
            'supplier_name'        => $quotation->supplier ? $quotation->supplier->name : ($quotation->supplier_name ?? '-'),
            'currency_code'        => $quotation->currency_code ?? 'IDR',
            'currency_symbol'      => $quotation->currency_symbol,
            'exchange_rate'        => (float)($quotation->exchange_rate ?? 1),
            'total_cost_idr'       => (float)($quotation->total_cost_idr ?? 0),
            'total_cost_foreign'   => (float)($quotation->total_cost_foreign ?? 0),
            'model_name'           => $quotation->ebdHeader ? ($quotation->ebdHeader->model_name ?? '') : '',
            'project_name'         => $quotation->ebdHeader ? ($quotation->ebdHeader->project_name ?? '') : '',
        ];

        // 2. Group details by EBD Item / Part for section repeaters
        $ebdItemsMap = [];
        $flatDetails = [];

        foreach ($quotation->details as $detail) {
            $item = $detail->ebdItem;
            $itemId = $detail->ebd_item_id ?? 'general';

            if (!isset($ebdItemsMap[$itemId])) {
                $ebdItemsMap[$itemId] = [
                    'ebd_item_id'       => $itemId,
                    'ebd_part_no'       => $item ? ($item->part_number ?? $item->part_no ?? '') : '',
                    'ebd_part_name'     => $item ? ($item->part_name ?? '') : '',
                    'ebd_mat_spec'      => $item ? ($item->material_spec ?? $item->material_type ?? '') : '',
                    'ebd_mat_thick'     => $item ? (float)($item->material_thickness ?? 0) : 0,
                    'ebd_model'         => $item ? ($item->model ?? '') : '',
                    'total_item_cost'   => 0,
                    'processes'         => [],
                ];
            }

            $detailCost = (float)($detail->cost_idr ?? 0);
            $ebdItemsMap[$itemId]['total_item_cost'] += $detailCost;

            $processData = [
                'op'                   => $detail->op,
                'process_name'         => $detail->tooling_process_name ?? ($detail->ebdToolingProcess ? $detail->ebdToolingProcess->name : ''),
                'tooling_type'         => $detail->tooling_type ?? '',
                'tonnage'              => (float)($detail->tonnage ?? 0),
                'die_height'           => (float)($detail->die_height ?? 0),
                'die_category'         => $detail->die_category ?? '',
                'cost_foreign'         => (float)($detail->cost_foreign ?? 0),
                'cost_idr'             => $detailCost,
                'remarks'              => $detail->remarks ?? '',
            ];

            $ebdItemsMap[$itemId]['processes'][] = $processData;

            // Flat item for simple single-level table loops
            $flatDetails[] = array_merge($processData, [
                'ebd_part_no'   => $ebdItemsMap[$itemId]['ebd_part_no'],
                'ebd_part_name' => $ebdItemsMap[$itemId]['ebd_part_name'],
                'ebd_mat_spec'  => $ebdItemsMap[$itemId]['ebd_mat_spec'],
                'ebd_mat_thick' => $ebdItemsMap[$itemId]['ebd_mat_thick'],
            ]);
        }

        $ebdItemsList = array_values($ebdItemsMap);

        return [
            'fields'        => $fields,
            'ebd_items'     => $ebdItemsList,
            'details'       => $flatDetails,
            'sections'      => [
                'ebd_items' => $ebdItemsList,
                'details'   => $flatDetails,
            ],
            // Backward compatibility keys
            'single_fields' => $fields,
            'table_loops'   => [
                'ebd_items' => $ebdItemsList,
                'details'   => $flatDetails,
            ]
        ];
    }
}
