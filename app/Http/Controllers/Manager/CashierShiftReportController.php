<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;

class CashierShiftReportController extends Controller
{
    public function index(Request $request)
    {
        $shifts = CashierShift::with(['outlet', 'cashier'])
            ->when($request->filled('date'), fn ($query) => $query->whereDate('opened_at', $request->date))
            ->when($request->filled('outlet_id'), fn ($query) => $query->where('outlet_id', $request->outlet_id))
            ->when($request->filled('cashier_id'), fn ($query) => $query->where('cashier_id', $request->cashier_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        return view('manager.cashier-shifts.index', [
            'title' => 'Cashier Shift Reports',
            'shifts' => $shifts,
            'outlets' => Outlet::orderBy('name')->get(),
            'cashiers' => User::whereIn('role', ['cashier', 'admin', 'super_admin'])->orderBy('name')->get(),
        ]);
    }

    public function show(CashierShift $shift)
    {
        return view('manager.cashier-shifts.show', [
            'title' => 'Cashier Shift Detail',
            'shift' => $shift->load(['outlet', 'cashier', 'reviewer', 'cashMovements', 'orders.payment']),
        ]);
    }
}
