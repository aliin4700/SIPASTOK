@extends('backend.v_layouts.app')

@section('content')
@php
    $iraPct = (float) ($cards['iraPercentage'] ?? 100);
    $iraColor = $iraPct >= 95 ? 'text-success' : ($iraPct >= 85 ? 'text-warning' : 'text-danger');
    $varianceVal = (float) ($cards['totalVarianceValue'] ?? 0);
    $opnameTypes = \App\Models\StockOpname::TYPES;
@endphp

<style>
    .sipastok-panel {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .05);
        padding: 20px;
        height: 100%;
    }
    .summary-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .05);
        padding: 18px;
        height: 100%;
    }
    .summary-label {
        color: #6c757d;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .summary-value {
        color: #212529;
        font-size: 24px;
        font-weight: 700;
        margin-top: 6px;
    }
    .chart-box {
        height: 270px;
        position: relative;
    }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h3 class="mb-1 fw-bold">Executive Dashboard SIPASTOK</h3>
        <p class="text-muted mb-0">
            Sistem Informasi Stok Opname &amp; Inventori Bahan Baku — Medja Cerita ({{ $cards['totalOutlets'] }} Outlet Aktif, Periode Evaluasi: <strong>{{ $evalMonthLabel }}</strong>)
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('backend.stock-opnames.index') }}" class="btn btn-primary btn-sm">
            Review Stock Opname
            @if($cards['pendingOpnamesCount'] > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $cards['pendingOpnamesCount'] }}</span>
            @endif
        </a>
        <a href="{{ route('backend.inventory-items.index') }}" class="btn btn-outline-secondary btn-sm">Master Bahan Baku</a>
    </div>
</div>

