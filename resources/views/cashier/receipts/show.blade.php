@extends('cashier.layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('cashier.receipts.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            <a href="{{ route('cashier.receipts.print', $order) }}" target="_blank" class="btn btn-bread btn-sm">Print</a>
        </div>

        <div class="cashier-card p-4">
            <div class="text-center border-bottom pb-3 mb-3">
                <h2 class="h4 mb-1">BreadLab</h2>
                <div class="text-muted">{{ $order->outlet->name ?? 'Outlet' }}</div>
            </div>

            @include('cashier.receipts.partials.receipt-body', ['order' => $order])
        </div>
    </div>
</div>
@endsection
