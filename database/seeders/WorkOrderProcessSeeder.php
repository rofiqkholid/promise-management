<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkOrderProcess;
use App\Models\Department;

class WorkOrderProcessSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch or create default departments to map as owners
        $engineering = Department::where('code', 'ENG')
            ->orWhere('name', 'like', '%Engineering%')
            ->first();
        if (!$engineering) {
            $engineering = Department::first() ?: Department::create(['name' => 'Engineering', 'code' => 'ENG']);
        }

        $purchasing = Department::where('code', 'PUR')
            ->orWhere('name', 'like', '%Purchasing%')
            ->first();
        if (!$purchasing) {
            $purchasing = Department::first() ?: Department::create(['name' => 'Purchasing', 'code' => 'PUR']);
        }

        $tooling = Department::where('code', 'TOL')
            ->orWhere('name', 'like', '%Tooling%')
            ->orWhere('name', 'like', '%Production%')
            ->first();
        if (!$tooling) {
            $tooling = Department::first() ?: Department::create(['name' => 'Tooling', 'code' => 'TOL']);
        }

        $qa = Department::where('code', 'QA')
            ->orWhere('name', 'like', '%QA%')
            ->orWhere('name', 'like', '%Quality%')
            ->first();
        if (!$qa) {
            $qa = Department::first() ?: Department::create(['name' => 'Quality Assurance', 'code' => 'QA']);
        }

        $processes = [
            [
                'process_code' => 'mpp',
                'process_name' => 'MPP (Manufacturing Planing Proses)',
                'owner_department_id' => $engineering->id,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'process_code' => 'kalkulasi_dies',
                'process_name' => 'Kalkulasi Dies',
                'owner_department_id' => $tooling->id,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'process_code' => 'lifetime_tooling',
                'process_name' => 'Life Time Manufacturing Tooling',
                'owner_department_id' => $tooling->id,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'process_code' => 'kalkulasi_cf',
                'process_name' => 'Kalkulasi CF',
                'owner_department_id' => $engineering->id,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'process_code' => 'modifikasi_tools',
                'process_name' => 'Modifikasi Tools',
                'owner_department_id' => $tooling->id,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'process_code' => 'sample_part',
                'process_name' => 'Sample Part',
                'owner_department_id' => $qa->id,
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'process_code' => 'design',
                'process_name' => 'Design',
                'owner_department_id' => $engineering->id,
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'process_code' => 'start_dev_tooling',
                'process_name' => 'Start development tooling',
                'owner_department_id' => $tooling->id,
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'process_code' => 'other_sourcing',
                'process_name' => 'Other (Sourcing Supplier)',
                'owner_department_id' => $purchasing->id,
                'sort_order' => 9,
                'is_active' => true,
            ],
        ];

        foreach ($processes as $process) {
            WorkOrderProcess::updateOrCreate(
                ['process_code' => $process['process_code']],
                $process
            );
        }
    }
}
