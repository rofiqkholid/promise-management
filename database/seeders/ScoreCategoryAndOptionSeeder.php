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
        DB::table('mng_priority_assessment_details')->delete();
        DB::table('mng_priority_assessments')->delete();
        DB::table('mng_score_options')->delete();
        DB::table('mng_score_categories')->delete();

        $data = [
            [
                'category_code' => 'customer_priority',
                'category_name' => 'Customer Priority',
                'sort_order' => 1,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'Strategis', 'score_value' => 175, 'description' => 'Top customer berdasarkan omzet, target bisnis, atau arahan manajemen'],
                    ['option_name' => 'Existing', 'score_value' => 105, 'description' => 'Customer aktif tetapi bukan customer prioritas utama'],
                    ['option_name' => 'Baru', 'score_value' => 35, 'description' => 'Customer yang belum pernah berbisnis atau belum memiliki histori order'],
                ]
            ],
            [
                'category_code' => 'volume_potential',
                'category_name' => 'Volume Potential',
                'sort_order' => 2,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'Tinggi', 'score_value' => 125, 'description' => '>3000u/month'],
                    ['option_name' => 'Sedang', 'score_value' => 75, 'description' => '1000 ~ 3000u/month'],
                    ['option_name' => 'Rendah', 'score_value' => 25, 'description' => '<1000u/month'],
                ]
            ],
            [
                'category_code' => 'product_type',
                'category_name' => 'Product Type',
                'sort_order' => 3,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'New Product', 'score_value' => 50, 'description' => 'Produk yang belum pernah diproduksi ( know-how belum ada )'],
                    ['option_name' => 'Similar', 'score_value' => 30, 'description' => 'produk baru tetapi memiliki kemiripan tinggi dengan produk existing ( know-how sudah ada)'],
                    ['option_name' => 'Minor Modification', 'score_value' => 10, 'description' => 'Perubahan kecil dari produk existing tanpa perubahan signifikan pada proses produksi'],
                ]
            ],
            [
                'category_code' => 'technical_capability',
                'category_name' => 'Technical Capability',
                'sort_order' => 4,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'Available', 'score_value' => 100, 'description' => 'Siap produksi dengan fasilitas saat ini.'],
                    ['option_name' => 'Minor Gap', 'score_value' => 60, 'description' => 'Perlu penyesuaian kecil'],
                    ['option_name' => 'Not Available', 'score_value' => 20, 'description' => 'Perlu capability baru atau investasi besar'],
                ]
            ],
            [
                'category_code' => 'investment_requirement',
                'category_name' => 'Investment Requirement',
                'sort_order' => 5,
                'is_active' => true,
                'options' => [
                    ['option_name' => 'Rendah', 'score_value' => 50, 'description' => '≤ Rp 250 juta'],
                    ['option_name' => 'Sedang', 'score_value' => 30, 'description' => 'Rp 250 juta – Rp 1 miliar'],
                    ['option_name' => 'Tinggi', 'score_value' => 10, 'description' => '> Rp 1 miliar'],
                ]
            ],
        ];

        foreach ($data as $catData) {
            $options = $catData['options'];
            unset($catData['options']);

            $category = ScoreCategory::create($catData);

            foreach ($options as $index => $optData) {
                $optData['category_id'] = $category->category_id;
                $optData['sort_order'] = $index + 1;
                ScoreOption::create($optData);
            }
        }
    }
}
