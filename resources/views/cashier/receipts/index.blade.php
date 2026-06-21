@extends('cashier.layouts.app')

@section('content')
<div class="cashier-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h5 mb-1">Receipts</h2>
            <p class="text-muted mb-0">Receipt order POS yang sudah selesai.</p>
        </div>
        <a href="{{ route('cashier.orders.history') }}" class="btn btn-outline-dark btn-sm">Riwayat POS</a>
    </div>

    <form action="{{ route('cashier.receipts.index') }}" method="get" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <label for="date" class="form-label">Tanggal</label>
            <input type="date" name="date" id="date" value="{{ request('date') }}" class="form-control">
        </div>
        <div class="col-md-2 d-grid">
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
                    <th>Payment</th>
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
                        <td>{{ str_replace('_', ' ', $order->payment_method ?? '-') }}</td>
                        <td class="text-end">Rp {{ number_format((float) $order->total_price, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('cashier.receipts.show', $order) }}" class="btn btn-outline-dark btn-sm">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada receipt POS.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection
