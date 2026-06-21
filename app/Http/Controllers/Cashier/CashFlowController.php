<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierCashMovement;
use App\Models\CashierShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CashFlowController extends Controller
{
    public function index()
    {
        $openShift = $this->openShift();
        $movements = $openShift
            ? $openShift->cashMovements()->latest()->get()
            : collect();

        return view('cashier.cash-flow.index', [
            'title' => 'Cash Flow',
            'openShift' => $openShift,
            'movements' => $movements,
            'cashInTotal' => $openShift?->cashInTotal() ?? 0,
            'cashOutTotal' => $openShift?->cashOutTotal() ?? 0,
            'expectedCash' => $openShift?->expectedCashAmount() ?? 0,
        ]);
    }

    public function store(Request $request)
    {
        $openShift = $this->openShift();

        if (!$openShift) {
            return back()->with('error', 'Buka shift terlebih dahulu sebelum mencatat cash in/cash out.');
        }

        $validated = $request->validate([
            'type' => ['required', Rule::in(['cash_in', 'cash_out'])],
            'category' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $categories = [
            'cash_in' => ['additional_cash', 'delivery_fee', 'other'],
            'cash_out' => ['petty_cash', 'refund', 'operational_purchase', 'other'],
        ];

        if (!in_array($validated['category'], $categories[$validated['type']], true)) {
            return back()
                ->withInput()
                ->withErrors(['category' => 'Kategori tidak sesuai dengan tipe kas.']);
        }

        DB::transaction(function () use ($openShift, $validated) {
            CashierCashMovement::create([
                'cashier_shift_id' => $openShift->id,
                'outlet_id' => $openShift->outlet_id,
                'cashier_id' => $openShift->cashier_id,
                'type' => $validated['type'],
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
            ]);

            $openShift->refreshExpectedCash();
        });

        return redirect()->route('cashier.cash-flow.index')->with('success', 'Cash movement berhasil dicatat.');
    }

    private function openShift(): ?CashierShift
    {
        return CashierShift::with('outlet')
            ->where('cashier_id', auth()->id())
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }
}
