@extends('cashier.layouts.app')

@section('content')
<div class="cashier-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 mb-1">Riwayat POS</h2>
            <p class="text-muted mb-0">Daftar order POS cashier.</p>
        </div>
        <a href="{{ route('cashier.orders.index') }}" class="btn btn-bread btn-sm">Buat Order</a>
    </div>

    <form action="{{ route('cashier.orders.history') }}" method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label for="date" class="form-label">Tanggal</label>
            <input type="date" name="date" id="date" value="{{ request('date') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="service_type" class="form-label">Service</label>
            <select name="service_type" id="service_type" class="form-control">
                <option value="">Semua</option>
                @foreach(['dine_in' => 'Dine In', 'pickup' => 'Pickup', 'local_delivery' => 'Local Delivery', 'shipping' => 'Shipping'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('service_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="payment_method" class="form-label">Payment</label>
            <select name="payment_method" id="payment_method" class="form-control">
                <option value="">Semua</option>
                @foreach(['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'qris_manual' => 'QRIS Manual'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment_method') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-bread">Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Waktu</th>
                    <th>Outlet</th>
                    <th>Cashier</th>
                    <th>Service</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number ?: '#' . $order->id }}</td>
                        <td>{{ $order->created_at?->format('d M Y H:i') }}</td>
                        <td>{{ $order->outlet->name ?? '-' }}</td>
                        <td>{{ $order->cashier->name ?? '-' }}</td>
                        <td>{{ str_replace('_', ' ', $order->service_type ?? '-') }}</td>
                        <td>{{ str_replace('_', ' ', $order->payment_method ?? '-') }}</td>
                        <td><span class="badge bg-success">{{ $order->paymentStatus() }}</span></td>
                        <td class="text-end">Rp {{ number_format((float) $order->total_price, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('cashier.receipts.show', $order) }}" class="btn btn-outline-dark btn-sm">Receipt</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Belum ada order POS.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection
