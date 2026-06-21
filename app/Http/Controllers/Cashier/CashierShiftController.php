<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierCashMovement;
use App\Models\CashierShift;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierShiftController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $openShift = CashierShift::with('outlet', 'cashMovements')
            ->where('cashier_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return view('cashier.shift.index', [
            'title' => 'Cashier Shift',
            'openShift' => $openShift,
            'cashierOutlet' => $this->cashierOutlet(),
            'outlets' => $user->hasAnyRole(['super_admin', 'admin'])
                ? Outlet::where('is_active', true)->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function open(Request $request)
    {
        $user = $request->user();
        $outlet = $this->resolveOutlet($request);

        if (!$outlet) {
            return back()->with('error', 'Akun kasir belum memiliki akses outlet.');
        }

        if (CashierShift::where('cashier_id', $user->id)->where('status', 'open')->exists()) {
            return back()->with('error', 'Anda masih memiliki shift yang terbuka.');
        }

        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($user, $outlet, $validated) {
            $shift = CashierShift::create([
                'outlet_id' => $outlet->id,
                'cashier_id' => $user->id,
                'opened_at' => now(),
                'opening_cash' => $validated['opening_cash'],
                'expected_cash' => $validated['opening_cash'],
                'status' => 'open',
            ]);

            $shift->update([
                'shift_code' => $this->shiftCode($outlet, $shift),
            ]);

            CashierCashMovement::create([
                'cashier_shift_id' => $shift->id,
                'outlet_id' => $outlet->id,
                'cashier_id' => $user->id,
                'type' => 'cash_in',
                'category' => 'opening_cash',
                'amount' => $validated['opening_cash'],
                'description' => 'Opening cash',
            ]);
        });

        return redirect()->route('cashier.shift.index')->with('success', 'Shift berhasil dibuka.');
    }

    public function close(Request $request)
    {
        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $shift = CashierShift::with('cashMovements')
            ->where('cashier_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$shift) {
            return back()->with('error', 'Tidak ada shift terbuka untuk ditutup.');
        }

        $expectedCash = $shift->expectedCashAmount();
        $actualCash = (float) $validated['actual_cash'];

        $shift->update([
            'actual_cash' => $actualCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $actualCash - $expectedCash,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        return redirect()->route('cashier.shift.index')->with('success', 'Shift berhasil ditutup.');
    }

    private function resolveOutlet(Request $request): ?Outlet
    {
        $user = $request->user();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            $validated = $request->validate([
                'outlet_id' => ['required', 'exists:outlets,id'],
            ]);

            return Outlet::where('is_active', true)->find($validated['outlet_id']);
        }

        return $this->cashierOutlet();
    }

    private function cashierOutlet(): ?Outlet
    {
        $access = auth()->user()->outletAccesses()
            ->with('outlet')
            ->where('can_cashier', true)
            ->first();

        return $access?->outlet;
    }

    private function shiftCode(Outlet $outlet, CashierShift $shift): string
    {
        return sprintf('BL-%s-SHIFT-%s-%03d', $outlet->code, now()->format('Ymd'), $shift->id);
    }
}
