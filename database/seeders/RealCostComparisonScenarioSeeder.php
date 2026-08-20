<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\ProjectModel;
use App\Models\CustomerCostPolicy;
use App\Models\MaterialCost;
use App\Models\MfgProcessStpCost;
use App\Models\MfgProcessCost;
use App\Models\MngEbdHeader;
use App\Models\MngEbdItem;
use App\Models\MngEbdToolingProcess;
use App\Models\MngEbdAddProcess;

class RealCostComparisonScenarioSeeder extends Seeder
{
    public function run(): void
    {
        // =========================================================================
        // 1. GET TARGET CUSTOMER (MMKI) & MODEL (5J45)
        // =========================================================================
        $custMMKI = Customer::where('code', 'MMKI')->first() ?? Customer::first();
        if (!$custMMKI) {
            return;
        }

        $modelMMKI = ProjectModel::where('name', '5J45')->where('customer_id', $custMMKI->id)->first()
            ?? ProjectModel::where('customer_id', $custMMKI->id)->first();

        if (!$modelMMKI) {
            return;
        }

        // =========================================================================
        // 2. SEED / UPDATE MASTER COST POLICIES (ENGINEERING HPP + SALES MMKI)
        // =========================================================================
        // Engineering Baseline (Internal HPP)
        CustomerCostPolicy::updateOrCreate(
            [
                'customer_id' => null,
                'rate_source' => 'Engineering',
            ],
            [
                'admin_matrl_pct' => 2.00,
                'admin_mfg_pct' => 4.00,
                'oh_profit_pct' => 0.00,
                'min_std_margin_pct' => 8.00,
                'tooling_oh_profit_pct' => 0.00,
                'tooling_min_std_margin_pct' => 20.00,
                'notes' => 'Internal Engineering Cost Baseline (Admin Matrl 2%, Admin Mfg 4%, OH 0%).',
            ]
        );

        // MMKI Sales Policy
        CustomerCostPolicy::updateOrCreate(
            [
                'customer_id' => $custMMKI->id,
                'rate_source' => 'Sales',
            ],
            [
                'admin_matrl_pct' => 2.50,
                'admin_mfg_pct' => 4.50,
                'oh_profit_pct' => 12.00,
                'min_std_margin_pct' => 12.00,
                'tooling_oh_profit_pct' => 20.00,
                'tooling_min_std_margin_pct' => 20.00,
                'notes' => 'Commercial Sales Policy for MMKI (Target Margin 12.00%, Markup OH 12.00%, Tooling OH 20%).',
            ]
        );

        // =========================================================================
        // 3. SEED / UPDATE MASTER MATERIAL COSTS (MMKI MATERIAL SHEET)
        // =========================================================================
        $materialsData = [
            ['spec' => 'MJSC270C-OD', 'type' => 'Sheet', 'thick' => 1.20, 'sales' => 18842.0, 'eng' => 17200.0, 'scrap' => 4500.0],
            ['spec' => 'MJSC270D-OD', 'type' => 'Sheet', 'thick' => 0.80, 'sales' => 19435.0, 'eng' => 17800.0, 'scrap' => 4500.0],
            ['spec' => 'SGT7F30/30',   'type' => 'Sheet', 'thick' => 0.80, 'sales' => 27614.0, 'eng' => 25200.0, 'scrap' => 5500.0],
            ['spec' => 'MJSH400W-OP', 'type' => 'Sheet', 'thick' => 2.30, 'sales' => 14326.0, 'eng' => 13100.0, 'scrap' => 4000.0],
            ['spec' => 'SGT7F',        'type' => 'Sheet', 'thick' => 1.20, 'sales' => 26940.0, 'eng' => 24600.0, 'scrap' => 5500.0],
            ['spec' => 'MJSH270C-OP', 'type' => 'Sheet', 'thick' => 2.30, 'sales' => 13965.0, 'eng' => 12800.0, 'scrap' => 4000.0],
        ];

        foreach ($materialsData as $m) {
            // Engineering Global
            MaterialCost::updateOrCreate(
                [
                    'material_spec' => $m['spec'],
                    'thickness' => $m['thick'],
                    'rate_source' => 'Engineering',
                    'customer_id' => null,
                ],
                [
                    'material_type' => $m['type'],
                    'price_per_kg' => $m['eng'],
                    'scrap_price_per_kg' => $m['scrap'],
                    'valid_from' => '2026-01-01',
                    'is_active' => true,
                ]
            );

            // Sales for MMKI
            MaterialCost::updateOrCreate(
                [
                    'material_spec' => $m['spec'],
                    'thickness' => $m['thick'],
                    'rate_source' => 'Sales',
                    'customer_id' => $custMMKI->id,
                ],
                [
                    'material_type' => $m['type'],
                    'price_per_kg' => $m['sales'],
                    'scrap_price_per_kg' => $m['scrap'],
                    'valid_from' => '2026-01-01',
                    'is_active' => true,
                ]
            );
        }

        // =========================================================================
        // 4. SEED / UPDATE MASTER STAMPING PROCESS RATES
        // =========================================================================
        $stampingMatrix = [
            // 110-150 Ton
            ['ton' => 110, 'cat' => 'Small', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 410.0, 'std_e' => 430.0, 'std_s' => 465.0],
            ['ton' => 110, 'cat' => 'Small', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 420.0, 'std_e' => 440.0, 'std_s' => 475.0],
            ['ton' => 110, 'cat' => 'Small', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 780.0, 'std_e' => 820.0, 'std_s' => 890.0],

            ['ton' => 150, 'cat' => 'Small', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 425.0, 'std_e' => 447.0, 'std_s' => 485.0],
            ['ton' => 150, 'cat' => 'Small', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 434.0, 'std_e' => 456.0, 'std_s' => 495.0],
            ['ton' => 150, 'cat' => 'Small', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 810.0, 'std_e' => 850.0, 'std_s' => 920.0],
            
            // 200 Ton
            ['ton' => 200, 'cat' => 'Small', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 434.0, 'std_e' => 457.0, 'std_s' => 495.0],
            ['ton' => 200, 'cat' => 'Small', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 443.0, 'std_e' => 466.0, 'std_s' => 505.0],
            ['ton' => 200, 'cat' => 'Small', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 830.0, 'std_e' => 875.0, 'std_s' => 950.0],
            
            // 300 Ton
            ['ton' => 300, 'cat' => 'Medium', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 1109.0, 'std_e' => 1167.0, 'std_s' => 1260.0],
            ['ton' => 300, 'cat' => 'Medium', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 1117.0, 'std_e' => 1176.0, 'std_s' => 1270.0],
            ['ton' => 300, 'cat' => 'Medium', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 2096.0, 'std_e' => 2206.0, 'std_s' => 2390.0],
            
            // 400 Ton
            ['ton' => 400, 'cat' => 'Medium', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 1373.0, 'std_e' => 1445.0, 'std_s' => 1560.0],
            ['ton' => 400, 'cat' => 'Medium', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 1383.0, 'std_e' => 1456.0, 'std_s' => 1575.0],
            ['ton' => 400, 'cat' => 'Medium', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 2595.0, 'std_e' => 2732.0, 'std_s' => 2950.0],
            
            // 500 Ton
            ['ton' => 500, 'cat' => 'Large', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 2319.0, 'std_e' => 2441.0, 'std_s' => 2650.0],
            ['ton' => 500, 'cat' => 'Large', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 2378.0, 'std_e' => 2503.0, 'std_s' => 2710.0],
            ['ton' => 500, 'cat' => 'Large', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 4830.0, 'std_e' => 5084.0, 'std_s' => 5500.0],
            
            // 600 Ton
            ['ton' => 600, 'cat' => 'Large', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 2295.0, 'std_e' => 2416.0, 'std_s' => 2620.0],
            ['ton' => 600, 'cat' => 'Large', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 2460.0, 'std_e' => 2589.0, 'std_s' => 2800.0],
            ['ton' => 600, 'cat' => 'Large', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 4950.0, 'std_e' => 5210.0, 'std_s' => 5650.0],
            
            // 800 Ton
            ['ton' => 800, 'cat' => 'Large', 'out' => 1, 'comp' => 'Inner', 'alias' => 'I', 'min_e' => 2437.0, 'std_e' => 2565.0, 'std_s' => 2780.0],
            ['ton' => 800, 'cat' => 'Large', 'out' => 1, 'comp' => 'Outer', 'alias' => 'O', 'min_e' => 2612.0, 'std_e' => 2749.0, 'std_s' => 2980.0],
            ['ton' => 800, 'cat' => 'Large', 'out' => 1, 'comp' => 'Outer Extra Large', 'alias' => 'OL', 'min_e' => 5250.0, 'std_e' => 5520.0, 'std_s' => 6000.0],
        ];

        foreach ($stampingMatrix as $s) {
            // Engineering Rate
            MfgProcessStpCost::updateOrCreate(
                [
                    'machine_type' => 'Tandem',
                    'tonnage' => $s['ton'],
                    'complexity_alias' => $s['alias'],
                    'rate_source' => 'Engineering',
                ],
                [
                    'machine_category' => $s['cat'],
                    'output_type' => 'Part',
                    'output_qty' => $s['out'],
                    'stroke' => 1.00,
                    'process_complexity' => $s['comp'],
                    'min_cost_rate' => $s['min_e'],
                    'std_cost_rate' => $s['std_e'],
                    'is_active' => true,
                ]
            );

            // Sales Rate
            MfgProcessStpCost::updateOrCreate(
                [
                    'machine_type' => 'Tandem',
                    'tonnage' => $s['ton'],
                    'complexity_alias' => $s['alias'],
                    'rate_source' => 'Sales',
                ],
                [
                    'machine_category' => $s['cat'],
                    'output_type' => 'Part',
                    'output_qty' => $s['out'],
                    'stroke' => 1.00,
                    'process_complexity' => $s['comp'],
                    'min_cost_rate' => $s['std_e'],
                    'std_cost_rate' => $s['std_s'],
                    'is_active' => true,
                ]
            );
        }

        // =========================================================================
        // 5. SEED / UPDATE MASTER NON-STAMPING & ADDITIONAL PROCESS COSTS
        // =========================================================================
        $nonStampingRates = [
            ['group' => 'Non Stamping', 'name' => 'RSW', 'cp' => 'Qty Spot', 'uom' => 'Spot', 'unit' => 'Idr / spot', 'min_e' => 217.6, 'std_e' => 229.0, 'std_s' => 250.0],
            ['group' => 'Non Stamping', 'name' => 'PSW', 'cp' => 'Qty Spot', 'uom' => 'Spot', 'unit' => 'Idr / spot', 'min_e' => 247.0, 'std_e' => 260.0, 'std_s' => 285.0],
            ['group' => 'Non Stamping', 'name' => 'SSW', 'cp' => 'Qty Spot', 'uom' => 'Spot', 'unit' => 'Idr / spot', 'min_e' => 135.9, 'std_e' => 143.0, 'std_s' => 158.0],
            ['group' => 'Non Stamping', 'name' => 'Stud Weld', 'cp' => 'Qty Spot', 'uom' => 'Spot', 'unit' => 'Idr / spot', 'min_e' => 173.9, 'std_e' => 183.0, 'std_s' => 200.0],
            ['group' => 'Non Stamping', 'name' => 'Shearing', 'cp' => 'Qty Spot', 'uom' => 'Stroke', 'unit' => 'Idr / stroke', 'min_e' => 397.1, 'std_e' => 418.0, 'std_s' => 460.0],
            ['group' => 'Non Stamping', 'name' => 'CO2', 'cp' => 'Length', 'uom' => 'mm', 'unit' => 'Idr / mm', 'min_e' => 15.0, 'std_e' => 18.0, 'std_s' => 20.0],
            ['group' => 'Others', 'name' => 'ED Painting', 'cp' => 'Area', 'uom' => 'pcs', 'unit' => 'Idr / pcs', 'min_e' => 2500.0, 'std_e' => 2800.0, 'std_s' => 3200.0],
            ['group' => 'Others', 'name' => 'Manual Process', 'cp' => 'Cycle time', 'uom' => 'second', 'unit' => 'Idr / second', 'min_e' => 14.5, 'std_e' => 17.0, 'std_s' => 19.0],
            ['group' => 'Others', 'name' => 'QC Check', 'cp' => 'Cycle time', 'uom' => 'second', 'unit' => 'Idr / second', 'min_e' => 14.5, 'std_e' => 17.0, 'std_s' => 19.0],
        ];

        foreach ($nonStampingRates as $ns) {
            MfgProcessCost::updateOrCreate(
                [
                    'process_name' => $ns['name'],
                    'rate_source' => 'Engineering',
                ],
                [
                    'category' => 'Product',
                    'process_group' => $ns['group'],
                    'control_point' => $ns['cp'],
                    'uom' => $ns['uom'],
                    'rate_unit' => $ns['unit'],
                    'min_cost_rate' => $ns['min_e'],
                    'std_cost_rate' => $ns['std_e'],
                    'is_active' => true,
                ]
            );

            MfgProcessCost::updateOrCreate(
                [
                    'process_name' => $ns['name'],
                    'rate_source' => 'Sales',
                ],
                [
                    'category' => 'Product',
                    'process_group' => $ns['group'],
                    'control_point' => $ns['cp'],
                    'uom' => $ns['uom'],
                    'rate_unit' => $ns['unit'],
                    'min_cost_rate' => $ns['std_e'],
                    'std_cost_rate' => $ns['std_s'],
                    'is_active' => true,
                ]
            );
        }

        // =========================================================================
        // 6. SEED / UPDATE EBD PROJECT: MMKI - 5J45 (WITHOUT DELETING OTHER PROJECTS)
        // =========================================================================
        $ebdHeader = MngEbdHeader::firstOrCreate(
            [
                'customer_id' => $custMMKI->id,
                'model_id' => $modelMMKI->id,
            ],
            [
                'date' => '2026-08-18',
                'revision' => '0',
                'status' => 'Released',
                'created_by' => 'Engineering Dept',
            ]
        );

        // A. Level 1: Top Level Assembly Part (Assy Part No: 17201W150P)
        $assyItem = MngEbdItem::updateOrCreate(
            [
                'ebd_header_id' => $ebdHeader->id,
                'part_no' => '17201W150P',
            ],
            [
                'parent_id' => null,
                'active_level' => 1,
                'part_name' => 'TANK ASSY, FUEL',
                'qty_unit' => 1,
                'pcs_month' => 2500,
                'width' => 580.00,
                'length' => 1065.00,
                'height' => 280.00,
                'weight' => 10.840,
                'part_rank' => 'I',
                'status' => 'New Part',
                'mat_spec' => null,
                'mat_thick' => null,
                'mat_width' => null,
                'mat_length' => null,
                'mat_pcs_sheet' => null,
                'mat_weight_pcs' => null,
                'mat_yield_ratio' => null,
                'packing_type' => 'Returnable Steel Rack',
                'pcs_packing' => 10,
                'part_vol_m2' => 0.0850,
                'truck_vol_m2' => 0.8500,
            ]
        );

        // Tooling / Fixture on Top Assembly
        $assyToolings = [
            ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 127.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 39166964, 'st' => 'NEW'],
            ['cat' => 'JIG', 'op' => null, 'name' => 'JIG ALIGNMENT', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 127.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 42179807, 'st' => 'NEW'],
            ['cat' => 'JIG', 'op' => null, 'name' => 'JIG SUB ASSY', 'line' => 'SAI', 'mach' => 'JW', 'ton' => null, 'dh' => 127.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 148489790, 'st' => 'MODIFICATION'],
        ];

