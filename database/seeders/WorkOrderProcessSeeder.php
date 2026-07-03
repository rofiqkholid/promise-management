<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkOrderProcess;
use App\Models\Department;

class WorkOrderProcessSeeder extends Seeder
{
    public function run(): void
    {
        $hasCode = \Illuminate\Support\Facades\Schema::hasColumn('departments', 'code');

        $engQuery = Department::query();
        if ($hasCode) {
            $engQuery->where('code', 'ENG');
        }
        $engineering = $engQuery->orWhere('name', 'like', '%Engineering%')->first();
        if (!$engineering) {
            $data = ['name' => 'Engineering'];
            if ($hasCode) $data['code'] = 'ENG';
            $engineering = Department::first() ?: Department::create($data);
        }

        $purQuery = Department::query();
        if ($hasCode) {
            $purQuery->where('code', 'PUR');
        }
        $purchasing = $purQuery->orWhere('name', 'like', '%Purchasing%')->first();
        if (!$purchasing) {
            $data = ['name' => 'Purchasing'];
            if ($hasCode) $data['code'] = 'PUR';
            $purchasing = Department::first() ?: Department::create($data);
        }

        $tolQuery = Department::query();
        if ($hasCode) {
            $tolQuery->where('code', 'TOL');
        }
        $tooling = $tolQuery->orWhere('name', 'like', '%Tooling%')
            ->orWhere('name', 'like', '%Production%')
            ->first();
        if (!$tooling) {
            $data = ['name' => 'Tooling'];
            if ($hasCode) $data['code'] = 'TOL';
            $tooling = Department::first() ?: Department::create($data);
        }

        $qaQuery = Department::query();
        if ($hasCode) {
            $qaQuery->where('code', 'QA');
        }
        $qa = $qaQuery->orWhere('name', 'like', '%QA%')
            ->orWhere('name', 'like', '%Quality%')
            ->first();
        if (!$qa) {
            $data = ['name' => 'Quality Assurance'];
            if ($hasCode) $data['code'] = 'QA';
            $qa = Department::first() ?: Department::create($data);
        }

        $processes = [
            [
                'process_code' => 'mpp',
                'process_name' => 'MPP (Manufacturing Planning Process)',
                'default_assigned_departments' => json_encode([$engineering->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'dies_calculation',
                'process_name' => 'Dies Calculation',
                'default_assigned_departments' => json_encode([$tooling->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'lifetime_tooling',
                'process_name' => 'Manufacturing Tooling Lifetime',
                'default_assigned_departments' => json_encode([$tooling->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'cf_calculation',
                'process_name' => 'CF Calculation',
                'default_assigned_departments' => json_encode([$engineering->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'tools_modification',
                'process_name' => 'Tools Modification',
                'default_assigned_departments' => json_encode([$tooling->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'sample_part',
                'process_name' => 'Sample Part',
                'default_assigned_departments' => json_encode([$qa->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'design',
                'process_name' => 'Design',
                'default_assigned_departments' => json_encode([$engineering->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'start_dev_tooling',
                'process_name' => 'Start Tooling Development',
                'default_assigned_departments' => json_encode([$tooling->id]),
                'is_active' => true,
            ],
            [
                'process_code' => 'other_sourcing',
                'process_name' => 'Other (Sourcing Supplier)',
                'default_assigned_departments' => json_encode([$purchasing->id]),
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