{{-- Kartu KPI Utama SIPASTOK --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl">
        <div class="summary-card border-primary">
            <div class="summary-label">Master Bahan Aktif</div>
            <div class="summary-value text-primary">{{ number_format($cards['totalItems'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Raw Material, WIP &amp; Packaging</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="summary-card border-warning">
            <div class="summary-label">Menunggu Review</div>
            <div class="summary-value text-warning">{{ number_format($cards['pendingOpnamesCount'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Total Reviewed: {{ number_format($cards['reviewedOpnamesCount'], 0, ',', '.') }} sesi</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="summary-card border-success">
            <div class="summary-label">Akurasi Stok (IRA {{ $evalMonthLabel }})</div>
            <div class="summary-value {{ $iraColor }}">{{ number_format($iraPct, 2, ',', '.') }}%</div>
            <div class="small text-muted mt-1">Target minimal &ge; 95,00%</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        @php
            $isDeficit = $varianceVal < -0.01;
            $isSurplus = $varianceVal > 0.01;
        @endphp
        <div class="summary-card {{ $isDeficit ? 'border-danger' : ($isSurplus ? 'border-success' : '') }}">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <div class="summary-label">Total Selisih Nilai ({{ $evalMonthLabel }})</div>
                <span class="badge {{ $isDeficit ? 'bg-danger' : ($isSurplus ? 'bg-success' : 'bg-secondary') }}">
                    {{ $isDeficit ? 'Selisih Minus' : ($isSurplus ? 'Surplus' : 'Balance') }}
                </span>
            </div>
            <div class="summary-value {{ $isDeficit ? 'text-danger' : ($isSurplus ? 'text-success' : 'text-dark') }}">
                Rp {{ number_format($varianceVal, 0, ',', '.') }}
            </div>
            <div class="small text-muted mt-1">Akumulasi opname reviewed</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl">
        <div class="summary-card border-danger">
            <div class="summary-label">Stok Kritis (&le; Min)</div>
            <div class="summary-value text-danger">{{ number_format($cards['lowStockCount'], 0, ',', '.') }}</div>
            <div class="small text-muted mt-1">Item perlu produksi / restock</div>
        </div>
    </div>
</div>

{{-- Grafik Analitik Inventori & Stok Opname --}}
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="sipastok-panel">
            <h5 class="mb-3 fw-bold">Status Sesi Stock Opname</h5>
            <div class="chart-box"><canvas id="opnameStatusChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sipastok-panel">
            <h5 class="mb-3 fw-bold">Jumlah Item per Divisi</h5>
            <div class="chart-box"><canvas id="itemsDivisionChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sipastok-panel">
            <h5 class="mb-3 fw-bold">Aktivitas Opname &amp; Mutasi (14 Hari)</h5>
            <div class="chart-box"><canvas id="activityTrendChart"></canvas></div>
        </div>
    </div>
</div>

{{-- Sesi Stock Opname Terbaru & Peringatan Stok Kritis --}}
<div class="row g-4">
    <div class="col-xl-7">
        <div class="sipastok-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Sesi Stock Opname Terbaru</h5>
                <a href="{{ route('backend.stock-opnames.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No Opname</th>
                            <th>Tanggal</th>
                            <th>Outlet / Divisi</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th class="text-end">Item</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestOpnames as $opname)
                            <tr>
                                <td class="fw-semibold">{{ $opname->opname_number ?? '-' }}</td>
                                <td>{{ $opname->opname_date?->format('d/m/Y') }}</td>
                                <td>{{ $opname->outlet->name ?? '-' }} / <strong>{{ $opname->division->name ?? '-' }}</strong></td>
                                <td>{{ $opnameTypes[$opname->opname_type] ?? $opname->opname_type }}</td>
                                <td>
                                    <span class="badge {{ $opname->status === 'reviewed' ? 'bg-success' : ($opname->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info') }} text-uppercase">
                                        {{ $opname->status }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $opname->items->count() }}</td>
                                <td>
                                    <a href="{{ route('backend.stock-opnames.show', $opname) }}" class="btn btn-sm {{ $opname->status === 'submitted' ? 'btn-warning' : 'btn-outline-primary' }}">
                                        {{ $opname->status === 'submitted' ? 'Review' : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Belum ada sesi stock opname.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="sipastok-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Peringatan Stok Kritis Outlet</h5>
                <a href="{{ route('backend.inventory-stocks.index') }}" class="btn btn-sm btn-outline-danger">Cek Stok</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bahan Baku</th>
                            <th>Outlet / Divisi</th>
                            <th class="text-end">Stok</th>
                            <th class="text-end">Min</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($criticalStocks as $stock)
                            <tr>
                                <td class="fw-semibold">{{ $stock->item->name ?? '-' }}</td>
                                <td>{{ $stock->outlet->name ?? '-' }} / {{ $stock->item->division->name ?? '-' }}</td>
                                <td class="text-end text-danger fw-bold">{{ number_format((float) $stock->current_stock, 2, ',', '.') }} {{ strtoupper($stock->unit) }}</td>
                                <td class="text-end">{{ number_format((float) ($stock->item->min_stock ?? 0), 2, ',', '.') }} {{ strtoupper($stock->unit) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Semua stok bahan baku berada di atas batas minimum.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const charts = @json($charts);
    const palette = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#0dcaf0'];

    const statusEl = document.getElementById('opnameStatusChart');
    if (statusEl) {
        new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: charts.opnameStatus.labels,
                datasets: [{
                    data: charts.opnameStatus.data,
                    backgroundColor: ['#198754', '#ffc107', '#0dcaf0', '#dc3545'],
                }],
            },
            options: { responsive: true, maintainAspectRatio: false },
        });
    }

    const divEl = document.getElementById('itemsDivisionChart');
    if (divEl) {
        new Chart(divEl, {
            type: 'bar',
            data: {
                labels: charts.itemsByDivision.labels,
                datasets: [{
                    label: 'Item Aktif',
                    data: charts.itemsByDivision.data,
                    backgroundColor: palette,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
            },
        });
    }

    const trendEl = document.getElementById('activityTrendChart');
    if (trendEl) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: charts.activityTrend.labels,
                datasets: [
                    {
                        label: 'Sesi Opname',
                        data: charts.activityTrend.opnames,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.12)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: 'Mutasi Stok',
                        data: charts.activityTrend.movements,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.08)',
                        tension: 0.35,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
            },
        });
    }
</script>
@endsection