        MngEbdToolingProcess::where('ebd_item_id', $assyItem->id)->delete();
        foreach ($assyToolings as $at) {
            MngEbdToolingProcess::create([
                'ebd_item_id' => $assyItem->id,
                'tool_rank' => 'A',
                'category' => $at['cat'],
                'op' => $at['op'],
                'process_name' => $at['name'],
                'prod_homeline' => $at['line'],
                'machine_type' => $at['mach'],
                'tonnage' => $at['ton'],
                'die_height' => $at['dh'],
                'output' => $at['out'],
                'output_type' => $at['out_t'],
                'stroke' => $at['stk'],
                'qty' => 1,
                'price_idr' => $at['price'],
                'tooling_status' => $at['st'],
            ]);
        }

        // Secondary Assembly Processes on Top Level
        MngEbdAddProcess::where('ebd_item_id', $assyItem->id)->delete();
        MngEbdAddProcess::create(['ebd_item_id' => $assyItem->id, 'process_name' => 'RSW', 'qty' => 12, 'unit' => 'Spot']);
        MngEbdAddProcess::create(['ebd_item_id' => $assyItem->id, 'process_name' => 'CO2', 'qty' => 150, 'unit' => 'mm']);
        MngEbdAddProcess::create(['ebd_item_id' => $assyItem->id, 'process_name' => 'QC Check', 'qty' => 30, 'unit' => 'second']);

