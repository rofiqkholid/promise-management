<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
                // Seed Master Configs
        $this->call([
            AssessmentRankingSeeder::class,
            ScoreCategoryAndOptionSeeder::class,
            WorkOrderProcessSeeder::class,
            MenuSeeder::class,
            MaterialCostSeeder::class,
            RealCostComparisonScenarioSeeder::class,
        ]);
    }
}
