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
        // 1. Fetch the menu IDs for app_management scope
        $menuIds = DB::table('menus')
            ->where('scope_id', 'app_management')
            ->pluck('id')
            ->toArray();

        if (empty($menuIds)) {
            return;
        }

        // 2. Seed role_scope_permissions (role 16: Admin, role 22: Viewer)
        // permission_id 1 is 'view'
        foreach ([16, 22] as $roleId) {
            foreach ($menuIds as $menuId) {
                DB::table('role_scope_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'scope_id' => 'app_management',
                    'menu_id' => $menuId,
                    'permission_id' => 1,
                ]);
            }
        }

        // 3. Assign app_management roles to all users in user_scope_roles
        // By default, assign Viewer (role 22) to all active users, and Admin (role 16) to user 97
        $users = DB::table('users')->where('is_active', 1)->get();
        foreach ($users as $user) {
            $roleId = ($user->id == 97) ? 16 : 22; // User 97 is Admin, others are Viewer

            DB::table('user_scope_roles')->updateOrInsert([
                'user_id' => $user->id,
                'scope_id' => 'app_management',
                'role_id' => $roleId,
            ]);
        }

        // 4. Update t1000_sso_user_access_app table to support app_management
        foreach ($users as $user) {
            DB::table('t1000_sso_user_access_app')->updateOrInsert(
                ['id_user' => $user->id],
                [
                    'app_management' => true,
                    'dev_management' => true,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove RBA/SSO seeding for app_management
        DB::table('user_scope_roles')->where('scope_id', 'app_management')->delete();
        DB::table('role_scope_permissions')->where('scope_id', 'app_management')->delete();
        
        DB::table('t1000_sso_user_access_app')->update([
            'app_management' => null,
            'dev_management' => null,
        ]);
    }
};
