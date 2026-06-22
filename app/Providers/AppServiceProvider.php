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
            \App\Repositories\Management\InquiryRepositoryInterface::class,
            \App\Repositories\Management\InquiryRepository::class
        );
    }

    public function boot(): void
    {
        view()->composer('*', function ($view) {
            static $menus = null;

            if ($menus === null) {
                $allowedMenuIds = session('allowed_menus', []);
                
                $allMenusQuery = \Illuminate\Support\Facades\DB::table('mng_menus')
                    ->where('is_active', 1)
                    ->where('is_visible', 1);
                    
                if (!empty($allowedMenuIds)) {
                    $allMenusQuery->whereIn('id', $allowedMenuIds);
                }
                
                $allMenus = $allMenusQuery->orderBy('sort_order', 'asc')->get();
                
                // Group menus by level/hierarchy
                $menus = [];
                $byParent = [];
                
                foreach ($allMenus as $menu) {
                    $menu->children = collect();
                    
                    // Top level menus
                    if ($menu->level == 1 || is_null($menu->parent_id) || $menu->parent_id == $menu->id) {
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
