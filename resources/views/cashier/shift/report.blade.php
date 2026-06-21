@extends('cashier.layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="h5 mb-1">Shift Report</h2>
        <p class="text-muted mb-0">Ringkasan shift kasir Anda.</p>
    </div>
    <a href="{{ route('cashier.shift.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Shift</a>
</div>

@if(!$shift)
    <div class="cashier-card p-4 text-center text-muted">Belum ada shift untuk akun ini.</div>
@else
    @include('cashier.shift.partials.report-detail', ['shift' => $shift, 'showReceiptLinks' => true])
@endif
@endsection
