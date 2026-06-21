@extends('backend.v_layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="mb-1">Cashier Shift Detail</h4>
        <p class="text-muted mb-0">{{ $shift->shift_code ?? '#' . $shift->id }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('backend.cashier-shifts.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        @if($shift->status === 'closed')
            <form action="{{ route('backend.cashier-shifts.review', $shift) }}" method="post">
                @csrf
                @method('patch')
                <button type="submit" class="btn btn-primary btn-sm">Mark Reviewed</button>
            </form>
        @endif
    </div>
</div>

@include('cashier.shift.partials.report-detail', ['shift' => $shift, 'showReceiptLinks' => true])
@endsection
