@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); padding: 24px; }
    .status-badge { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 700; padding: 6px 10px; text-transform: capitalize; }
    .status-pending, .status-waiting_confirmation { background: #fff3cd; color: #8a6400; }
    .status-paid { background: #d1e7dd; color: #0f5132; }
    .status-rejected, .status-failed, .status-expired, .status-cancelled { background: #f8d7da; color: #842029; }
</style>

<div class="breadlab-admin-panel">
    <h3 class="mb-1">{{ $judul }}</h3>
    <p class="text-muted mb-4">Verifikasi pembayaran manual BreadLab.</p>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Paid At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->order->order_number ?? '-' }}</td>
                    <td>{{ $payment->order->customer->user->name ?? '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td><span class="status-badge status-{{ $payment->payment_status }}">{{ str_replace('_', ' ', $payment->payment_status) }}</span></td>
                    <td>Rp {{ number_format($payment->order->total_price ?? 0, 0, ',', '.') }}</td>
                    <td>{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td><a href="{{ route('backend.payment.show', $payment) }}" class="btn btn-primary btn-sm">Detail</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Belum ada pembayaran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $payments->links() }}
    </div>
</div>
@endsection
