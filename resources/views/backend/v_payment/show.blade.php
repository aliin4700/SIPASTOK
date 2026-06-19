@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); padding: 24px; }
    .status-badge { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 700; padding: 6px 10px; text-transform: capitalize; }
    .status-pending, .status-waiting_confirmation { background: #fff3cd; color: #8a6400; }
    .status-paid { background: #d1e7dd; color: #0f5132; }
    .status-rejected, .status-failed, .status-expired, .status-cancelled { background: #f8d7da; color: #842029; }
</style>

<div class="breadlab-admin-panel mb-4">
    <a href="{{ route('backend.payment.index') }}" class="text-decoration-none">&larr; Kembali ke Pembayaran</a>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3 mb-4">
        <div>
            <h3 class="mb-1">{{ $payment->order->order_number ?? 'Payment' }}</h3>
            <p class="text-muted mb-0">{{ $payment->order->customer->user->name ?? '-' }}</p>
        </div>
        <span class="status-badge status-{{ $payment->payment_status }}">{{ str_replace('_', ' ', $payment->payment_status) }}</span>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <h4 class="h5 mb-3">Detail Payment</h4>
            <dl class="row">
                <dt class="col-sm-4">Method</dt>
                <dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</dd>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">{{ str_replace('_', ' ', $payment->payment_status) }}</dd>
                <dt class="col-sm-4">Paid At</dt>
                <dd class="col-sm-8">{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</dd>
                <dt class="col-sm-4">Total Order</dt>
                <dd class="col-sm-8">Rp {{ number_format($payment->order->total_price ?? 0, 0, ',', '.') }}</dd>
                <dt class="col-sm-4">Order Status</dt>
                <dd class="col-sm-8">{{ str_replace('_', ' ', $payment->order->status ?? '-') }}</dd>
            </dl>
        </div>
        <div class="col-lg-5">
            <h4 class="h5 mb-3">Bukti Pembayaran</h4>
            @if($payment->payment_proof)
            <a href="{{ asset('storage/' . $payment->payment_proof) }}" target="_blank" class="btn btn-outline-dark">Lihat File Bukti</a>
            @else
            <p class="text-muted">Belum ada bukti pembayaran.</p>
            @endif
        </div>
    </div>
</div>

<div class="breadlab-admin-panel">
    <h4 class="h5 mb-3">Aksi Admin</h4>
    <div class="d-flex flex-wrap gap-2">
        @if($payment->payment_method === 'cash' && $payment->payment_status !== 'paid')
        <form action="{{ route('backend.payment.confirmCash', $payment) }}" method="post">
            @csrf
            @method('patch')
            <button class="btn btn-success" type="submit">Confirm Cash</button>
        </form>
        @endif

        @if($payment->payment_method === 'bank_transfer' && in_array($payment->payment_status, ['waiting_confirmation', 'pending', 'rejected'], true))
        <form action="{{ route('backend.payment.verify', $payment) }}" method="post">
            @csrf
            @method('patch')
            <button class="btn btn-success" type="submit">Verify Payment</button>
        </form>
        <form action="{{ route('backend.payment.reject', $payment) }}" method="post">
            @csrf
            @method('patch')
            <button class="btn btn-danger" type="submit">Reject Payment</button>
        </form>
        @endif

        @if($payment->payment_method === 'midtrans')
        <span class="text-muted">Midtrans masih placeholder. Tidak ada aksi manual pada tahap ini.</span>
        @endif
    </div>

    <div class="mt-4">
        <a href="{{ route('backend.order.show', $payment->order) }}" class="btn btn-outline-primary">Lihat Detail Order</a>
    </div>
</div>
@endsection
