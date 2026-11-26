@extends('manager.layouts.app')

@section('content')
@php
    $opnameTypes = \App\Models\StockOpname::TYPES;
    $varianceReasons = \App\Models\StockOpnameItem::VARIANCE_REASONS;
    $iraColorClass = $iraPercentage >= 95 ? 'text-success' : ($iraPercentage >= 85 ? 'text-warning' : 'text-danger');
    $iraBadgeClass = $iraPercentage >= 95 ? 'bg-success' : ($iraPercentage >= 85 ? 'bg-warning text-dark' : 'bg-danger');
@endphp

<!-- Header & Filter Cabang / Periode -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <span class="badge bg-primary text-uppercase mb-1">SIPASTOK Executive Dashboard</span>
                <h3 class="mb-1 fw-bold">Panel Monitoring Operasional &amp; Stok Opname — Medja Cerita</h3>
                <div class="text-muted small">
                    Cabang Aktif: <strong>{{ $selectedOutlet?->name ?? 'Semua Outlet (Mejasem, Slawi, Kagok CK)' }}</strong> &bull;
                    Periode Evaluasi: <strong>{{ $selectedMonth }}</strong> ({{ $totalSessionsPeriod }} sesi opname)
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('manager.stock-opnames.index', array_filter(['outlet_id' => $selectedOutletId, 'status' => 'submitted'])) }}" class="btn btn-warning fw-semibold">
                    Review Stock Opname
                    @if($pendingOpnamesCount > 0)
                        <span class="badge bg-danger ms-1">{{ $pendingOpnamesCount }}</span>
                    @endif
                </a>
                <a href="{{ route('manager.opening-alerts.index', array_filter(['outlet_id' => $selectedOutletId])) }}" class="btn btn-outline-danger fw-semibold">
                    Cek Stok Kritis ({{ $lowStockCount }})
                </a>
            </div>
        </div>

        <form method="get" action="{{ route('manager.dashboard') }}" class="row g-2 align-items-end pt-2 border-top">
            <div class="col-md-4 col-sm-6">
                <label class="form-label small fw-semibold mb-1">Filter Cabang / Outlet</label>
                <select name="outlet_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Outlet (Mejasem, Slawi, Kagok CK)</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected((int) $selectedOutletId === (int) $outlet->id)>
                            {{ $outlet->name }} ({{ $outlet->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label small fw-semibold mb-1">Periode Bulan (IRA &amp; Variance)</label>
                <input type="month" name="month" value="{{ $selectedMonth }}" class="form-control form-control-sm" onchange="this.form.submit()">
            </div>
            <div class="col-md-4 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Terapkan Filter</button>
                <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- 4 Kartu KPI Utama (Grid 2x2 di Mobile, 4 Kolom di Desktop) -->
<div class="row g-3 mb-4">
    <!-- Kartu 1: Menunggu Review -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 shadow-sm {{ $pendingOpnamesCount > 0 ? 'border-warning' : 'border-0' }}">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                        <span class="text-muted small text-uppercase fw-semibold">Menunggu Review</span>
                        @if($pendingOpnamesCount > 0)
                            <span class="badge bg-danger">Perlu Tindakan</span>
                        @else
                            <span class="badge bg-success">Terkendali</span>
                        @endif
                    </div>
                    <h2 class="fw-bold mb-1 {{ $pendingOpnamesCount > 0 ? 'text-warning' : 'text-dark' }}">
                        {{ $pendingOpnamesCount }} <span class="fs-6 fw-normal text-muted">Dokumen</span>
                    </h2>
                </div>
                <div class="mt-2 pt-2 border-top">
                    <a href="{{ route('manager.stock-opnames.index', array_filter(['outlet_id' => $selectedOutletId, 'status' => 'submitted'])) }}" class="small text-decoration-none fw-semibold">
                        Lihat Antrean Submitted &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Kartu 2: Akurasi Stok (IRA) -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                        <span class="text-muted small text-uppercase fw-semibold">Akurasi Stok (IRA)</span>
                        <span class="badge {{ $iraBadgeClass }}">
                            {{ $iraPercentage >= 95 ? 'Sangat Baik' : ($iraPercentage >= 85 ? 'Waspada' : 'Kritis') }}
                        </span>
                    </div>
                    <h2 class="fw-bold mb-1 {{ $iraColorClass }}">
                        {{ number_format($iraPercentage, 2, ',', '.') }}%
                    </h2>
                </div>
                <div class="mt-2 pt-2 border-top small text-muted">
                    {{ number_format($totalItemsMatched, 0, ',', '.') }} sesuai dari {{ number_format($totalItemsCounted, 0, ',', '.') }} item dihitung
                </div>
            </div>
        </div>
    </div>

    <!-- Kartu 3: Total Selisih Nilai (Rp) -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                        <span class="text-muted small text-uppercase fw-semibold">Total Selisih Nilai</span>
                        <span class="badge {{ $totalVarianceValue < 0 ? 'bg-danger' : ($totalVarianceValue > 0 ? 'bg-info text-dark' : 'bg-secondary') }}">
                            {{ $totalVarianceValue < 0 ? 'Defisit' : ($totalVarianceValue > 0 ? 'Surplus' : 'Balance') }}
                        </span>
                    </div>
                    <h3 class="fw-bold mb-1 {{ $totalVarianceValue < 0 ? 'text-danger' : 'text-dark' }}">
                        Rp {{ number_format($totalVarianceValue, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="mt-2 pt-2 border-top small text-muted">
                    Akumulasi opname reviewed ({{ $selectedMonth }})
                </div>
            </div>
        </div>
    </div>

    <!-- Kartu 4: Stok Di Bawah Minimum -->
    <div class="col-6 col-lg-3">
        <div class="card h-100 shadow-sm {{ $lowStockCount > 0 ? 'border-danger' : 'border-0' }}">
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
                        <span class="text-muted small text-uppercase fw-semibold">Stok &le; Minimum</span>
                        <span class="badge {{ $lowStockCount > 0 ? 'bg-danger' : 'bg-success' }}">
                            {{ $lowStockCount > 0 ? 'Stok Kritis' : 'Aman' }}
                        </span>
                    </div>
                    <h2 class="fw-bold mb-1 {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $lowStockCount }} <span class="fs-6 fw-normal text-muted">Bahan</span>
                    </h2>
                </div>
                <div class="mt-2 pt-2 border-top">
                    <a href="{{ route('manager.opening-alerts.index', array_filter(['outlet_id' => $selectedOutletId])) }}" class="small text-danger text-decoration-none fw-semibold">
                        Buka Opening Alerts &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel Antrean Persetujuan Stock Opname (Submitted) -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold">Antrean Persetujuan Stock Opname (Status: Submitted)</h5>
            <div class="text-muted small">Dokumen perhitungan fisik dari Staff yang menunggu verifikasi &amp; penyesuaian stok Manager.</div>
        </div>
        <a href="{{ route('manager.stock-opnames.index', array_filter(['outlet_id' => $selectedOutletId])) }}" class="btn btn-sm btn-outline-primary">
            Semua Riwayat Opname &rarr;
        </a>
    </div>
    <div class="card-body">
        @if($pendingOpnames->isEmpty())
            <div class="alert alert-success mb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <strong>Semua dokumen Stock Opname telah direview!</strong> Tidak ada antrean yang tertunda saat ini.
                </div>
                <a href="{{ route('manager.stock-opnames.index', array_filter(['outlet_id' => $selectedOutletId, 'month' => $selectedMonth])) }}" class="btn btn-sm btn-success">
                    Lihat Riwayat Periode {{ $selectedMonth }}
                </a>
            </div>
        @else
            <!-- Tabel Desktop -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No Opname</th>
                            <th>Tanggal</th>
                            <th>Outlet / Divisi</th>
                            <th>Jenis Opname</th>
                            <th>Petugas Hitung</th>
                            <th class="text-end">Item Dihitung</th>
                            <th class="text-end">Estimasi Selisih (Rp)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingOpnames as $op)
                            @php
                                $varVal = (float) ($op->total_variance_value ?? $op->items->sum('variance_value'));
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $op->opname_number ?? '-' }}</td>
                                <td>{{ $op->opname_date?->format('d/m/Y') }}</td>
                                <td>{{ $op->outlet->name ?? '-' }} / <strong>{{ $op->division->name ?? '-' }}</strong></td>
                                <td>{{ $opnameTypes[$op->opname_type] ?? $op->opname_type }}</td>
                                <td>{{ $op->counter->name ?? '-' }}</td>
                                <td class="text-end">{{ $op->items->count() }} item</td>
                                <td class="text-end {{ $varVal < 0 ? 'text-danger fw-bold' : '' }}">
                                    Rp {{ number_format($varVal, 2, ',', '.') }}
                                </td>
                                <td>
                                    <a href="{{ route('manager.stock-opnames.show', $op) }}" class="btn btn-sm btn-warning fw-bold">
                                        Review Sekarang
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Card Mobile -->
            <div class="d-md-none">
                @foreach($pendingOpnames as $op)
                    @php
                        $varVal = (float) ($op->total_variance_value ?? $op->items->sum('variance_value'));
                    @endphp
                    <div class="card border-warning mb-2">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold">{{ $op->opname_number ?? '-' }}</div>
                                    <div class="small text-muted">{{ $op->opname_date?->format('d/m/Y') }} &bull; {{ $op->outlet->name ?? '-' }} ({{ $op->division->name ?? '-' }})</div>
                                </div>
                                <span class="badge bg-warning text-dark">SUBMITTED</span>
                            </div>
                            <div class="small mb-2">
                                Petugas: <strong>{{ $op->counter->name ?? '-' }}</strong> &bull;
                                Item: <strong>{{ $op->items->count() }}</strong> &bull;
                                Selisih: <strong>Rp {{ number_format($varVal, 0, ',', '.') }}</strong>
                            </div>
                            <a href="{{ route('manager.stock-opnames.show', $op) }}" class="btn btn-warning btn-sm w-100 fw-bold">
                                Review Sekarang
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Baris Panel Evaluasi: Top 5 Selisih Terbesar & Monitoring Operasional Divisi Hari Ini -->
<div class="row g-4 mb-4">
    <!-- Panel Top 5 Item Selisih Terbesar -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Top 5 Item dengan Selisih (Variance) Terbesar</h5>
                    <div class="text-muted small">Bahan baku dengan selisih fisik vs sistem tertinggi pada periode {{ $selectedMonth }}.</div>
                </div>
            </div>
            <div class="card-body">
                @if($topVarianceItems->isEmpty())
                    <div class="text-center text-muted py-4 border rounded bg-light">
                        Tidak ditemukan item dengan selisih signifikan pada periode {{ $selectedMonth }} (Akurasi 100%).
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bahan Baku</th>
                                    <th>Outlet / Divisi</th>
                                    <th class="text-end">Selisih Qty</th>
                                    <th class="text-end">Nilai Selisih (Rp)</th>
                                    <th>Alasan / Evaluasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topVarianceItems as $vItem)
                                    @php
                                        $diffQty = (float) $vItem->difference_qty;
                                        $varRp = (float) $vItem->variance_value;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $vItem->item->name ?? '-' }}</div>
                                            <small class="text-muted">{{ $vItem->stockOpname?->opname_date?->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            {{ $vItem->stockOpname?->outlet?->name ?? '-' }} /
                                            <span class="fw-semibold">{{ $vItem->stockOpname?->division?->name ?? '-' }}</span>
                                        </td>
                                        <td class="text-end fw-bold {{ $diffQty < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ ($diffQty > 0 ? '+' : '') . number_format($diffQty, 2, ',', '.') }} {{ strtoupper($vItem->unit) }}
                                        </td>
                                        <td class="text-end {{ $varRp < 0 ? 'text-danger fw-bold' : '' }}">
                                            Rp {{ number_format($varRp, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $varianceReasons[$vItem->variance_reason] ?? ($vItem->variance_reason ?: 'Perlu Evaluasi') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Panel Status Operasional Hari Ini per Divisi -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold">Status Operasional Divisi Hari Ini</h5>
                    <div class="text-muted small">Tanggal: {{ today()->format('d/m/Y') }}</div>
                </div>
                <a href="{{ route('manager.daily-operations.index') }}" class="btn btn-sm btn-outline-secondary">
                    Detail Harian
                </a>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($todayOperations as $opRow)
                        @php
                            $div = $opRow['division'];
                            $st = $opRow['status'];
                            $badgeClass = match ($st) {
                                'open' => 'bg-primary',
                                'submitted' => 'bg-warning text-dark',
                                'approved' => 'bg-success',
                                default => 'bg-secondary',
                            };
                            $label = match ($st) {
                                'open' => 'OPEN (Berjalan)',
                                'submitted' => 'SUBMITTED (Closing)',
                                'approved' => 'APPROVED (Selesai)',
                                default => 'BELUM DIBUKA',
                            };
                        @endphp
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="fw-bold">{{ $div->name }} <span class="text-muted small">({{ $div->code }})</span></div>
                                <div class="small text-muted">
                                    @if($opRow['operation'])
                                        Outlet: {{ $opRow['outlet_name'] }} &bull; Dibuka: {{ $opRow['operation']->opened_at?->format('H:i') ?? '-' }}
                                    @else
                                        Belum ada sesi dibuka hari ini
                                    @endif
                                </div>
                            </div>
                            <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Baris Tambahan: 5 Stok Kritis Teratas & Catatan Waste Terbaru -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">5 Item Stok Kritis (&le; Minimum Stock)</h6>
                <a href="{{ route('manager.inventory.index', array_filter(['outlet_id' => $selectedOutletId])) }}" class="btn btn-sm btn-outline-primary">Lihat Semua Stok</a>
            </div>
            <div class="card-body">
                @if($lowStockItems->isEmpty())
                    <div class="text-muted small text-center py-3">Seluruh stok bahan baku berada di atas batas minimum.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bahan Baku</th>
                                    <th>Outlet / Divisi</th>
                                    <th class="text-end">Stok Saat Ini</th>
                                    <th class="text-end">Min. Stok</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockItems as $stock)
                                    <tr>
                                        <td class="fw-semibold">{{ $stock->item->name ?? '-' }}</td>
                                        <td>{{ $stock->outlet->name ?? '-' }} / {{ $stock->item->division->name ?? '-' }}</td>
                                        <td class="text-end text-danger fw-bold">{{ number_format((float) $stock->current_stock, 2, ',', '.') }} {{ strtoupper($stock->unit) }}</td>
                                        <td class="text-end">{{ number_format((float) ($stock->item->min_stock ?? 0), 2, ',', '.') }} {{ strtoupper($stock->unit) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Catatan Waste / Bahan Rusak Terbaru</h6>
                <a href="{{ route('manager.waste-records.index') }}" class="btn btn-sm btn-outline-secondary">Semua Waste</a>
            </div>
            <div class="card-body">
                @if($recentWaste->isEmpty())
                    <div class="text-muted small text-center py-3">Belum ada catatan waste bahan baku terbaru.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No Waste</th>
                                    <th>Tanggal</th>
                                    <th>Outlet / Divisi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentWaste as $waste)
                                    <tr>
                                        <td class="fw-semibold">{{ $waste->waste_number ?? '-' }}</td>
                                        <td>{{ $waste->waste_date?->format('d/m/Y') }}</td>
                                        <td>{{ $waste->outlet->name ?? '-' }} / {{ $waste->division->name ?? '-' }}</td>
                                        <td><span class="badge bg-secondary text-uppercase">{{ $waste->status }}</span></td>
                                        <td><a href="{{ route('manager.waste-records.show', $waste) }}" class="btn btn-sm btn-outline-primary py-0">Detail</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
