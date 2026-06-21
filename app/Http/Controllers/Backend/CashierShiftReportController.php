<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;

class CashierShiftReportController extends Controller
{
    public function index(Request $request)
    {
        $shifts = $this->query($request)->paginate(15)->withQueryString();

        return view('backend.cashier-shifts.index', [
            'shifts' => $shifts,
            'outlets' => Outlet::orderBy('name')->get(),
            'cashiers' => User::whereIn('role', ['cashier', 'admin', 'super_admin'])->orderBy('name')->get(),
        ]);
    }

    public function show(CashierShift $shift)
    {
        return view('backend.cashier-shifts.show', [
            'shift' => $this->loadShift($shift),
        ]);
    }

    public function review(CashierShift $shift)
    {
        if ($shift->status !== 'closed') {
            return back()->with('error', 'Hanya shift closed yang bisa direview.');
        }

        $shift->update([
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Shift berhasil direview.');
    }

    private function query(Request $request)
    {
        return CashierShift::with(['outlet', 'cashier'])
            ->when($request->filled('date'), fn ($query) => $query->whereDate('opened_at', $request->date))
            ->when($request->filled('outlet_id'), fn ($query) => $query->where('outlet_id', $request->outlet_id))
            ->when($request->filled('cashier_id'), fn ($query) => $query->where('cashier_id', $request->cashier_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest('opened_at');
    }

    private function loadShift(CashierShift $shift): CashierShift
    {
        return $shift->load(['outlet', 'cashier', 'reviewer', 'cashMovements', 'orders.payment']);
    }
}
