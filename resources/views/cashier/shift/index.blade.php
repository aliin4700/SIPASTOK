@extends('cashier.layouts.app')

@section('content')
@if(!$openShift)
    <div class="cashier-card p-4">
        <h2 class="h5 mb-3">Open Shift</h2>

        @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
            @if($outlets->isEmpty())
                <div class="alert alert-warning mb-0">Belum ada outlet aktif.</div>
            @else
                <form action="{{ route('cashier.shift.open') }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <label for="outlet_id" class="form-label">Outlet</label>
                        <select name="outlet_id" id="outlet_id" class="form-control" required>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id || $outlets->count() === 1)>{{ $outlet->name }} ({{ $outlet->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="opening_cash" class="form-label">Opening Cash</label>
                        <input type="number" min="0" step="100" name="opening_cash" id="opening_cash" value="{{ old('opening_cash', 0) }}" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-bread">Open Shift</button>
                </form>
            @endif
        @else
            @if(!$cashierOutlet)
                <div class="alert alert-warning mb-0">Akun kasir belum memiliki akses outlet.</div>
            @else
                <form action="{{ route('cashier.shift.open') }}" method="post">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Outlet</label>
                        <div class="form-control bg-light">{{ $cashierOutlet->name }} ({{ $cashierOutlet->code }})</div>
                    </div>
                    <div class="mb-3">
                        <label for="opening_cash" class="form-label">Opening Cash</label>
                        <input type="number" min="0" step="100" name="opening_cash" id="opening_cash" value="{{ old('opening_cash', 0) }}" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-bread">Open Shift</button>
                </form>
            @endif
        @endif
    </div>
@else
    <div class="cashier-card p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Close Shift</h2>
                <div class="text-muted">Shift sedang berjalan.</div>
            </div>
            <span class="badge bg-success">{{ ucfirst($openShift->status) }}</span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Shift Code</div>
                    <div class="fw-semibold">{{ $openShift->shift_code }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Outlet</div>
                    <div class="fw-semibold">{{ $openShift->outlet->name ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Opened At</div>
                    <div class="fw-semibold">{{ $openShift->opened_at?->format('d M Y H:i') ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small">Opening Cash</div>
                    <div class="fw-semibold">Rp {{ number_format((float) $openShift->opening_cash, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <form action="{{ route('cashier.shift.close') }}" method="post">
            @csrf
            <div class="mb-3">
                <label class="form-label">Expected Cash</label>
                <div class="form-control bg-light">Rp {{ number_format((float) $openShift->expected_cash, 0, ',', '.') }}</div>
            </div>
            <div class="mb-3">
                <label for="actual_cash" class="form-label">Actual Cash</label>
                <input type="number" min="0" step="100" name="actual_cash" id="actual_cash" value="{{ old('actual_cash', $openShift->expected_cash) }}" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-bread">Close Shift</button>
        </form>
    </div>
@endif
@endsection
