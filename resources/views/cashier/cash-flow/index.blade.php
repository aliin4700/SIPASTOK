@extends('cashier.layouts.app')

@section('content')
@if(!$openShift)
    <div class="cashier-card p-4">
        <h2 class="h5 mb-2">Cash Flow</h2>
        <p class="text-muted mb-3">Buka shift terlebih dahulu sebelum mencatat cash in/cash out.</p>
        <a href="{{ route('cashier.shift.index') }}" class="btn btn-bread">Buka Shift</a>
    </div>
@else
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="cashier-card p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Shift</div>
                <div class="h6 mt-2 mb-0">{{ $openShift->shift_code }}</div>
                <div class="text-muted small">{{ $openShift->outlet->name ?? '-' }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="cashier-card p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Modal Awal</div>
                <div class="h5 mt-2 mb-0">Rp {{ number_format((float) $openShift->opening_cash, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="cashier-card p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Kas Masuk</div>
                <div class="h5 mt-2 mb-0">Rp {{ number_format((float) $cashInTotal, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="cashier-card p-3 h-100">
                <div class="text-muted small fw-semibold text-uppercase">Kas Keluar</div>
                <div class="h5 mt-2 mb-0">Rp {{ number_format((float) $cashOutTotal, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="cashier-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">Catat Cash Movement</h2>
                <p class="text-muted mb-0">Kas Seharusnya: <strong>Rp {{ number_format((float) $expectedCash, 0, ',', '.') }}</strong></p>
            </div>
        </div>

        <form action="{{ route('cashier.cash-flow.store') }}" method="post">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Tipe</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="cash_in" @selected(old('type') === 'cash_in')>Kas Masuk</option>
                        <option value="cash_out" @selected(old('type') === 'cash_out')>Kas Keluar</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Kategori</label>
                    <select name="category" id="category" class="form-control" required>
                        <option value="additional_cash" @selected(old('category') === 'additional_cash')>Tambahan Kas</option>
                        <option value="delivery_fee" @selected(old('category') === 'delivery_fee')>Delivery Fee</option>
                        <option value="petty_cash" @selected(old('category') === 'petty_cash')>Petty Cash</option>
                        <option value="refund" @selected(old('category') === 'refund')>Refund</option>
                        <option value="operational_purchase" @selected(old('category') === 'operational_purchase')>Belanja Operasional</option>
                        <option value="other" @selected(old('category') === 'other')>Lainnya</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="amount" class="form-label">Nominal</label>
                    <input type="number" min="1" step="100" name="amount" id="amount" value="{{ old('amount') }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="description" class="form-label">Keterangan</label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}" maxlength="500" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-bread mt-3">Simpan Movement</button>
        </form>
    </div>

    <div class="cashier-card p-4">
        <h2 class="h5 mb-3">Recent Cash Movement</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Tipe</th>
                        <th>Kategori</th>
                        <th>Nominal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge {{ $movement->type === 'cash_in' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $movement->type === 'cash_in' ? 'Kas Masuk' : 'Kas Keluar' }}
                                </span>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $movement->category)) }}</td>
                            <td>Rp {{ number_format((float) $movement->amount, 0, ',', '.') }}</td>
                            <td>{{ $movement->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada cash movement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
