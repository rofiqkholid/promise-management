<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class SyncAllowedMenus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            // Get role-based menu permissions under app_management scope
            $roleMenuIds = DB::table('user_scope_roles')
                ->join('role_scope_permissions', function($join) {
                    $join->on('user_scope_roles.role_id', '=', 'role_scope_permissions.role_id')
                         ->on('user_scope_roles.scope_id', '=', 'role_scope_permissions.scope_id');
                })
                ->join('permissions', 'permissions.id', '=', 'role_scope_permissions.permission_id')
                ->where('user_scope_roles.user_id', $user->id)
                ->where('user_scope_roles.scope_id', 'app_management')
                ->where('permissions.permission_name', 'view')
                ->pluck('role_scope_permissions.menu_id')
                ->toArray();

            // Get user-specific menu overrides (ALLOW)
            $allowedOverrides = DB::table('user_scope_permissions')
                ->join('permissions', 'permissions.id', '=', 'user_scope_permissions.permission_id')
                ->where('user_scope_permissions.user_id', $user->id)
                ->where('user_scope_permissions.scope_id', 'app_management')
                ->where('user_scope_permissions.access_type', 'ALLOW')
                ->where('permissions.permission_name', 'view')
                ->pluck('user_scope_permissions.menu_id')
                ->toArray();

            // Get user-specific menu overrides (DENY)
            $deniedOverrides = DB::table('user_scope_permissions')
                ->join('permissions', 'permissions.id', '=', 'user_scope_permissions.permission_id')
                ->where('user_scope_permissions.user_id', $user->id)
                ->where('user_scope_permissions.scope_id', 'app_management')
                ->where('user_scope_permissions.access_type', 'DENY')
                ->where('permissions.permission_name', 'view')
                ->pluck('user_scope_permissions.menu_id')
                ->toArray();

            // allowed global menus inside 'menus' table
            $allowedGlobalMenuIds = array_diff(array_unique(array_merge($roleMenuIds, $allowedOverrides)), $deniedOverrides);

            // Fetch routes corresponding to allowed global menus
            $allowedRoutes = DB::table('menus')
                ->whereIn('id', $allowedGlobalMenuIds)
                ->where('scope_id', 'app_management')
                ->pluck('route')
                ->toArray();

            // Include parent route names if they exist to be safe
            $parentRoutes = [];
            $parentIds = DB::table('menus')
                ->whereIn('id', $allowedGlobalMenuIds)
                ->where('scope_id', 'app_management')
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->toArray();

            if (!empty($parentIds)) {
                $parentRoutes = DB::table('menus')
                    ->whereIn('id', $parentIds)
                    ->where('scope_id', 'app_management')
                    ->pluck('route')
                    ->toArray();
            }

            $allowedRoutes = array_unique(array_merge($allowedRoutes, $parentRoutes));

            // Also always allow dashboard route
            if (!in_array('dashboard', $allowedRoutes)) {
                $allowedRoutes[] = 'dashboard';
            }

            session(['allowed_menus' => $allowedRoutes]);
        }

        return $next($request);
    }
}
