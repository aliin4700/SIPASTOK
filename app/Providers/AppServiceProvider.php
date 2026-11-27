<?php

namespace App\Providers;

use App\Models\InventoryStock;
use App\Models\StockOpname;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Semua pagination backend memakai Bootstrap 5 agar tampilan list konsisten.
        Paginator::useBootstrapFive();

        View::composer('backend.v_layouts.app', function ($view) {
            try {
                // Badge sidebar dibuat ringan: hanya count item dan opname yang butuh perhatian admin.
                $view->with('backendSidebarBadges', [
                    'pending_opnames' => StockOpname::where('status', 'submitted')->count(),
                    'inventory' => InventoryStock::whereHas('item', function ($query) {
                        $query->where('is_active', true)
                            ->whereColumn('inventory_stocks.current_stock', '<=', 'inventory_items.min_stock');
                    })->count(),
                ]);
            } catch (Throwable) {
                $view->with('backendSidebarBadges', []);
            }
        });
    }
}
