@extends('staff.layouts.app')

@section('content')
@php
    $isOpen = $operation?->status === 'open';
    $isSubmittedOrApproved = in_array($operation?->status, ['submitted', 'approved'], true);
    $totalAlerts = ($counts['wip'] ?? 0) + ($counts['raw'] ?? 0) + ($counts['packaging'] ?? 0);
    $queryParams = array_filter(['outlet_id' => $selectedOutletId ?? $outlet?->id, 'division_id' => $selectedDivisionId ?? $division?->id]);
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <span class="badge bg-primary text-uppercase mb-1">SIPASTOK Staff Panel</span>
        <h3 class="mb-1 fw-bold">Dashboard Operasional Harian &amp; Stok Opname</h3>
        <div class="text-muted small">Ikuti panduan 7 langkah di bawah ini secara berurutan dari buka shift hingga tutup shift.</div>
    </div>
</div>

@if($message)
    <div class="alert alert-warning">{{ $message }}</div>
@else
    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-md-5 col-6">
                        <label class="form-label small fw-semibold mb-1">Simulasi Outlet</label>
                        <select name="outlet_id" class="form-select form-select-sm">
                            @foreach($outlets as $o)
                                <option value="{{ $o->id }}" @selected(($selectedOutletId ?? $outlet?->id) == $o->id)>{{ $o->name }} ({{ $o->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 col-6">
                        <label class="form-label small fw-semibold mb-1">Simulasi Divisi</label>
                        <select name="division_id" class="form-select form-select-sm">
                            @foreach($divisions as $d)
                                <option value="{{ $d->id }}" @selected(($selectedDivisionId ?? $division?->id) == $d->id)>{{ $d->name }} ({{ $d->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-12">
                        <button class="btn btn-primary btn-sm w-100 fw-semibold">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Status Shift & Ringkasan Stok Kritis -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Outlet &amp; Divisi</div>
                    <h5 class="fw-bold mb-0 mt-1">{{ $outlet->name ?? '-' }}</h5>
                    <div class="badge bg-dark mt-1">{{ $division->name ?? '-' }} ({{ $division->code ?? '-' }})</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100 shadow-sm {{ $isOpen ? 'border-success' : ($isSubmittedOrApproved ? 'border-primary' : 'border-warning') }}">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Status Shift Hari Ini</div>
                    <h5 class="fw-bold mb-1 mt-1 text-uppercase {{ $isOpen ? 'text-success' : ($isSubmittedOrApproved ? 'text-primary' : 'text-warning') }}">
                        {{ $operation?->status ?? 'Belum Dibuka' }}
                    </h5>
                    <div class="small text-muted">{{ today()->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">WIP Perlu Produksi</div>
                    <h3 class="fw-bold mb-0 mt-1 {{ ($counts['wip'] ?? 0) > 0 ? 'text-warning' : 'text-success' }}">{{ $counts['wip'] }}</h3>
                    <div class="small text-muted">Stok WIP &le; batas minimum</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Bahan &amp; Packaging Kritis</div>
                    <h3 class="fw-bold mb-0 mt-1 {{ (($counts['raw'] ?? 0) + ($counts['packaging'] ?? 0)) > 0 ? 'text-danger' : 'text-success' }}">
                        {{ ($counts['raw'] ?? 0) + ($counts['packaging'] ?? 0) }}
                    </h3>
                    <div class="small text-muted">Raw: {{ $counts['raw'] }} &bull; Pack: {{ $counts['packaging'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panduan 7 Langkah Alur Operasional Harian (Step-by-Step Checklist) -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold">Alur Kerja Operasional Shift Harian (Step 1 s/d Step 7)</h5>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <!-- Langkah 1 -->
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge {{ $operation ? 'bg-success' : 'bg-primary' }} me-1">Langkah 1</span>
                        <strong class="fs-6">Buka Operasional Harian</strong>
                        <div class="small text-muted mt-1">Buka sesi shift hari ini agar form Take Bahan, Produksi, Waste, dan Stock Opname dapat digunakan.</div>
                    </div>
                    <div>
                        @if(! $operation)
                            <form method="post" action="{{ route('staff.daily-operations.open') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="outlet_id" value="{{ $selectedOutletId ?? $outlet?->id }}">
                                <input type="hidden" name="division_id" value="{{ $selectedDivisionId ?? $division?->id }}">
                                <button type="submit" class="btn btn-primary fw-semibold">Buka Shift Sekarang</button>
                            </form>
                        @else
                            <span class="badge bg-success px-3 py-2">&#10003; Sudah Dibuka ({{ $operation->opened_at?->format('H:i') }})</span>
                        @endif
                    </div>
                </div>

                <!-- Langkah 2 -->
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-secondary me-1">Langkah 2</span>
                        <strong class="fs-6">Cek Opening Alert (Stok Kritis Awal Shift)</strong>
                        <div class="small text-muted mt-1">Periksa daftar bahan baku dan WIP yang stoknya berada di bawah batas minimum ({{ $totalAlerts }} item kritis).</div>
                    </div>
                    <a href="{{ route('staff.opening-alerts.index', $queryParams) }}" class="btn btn-outline-primary btn-sm fw-semibold">
                        Cek Opening Alert ({{ $totalAlerts }}) &rarr;
                    </a>
                </div>

                <!-- Langkah 3 -->
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-secondary me-1">Langkah 3</span>
                        <strong class="fs-6">Take Bahan / Barang Datang (Restock Divisi)</strong>
                        <div class="small text-muted mt-1">Catat pengambilan bahan atau barang masuk ke divisi hari ini ({{ $shiftStats['material_requests'] ?? 0 }} dokumen tercatat).</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.material-requests.create', $queryParams) }}" class="btn btn-outline-primary btn-sm fw-semibold">+ Input Take Bahan</a>
                        <a href="{{ route('staff.material-requests.index', $queryParams) }}" class="btn btn-light btn-sm border">Riwayat</a>
                    </div>
                </div>

                <!-- Langkah 4 -->
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-secondary me-1">Langkah 4</span>
                        <strong class="fs-6">Input Produksi / WIP Harian</strong>
                        <div class="small text-muted mt-1">Catat hasil pembuatan bahan setengah jadi / WIP ({{ $shiftStats['productions'] ?? 0 }} dokumen tercatat).</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.production.create', $queryParams) }}" class="btn btn-outline-primary btn-sm fw-semibold">+ Input Produksi WIP</a>
                        <a href="{{ route('staff.production.index', $queryParams) }}" class="btn btn-light btn-sm border">Riwayat</a>
                    </div>
                </div>

                <!-- Langkah 5 -->
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-secondary me-1">Langkah 5</span>
                        <strong class="fs-6">Input Waste (Bahan Rusak / Terbuang)</strong>
                        <div class="small text-muted mt-1">Catat jika ada bahan baku atau WIP yang rusak/basi selama operasional ({{ $shiftStats['wastes'] ?? 0 }} dokumen tercatat).</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.waste.create', $queryParams) }}" class="btn btn-outline-danger btn-sm fw-semibold">+ Input Waste</a>
                        <a href="{{ route('staff.waste.index', $queryParams) }}" class="btn btn-light btn-sm border">Riwayat</a>
                    </div>
                </div>

                <!-- Langkah 6 -->
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge {{ ($shiftStats['opname_ready'] ?? false) ? 'bg-success' : 'bg-primary' }} me-1">Langkah 6 (Wajib)</span>
                        <strong class="fs-6">Closing Stock Opname (Mode Blind Count)</strong>
                        <div class="small text-muted mt-1">Hitung fisik sisa stok bahan baku di akhir shift dan klik Submit untuk dikirim ke Manager.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.stock-opnames.create', $queryParams) }}" class="btn btn-primary btn-sm fw-semibold">+ Input Stock Opname</a>
                        <a href="{{ route('staff.stock-opnames.index', $queryParams) }}" class="btn btn-light btn-sm border">Riwayat ({{ $shiftStats['stock_opnames'] ?? 0 }})</a>
                    </div>
                </div>

                <!-- Langkah 7 -->
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge {{ $isSubmittedOrApproved ? 'bg-success' : 'bg-dark' }} me-1">Langkah 7</span>
                        <strong class="fs-6">Tutup Operasional Harian (Closing Shift)</strong>
                        <div class="small text-muted mt-1">Periksa ringkasan penutupan shift dan kirim laporan operasional harian ke Manager.</div>
                    </div>
                    <a href="{{ route('staff.daily-operations.closing-summary', $queryParams) }}" class="btn btn-success fw-semibold">
                        Buka Closing Summary &amp; Tutup Shift &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
