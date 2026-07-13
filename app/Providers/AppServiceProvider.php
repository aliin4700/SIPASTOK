<?php

namespace App\Providers;

use App\Models\InventoryStock;
use App\Models\Message;
use App\Models\Order;
use App\Models\Payment;
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
                // Badge sidebar dibuat ringan: hanya count item yang butuh perhatian admin.
                $view->with('backendSidebarBadges', [
                    'orders' => Order::whereIn('status', ['pending', 'waiting_payment', 'paid', 'processing'])->count(),
                    'payments' => Payment::where('payment_status', 'waiting_confirmation')->count(),
                    'chats' => Message::where('sender_type', 'customer')->where('is_read', false)->count(),
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
