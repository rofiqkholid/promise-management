<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaterialCost;
use App\Models\Customer;

class MaterialCostSeeder extends Seeder
{
    public function run(): void
    {
        $firstCustomer = Customer::first();
        $customerId = $firstCustomer ? $firstCustomer->id : null;

        $items = [
            [
                'customer_id' => null, // Umum
                'material_spec' => 'SPCC-SD',
                'material_type' => 'Sheet',
                'thickness' => 1.20,
                'price_per_kg' => 14500.0,
                'scrap_price_per_kg' => 4200.0,
                'rate_source' => 'Engineering',
                'valid_from' => now()->startOfYear()->toDateString(),
            ],
            [
                'customer_id' => null, // Umum
                'material_spec' => 'SPHC-PO',
                'material_type' => 'Sheet',
                'thickness' => 2.00,
                'price_per_kg' => 13800.0,
                'scrap_price_per_kg' => 4000.0,
                'rate_source' => 'Engineering',
                'valid_from' => now()->startOfYear()->toDateString(),
            ],
            [
                'customer_id' => null, // Umum
                'material_spec' => 'SECC-P',
                'material_type' => 'Coil',
                'thickness' => 1.00,
                'price_per_kg' => 16200.0,
                'scrap_price_per_kg' => 4500.0,
                'rate_source' => 'Engineering',
                'valid_from' => now()->startOfYear()->toDateString(),
            ],
            [
                'customer_id' => $customerId, // Customer Specific
                'material_spec' => 'JAC270D',
                'material_type' => 'Sheet',
                'thickness' => 1.40,
                'price_per_kg' => 17500.0,
                'scrap_price_per_kg' => 4800.0,
                'rate_source' => 'Sales',
                'valid_from' => now()->startOfMonth()->toDateString(),
            ],
            [
                'customer_id' => null, // Umum
                'material_spec' => 'SUS304',
                'material_type' => 'Sheet',
                'thickness' => 1.50,
                'price_per_kg' => 42000.0,
                'scrap_price_per_kg' => 12500.0,
                'rate_source' => 'Engineering',
                'valid_from' => now()->startOfYear()->toDateString(),
            ],
        ];

        foreach ($items as $item) {
            MaterialCost::updateOrCreate(
                [
                    'material_spec' => $item['material_spec'],
                    'material_type' => $item['material_type'],
                    'thickness' => $item['thickness'],
                    'customer_id' => $item['customer_id'],
                ],
                $item
            );
        }
    }
}
