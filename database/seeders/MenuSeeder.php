<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the app_management scope exists in the scopes table
        DB::table('scopes')->updateOrInsert(
            ['id' => 'app_management'],
            [
                'scope_name' => 'Management App',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Ensure permission 'view' exists
        DB::table('permissions')->updateOrInsert(['permission_name' => 'view'], ['permission_name' => 'view']);

        // ----------------------------------------------------------------
        // Sync all menus into the global `menus` table (source of truth)
        // managed from promise-admin.
        //
        // Structure:
        //   1. Dashboard            (level 1, no parent)
        //   2. Project Inquiry      (level 1, no parent)
        //   3. Work Order (SPK)     (level 1, no parent) <-- parent group
        //      3a. SPK List         (level 2, parent = Work Order)
        //      3b. Approval Inbox   (level 2, parent = Work Order)
        //   4. EBD                  (level 1, no parent) <-- parent group
        //      4a. EBD List         (level 2, parent = EBD)
        //   5. Approval Config      (level 1, no parent)
        //   6. Calendar & Holiday   (level 1, no parent)
        // ----------------------------------------------------------------

        // 1. Dashboard
        DB::table('menus')->updateOrInsert(
            ['route' => 'dashboard', 'scope_id' => 'app_management'],
            [
                'title'      => 'Dashboard',
                'icon'       => 'fa-solid fa-chart-line',
                'sort_order' => 1,
                'level'      => 1,
                'parent_id'  => null,
                'is_active'  => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Project Inquiry
        DB::table('menus')->updateOrInsert(
            ['route' => 'management.inquiry.index', 'scope_id' => 'app_management'],
            [
                'title'      => 'Project Inquiry',
                'icon'       => 'fa-solid fa-folder-open',
                'sort_order' => 2,
                'level'      => 1,
                'parent_id'  => null,
                'is_active'  => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Work Order (SPK) — parent group (no route, used as dropdown header)
        DB::table('menus')->updateOrInsert(
            ['route' => 'management.work-order.parent', 'scope_id' => 'app_management'],
            [
                'title'      => 'Work Order (SPK)',
                'icon'       => 'fa-solid fa-file-signature',
                'sort_order' => 3,
                'level'      => 1,
                'parent_id'  => null,
                'is_active'  => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $woParentId = DB::table('menus')
            ->where('route', 'management.work-order.parent')
            ->where('scope_id', 'app_management')
            ->value('id');

        // 3a. SPK 1 List (submenu)
        if ($woParentId) {
            DB::table('menus')->updateOrInsert(
                ['route' => 'management.work-order.index', 'scope_id' => 'app_management'],
                [
                    'title'      => 'SPK 1 List',
                    'icon'       => 'fa-solid fa-list',
                    'sort_order' => 1,
                    'level'      => 2,
                    'parent_id'  => $woParentId,
                    'is_active'  => true,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 3b. SPK 2 Tooling Cost (submenu)
            DB::table('menus')->updateOrInsert(
                ['route' => 'management.work-order-tooling.index', 'scope_id' => 'app_management'],
                [
                    'title'      => 'SPK 2 Tooling Cost',
                    'icon'       => 'fa-solid fa-calculator',
                    'sort_order' => 2,
                    'level'      => 2,
                    'parent_id'  => $woParentId,
                    'is_active'  => true,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 3c. WO Inbox (submenu)
            DB::table('menus')->updateOrInsert(
                ['route' => 'management.work-order.approval-inbox', 'scope_id' => 'app_management'],
                [
                    'title'      => 'WO Inbox',
                    'icon'       => 'fa-solid fa-envelope-open-text',
                    'sort_order' => 3,
                    'level'      => 2,
                    'parent_id'  => $woParentId,
                    'is_active'  => true,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Remove Assessment Config — now embedded as a modal in the inquiry page
        DB::table('menus')
            ->where('route', 'management.assessment-config.index')
            ->where('scope_id', 'app_management')
            ->delete();

        // 4. EBD — parent group (dropdown header)
        DB::table('menus')->updateOrInsert(
            ['route' => 'management.ebd.parent', 'scope_id' => 'app_management'],
            [
                'title'      => 'EBD',
                'icon'       => 'fa-solid fa-cubes',
                'sort_order' => 4,
                'level'      => 1,
                'parent_id'  => null,
                'is_active'  => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        $ebdParentId = DB::table('menus')
            ->where('route', 'management.ebd.parent')
            ->where('scope_id', 'app_management')
            ->value('id');

        // 4a. EBD List (submenu)
        if ($ebdParentId) {
            DB::table('menus')->updateOrInsert(
                ['route' => 'management.ebd.index', 'scope_id' => 'app_management'],
                [
                    'title'      => 'EBD List',
                    'icon'       => 'fa-solid fa-table-list',
                    'sort_order' => 1,
                    'level'      => 2,
                    'parent_id'  => $ebdParentId,
                    'is_active'  => true,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 5. Approval Config
        DB::table('menus')->updateOrInsert(
            ['route' => 'management.approval-config.index', 'scope_id' => 'app_management'],
            [
                'title'      => 'Approval Config',
                'icon'       => 'fa-solid fa-user-check',
                'sort_order' => 5,
                'level'      => 1,
                'parent_id'  => null,
                'is_active'  => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 6. Calendar & Holiday
        DB::table('menus')->updateOrInsert(
            ['route' => 'management.calendar.index', 'scope_id' => 'app_management'],
            [
                'title'      => 'Calendar & Holiday',
                'icon'       => 'fa-solid fa-calendar-days',
                'sort_order' => 6,
                'level'      => 1,
                'parent_id'  => null,
                'is_active'  => true,
                'is_visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // ----------------------------------------------------------------
        // Grant "Mng User" role (ID 34) access to all management menus
        // ----------------------------------------------------------------
        $allMngMenuIds = DB::table('menus')
            ->where('scope_id', 'app_management')
            ->pluck('id')
            ->toArray();

        foreach ($allMngMenuIds as $menuId) {
            DB::table('role_scope_permissions')->updateOrInsert([
                'role_id'       => 34,
                'scope_id'      => 'app_management',
                'menu_id'       => $menuId,
                'permission_id' => 1,
            ]);
        }
    }
}
