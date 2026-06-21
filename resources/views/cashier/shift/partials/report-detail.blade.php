@php
    $cashSales = $shift->cashSalesTotal();
    $extraCashIn = $shift->extraCashInTotal();
    $cashOut = $shift->cashOutTotal();
    $expectedCash = $shift->expectedCashAmount();
    $posSales = $shift->posSalesTotal();
    $cashPayment = $shift->paymentTotal('cash');
    $bankTransfer = $shift->paymentTotal('bank_transfer');
    $qrisManual = $shift->paymentTotal('qris_manual');
    $showReceiptLinks = $showReceiptLinks ?? true;
@endphp

<div class="row g-3 mb-4">
    @foreach([
        'Opening Cash' => $shift->opening_cash,
        'Cash Sales' => $cashSales,
        'Cash In Lain' => $extraCashIn,
        'Cash Out' => $cashOut,
        'Expected Cash' => $expectedCash,
        'Actual Cash' => $shift->actual_cash,
        'Cash Difference' => $shift->cash_difference,
        'Total POS Sales' => $posSales,
    ] as $label => $value)
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="h5 mb-0">Rp {{ number_format((float) $value, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Cash Payment</div>
                <div class="h5 mb-0">Rp {{ number_format($cashPayment, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Bank Transfer</div>
                <div class="h5 mb-0">Rp {{ number_format($bankTransfer, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">QRIS Manual</div>
                <div class="h5 mb-0">Rp {{ number_format($qrisManual, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Shift Summary</h2>
        <div class="row g-2">
            <div class="col-md-4"><span class="text-muted">Shift</span><br><strong>{{ $shift->shift_code ?? '#' . $shift->id }}</strong></div>
            <div class="col-md-4"><span class="text-muted">Outlet</span><br><strong>{{ $shift->outlet->name ?? '-' }}</strong></div>
            <div class="col-md-4"><span class="text-muted">Cashier</span><br><strong>{{ $shift->cashier->name ?? '-' }}</strong></div>
            <div class="col-md-4"><span class="text-muted">Opened</span><br>{{ $shift->opened_at?->format('d M Y H:i') ?? '-' }}</div>
            <div class="col-md-4"><span class="text-muted">Closed</span><br>{{ $shift->closed_at?->format('d M Y H:i') ?? '-' }}</div>
            <div class="col-md-4"><span class="text-muted">Status</span><br><span class="badge bg-secondary">{{ $shift->status }}</span></div>
            <div class="col-md-4"><span class="text-muted">Reviewed By</span><br>{{ $shift->reviewer->name ?? '-' }}</div>
            <div class="col-md-4"><span class="text-muted">Reviewed At</span><br>{{ $shift->reviewed_at?->format('d M Y H:i') ?? '-' }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Cash Movements</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th class="text-end">Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shift->cashMovements->sortByDesc('created_at') as $movement)
                        <tr>
                            <td>{{ $movement->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ str_replace('_', ' ', $movement->type) }}</td>
                            <td>{{ str_replace('_', ' ', $movement->category) }}</td>
                            <td class="text-end">Rp {{ number_format((float) $movement->amount, 0, ',', '.') }}</td>
                            <td>{{ $movement->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada cash movement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">POS Orders</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Time</th>
                        <th>Service</th>
                        <th>Payment</th>
                        <th class="text-end">Total</th>
                        @if($showReceiptLinks)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($shift->orders->where('order_channel', 'cashier_pos')->sortByDesc('created_at') as $order)
                        <tr>
                            <td>{{ $order->order_number ?: '#' . $order->id }}</td>
                            <td>{{ $order->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ str_replace('_', ' ', $order->service_type ?? '-') }}</td>
                            <td>{{ str_replace('_', ' ', $order->payment_method ?? '-') }}</td>
                            <td class="text-end">Rp {{ number_format((float) $order->total_price, 0, ',', '.') }}</td>
                            @if($showReceiptLinks)
                                <td class="text-end"><a href="{{ route('cashier.receipts.show', $order) }}" class="btn btn-outline-dark btn-sm">Receipt</a></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $showReceiptLinks ? 6 : 5 }}" class="text-center text-muted py-4">Belum ada order POS.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
