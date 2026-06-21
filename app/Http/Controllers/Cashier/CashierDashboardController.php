<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Outlet;
use Illuminate\Support\Facades\Schema;

class CashierDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $outlet = $this->currentOutlet();
        $currentShift = null;
        $latestShift = null;

        if (Schema::hasTable('cashier_shifts')) {
            $currentShift = CashierShift::with('outlet')
                ->where('cashier_id', $user->id)
                ->where('status', 'open')
                ->latest('opened_at')
                ->first();

            $latestShift = CashierShift::with('outlet')
                ->where('cashier_id', $user->id)
                ->latest('opened_at')
                ->first();
        }

        return view('cashier.index', [
            'title' => 'Cashier POS',
            'currentOutlet' => $outlet,
            'currentShift' => $currentShift,
            'latestShift' => $latestShift,
            'todayPosOrders' => 0,
            'todayCashSales' => 0,
        ]);
    }

    private function currentOutlet(): ?Outlet
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return Schema::hasTable('outlets') ? Outlet::where('is_active', true)->orderBy('name')->first() : null;
        }

        if (!Schema::hasTable('user_outlet_access')) {
            return null;
        }

        $access = $user->outletAccesses()
            ->with('outlet')
            ->where('can_cashier', true)
            ->first();

        return $access?->outlet;
    }
}
