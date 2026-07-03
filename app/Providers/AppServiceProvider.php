<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\FeasibilityStudy\InquiryRepositoryInterface::class,
            \App\Repositories\FeasibilityStudy\InquiryRepository::class
        );
        $this->app->bind(
            \App\Repositories\FeasibilityStudy\WorkOrderRepositoryInterface::class,
            \App\Repositories\FeasibilityStudy\WorkOrderRepository::class
        );
    }

    public function boot(): void
    {
        view()->composer('*', function ($view) {
            static $menus = null;

            if ($menus === null) {
                $allowedRoutes = session('allowed_menus', []);

                $allMenusQuery = \Illuminate\Support\Facades\DB::table('menus')
                    ->where('scope_id', 'app_management')
                    ->where('is_active', 1)
                    ->where('is_visible', 1);

                if (!empty($allowedRoutes)) {
                    $allMenusQuery->whereIn('route', $allowedRoutes);
                }

                $allMenus = $allMenusQuery->orderBy('sort_order', 'asc')->get();

                // Group menus by level/hierarchy using parent_id from global menus table
                $menus = [];
                $byParent = [];

                foreach ($allMenus as $menu) {
                    $menu->children = collect();

                    // Top level menus: no parent, or parent_id equals own id
                    if (is_null($menu->parent_id) || $menu->parent_id == $menu->id) {
                        $menus[] = $menu;
                    } else {
                        $byParent[$menu->parent_id][] = $menu;
                    }
                }

                // Assign children to their respective parent menus
                foreach ($menus as $rootMenu) {
                    if (isset($byParent[$rootMenu->id])) {
                        $rootMenu->children = collect($byParent[$rootMenu->id]);
                    }
                }
            }

            $view->with('menus', $menus);
        });
    }
}
