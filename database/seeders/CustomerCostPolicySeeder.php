<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomerCostPolicy;
use App\Models\Customer;

class CustomerCostPolicySeeder extends Seeder
{
    public function run(): void
    {
        $firstCustomer = Customer::first();
        $customerId = $firstCustomer ? $firstCustomer->id : null;

        $items = [
            [
                'customer_id' => null, // Global / Standard Engineering
                'admin_matrl_pct' => 2.00,
                'admin_mfg_pct' => 4.00,
                'oh_profit_pct' => 0.00,
                'min_std_margin_pct' => 12.00,
                'rate_source' => 'Engineering',
                'notes' => 'Standard internal engineering cost baseline (Admin Matrl 2%, Admin Mfg 4%, OH 0%).',
            ],
            [
                'customer_id' => null, // Global Sales Standard
                'admin_matrl_pct' => 3.00,
                'admin_mfg_pct' => 5.00,
                'oh_profit_pct' => 10.00,
                'min_std_margin_pct' => 12.00,
                'rate_source' => 'Sales',
                'notes' => 'General sales markup baseline for unassigned customer quotations.',
            ],
        ];

        if ($customerId) {
            $items[] = [
                'customer_id' => $customerId, // Customer Specific
                'admin_matrl_pct' => 2.50,
                'admin_mfg_pct' => 4.50,
                'oh_profit_pct' => 12.00,
                'min_std_margin_pct' => 12.00,
                'rate_source' => 'Sales',
                'notes' => 'Agreed commercial rate policy for customer ' . ($firstCustomer->name ?? ''),
            ];
        }

        foreach ($items as $item) {
            CustomerCostPolicy::updateOrCreate(
                [
                    'customer_id' => $item['customer_id'],
                    'rate_source' => $item['rate_source'],
                ],
                $item
            );
        }
    }
}
