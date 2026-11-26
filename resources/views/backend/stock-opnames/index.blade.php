@extends('backend.v_layouts.app')

@section('content')
@php
    $latestOpnameDate = \App\Models\StockOpname::max('opname_date');
    $selectedMonth = request('month', $latestOpnameDate ? \Carbon\Carbon::parse($latestOpnameDate)->format('Y-m') : now()->format('Y-m'));
    [$year, $month] = array_pad(explode('-', $selectedMonth), 2, now()->format('m'));

    $selectedOutletId = request('outlet_id');
    $selectedDivisionId = request('division_id');
    $selectedStatus = request('status');

    $outletsList = $outlets ?? \App\Models\Outlet::where('is_active', true)->orderBy('name')->get();
    $divisionsList = $divisions ?? \App\Models\Division::where('is_active', true)->orderBy('name')->get();
    $statusesList = $statuses ?? \App\Models\StockOpname::STATUSES;

    $monthlyOpnames = \App\Models\StockOpname::with('items')
        ->whereYear('opname_date', (int) $year)
        ->whereMonth('opname_date', (int) $month)
        ->when($selectedOutletId, fn ($q) => $q->where('outlet_id', $selectedOutletId))
        ->when($selectedDivisionId, fn ($q) => $q->where('division_id', $selectedDivisionId))
        ->when($selectedStatus, fn ($q) => $q->where('status', $selectedStatus))
        ->get();

    $totalSessions = $monthlyOpnames->count();
    $sessionAccuracies = $monthlyOpnames->map(function ($op) {
        $total = $op->items->count();
        if ($total === 0) {
            return 100.0;
        }
        $matched = $op->items->filter(fn ($i) => abs((float) $i->difference_qty) < 0.0001)->count();
        return ($matched / $total) * 100.0;
    });
    $avgIra = $sessionAccuracies->isNotEmpty() ? $sessionAccuracies->avg() : 0.0;
    $monthlyVarianceValue = (float) $monthlyOpnames->sum('total_variance_value');
    $opnameTypes = \App\Models\StockOpname::TYPES;
@endphp

<x-sipastok-status-stepper
    status="submitted"
    role="admin"
    customGuide="Gunakan filter Outlet, Divisi, dan Status di bawah ini untuk memantau tingkat akurasi stok (IRA) dan menyetujui (Approve) sesi Stock Opname yang telah disubmit oleh Staff."
/>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">{{ $judul }} & Rekap Akurasi Stok (IRA)</h4>
        <div class="text-muted small">Monitoring Inventory Record Accuracy (IRA) & persetujuan penyesuaian stok opname seluruh outlet Medja Cerita.</div>
    </div>
</div>

