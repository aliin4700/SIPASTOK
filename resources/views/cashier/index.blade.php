@extends('cashier.layouts.app')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="cashier-card p-3 h-100">
            <div class="text-muted small fw-semibold text-uppercase">Current Outlet</div>
            <div class="h5 mt-2 mb-0">{{ $currentOutlet->name ?? 'Belum ada outlet' }}</div>
            <div class="text-muted small">{{ $currentOutlet->code ?? 'Assign outlet untuk cashier nanti.' }}</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="cashier-card p-3 h-100">
            <div class="text-muted small fw-semibold text-uppercase">Current Shift Status</div>
            <div class="h5 mt-2 mb-0">{{ $currentShift?->status ? ucfirst($currentShift->status) : 'Belum open shift' }}</div>
            <div class="text-muted small">{{ $currentShift?->shift_code ?? ($latestShift?->shift_code ?? 'Open shift dari menu Shift.') }}</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="cashier-card p-3 h-100">
            <div class="text-muted small fw-semibold text-uppercase">Today POS Orders</div>
            <div class="h4 mt-2 mb-0">{{ number_format($todayPosOrders, 0, ',', '.') }}</div>
            <div class="text-muted small">Placeholder sampai POS order dibuat.</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="cashier-card p-3 h-100">
            <div class="text-muted small fw-semibold text-uppercase">Today Cash Sales</div>
            <div class="h4 mt-2 mb-0">Rp {{ number_format($todayCashSales, 0, ',', '.') }}</div>
            <div class="text-muted small">Tidak termasuk finance/net profit.</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="cashier-card p-3 h-100">
            <div class="text-muted small fw-semibold text-uppercase">Opening Cash</div>
            <div class="h5 mt-2 mb-0">Rp {{ number_format((float) ($currentShift?->opening_cash ?? $latestShift?->opening_cash ?? 0), 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="cashier-card p-3 h-100">
            <div class="text-muted small fw-semibold text-uppercase">Expected Cash</div>
            <div class="h5 mt-2 mb-0">Rp {{ number_format((float) ($currentShift?->expected_cash ?? $latestShift?->expected_cash ?? 0), 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="cashier-card p-3 h-100">
            <div class="text-muted small fw-semibold text-uppercase">Cash Difference</div>
            <div class="h5 mt-2 mb-0">Rp {{ number_format((float) ($latestShift?->cash_difference ?? 0), 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="cashier-card p-4">
    <h2 class="h5 mb-3">Shortcut</h2>
    <div class="row g-3">
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('cashier.orders.index') }}" class="btn btn-bread w-100 py-3">POS / Orders</a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('cashier.shift.index') }}" class="btn btn-outline-dark w-100 py-3">Shift</a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('cashier.cash-flow.index') }}" class="btn btn-outline-dark w-100 py-3">Cash Flow</a>
        </div>
        <div class="col-md-6 col-xl-3">
            <a href="{{ route('cashier.receipts.index') }}" class="btn btn-outline-dark w-100 py-3">Receipts</a>
        </div>
    </div>
</div>
@endsection
