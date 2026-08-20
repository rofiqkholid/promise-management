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
            ['field_key' => 'date', 'label' => 'EBD Document Date', 'group' => 'ebd_header', 'data_type' => 'date', 'target_table' => 'mng_ebd_headers', 'target_column' => 'date', 'is_required' => false],
            ['field_key' => 'revision', 'label' => 'EBD Revision', 'group' => 'ebd_header', 'data_type' => 'string', 'target_table' => 'mng_ebd_headers', 'target_column' => 'revision', 'is_required' => false],
            ['field_key' => 'status', 'label' => 'EBD Document Status', 'group' => 'ebd_header', 'data_type' => 'string', 'target_table' => 'mng_ebd_headers', 'target_column' => 'status', 'is_required' => false],

            // Legacy EBD Header Aliases
            ['field_key' => 'ebd_date', 'label' => 'EBD Date (Legacy)', 'group' => 'ebd_header', 'data_type' => 'date', 'target_table' => 'mng_ebd_headers', 'target_column' => 'date', 'is_required' => false],
            ['field_key' => 'ebd_revision', 'label' => 'EBD Revision (Legacy)', 'group' => 'ebd_header', 'data_type' => 'string', 'target_table' => 'mng_ebd_headers', 'target_column' => 'revision', 'is_required' => false],
            ['field_key' => 'ebd_status', 'label' => 'EBD Status (Legacy)', 'group' => 'ebd_header', 'data_type' => 'string', 'target_table' => 'mng_ebd_headers', 'target_column' => 'status', 'is_required' => false],

            // =========================================================================
            // 2. EBD ITEM / BOM FIELDS (mng_ebd_items) — Matching Column Names
            // =========================================================================
            ['field_key' => 'part_no', 'label' => 'Part Number', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_no', 'is_required' => true],
            ['field_key' => 'part_name', 'label' => 'Part Name', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_name', 'is_required' => true],
            ['field_key' => 'part_rank', 'label' => 'Part Rank (A/B/C)', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_rank', 'is_required' => false],
            ['field_key' => 'active_level', 'label' => 'BOM Level (1, 2, 3...)', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'active_level', 'is_required' => false],
            ['field_key' => 'qty_unit', 'label' => 'Qty / Unit Vehicle', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'qty_unit', 'is_required' => false],
            ['field_key' => 'pcs_month', 'label' => 'Production Pcs / Month', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'pcs_month', 'is_required' => false],
            
            // Dimensions
            ['field_key' => 'width', 'label' => 'Part Width (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'width', 'is_required' => false],
            ['field_key' => 'length', 'label' => 'Part Length (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'length', 'is_required' => false],
            ['field_key' => 'height', 'label' => 'Part Height (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'height', 'is_required' => false],
            ['field_key' => 'weight', 'label' => 'Part Weight (kg)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'weight', 'is_required' => false],
            
            // Material Specification
            ['field_key' => 'mat_spec', 'label' => 'Material Spec', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_spec', 'is_required' => false],
            ['field_key' => 'mat_thick', 'label' => 'Material Thickness (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_thick', 'is_required' => false],
            ['field_key' => 'mat_width', 'label' => 'Blank Width (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_width', 'is_required' => false],
            ['field_key' => 'mat_length', 'label' => 'Blank Length (mm)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_length', 'is_required' => false],
            ['field_key' => 'mat_pcs_sheet', 'label' => 'Pcs / Sheet', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_pcs_sheet', 'is_required' => false],
            ['field_key' => 'mat_weight_pcs', 'label' => 'Material Weight / Pcs (kg)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_weight_pcs', 'is_required' => false],
            ['field_key' => 'mat_yield_ratio', 'label' => 'Yield Ratio (%)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_yield_ratio', 'is_required' => false],

            // Standard Components
            ['field_key' => 'std_part_no', 'label' => 'Standard Part No', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_part_no', 'is_required' => false],
            ['field_key' => 'std_part_name', 'label' => 'Standard Part Name', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_part_name', 'is_required' => false],
            ['field_key' => 'std_qty', 'label' => 'Standard Part Qty', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_qty', 'is_required' => false],
            ['field_key' => 'std_uom', 'label' => 'Standard Part Unit', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'std_uom', 'is_required' => false],

            // Packing & Transport
            ['field_key' => 'sketch', 'label' => 'Part Sketch Image / Path', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'sketch', 'is_required' => false],
            ['field_key' => 'packing_type', 'label' => 'Packing Type', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'packing_type', 'is_required' => false],
            ['field_key' => 'pcs_packing', 'label' => 'Pcs / Packing', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'pcs_packing', 'is_required' => false],
            ['field_key' => 'part_vol_m2', 'label' => 'Part Volume (m2)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_vol_m2', 'is_required' => false],
            ['field_key' => 'truck_vol_m2', 'label' => 'Truck Volume (m2)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'truck_vol_m2', 'is_required' => false],

            // Legacy EBD Item Aliases (for backward compatibility)
            ['field_key' => 'ebd_part_no', 'label' => 'Part No (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_no', 'is_required' => false],
            ['field_key' => 'ebd_part_name', 'label' => 'Part Name (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_name', 'is_required' => false],
            ['field_key' => 'ebd_mat_spec', 'label' => 'Material Spec (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_spec', 'is_required' => false],
            ['field_key' => 'ebd_mat_thick', 'label' => 'Material Thickness (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_thick', 'is_required' => false],
            ['field_key' => 'ebd_mat_width', 'label' => 'Blank Width (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_width', 'is_required' => false],
            ['field_key' => 'ebd_mat_length', 'label' => 'Blank Length (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_length', 'is_required' => false],
            ['field_key' => 'ebd_mat_pcs_sheet', 'label' => 'Pcs / Sheet (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_pcs_sheet', 'is_required' => false],
            ['field_key' => 'ebd_mat_weight_pcs', 'label' => 'Material Weight / Pcs (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_weight_pcs', 'is_required' => false],
            ['field_key' => 'ebd_mat_yield_ratio', 'label' => 'Yield Ratio (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'mat_yield_ratio', 'is_required' => false],
            ['field_key' => 'ebd_qty_unit', 'label' => 'Qty / Unit (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'qty_unit', 'is_required' => false],
            ['field_key' => 'ebd_pcs_month', 'label' => 'Pcs / Month (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'pcs_month', 'is_required' => false],
            ['field_key' => 'ebd_part_width', 'label' => 'Part Width (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'width', 'is_required' => false],
            ['field_key' => 'ebd_part_length', 'label' => 'Part Length (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'length', 'is_required' => false],
            ['field_key' => 'ebd_part_height', 'label' => 'Part Height (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'height', 'is_required' => false],
            ['field_key' => 'ebd_part_weight', 'label' => 'Part Weight (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => 'weight', 'is_required' => false],
            ['field_key' => 'ebd_part_rank', 'label' => 'Part Rank (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_rank', 'is_required' => false],
            ['field_key' => 'ebd_active_level', 'label' => 'BOM Level (Legacy ebd_)', 'group' => 'ebd_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'active_level', 'is_required' => false],

            // =========================================================================
            // 3. EBD TOOLING PROCESSES (mng_ebd_tooling_processes) — Matching Column Names
            // =========================================================================
            ['field_key' => 'op', 'label' => 'OP Number', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'op', 'is_required' => false],
            ['field_key' => 'tool_rank', 'label' => 'Tooling Rank (A/B/C)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'tool_rank', 'is_required' => false],
            ['field_key' => 'process_name', 'label' => 'Process Description / Name', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'process_name', 'is_required' => false],
            ['field_key' => 'category', 'label' => 'Tooling Category (DIE/JIG/CF)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'category', 'is_required' => false],
            ['field_key' => 'prod_homeline', 'label' => 'Production Homeline (SAI/SUBCONT)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'prod_homeline', 'is_required' => false],
            ['field_key' => 'tonnage', 'label' => 'Press Tonnage', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'tonnage', 'is_required' => false],
            ['field_key' => 'die_height', 'label' => 'Die Height (mm)', 'group' => 'ebd_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'die_height', 'is_required' => false],
            ['field_key' => 'output', 'label' => 'Tool Output Cavity', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'output', 'is_required' => false],
            ['field_key' => 'output_type', 'label' => 'Tool Output Type', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'output_type', 'is_required' => false],
            ['field_key' => 'qty', 'label' => 'Tooling Qty', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'qty', 'is_required' => false],
            ['field_key' => 'price_idr', 'label' => 'Tooling Price (IDR)', 'group' => 'ebd_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'price_idr', 'is_required' => false],
            ['field_key' => 'tooling_status', 'label' => 'Tooling Status (NEW/MODIF)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'tooling_status', 'is_required' => false],
            ['field_key' => 'information', 'label' => 'Process Notes / Information', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'information', 'is_required' => false],
            ['field_key' => 'stroke', 'label' => 'Press Stroke', 'group' => 'ebd_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'stroke', 'is_required' => false],
            ['field_key' => 'machine_type', 'label' => 'Machine Type (Tandem/Transfer/Prog)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'machine_type', 'is_required' => false],

            // Legacy EBD Tooling Aliases
            ['field_key' => 'ebd_tool_op', 'label' => 'OP Number (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'op', 'is_required' => false],
            ['field_key' => 'ebd_tool_process_name', 'label' => 'Process Description (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'process_name', 'is_required' => false],
            ['field_key' => 'ebd_tool_category', 'label' => 'Category (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'category', 'is_required' => false],
            ['field_key' => 'ebd_tool_homeline', 'label' => 'Homeline (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'prod_homeline', 'is_required' => false],
            ['field_key' => 'ebd_tool_tonnage', 'label' => 'Press Tonnage (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'tonnage', 'is_required' => false],
            ['field_key' => 'ebd_tool_die_height', 'label' => 'Die Height (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'die_height', 'is_required' => false],
            ['field_key' => 'ebd_tool_output', 'label' => 'Output Cavity (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'output', 'is_required' => false],
            ['field_key' => 'ebd_tool_price_idr', 'label' => 'Tooling Price (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'price_idr', 'is_required' => false],
            ['field_key' => 'ebd_tool_status', 'label' => 'Tooling Status (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'tooling_status', 'is_required' => false],
            ['field_key' => 'ebd_tool_information', 'label' => 'Information (Legacy ebd_)', 'group' => 'ebd_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_tooling_processes', 'target_column' => 'information', 'is_required' => false],

            // =========================================================================
            // 4. EBD ADD PROCESSES (mng_ebd_add_processes) — Secondary Add Process Repeater
            // =========================================================================
            ['field_key' => 'add_process_name', 'label' => 'Add Process Name', 'group' => 'ebd_add_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'process_name', 'is_required' => false],
            ['field_key' => 'add_qty', 'label' => 'Add Process Qty', 'group' => 'ebd_add_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'qty', 'is_required' => false],
            ['field_key' => 'add_unit', 'label' => 'Add Process Unit', 'group' => 'ebd_add_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'unit', 'is_required' => false],
            ['field_key' => 'add_cost_idr', 'label' => 'Add Process Cost (IDR)', 'group' => 'ebd_add_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'cost_idr', 'is_required' => false],
            ['field_key' => 'ebd_add_process_name', 'label' => 'Add Process Name (Legacy ebd_)', 'group' => 'ebd_add_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'process_name', 'is_required' => false],
            ['field_key' => 'ebd_add_process_qty', 'label' => 'Add Process Qty (Legacy ebd_)', 'group' => 'ebd_add_process', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'qty', 'is_required' => false],
            ['field_key' => 'ebd_add_process_unit', 'label' => 'Add Process Unit (Legacy ebd_)', 'group' => 'ebd_add_process', 'data_type' => 'string', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'unit', 'is_required' => false],
            ['field_key' => 'ebd_add_process_cost_idr', 'label' => 'Add Process Cost (Legacy ebd_)', 'group' => 'ebd_add_process', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_add_processes', 'target_column' => 'cost_idr', 'is_required' => false],

            // =========================================================================
            // 5. GENERAL & TOOLING QUOTATION FIELDS
            // =========================================================================
            ['field_key' => 'quotation_no', 'label' => 'Quotation Number', 'group' => 'header', 'data_type' => 'string', 'target_table' => 'mng_tooling_quotations', 'target_column' => 'rfq_no', 'is_required' => false],
            ['field_key' => 'quote_date', 'label' => 'Quotation Date', 'group' => 'header', 'data_type' => 'date', 'target_table' => 'mng_tooling_quotations', 'target_column' => 'quote_date', 'is_required' => false],
            ['field_key' => 'supplier_name', 'label' => 'Supplier / Vendor Name', 'group' => 'header', 'data_type' => 'string', 'target_table' => 'suppliers', 'target_column' => 'name', 'is_required' => false],
            ['field_key' => 'part_number', 'label' => 'Part Number (General)', 'group' => 'header', 'data_type' => 'string', 'target_table' => 'mng_tooling_quotation_details', 'target_column' => 'detail_part_number', 'is_required' => false],
            ['field_key' => 'part_name', 'label' => 'Part Name (General)', 'group' => 'header', 'data_type' => 'string', 'target_table' => 'mng_tooling_quotation_details', 'target_column' => 'detail_part_name', 'is_required' => false],
            ['field_key' => 'currency_code', 'label' => 'Currency Code', 'group' => 'header', 'data_type' => 'string', 'target_table' => 'mng_tooling_quotations', 'target_column' => 'currency_code', 'is_required' => false],
            ['field_key' => 'exchange_rate', 'label' => 'Exchange Rate to IDR', 'group' => 'header', 'data_type' => 'decimal', 'target_table' => 'mng_tooling_quotations', 'target_column' => 'exchange_rate', 'is_required' => false],
            ['field_key' => 'total_cost_idr', 'label' => 'Total Cost (IDR)', 'group' => 'header', 'data_type' => 'decimal', 'target_table' => 'mng_tooling_quotations', 'target_column' => 'total_cost_idr', 'is_required' => false],
            ['field_key' => 'total_cost_foreign', 'label' => 'Total Cost (Foreign)', 'group' => 'header', 'data_type' => 'decimal', 'target_table' => 'mng_tooling_quotations', 'target_column' => 'total_cost_foreign', 'is_required' => false],

            // =========================================================================
            // 6. COST COMPARISON & QUOTATION SUMMARY (Header Matrix)
            // =========================================================================
            ['field_key' => 'cogs_eng', 'label' => 'Total COGS (Engineering / HPP)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'cogs_sales', 'label' => 'Total COGS (Sales / Quotation)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'margin_idr', 'label' => 'Total Gross Margin (IDR)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'margin_pct', 'label' => 'Total Gross Margin (%)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'total_material_eng', 'label' => 'Total Material Cost (Eng)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'total_material_sales', 'label' => 'Total Material Cost (Sales)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'total_mfg_eng', 'label' => 'Total Mfg Process Cost (Eng)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'total_mfg_sales', 'label' => 'Total Mfg Process Cost (Sales)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'total_cogm_eng', 'label' => 'Total COGM (Eng)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'total_cogm_sales', 'label' => 'Total COGM (Sales)', 'group' => 'cost_comparison_summary', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'total_parts_count', 'label' => 'Total Parts Count', 'group' => 'cost_comparison_summary', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_headers', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'customer_code', 'label' => 'Customer Code', 'group' => 'cost_comparison_summary', 'data_type' => 'string', 'target_table' => 'customers', 'target_column' => 'code', 'is_required' => false],
            ['field_key' => 'customer_name', 'label' => 'Customer Name', 'group' => 'cost_comparison_summary', 'data_type' => 'string', 'target_table' => 'customers', 'target_column' => 'name', 'is_required' => false],
            ['field_key' => 'model_name', 'label' => 'Project Model Name', 'group' => 'cost_comparison_summary', 'data_type' => 'string', 'target_table' => 'project_models', 'target_column' => 'name', 'is_required' => false],

            // =========================================================================
            // 7. COST COMPARISON & QUOTATION PER-PART DETAIL (Loop Items)
            // =========================================================================
            ['field_key' => 'part_no', 'label' => 'Part Number (Loop)', 'group' => 'cost_comparison_item', 'data_type' => 'string', 'target_table' => 'mng_ebd_items', 'target_column' => 'part_no', 'is_required' => false],
            ['field_key' => 'qty_unit', 'label' => 'Quantity per Vehicle Unit', 'group' => 'cost_comparison_item', 'data_type' => 'numeric', 'target_table' => 'mng_ebd_items', 'target_column' => 'qty_unit', 'is_required' => false],
            ['field_key' => 'material_eng', 'label' => 'Material Cost (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'material_sales', 'label' => 'Material Cost (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'mat_price_eng', 'label' => 'Material Price / Kg (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'mat_price_sales', 'label' => 'Material Price / Kg (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'stamping_eng', 'label' => 'Stamping Cost (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'stamping_sales', 'label' => 'Stamping Cost (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'add_proc_eng', 'label' => 'Additional / Subcon Cost (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'add_proc_sales', 'label' => 'Additional / Subcon Cost (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'mfg_eng', 'label' => 'Total Mfg Process Cost (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'mfg_sales', 'label' => 'Total Mfg Process Cost (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'cogm_eng', 'label' => 'Total COGM (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'cogm_sales', 'label' => 'Total COGM (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'admin_matrl_eng', 'label' => 'Admin Material Cost (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'admin_matrl_sales', 'label' => 'Admin Material Cost (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'admin_mfg_eng', 'label' => 'Admin Mfg Cost (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'admin_mfg_sales', 'label' => 'Admin Mfg Cost (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'oh_profit_eng', 'label' => 'Overhead & Profit (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'oh_profit_sales', 'label' => 'Overhead & Profit (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'item_cogs_eng', 'label' => 'Unit COGS (Eng)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'item_cogs_sales', 'label' => 'Unit COGS / Quotation (Sales)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'item_margin_idr', 'label' => 'Unit Margin (IDR)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'item_margin_pct', 'label' => 'Unit Margin (%)', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
            ['field_key' => 'selling_price', 'label' => 'Selling Price / Unit Price', 'group' => 'cost_comparison_item', 'data_type' => 'decimal', 'target_table' => 'mng_ebd_items', 'target_column' => null, 'is_required' => false],
        ];

        foreach ($fields as $field) {
            MngCfgSystemField::updateOrCreate(
                ['field_key' => $field['field_key']],
                $field
            );
        }
    }
}
