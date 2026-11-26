@extends('staff.layouts.app')

@section('content')
@php
    $opnameTypes = \App\Models\StockOpname::TYPES;
@endphp

<x-sipastok-status-stepper
    status="draft"
    role="staff"
    customGuide="Di halaman ini Anda dapat melihat riwayat Stock Opname divisi Anda atau memulai pencatatan stok fisik baru dengan menekan tombol '+ Input Stock Opname Baru'."
/>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">Riwayat Stock Opname (SIPASTOK)</h4>
                <div class="text-muted small">Catatan perhitungan stok fisik bahan baku & WIP per outlet dan divisi.</div>
            </div>
            @unless($message)
                <a href="{{ route('staff.stock-opnames.create', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary btn-lg fw-semibold">
                    + Input Stock Opname Baru
                </a>
            @endunless
        </div>

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @else
            @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form method="get" class="row g-2 mb-3 p-3 bg-light rounded border">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Outlet</label>
                        <select name="outlet_id" class="form-select form-select-sm">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold mb-1">Divisi</label>
                        <select name="division_id" class="form-select form-select-sm">
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm w-100">Tampilkan</button>
                    </div>
                </form>
            @endif

            <!-- Tampilan Tabel Desktop (md ke atas) -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No Opname</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Outlet / Divisi</th>
                            <th>Status</th>
                            <th class="text-end">Total Item</th>
                            <th class="text-end">Nilai Selisih (Rp)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($opnames as $opname)
                            @php
                                $varVal = (float) ($opname->total_variance_value ?? $opname->items->sum('variance_value'));
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $opname->opname_number ?? '-' }}</td>
                                <td>{{ $opname->opname_date?->format('d/m/Y') }}</td>
                                <td>{{ $opnameTypes[$opname->opname_type] ?? $opname->opname_type }}</td>
                                <td>{{ $opname->outlet->name ?? '-' }} / {{ $opname->division->name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $opname->status === 'reviewed' ? 'bg-success' : ($opname->status === 'submitted' ? 'bg-warning text-dark' : ($opname->status === 'cancelled' ? 'bg-danger' : 'bg-info')) }} text-uppercase">
                                        {{ $opname->status }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $opname->items->count() }}</td>
                                <td class="text-end {{ $varVal < 0 ? 'text-danger fw-semibold' : '' }}">
                                    Rp {{ number_format($varVal, 2, ',', '.') }}
                                </td>
                                <td>
                                    <a href="{{ route('staff.stock-opnames.show', $opname) }}" class="btn btn-sm btn-outline-primary">
                                        Buka Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada stock opname.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Tampilan Card Mobile (< md) -->
            <div class="d-md-none">
                @forelse($opnames as $opname)
                    @php
                        $varVal = (float) ($opname->total_variance_value ?? $opname->items->sum('variance_value'));
                    @endphp
                    <div class="card border mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold fs-6">{{ $opname->opname_number ?? 'Draft Opname' }}</div>
                                    <div class="text-muted small">{{ $opname->opname_date?->format('d/m/Y') }} &bull; {{ $opnameTypes[$opname->opname_type] ?? $opname->opname_type }}</div>
                                </div>
                                <span class="badge {{ $opname->status === 'reviewed' ? 'bg-success' : ($opname->status === 'submitted' ? 'bg-warning text-dark' : ($opname->status === 'cancelled' ? 'bg-danger' : 'bg-info')) }} text-uppercase">
                                    {{ $opname->status }}
                                </span>
                            </div>
                            <div class="small mb-2">
                                <div><strong>Outlet / Divisi:</strong> {{ $opname->outlet->name ?? '-' }} / {{ $opname->division->name ?? '-' }}</div>
                                <div><strong>Total Item:</strong> {{ $opname->items->count() }} item</div>
                                <div><strong>Nilai Selisih:</strong> <span class="{{ $varVal < 0 ? 'text-danger fw-bold' : 'fw-semibold' }}">Rp {{ number_format($varVal, 2, ',', '.') }}</span></div>
                            </div>
                            <a href="{{ route('staff.stock-opnames.show', $opname) }}" class="btn btn-primary w-100 fw-semibold">
                                Buka Detail Opname
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 border rounded bg-light">Belum ada stock opname.</div>
                @endforelse
            </div>

            {{ $opnames->links() }}
        @endif
    </div>
</div>
@endsection
