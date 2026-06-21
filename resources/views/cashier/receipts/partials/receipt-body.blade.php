<div class="row g-2 small mb-3">
    <div class="col-6">Order</div>
    <div class="col-6 text-end fw-semibold">{{ $order->order_number ?: '#' . $order->id }}</div>
    <div class="col-6">Tanggal</div>
    <div class="col-6 text-end">{{ $order->created_at?->format('d M Y H:i') }}</div>
    <div class="col-6">Cashier</div>
    <div class="col-6 text-end">{{ $order->cashier->name ?? '-' }}</div>
    <div class="col-6">Service</div>
    <div class="col-6 text-end">{{ str_replace('_', ' ', $order->service_type ?? '-') }}</div>
    @if($order->customer_name)
        <div class="col-6">Customer</div>
        <div class="col-6 text-end">{{ $order->customer_name }}</div>
    @endif
    @if($order->customer_phone)
        <div class="col-6">No HP</div>
        <div class="col-6 text-end">{{ $order->customer_phone }}</div>
    @endif
    @if($order->table_number)
        <div class="col-6">Meja</div>
        <div class="col-6 text-end">{{ $order->table_number }}</div>
    @endif
    @if($order->pickup_time)
        <div class="col-6">Pickup</div>
        <div class="col-6 text-end">{{ $order->pickup_time->format('d M Y H:i') }}</div>
    @endif
    @if($order->address)
        <div class="col-12">Alamat</div>
        <div class="col-12 text-muted">{{ $order->address }}</div>
    @endif
</div>

<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Harga</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">Rp {{ number_format((float) $item->price, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-top pt-3">
    <div class="d-flex justify-content-between">
        <span>Subtotal</span>
        <strong>Rp {{ number_format((float) $order->subtotal, 0, ',', '.') }}</strong>
    </div>
    <div class="d-flex justify-content-between fs-5 mt-2">
        <span>Total</span>
        <strong>Rp {{ number_format((float) $order->total_price, 0, ',', '.') }}</strong>
    </div>
</div>

<div class="border-top mt-3 pt-3 small">
    <div class="d-flex justify-content-between">
        <span>Payment</span>
        <span>{{ str_replace('_', ' ', $order->payment?->payment_method ?? $order->payment_method ?? '-') }}</span>
    </div>
    <div class="d-flex justify-content-between">
        <span>Status</span>
        <span>{{ $order->paymentStatus() }}</span>
    </div>
    <div class="d-flex justify-content-between">
        <span>Paid</span>
        <span>Rp {{ number_format((float) ($order->payment?->paid_amount ?? $order->total_price), 0, ',', '.') }}</span>
    </div>
    <div class="d-flex justify-content-between">
        <span>Kembalian</span>
        <span>Rp {{ number_format((float) ($order->payment?->change_amount ?? 0), 0, ',', '.') }}</span>
    </div>
</div>
