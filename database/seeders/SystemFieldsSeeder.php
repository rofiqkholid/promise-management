<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MngCfgSystemField;

class SystemFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            // =========================================================================
            // 1. EBD HEADER FIELDS (mng_ebd_headers)
            // =========================================================================
            ['field_key' => 'ebd_date', 'label' => 'EBD Document Date', 'group' => 'ebd_header', 'data_type' => 'date', 'target_table' => 'mng_ebd_headers', 'target_column' => 'date', 'is_required' => false],
            ['field_key' => 'ebd_revision', 'label' => 'EBD Revision', 'group' => 'ebd_header', 'data_type' => 'string', 'target_table' => 'mng_ebd_headers', 'target_column' => 'revision', 'is_required' => false],
            ['field_key' => 'ebd_status', 'label' => 'EBD Document Status', 'group' => 'ebd_header', 'data_type' => 'string', 'target_table' => 'mng_ebd_headers', 'target_column' => 'status', 'is_required' => false],

            // =========================================================================
            // 2. EBD ITEM / BOM FIELDS (mng_ebd_items) — Parent Part Repeater
            // =========================================================================
            ['field_key' => 'ebd_active_level', 'label' => 'BOM Level (1, 2, 3...)', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'active_level', 'is_required' => false],
            ['field_key' => 'ebd_part_no', 'label' => 'Part Number', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_no', 'is_required' => true],
            ['field_key' => 'ebd_part_name', 'label' => 'Part Name', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_name', 'is_required' => true],
            ['field_key' => 'ebd_pcs_month', 'label' => 'Production Pcs / Month', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'pcs_month', 'is_required' => false],
            ['field_key' => 'ebd_qty_unit', 'label' => 'Qty / Unit Vehicle', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'qty_unit', 'is_required' => false],
            ['field_key' => 'ebd_part_width', 'label' => 'Part Width (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'width', 'is_required' => false],
            ['field_key' => 'ebd_part_length', 'label' => 'Part Length (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'length', 'is_required' => false],
            ['field_key' => 'ebd_part_height', 'label' => 'Part Height (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'height', 'is_required' => false],
            ['field_key' => 'ebd_part_weight', 'label' => 'Part Weight (kg)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'weight', 'is_required' => false],
            ['field_key' => 'ebd_part_rank', 'label' => 'Part Rank (A/B/C)', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_rank', 'is_required' => false],
            
            // Material Spec Fields (mng_ebd_items)
            ['field_key' => 'ebd_mat_spec', 'label' => 'Material Spec', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_spec', 'is_required' => false],
            ['field_key' => 'ebd_mat_thick', 'label' => 'Material Thickness (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_thick', 'is_required' => false],
            ['field_key' => 'ebd_mat_width', 'label' => 'Blank Width (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_width', 'is_required' => false],
            ['field_key' => 'ebd_mat_length', 'label' => 'Blank Length (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_length', 'is_required' => false],
            ['field_key' => 'ebd_mat_pcs_sheet', 'label' => 'Pcs / Sheet', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_pcs_sheet', 'is_required' => false],
            ['field_key' => 'ebd_mat_weight_pcs', 'label' => 'Material Weight / Pcs (kg)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_weight_pcs', 'is_required' => false],
            ['field_key' => 'ebd_mat_yield_ratio', 'label' => 'Yield Ratio (%)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_yield_ratio', 'is_required' => false],

            // Standard Component Fields (mng_ebd_items)
            ['field_key' => 'ebd_std_part_no', 'label' => 'Standard Part No', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_part_no', 'is_required' => false],
            ['field_key' => 'ebd_std_part_name', 'label' => 'Standard Part Name', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_part_name', 'is_required' => false],
            ['field_key' => 'ebd_std_qty', 'label' => 'Standard Part Qty', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_qty', 'is_required' => false],
            ['field_key' => 'ebd_std_uom', 'label' => 'Standard Part Unit', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_uom', 'is_required' => false],

            // =========================================================================
            // 3. EBD TOOLING PROCESSES (mng_ebd_tooling_processes) — Child Process Repeater
            // =========================================================================
            ['field_key' => 'ebd_tool_op', 'label' => 'OP Number', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'op', 'is_required' => false],
            ['field_key' => 'ebd_tool_process_name', 'label' => 'Process Description', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'process_name', 'is_required' => false],
            ['field_key' => 'ebd_tool_category', 'label' => 'Tooling Category (DIES/JIG/CF)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'category', 'is_required' => false],
            ['field_key' => 'ebd_tool_homeline', 'label' => 'Production Homeline (SAI/SUBCONT)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'prod_homeline', 'is_required' => false],
            ['field_key' => 'ebd_tool_tonnage', 'label' => 'Press Tonnage', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'tonnage', 'is_required' => false],
            ['field_key' => 'ebd_tool_die_height', 'label' => 'Die Height (mm)', 'group' => 'ebd_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'die_height', 'is_required' => false],
            ['field_key' => 'ebd_tool_output', 'label' => 'Tool Output Cavity', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'output', 'is_required' => false],
            ['field_key' => 'ebd_tool_price_idr', 'label' => 'Tooling Price (IDR)', 'group' => 'ebd_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'price_idr', 'is_required' => false],
            ['field_key' => 'ebd_tool_status', 'label' => 'Tooling Status (NEW/MODIF)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'tooling_status', 'is_required' => false],
            ['field_key' => 'ebd_tool_information', 'label' => 'Process Notes / Information', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'information', 'is_required' => false],

            // =========================================================================
            // 4. EBD ADD PROCESSES (mng_ebd_add_processes) — Secondary Add Process Repeater
            // =========================================================================
            ['field_key' => 'ebd_add_process_name', 'label' => 'Add Process Name', 'group' => 'ebd_add_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'process_name', 'is_required' => false],
            ['field_key' => 'ebd_add_process_qty', 'label' => 'Add Process Qty', 'group' => 'ebd_add_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'qty', 'is_required' => false],
            ['field_key' => 'ebd_add_process_unit', 'label' => 'Add Process Unit', 'group' => 'ebd_add_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'unit', 'is_required' => false],
            ['field_key' => 'ebd_add_process_cost_idr', 'label' => 'Add Process Cost (IDR)', 'group' => 'ebd_add_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'cost_idr', 'is_required' => false],
        ];

        foreach ($fields as $field) {
            MngCfgSystemField::updateOrCreate(
                ['field_key' => $field['field_key']],
                $field
            );
        }
    }
}
