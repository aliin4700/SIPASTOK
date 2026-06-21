@extends('manager.layouts.app')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Cashier Shift Reports</h1>
                <p class="text-muted mb-0">Read-only monitoring shift kasir.</p>
            </div>
        </div>

        <form action="{{ route('manager.cashier-shifts.index') }}" method="get" class="row g-2 align-items-end mb-3">
            <div class="col-md-2">
                <label for="date" class="form-label">Tanggal</label>
                <input type="date" name="date" id="date" value="{{ request('date') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="outlet_id" class="form-label">Outlet</label>
                <select name="outlet_id" id="outlet_id" class="form-control">
                    <option value="">Semua</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected((string) request('outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="cashier_id" class="form-label">Cashier</label>
                <select name="cashier_id" id="cashier_id" class="form-control">
                    <option value="">Semua</option>
                    @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" @selected((string) request('cashier_id') === (string) $cashier->id)>{{ $cashier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">Semua</option>
                    @foreach(['open', 'closed', 'reviewed', 'locked'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Shift</th>
                        <th>Outlet</th>
                        <th>Cashier</th>
                        <th>Opened</th>
                        <th>Closed</th>
                        <th>Status</th>
                        <th class="text-end">Expected</th>
                        <th class="text-end">Actual</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                        <tr>
                            <td>{{ $shift->shift_code ?? '#' . $shift->id }}</td>
                            <td>{{ $shift->outlet->name ?? '-' }}</td>
                            <td>{{ $shift->cashier->name ?? '-' }}</td>
                            <td>{{ $shift->opened_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $shift->closed_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $shift->status }}</span></td>
                            <td class="text-end">Rp {{ number_format((float) $shift->expected_cash, 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format((float) $shift->actual_cash, 0, ',', '.') }}</td>
                            <td class="text-end"><a href="{{ route('manager.cashier-shifts.show', $shift) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Belum ada shift.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $shifts->links() }}
    </div>
</div>
@endsection
