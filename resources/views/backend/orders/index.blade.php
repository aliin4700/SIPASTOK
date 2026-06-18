@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-panel {
        background: #fff;
        border: 1px solid #f0e4d3;
        border-radius: 8px;
        box-shadow: 0 10px 24px rgba(139, 69, 19, .08);
        padding: 24px;
    }

    .status-badge {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        padding: 6px 10px;
        text-transform: capitalize;
    }

    .status-pending, .status-waiting_payment { background: #fff3cd; color: #8a6400; }
    .status-paid, .status-processing, .status-ready_for_pickup, .status-delivering, .status-shipped, .status-completed { background: #d1e7dd; color: #0f5132; }
    .status-cancelled { background: #f8d7da; color: #842029; }
</style>

<div class="breadlab-admin-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="mb-1">{{ $judul }}</h3>
            <p class="text-muted mb-0">Kelola pesanan pelanggan BreadLab Bakery.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Transaction</th>
                    <th>Total Price</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="fw-bold">#{{ $order->id }}</td>
                    <td>{{ $order->user->nama ?? $order->user->name ?? '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $order->transaction_method ?? 'shipping')) }}</td>
                    <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                    <td>
                        <span class="status-badge status-{{ $order->paymentStatus() }}">
                            {{ $order->paymentStatus() }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $order->normalizedStatus() }}">
                            {{ $order->normalizedStatus() }}
                        </span>
                    </td>
                    <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-primary btn-sm">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Belum ada pesanan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>
</div>
@endsection
