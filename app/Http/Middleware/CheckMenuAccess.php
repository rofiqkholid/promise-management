<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;
use DB;

class CheckMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $menuRoute): Response
    {
        $user = $request->user();
        if ($user) {
            $allowedRoutes = session('allowed_menus', []);

            if (in_array($menuRoute, $allowedRoutes)) {
                return $next($request);
            }

            // If not directly allowed, let's try to redirect them to their first allowed menu
            if (!empty($allowedRoutes)) {
                $firstMenu = DB::table('menus')
                    ->whereIn('route', $allowedRoutes)
                    ->where('scope_id', 'app_management')
                    ->where('is_active', 1)
                    ->whereNotNull('route')
                    ->where('route', '!=', '')
                    ->orderBy('sort_order', 'asc')
                    ->first();

                if ($firstMenu && Route::has($firstMenu->route)) {
                    return redirect()->route($firstMenu->route);
                }
            }
        }

        abort(403, 'ANDA TIDAK MEMILIKI HAK AKSES.');
    }
}