<!-- Filter Bar: Outlet, Divisi, Status, & Periode KPI -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3 col-6">
                <label class="form-label small fw-semibold mb-1">Outlet</label>
                <select name="outlet_id" class="form-select form-select-sm">
                    <option value="">Semua Outlet</option>
                    @foreach($outletsList as $outlet)
                        <option value="{{ $outlet->id }}" @selected((string) $selectedOutletId === (string) $outlet->id)>
                            {{ $outlet->name }} ({{ $outlet->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small fw-semibold mb-1">Divisi</label>
                <select name="division_id" class="form-select form-select-sm">
                    <option value="">Semua Divisi</option>
                    @foreach($divisionsList as $division)
                        <option value="{{ $division->id }}" @selected((string) $selectedDivisionId === (string) $division->id)>
                            {{ $division->name }} ({{ $division->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small fw-semibold mb-1">Status Opname</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach($statusesList as $st)
                        <option value="{{ $st }}" @selected($selectedStatus === $st)>{{ strtoupper($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label small fw-semibold mb-1">Periode KPI</label>
                <input type="month" name="month" value="{{ $selectedMonth }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 col-12 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">Terapkan Filter</button>
                <a href="{{ route('backend.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Kartu Ringkasan KPI Bulanan (IRA) -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-primary h-100 mb-0">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total Sesi Opname ({{ $selectedMonth }})</div>
                <h3 class="mb-1 fw-bold text-primary">{{ $totalSessions }} Sesi</h3>
                <div class="small text-muted">Total pelaksanaan opname periode terpilih</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success h-100 mb-0">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Rata-rata Akurasi Stok / IRA (%)</div>
                <h3 class="mb-1 fw-bold {{ $avgIra >= 95 ? 'text-success' : ($avgIra >= 85 ? 'text-warning' : 'text-danger') }}">
                    {{ number_format($avgIra, 2, ',', '.') }}%
                </h3>
                <div class="small text-muted">Persentase kesesuaian stok sistem vs fisik</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger h-100 mb-0">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total Nilai Selisih (Rp)</div>
                <h3 class="mb-1 fw-bold {{ $monthlyVarianceValue < 0 ? 'text-danger' : 'text-dark' }}">
                    Rp {{ number_format($monthlyVarianceValue, 2, ',', '.') }}
                </h3>
                <div class="small text-muted">Total nilai finansial selisih opname ({{ $selectedMonth }})</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title mb-3 fw-bold">Riwayat Sesi Stock Opname</h5>

        <!-- Tabel Desktop (md ke atas) -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No Opname</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Counter</th>
                        <th>Status</th>
                        <th class="text-end">Total Item</th>
                        <th class="text-end">Akurasi (IRA)</th>
                        <th class="text-end">Total Nilai Selisih</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($opnames as $opname)
                        @php
                            $itemCount = $opname->items->count();
                            $matchedCount = $opname->items->filter(fn ($i) => abs((float) $i->difference_qty) < 0.0001)->count();
                            $iraPct = $itemCount > 0 ? ($matchedCount / $itemCount) * 100.0 : 100.0;
                            $varVal = (float) ($opname->total_variance_value ?? $opname->items->sum('variance_value'));
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $opname->opname_number ?? '-' }}</td>
                            <td>{{ $opname->opname_date?->format('d/m/Y') }}</td>
                            <td>{{ $opnameTypes[$opname->opname_type] ?? $opname->opname_type }}</td>
                            <td>{{ $opname->outlet->name ?? '-' }}</td>
                            <td>{{ $opname->division->name ?? '-' }}</td>
                            <td>{{ $opname->counter->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $opname->status === 'reviewed' ? 'badge-success' : ($opname->status === 'submitted' ? 'badge-warning' : ($opname->status === 'cancelled' ? 'badge-danger' : 'badge-info')) }} text-uppercase">
                                    {{ $opname->status }}
                                </span>
                            </td>
                            <td class="text-end">{{ $itemCount }}</td>
                            <td class="text-end fw-semibold {{ $iraPct >= 95 ? 'text-success' : 'text-warning' }}">
                                {{ number_format($iraPct, 1, ',', '.') }}%
                            </td>
                            <td class="text-end {{ $varVal < 0 ? 'text-danger fw-semibold' : '' }}">
                                Rp {{ number_format($varVal, 2, ',', '.') }}
                            </td>
                            <td>
                                <a href="{{ route('backend.stock-opnames.show', $opname) }}" class="btn btn-sm {{ $opname->status === 'submitted' ? 'btn-warning fw-semibold' : 'btn-cyan' }}">
                                    {{ $opname->status === 'submitted' ? 'Review Sekarang' : 'Buka Detail' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">Belum ada stock opname sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Card Mobile (< md) -->
        <div class="d-md-none">
            @forelse($opnames as $opname)
                @php
                    $itemCount = $opname->items->count();
                    $matchedCount = $opname->items->filter(fn ($i) => abs((float) $i->difference_qty) < 0.0001)->count();
                    $iraPct = $itemCount > 0 ? ($matchedCount / $itemCount) * 100.0 : 100.0;
                    $varVal = (float) ($opname->total_variance_value ?? $opname->items->sum('variance_value'));
                @endphp
                <div class="card border mb-3 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold fs-6">{{ $opname->opname_number ?? '-' }}</div>
                                <div class="text-muted small">{{ $opname->opname_date?->format('d/m/Y') }} &bull; {{ $opnameTypes[$opname->opname_type] ?? $opname->opname_type }}</div>
                            </div>
                            <span class="badge {{ $opname->status === 'reviewed' ? 'badge-success' : ($opname->status === 'submitted' ? 'badge-warning' : ($opname->status === 'cancelled' ? 'badge-danger' : 'badge-info')) }} text-uppercase">
                                {{ $opname->status }}
                            </span>
                        </div>
                        <div class="small mb-3">
                            <div><strong>Outlet / Divisi:</strong> {{ $opname->outlet->name ?? '-' }} / {{ $opname->division->name ?? '-' }}</div>
                            <div><strong>Total Item & Akurasi:</strong> {{ $itemCount }} item (IRA: <strong>{{ number_format($iraPct, 1, ',', '.') }}%</strong>)</div>
                            <div><strong>Nilai Selisih:</strong> <span class="{{ $varVal < 0 ? 'text-danger fw-bold' : 'fw-semibold' }}">Rp {{ number_format($varVal, 2, ',', '.') }}</span></div>
                        </div>
                        <a href="{{ route('backend.stock-opnames.show', $opname) }}" class="btn {{ $opname->status === 'submitted' ? 'btn-warning' : 'btn-cyan' }} w-100 fw-semibold">
                            {{ $opname->status === 'submitted' ? 'Buka & Review Sekarang' : 'Buka Detail' }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4 border rounded bg-light">Belum ada stock opname sesuai filter.</div>
            @endforelse
        </div>

        {{ $opnames->links() }}
    </div>
</div>
@endsection
