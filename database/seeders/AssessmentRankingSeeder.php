<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssessmentRanking;

class AssessmentRankingSeeder extends Seeder
{
    public function run(): void
    {
        $rankings = [
            [
                'rank_code' => 'A',
                'min_score' => 400,
                'max_score' => 9999,
                'priority_label' => 'Review Now',
                'recommendation' => 'Same-day review (Fast Track)',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'rank_code' => 'B',
                'min_score' => 300,
                'max_score' => 399,
                'priority_label' => 'Review Next',
                'recommendation' => 'Review after Rank A',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'rank_code' => 'C',
                'min_score' => 200,
                'max_score' => 299,
                'priority_label' => 'Pending',
                'recommendation' => 'Review if capacity is available',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'rank_code' => 'D',
                'min_score' => 0,
                'max_score' => 199,
                'priority_label' => 'Hold',
                'recommendation' => 'Hold / Reject / Review later',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($rankings as $ranking) {
            AssessmentRanking::updateOrCreate(
                ['rank_code' => $ranking['rank_code']],
                $ranking
            );
        }
    }
}
