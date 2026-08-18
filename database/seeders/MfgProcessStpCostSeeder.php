<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MfgProcessStpCost;

class MfgProcessStpCostSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'machine_type' => 'Tandem',
                'tonnage' => 110,
                'machine_category' => 'Small',
                'output_type' => 'Part',
                'output_qty' => 1,
                'stroke' => 1.00,
                'process_complexity' => 'Inner',
                'complexity_alias' => 'A',
                'min_cost_rate' => 150.0,
                'std_cost_rate' => 175.0,
                'rate_source' => 'Sales',
            ],
            [
                'machine_type' => 'Tandem',
                'tonnage' => 200,
                'machine_category' => 'Small',
                'output_type' => 'Part',
                'output_qty' => 1,
                'stroke' => 1.00,
                'process_complexity' => 'Inner',
                'complexity_alias' => 'A',
                'min_cost_rate' => 220.0,
                'std_cost_rate' => 245.0,
                'rate_source' => 'Sales',
            ],
            [
                'machine_type' => 'Tandem',
                'tonnage' => 300,
                'machine_category' => 'Medium',
                'output_type' => 'Part',
                'output_qty' => 1,
                'stroke' => 1.00,
                'process_complexity' => 'Deep Draw',
                'complexity_alias' => 'B',
                'min_cost_rate' => 350.0,
                'std_cost_rate' => 380.0,
                'rate_source' => 'Sales',
            ],
            [
                'machine_type' => 'Tandem',
                'tonnage' => 500,
                'machine_category' => 'Medium',
                'output_type' => 'Part',
                'output_qty' => 1,
                'stroke' => 1.00,
                'process_complexity' => 'Outer Panel',
                'complexity_alias' => 'C',
                'min_cost_rate' => 520.0,
                'std_cost_rate' => 560.0,
                'rate_source' => 'Sales',
            ],
            [
                'machine_type' => 'Tandem',
                'tonnage' => 800,
                'machine_category' => 'Large',
                'output_type' => 'Part',
                'output_qty' => 1,
                'stroke' => 1.00,
                'process_complexity' => 'Outer Panel',
                'complexity_alias' => 'C',
                'min_cost_rate' => 850.0,
                'std_cost_rate' => 920.0,
                'rate_source' => 'Sales',
            ],
            [
                'machine_type' => 'Transfer',
                'tonnage' => 1000,
                'machine_category' => 'Large',
                'output_type' => 'Cavity',
                'output_qty' => 2,
                'stroke' => 1.00,
                'process_complexity' => 'Deep Draw',
                'complexity_alias' => 'B',
                'min_cost_rate' => 1100.0,
                'std_cost_rate' => 1250.0,
                'rate_source' => 'Engineering',
            ],
            [
                'machine_type' => 'Progressive',
                'tonnage' => 250,
                'machine_category' => 'Medium',
                'output_type' => 'Process',
                'output_qty' => 1,
                'stroke' => 1.00,
                'process_complexity' => 'Inner',
                'complexity_alias' => 'A',
                'min_cost_rate' => 280.0,
                'std_cost_rate' => 310.0,
                'rate_source' => 'Sales',
            ],
        ];

        foreach ($items as $item) {
            MfgProcessStpCost::updateOrCreate(
                [
                    'machine_type' => $item['machine_type'],
                    'tonnage' => $item['tonnage'],
                    'output_type' => $item['output_type'],
                    'process_complexity' => $item['process_complexity'],
                ],
                $item
            );
        }
    }
}
