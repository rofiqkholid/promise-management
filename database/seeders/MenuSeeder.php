<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Delete obsolete menus
        DB::table('mng_menus')->where('route', 'management.ranking.index')->delete();

        // 1. Project Inquiry Menu
        DB::table('mng_menus')->updateOrInsert(
            ['route' => 'management.inquiry.index'],
            [
                'title' => 'Project Inquiry',
                'icon' => 'fa-solid fa-folder-open',
                'sort_order' => 2,
                'level' => 1,
                'is_active' => 1,
                'is_visible' => 1,
            ]
        );

        $inq = DB::table('mng_menus')->where('route', 'management.inquiry.index')->first();
        if ($inq) {
            DB::table('mng_menus')->where('id', $inq->id)->update(['parent_id' => $inq->id]);
        }

        // 2. Work Order (SPK) Menu
        DB::table('mng_menus')->updateOrInsert(
            ['route' => 'management.work-order.index'],
            [
                'title' => 'Work Order (SPK)',
                'icon' => 'fa-solid fa-file-signature',
                'sort_order' => 3,
                'level' => 1,
                'is_active' => 1,
                'is_visible' => 1,
            ]
        );

        $wo = DB::table('mng_menus')->where('route', 'management.work-order.index')->first();
        if ($wo) {
            DB::table('mng_menus')->where('id', $wo->id)->update(['parent_id' => $wo->id]);
        }

        // 3. Assessment Configuration Menu
        DB::table('mng_menus')->updateOrInsert(
            ['route' => 'management.assessment-config.index'],
            [
                'title' => 'Assessment Config',
                'icon' => 'fa-solid fa-gears',
                'sort_order' => 4,
                'level' => 1,
                'is_active' => 1,
                'is_visible' => 1,
            ]
        );

        $config = DB::table('mng_menus')->where('route', 'management.assessment-config.index')->first();
        if ($config) {
            DB::table('mng_menus')->where('id', $config->id)->update(['parent_id' => $config->id]);
        }

        // 4. Approval Config Menu
        DB::table('mng_menus')->updateOrInsert(
            ['route' => 'management.approval-config.index'],
            [
                'title' => 'Approval Config',
                'icon' => 'fa-solid fa-user-check',
                'sort_order' => 5,
                'level' => 1,
                'is_active' => 1,
                'is_visible' => 1,
            ]
        );

        $approval = DB::table('mng_menus')->where('route', 'management.approval-config.index')->first();
        if ($approval) {
            DB::table('mng_menus')->where('id', $approval->id)->update(['parent_id' => $approval->id]);
        }
    }
}
