<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaterialCost;
use App\Models\Customer;

class MaterialCostSeeder extends Seeder
{
    public function run(): void
    {
        $custMMKI = Customer::where('code', 'MMKI')->first() ?? Customer::first();
        $customerId = $custMMKI ? $custMMKI->id : null;

        // Data directly from MMKI Material Information Sheet (Page 1)
        $materials = [
            [
                'material_spec' => 'MJSC270C-OD',
                'material_type' => 'Sheet',
                'thickness' => 1.20,
                'price_sales' => 18842.0,
                'price_eng' => 17200.0,
                'scrap_price' => 4500.0,
            ],
            [
                'material_spec' => 'MJSC270D-OD',
                'material_type' => 'Sheet',
                'thickness' => 0.80,
                'price_sales' => 19435.0,
                'price_eng' => 17800.0,
                'scrap_price' => 4500.0,
            ],
            [
                'material_spec' => 'SGT7F30/30',
                'material_type' => 'Sheet',
                'thickness' => 0.80,
                'price_sales' => 27614.0,
                'price_eng' => 25200.0,
                'scrap_price' => 5500.0,
            ],
            [
                'material_spec' => 'MJSH400W-OP',
                'material_type' => 'Sheet',
                'thickness' => 2.30,
                'price_sales' => 14326.0,
                'price_eng' => 13100.0,
                'scrap_price' => 4000.0,
            ],
            [
                'material_spec' => 'SGT7F',
                'material_type' => 'Sheet',
                'thickness' => 1.20,
                'price_sales' => 26940.0,
                'price_eng' => 24600.0,
                'scrap_price' => 5500.0,
            ],
            [
                'material_spec' => 'MJSH270C-OP',
                'material_type' => 'Sheet',
                'thickness' => 2.30,
                'price_sales' => 13965.0,
                'price_eng' => 12800.0,
                'scrap_price' => 4000.0,
            ],
        ];

        foreach ($materials as $m) {
            // Engineering Rate (Global)
            MaterialCost::updateOrCreate(
                [
                    'material_spec' => $m['material_spec'],
                    'thickness' => $m['thickness'],
                    'rate_source' => 'Engineering',
                    'customer_id' => null,
                ],
                [
                    'material_type' => $m['material_type'],
                    'price_per_kg' => $m['price_eng'],
                    'scrap_price_per_kg' => $m['scrap_price'],
                    'valid_from' => '2026-01-01',
                    'is_active' => true,
                ]
            );

            // Sales Rate (Customer Specific & Global)
            foreach ([$customerId, null] as $cid) {
                MaterialCost::updateOrCreate(
                    [
                        'material_spec' => $m['material_spec'],
                        'thickness' => $m['thickness'],
                        'rate_source' => 'Sales',
                        'customer_id' => $cid,
                    ],
                    [
                        'material_type' => $m['material_type'],
                        'price_per_kg' => $m['price_sales'],
                        'scrap_price_per_kg' => $m['scrap_price'],
                        'valid_from' => '2026-01-01',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
