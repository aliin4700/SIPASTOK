@extends('manager.layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h4 mb-1">Cashier Shift Detail</h1>
        <p class="text-muted mb-0">{{ $shift->shift_code ?? '#' . $shift->id }} - read only</p>
    </div>
    <a href="{{ route('manager.cashier-shifts.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
</div>

@include('cashier.shift.partials.report-detail', ['shift' => $shift, 'showReceiptLinks' => false])
@endsection
