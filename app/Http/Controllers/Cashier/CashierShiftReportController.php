<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;

class CashierShiftReportController extends Controller
{
    public function report()
    {
        $shift = CashierShift::with(['outlet', 'cashier', 'reviewer', 'cashMovements', 'orders.payment'])
            ->where('cashier_id', auth()->id())
            ->latest('opened_at')
            ->first();

        return view('cashier.shift.report', [
            'title' => 'Shift Report',
            'shift' => $shift,
        ]);
    }
}