        // B. Level 2: 9 Child Parts under 17201W150P Assy
        $childParts = [
            [
                'part_no' => 'MB094552',
                'part_name' => 'BRACKET',
                'status' => 'New Part',
                'width' => 140.00,
                'length' => 260.00,
                'height' => 45.00,
                'weight' => 0.485,
                'part_rank' => 'I', // Inner (I)
                'mat_spec' => 'MJSC270C-OD',
                'mat_thick' => 1.20,
                'mat_width' => 1173.00,
                'mat_length' => 630.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'BL (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 450.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 115000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'FORM/PI (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 450.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 125000000, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 100.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 22000000, 'st' => 'NEW'],
                ],
                'adds' => [
                    ['name' => 'RSW', 'qty' => 4, 'unit' => 'Spot'],
                    ['name' => 'QC Check', 'qty' => 10, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => 'MT382549',
                'part_name' => 'CLIP',
                'status' => 'Purchase',
                'width' => 45.00,
                'length' => 120.00,
                'height' => 15.00,
                'weight' => 0.220,
                'part_rank' => 'I', // Inner (I)
                'mat_spec' => 'MJSC270D-OD',
                'mat_thick' => 0.80,
                'mat_width' => 1251.00,
                'mat_length' => 450.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'BL (G)', 'line' => 'SUBCONT', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 85000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'FORM (G)', 'line' => 'SUBCONT', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 90000000, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 80.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 18000000, 'st' => 'COMMON'],
                ],
                'adds' => [
                    ['name' => 'QC Check', 'qty' => 8, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => '17205W000P-R',
                'part_name' => 'TANK, FUEL UPPER',
                'status' => 'New Part',
                'width' => 520.00,
                'length' => 980.00,
                'height' => 140.00,
                'weight' => 2.850,
                'part_rank' => 'OL', // Outer Extra Large (OL)
                'mat_spec' => 'SGT7F30/30',
                'mat_thick' => 0.80,
                'mat_width' => 1065.00,
                'mat_length' => 580.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'DRAW (TIC)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Cav', 'stk' => 1.0, 'price' => 167800815, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'TRIM 1 + TRIM 2 (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.5, 'price' => 113588244, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 30, 'name' => 'FLG (TIC)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 400.0, 'out' => 2, 'out_t' => 'Cav', 'stk' => 0.5, 'price' => 154256892, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 40, 'name' => 'C/REST', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 2, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 163283101, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 127.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 28739778, 'st' => 'COMMON'],
                ],
                'adds' => [
                    ['name' => 'CO2', 'qty' => 120, 'unit' => 'mm'],
                    ['name' => 'ED Painting', 'qty' => 1, 'unit' => 'pcs'],
                    ['name' => 'QC Check', 'qty' => 25, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => '17206W000P-R',
                'part_name' => 'TANK, FUEL LOWER',
                'status' => 'New Part',
                'width' => 520.00,
                'length' => 980.00,
                'height' => 140.00,
                'weight' => 2.920,
                'part_rank' => 'OL', // Outer Extra Large (OL)
                'mat_spec' => 'SGT7F30/30',
                'mat_thick' => 0.80,
                'mat_width' => 1065.00,
                'mat_length' => 580.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'DRAW (HC)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Cav', 'stk' => 1.0, 'price' => 130927918, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'TRIM-PIE+TRIM-PIE (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 165219264, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 30, 'name' => 'FLG REST + FLG (TIC) (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Cav', 'stk' => 1.0, 'price' => 207114149, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 40, 'name' => 'PIE', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 110, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 76976319, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 107.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 31046144, 'st' => 'NEW'],
                ],
                'adds' => [
                    ['name' => 'CO2', 'qty' => 120, 'unit' => 'mm'],
                    ['name' => 'ED Painting', 'qty' => 1, 'unit' => 'pcs'],
                    ['name' => 'QC Check', 'qty' => 25, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => 'MB129324',
                'part_name' => 'STIFFENER',
                'status' => 'New Part',
                'width' => 180.00,
                'length' => 420.00,
                'height' => 35.00,
                'weight' => 1.150,
                'part_rank' => 'I', // Inner (I)
                'mat_spec' => 'MJSH400W-OP',
                'mat_thick' => 2.30,
                'mat_width' => 1360.10,
                'mat_length' => 354.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'BL (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 300, 'dh' => 480.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 160000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'FORM (Bending)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 300, 'dh' => 480.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 170000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 30, 'name' => 'PI (Piercing)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 450.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 140000000, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 110.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 25000000, 'st' => 'NEW'],
                ],
                'adds' => [
                    ['name' => 'RSW', 'qty' => 6, 'unit' => 'Spot'],
                    ['name' => 'QC Check', 'qty' => 10, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => '17242W050P',
                'part_name' => 'SUPPORT',
                'status' => 'Supply',
                'width' => 65.00,
                'length' => 95.00,
                'height' => 25.00,
                'weight' => 0.180,
                'part_rank' => 'I', // Inner (I)
                'mat_spec' => 'SGT7F30/30',
                'mat_thick' => 0.80,
                'mat_width' => 100.00,
                'mat_length' => 105.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'BL (G)', 'line' => 'SUBCONT', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 75000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'FORM/PI (G)', 'line' => 'SUBCONT', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 80000000, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 90.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 16000000, 'st' => 'COMMON'],
                ],
                'adds' => [
                    ['name' => 'Stud Weld', 'qty' => 2, 'unit' => 'Spot'],
                    ['name' => 'QC Check', 'qty' => 8, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => '17242W010P',
                'part_name' => 'PLATE',
                'status' => 'New Part',
                'width' => 110.00,
                'length' => 320.00,
                'height' => 10.00,
                'weight' => 0.650,
                'part_rank' => 'O', // Outer (O)
                'mat_spec' => 'SGT7F',
                'mat_thick' => 1.20,
                'mat_width' => 1100.00,
                'mat_length' => 450.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'BL (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 450.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 110000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'FORM (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 450.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 120000000, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 100.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 20000000, 'st' => 'NEW'],
                ],
                'adds' => [
                    ['name' => 'RSW', 'qty' => 4, 'unit' => 'Spot'],
                    ['name' => 'QC Check', 'qty' => 10, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => '17311W010P',
                'part_name' => 'PLATE',
                'status' => 'Purchase',
                'width' => 70.00,
                'length' => 150.00,
                'height' => 10.00,
                'weight' => 0.140,
                'part_rank' => 'I', // Inner (I)
                'mat_spec' => 'SGT7F30/30',
                'mat_thick' => 0.80,
                'mat_width' => 188.00,
                'mat_length' => 84.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'BL (G)', 'line' => 'SUBCONT', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 70000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'FORM (G)', 'line' => 'SUBCONT', 'mach' => 'Tandem', 'ton' => 150, 'dh' => 400.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 75000000, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 90.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 15000000, 'st' => 'COMMON'],
                ],
                'adds' => [
                    ['name' => 'QC Check', 'qty' => 6, 'unit' => 'second'],
                ],
            ],
            [
                'part_no' => 'MB329780',
                'part_name' => 'PLATE',
                'status' => 'New Part',
                'width' => 220.00,
                'length' => 560.00,
                'height' => 15.00,
                'weight' => 1.450,
                'part_rank' => 'I', // Inner (I)
                'mat_spec' => 'MJSH270C-OP',
                'mat_thick' => 2.30,
                'mat_width' => 1358.10,
                'mat_length' => 720.00,
                'ops' => [
                    ['cat' => 'DIE', 'op' => 10, 'name' => 'BL (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 300, 'dh' => 480.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 150000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 20, 'name' => 'FORM (G)', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 300, 'dh' => 480.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 160000000, 'st' => 'NEW'],
                    ['cat' => 'DIE', 'op' => 30, 'name' => 'PIE', 'line' => 'SAI', 'mach' => 'Tandem', 'ton' => 200, 'dh' => 450.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 130000000, 'st' => 'NEW'],
                    ['cat' => 'CF', 'op' => null, 'name' => 'CF', 'line' => 'SAI', 'mach' => 'M', 'ton' => null, 'dh' => 110.0, 'out' => 1, 'out_t' => 'Part', 'stk' => 1.0, 'price' => 24000000, 'st' => 'MODIFICATION'],
                ],
                'adds' => [
                    ['name' => 'RSW', 'qty' => 4, 'unit' => 'Spot'],
                    ['name' => 'QC Check', 'qty' => 10, 'unit' => 'second'],
                ],
            ],
        ];

        foreach ($childParts as $p) {
            $item = MngEbdItem::updateOrCreate(
                [
                    'ebd_header_id' => $ebdHeader->id,
                    'part_no' => $p['part_no'],
                ],
                [
                    'parent_id' => $assyItem->id,
                    'active_level' => 2,
                    'part_name' => $p['part_name'],
                    'qty_unit' => 1,
                    'pcs_month' => 2500,
                    'width' => $p['width'],
                    'length' => $p['length'],
                    'height' => $p['height'],
                    'weight' => $p['weight'],
                    'part_rank' => $p['part_rank'],
                    'status' => $p['status'] ?? 'New Part',
                    'mat_spec' => $p['mat_spec'],
                    'mat_thick' => $p['mat_thick'],
                    'mat_width' => $p['mat_width'],
                    'mat_length' => $p['mat_length'],
                    'mat_pcs_sheet' => 1,
                    'mat_weight_pcs' => $p['weight'],
                    'mat_yield_ratio' => 83.00,
                    'packing_type' => 'Returnable Steel Rack',
                    'pcs_packing' => 20,
                    'part_vol_m2' => 0.0150,
                    'truck_vol_m2' => 0.3000,
                ]
            );

            // Re-create tooling processes for this item only
            MngEbdToolingProcess::where('ebd_item_id', $item->id)->delete();
            foreach ($p['ops'] as $op) {
                MngEbdToolingProcess::create([
                    'ebd_item_id' => $item->id,
                    'tool_rank' => 'A',
                    'category' => $op['cat'],
                    'op' => $op['op'],
                    'process_name' => $op['name'],
                    'prod_homeline' => $op['line'],
                    'machine_type' => $op['mach'],
                    'tonnage' => $op['ton'],
                    'die_height' => $op['dh'],
                    'output' => $op['out'],
                    'output_type' => $op['out_t'],
                    'stroke' => $op['stk'],
                    'qty' => 1,
                    'price_idr' => $op['price'],
                    'tooling_status' => $op['st'],
                ]);
            }

            // Re-create additional processes for this item only
            MngEbdAddProcess::where('ebd_item_id', $item->id)->delete();
            foreach ($p['adds'] as $add) {
                MngEbdAddProcess::create([
                    'ebd_item_id' => $item->id,
                    'process_name' => $add['name'],
                    'qty' => $add['qty'],
                    'unit' => $add['unit'],
                ]);
            }
        }
    }
}
