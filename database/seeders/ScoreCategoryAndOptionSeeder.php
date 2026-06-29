<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScoreCategory;
use App\Models\ScoreOption;
use Illuminate\Support\Facades\DB;

class ScoreCategoryAndOptionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('mng_inq_assessment_details')->delete();
        DB::table('mng_inq_assessments')->delete();
        DB::table('mng_inq_score_options')->delete();
        DB::table('mng_inq_score_categories')->delete();

        $data = [
            [
                'category_code' => 'customer_priority',
                'category_name' => 'Customer Priority',
                'sort_order' => 1,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'Strategic', 'score_value' => 175, 'description' => 'Top customer based on revenue, business targets, or management directives'],
                    ['option_name' => 'Existing', 'score_value' => 105, 'description' => 'Active customer but not a top priority customer'],
                    ['option_name' => 'New', 'score_value' => 35, 'description' => 'Customer who has never done business with us or has no order history'],
                ]
            ],
            [
                'category_code' => 'volume_potential',
                'category_name' => 'Volume Potential',
                'sort_order' => 2,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'High', 'score_value' => 125, 'description' => '>3000u/month'],
                    ['option_name' => 'Medium', 'score_value' => 75, 'description' => '1000 ~ 3000u/month'],
                    ['option_name' => 'Low', 'score_value' => 25, 'description' => '<1000u/month'],
                ]
            ],
            [
                'category_code' => 'product_type',
                'category_name' => 'Product Type',
                'sort_order' => 3,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'New Product', 'score_value' => 50, 'description' => 'Product never produced before (know-how not yet available)'],
                    ['option_name' => 'Similar', 'score_value' => 30, 'description' => 'New product but has high similarity with existing products (know-how already available)'],
                    ['option_name' => 'Minor Modification', 'score_value' => 10, 'description' => 'Small change from an existing product without significant changes to the production process'],
                ]
            ],
            [
                'category_code' => 'technical_capability',
                'category_name' => 'Technical Capability',
                'sort_order' => 4,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'Available', 'score_value' => 100, 'description' => 'Ready for production with current facilities.'],
                    ['option_name' => 'Minor Gap', 'score_value' => 60, 'description' => 'Requires minor adjustments'],
                    ['option_name' => 'Not Available', 'score_value' => 20, 'description' => 'Requires new capability or major investment'],
                ]
            ],
            [
                'category_code' => 'investment_requirement',
                'category_name' => 'Investment Requirement',
                'sort_order' => 5,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'Low', 'score_value' => 50, 'description' => '≤ IDR 250 million'],
                    ['option_name' => 'Medium', 'score_value' => 30, 'description' => 'IDR 250 million – IDR 1 billion'],
                    ['option_name' => 'High', 'score_value' => 10, 'description' => '> IDR 1 billion'],
                ]
            ],
        ];

        foreach ($data as $catData) {
            $options = $catData['options'];
            unset($catData['options']);

            $category = ScoreCategory::create($catData);

            foreach ($options as $index => $optData) {
                $optData['category_id'] = $category->id;
                $optData['sort_order'] = $index + 1;
                ScoreOption::create($optData);
            }
        }
    }
}
