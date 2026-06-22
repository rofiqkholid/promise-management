<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Seed Scope for Management
        DB::table('scopes')->updateOrInsert(
            ['id' => 'app_management'],
            [
                'scope_name' => 'Promise Management',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Seed Menus in global menus table for app_management scope
        $menus = [
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'fa-solid fa-chart-line',
                'sort_order' => 1,
                'level' => 1,
                'is_active' => true,
                'is_visible' => true,
                'scope_id' => 'app_management',
            ],
            [
                'title' => 'Project Inquiry',
                'route' => 'management.inquiry.index',
                'icon' => 'fa-solid fa-folder-open',
                'sort_order' => 2,
                'level' => 1,
                'is_active' => true,
                'is_visible' => true,
                'scope_id' => 'app_management',
            ],
            [
                'title' => 'Work Order (SPK)',
                'route' => 'management.work-order.index',
                'icon' => 'fa-solid fa-file-signature',
                'sort_order' => 3,
                'level' => 1,
                'is_active' => true,
                'is_visible' => true,
                'scope_id' => 'app_management',
            ],
            [
                'title' => 'Assessment Config',
                'route' => 'management.assessment-config.index',
                'icon' => 'fa-solid fa-gears',
                'sort_order' => 4,
                'level' => 1,
                'is_active' => true,
                'is_visible' => true,
                'scope_id' => 'app_management',
            ],
            [
                'title' => 'Approval Config',
                'route' => 'management.approval-config.index',
                'icon' => 'fa-solid fa-user-check',
                'sort_order' => 5,
                'level' => 1,
                'is_active' => true,
                'is_visible' => true,
                'scope_id' => 'app_management',
            ]
        ];

        foreach ($menus as $menu) {
            DB::table('menus')->updateOrInsert(
                ['route' => $menu['route'], 'scope_id' => 'app_management'],
                array_merge($menu, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menus')->where('scope_id', 'app_management')->delete();
        DB::table('scopes')->where('id', 'app_management')->delete();
    }
};
