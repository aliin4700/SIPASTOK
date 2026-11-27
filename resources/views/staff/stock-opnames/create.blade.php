@extends('staff.layouts.app')

@section('content')
@include('partials.searchable-select-assets')

@php
    $opnameTypes = \App\Models\StockOpname::TYPES;
    $varianceReasons = \App\Models\StockOpnameItem::VARIANCE_REASONS;
    $defaultBlindCount = old('is_blind_count', '1') == '1';
@endphp

<style>
    .sipastok-sticky-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8f9fa;
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
    .filter-pill-btn {
        border-radius: 999px;
        font-size: 0.85rem;
        padding: 0.35rem 0.9rem;
    }
    .sipastok-sticky-bottom {
        position: sticky;
        bottom: 0;
        z-index: 20;
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(6px);
        border-top: 2px solid #0d6efd;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
        padding: 0.85rem 1rem;
        margin-top: 1rem;
        border-radius: 0.6rem;
    }
    .qty-stepper-group .btn {
        min-width: 38px;
        font-weight: 700;
    }

    @media (max-width: 767.98px) {
        .sipastok-responsive-table thead {
            display: none;
        }
        .sipastok-responsive-table,
        .sipastok-responsive-table tbody {
            display: block;
            width: 100%;
        }
        .sipastok-responsive-table tr.opname-item-row {
            display: block;
            background: #fff;
            border: 1px solid #cfe2ff;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 0.85rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }
        .sipastok-responsive-table tr.opname-item-row.is-filled {
            border-color: #198754;
            background: #f8fffb;
        }
        .sipastok-responsive-table td {
            display: block;
            border: none !important;
            padding: 0.25rem 0 !important;
            text-align: left !important;
        }
        .mobile-reason-panel {
            display: none;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #dee2e6;
        }
        .mobile-reason-panel.is-open {
            display: block;
        }
    }
</style>

<x-sipastok-status-stepper
    status="draft"
    role="staff"
    customGuide="Hitung jumlah fisik bahan baku yang ada di rak/dapur saat ini dan ketik angkanya pada kolom Physical Stock. Anda cukup mengisi bahan yang dihitung, lalu tekan tombol Simpan Draft Opname di bagian bawah layar."
/>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1 fw-bold">Input Stock Opname Bahan Baku</h4>
                <div class="text-muted small">Pencatatan stok fisik harian & audit berkala Medja Cerita (SIPASTOK).</div>
            </div>
            <a href="{{ route('staff.stock-opnames.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-outline-secondary btn-sm">
                &larr; Daftar Riwayat Opname
            </a>
        </div>

        @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
            <form method="get" class="row g-2 mb-3 p-3 bg-light rounded border">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Pilih Outlet</label>
                    <select name="outlet_id" class="form-select form-select-sm">
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Pilih Divisi</label>
                    <select name="division_id" class="form-select form-select-sm">
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary btn-sm w-100">Ganti Outlet / Divisi</button>
                </div>
            </form>
        @endif

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @elseif(! $operation || $operation->status !== 'open')
            <div class="card border-warning bg-light my-3">
                <div class="card-body text-center py-5">
                    <div class="mb-2">
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">Operasional Harian Belum Dibuka</span>
                    </div>
                    <h5 class="fw-bold mt-2">Buka Operasional Hari Ini Terlebih Dahulu</h5>
                    <p class="text-muted mx-auto mb-4" style="max-width: 520px;">
                        Sebelum mencatat Stock Opname, sesi operasional divisi untuk hari ini ({{ today()->format('d/m/Y') }}) harus berstatus <strong>OPEN</strong>.
                    </p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <form method="post" action="{{ route('staff.daily-operations.open') }}">
                            @csrf
                            <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                            <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                Buka Operasional Hari Ini Sekarang
                            </button>
                        </form>
                        <a href="{{ route('staff.daily-operations.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-outline-secondary btn-lg">
                            Ke Halaman Operasional
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    Outlet: <strong>{{ $operation->outlet->name ?? '-' }}</strong> &bull;
                    Divisi: <strong>{{ $operation->division->name ?? '-' }}</strong> &bull;
                    Status Operasional: <span class="badge bg-success text-uppercase">{{ $operation->status }}</span>
                </div>
                <span class="badge bg-dark">Tanggal Opname: {{ today()->format('d/m/Y') }}</span>
            </div>

            <form method="post" action="{{ route('staff.stock-opnames.store') }}" id="stockOpnameForm">
                @csrf
                <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">

                <div class="row g-3 mb-3 p-3 bg-light border rounded">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold mb-1">Jenis Pelaksanaan Stock Opname</label>
                        <select name="opname_type" class="form-select">
                            @foreach($opnameTypes as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" @selected(old('opname_type', 'daily_closing') === $typeKey)>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 d-flex align-items-center">
                        <div class="form-check form-switch mt-2">
                            <input type="hidden" name="is_blind_count" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="blindCountToggle" name="is_blind_count" value="1" @checked($defaultBlindCount)>
                            <label class="form-check-label fw-semibold" for="blindCountToggle">
                                Mode Blind Count (Sembunyikan Stok Sistem Saat Menghitung)
                            </label>
                            <div class="text-muted small">Saat aktif, angka stok komputer disembunyikan agar petugas menghitung jumlah fisik apa adanya.</div>
                        </div>
                    </div>
                </div>

                <!-- Live Search & Quick Filter Bar (Client-Side JS, tanpa reload halaman) -->
                <div class="card border mb-3 bg-white">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-semibold">Cari</span>
                                    <input type="text" id="liveSearchInput" class="form-control" placeholder="Ketik nama bahan atau kode..." autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary" id="clearSearchBtn">Reset</button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select id="quickJumpOpnameSelect" class="form-select sipastok-searchable-select">
                                    <option value="">-- Lompat Cepat ke Item --</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-search="{{ strtolower($item->name . ' ' . ($item->code ?? '')) }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 text-md-end small text-muted">
                                Tampil <strong id="visibleItemCount">{{ $items->count() }}</strong> / <strong>{{ $items->count() }}</strong> item
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex flex-wrap gap-2" id="quickFilterPills">
                                <button type="button" class="btn btn-primary filter-pill-btn active" data-filter="all">Semua ({{ $items->count() }})</button>
                                <button type="button" class="btn btn-outline-secondary filter-pill-btn" data-filter="unfilled">Belum Diisi</button>
                                <button type="button" class="btn btn-outline-success filter-pill-btn" data-filter="filled">Sudah Diisi</button>
                                <button type="button" class="btn btn-outline-dark filter-pill-btn" data-filter="raw_material">Raw Material</button>
                                <button type="button" class="btn btn-outline-info filter-pill-btn" data-filter="wip">WIP</button>
                            </div>
                            <button type="button" id="resetAllOpnameInputsBtn" class="btn btn-sm btn-outline-danger">
                                Reset / Kosongkan Form
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle sipastok-sticky-table sipastok-responsive-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Bahan & Kode</th>
                                <th>Kategori</th>
                                <th class="blind-col text-end" @if($defaultBlindCount) style="display:none;" @endif>Stok Sistem</th>
                                <th style="width: 220px;">Stok Fisik Aktual</th>
                                <th class="blind-col text-end" @if($defaultBlindCount) style="display:none;" @endif>Selisih</th>
                                <th>Satuan</th>
                                <th style="width: 220px;">Alasan Selisih (Opsional)</th>
                                <th>Catatan Item</th>
                            </tr>
                        </thead>
                        <tbody id="opnameItemsTbody">
                            @forelse($items as $i => $item)
                                @php
                                    $stock = $stocks->get($item->id);
                                    $sysStock = (float) ($stock?->current_stock ?? 0);
                                    $unitLabel = strtoupper($stock?->unit ?? $item->base_unit);
                                    $oldPhys = old("items.$i.physical_stock");
                                    $oldReason = old("items.$i.variance_reason");
                                    $oldNotes = old("items.$i.notes");
                                    $hasExtra = !empty($oldReason) || !empty($oldNotes);
                                @endphp
                                <tr class="opname-item-row {{ $oldPhys !== null && $oldPhys !== '' ? 'is-filled' : '' }}"
                                    data-item-id="{{ $item->id }}"
                                    data-system-stock="{{ $sysStock }}"
                                    data-name="{{ strtolower($item->name . ' ' . ($item->code ?? '')) }}"
                                    data-type="{{ strtolower($item->item_type) }}">
                                    <td>
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="fw-bold fs-6">{{ $item->name }}</div>
                                                <div class="text-muted small">
                                                    Kode: <span class="fw-semibold">{{ $item->code ?? '-' }}</span>
                                                </div>
                                            </div>
                                            <span class="badge bg-primary fs-6 d-md-none">{{ $unitLabel }}</span>
                                        </div>
                                        <input type="hidden" name="items[{{ $i }}][inventory_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <span class="badge {{ $item->item_type === 'wip' ? 'bg-info text-dark' : 'bg-secondary' }} text-uppercase">
                                            {{ str_replace('_', ' ', $item->item_type) }}
                                        </span>
                                    </td>
                                    <td class="blind-col text-end fw-semibold" @if($defaultBlindCount) style="display:none;" @endif>
                                        <span class="d-md-none text-muted small me-1">Stok Sistem:</span>
                                        {{ number_format($sysStock, 2, ',', '.') }} {{ $unitLabel }}
                                    </td>
                                    <td>
                                        <label class="form-label small fw-semibold text-primary d-md-none mb-1">
                                            Jumlah Fisik Aktual ({{ $unitLabel }}):
                                        </label>
                                        <div class="input-group qty-stepper-group">
                                            <button type="button" class="btn btn-outline-secondary qty-step-btn" data-step="-1" tabindex="-1">&minus;</button>
                                            <input type="number"
                                                   step="any"
                                                   min="0"
                                                   inputmode="decimal"
                                                   name="items[{{ $i }}][physical_stock]"
                                                   value="{{ $oldPhys }}"
                                                   class="form-control text-center fw-bold physical-stock-input"
                                                   placeholder="Ketik stok...">
                                            <button type="button" class="btn btn-outline-secondary qty-step-btn" data-step="1" tabindex="-1">&plus;</button>
                                            <button type="button" class="btn btn-outline-danger clear-opname-row-btn" title="Kosongkan baris ini" tabindex="-1">&times;</button>
                                        </div>
                                    </td>
                                    <td class="blind-col text-end diff-preview text-muted" @if($defaultBlindCount) style="display:none;" @endif>
                                        -
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge bg-light text-dark border">{{ $unitLabel }}</span>
                                    </td>
                                    <td colspan="2" class="d-md-none">
                                        <button type="button" class="btn btn-sm btn-link text-decoration-none px-0 toggle-mobile-extra">
                                            + Isi Alasan Selisih / Catatan Item
                                        </button>
                                    </td>
                                    <td class="mobile-reason-panel {{ $hasExtra ? 'is-open' : '' }}">
                                        <label class="form-label small text-muted d-md-none mb-1">Alasan Selisih:</label>
                                        <select name="items[{{ $i }}][variance_reason]" class="form-select form-select-sm">
                                            <option value="">-- Pilih Jika Selisih --</option>
                                            @foreach($varianceReasons as $reasonKey => $reasonLabel)
                                                <option value="{{ $reasonKey }}" @selected($oldReason === $reasonKey)>{{ $reasonLabel }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="mobile-reason-panel {{ $hasExtra ? 'is-open' : '' }}">
                                        <label class="form-label small text-muted d-md-none mb-1">Catatan Item:</label>
                                        <input type="text" name="items[{{ $i }}][notes]" value="{{ $oldNotes }}" class="form-control form-control-sm" placeholder="Catatan kondisi bahan...">
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada item aktif untuk divisi ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="form-group mb-3 mt-3">
                    <label class="form-label fw-semibold">Catatan Umum Sesi Opname (Opsional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Opname closing shift malam, kondisi chiller normal...">{{ old('notes') }}</textarea>
                </div>

                <!-- Sticky Bottom Bar -->
                <div class="sipastok-sticky-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-bold text-dark">
                            Progres Input: <span class="text-primary" id="filledCountText">0</span> / <span id="totalCountText">{{ $items->count() }}</span> Item Terisi
                        </div>
                        <div class="small text-muted">Item yang dikosongkan tidak akan disimpan ke dalam draft opname.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('staff.stock-opnames.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-outline-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-4 fw-semibold">
                            Simpan Draft Opname
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const blindToggle = document.getElementById('blindCountToggle');
    const blindCols = document.querySelectorAll('.blind-col');
    const rows = Array.from(document.querySelectorAll('.opname-item-row'));
    const searchInput = document.getElementById('liveSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const filterButtons = document.querySelectorAll('#quickFilterPills [data-filter]');
    const filledCountText = document.getElementById('filledCountText');
    const visibleItemCount = document.getElementById('visibleItemCount');

    let currentFilter = 'all';

    function updateBlindVisibility() {
        if (!blindToggle) return;
        const hide = blindToggle.checked;
        blindCols.forEach(function (col) {
            col.style.display = hide ? 'none' : '';
        });
    }

    function isRowFilled(row) {
        const input = row.querySelector('.physical-stock-input');
        return input && input.value.trim() !== '' && !isNaN(parseFloat(input.value));
    }

    function updateRowState(row) {
        const input = row.querySelector('.physical-stock-input');
        const diffCell = row.querySelector('.diff-preview');
        const filled = isRowFilled(row);

        row.classList.toggle('is-filled', filled);

        if (!diffCell || !input) return;

        if (!filled) {
            diffCell.textContent = '-';
            diffCell.className = 'blind-col text-end diff-preview text-muted';
            return;
        }

        const sysStock = parseFloat(row.getAttribute('data-system-stock') || '0');
        const physStock = parseFloat(input.value);
        const diff = physStock - sysStock;
        const formatted = (diff > 0 ? '+' : '') + diff.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        diffCell.textContent = formatted;

        if (Math.abs(diff) < 0.0001) {
            diffCell.className = 'blind-col text-end diff-preview text-success fw-semibold';
        } else if (diff < 0) {
            diffCell.className = 'blind-col text-end diff-preview text-danger fw-semibold';
        } else {
            diffCell.className = 'blind-col text-end diff-preview text-warning fw-semibold';
        }
    }

    function updateFilledCounter() {
        let filledCount = 0;
        rows.forEach(function (row) {
            if (isRowFilled(row)) filledCount++;
        });
        if (filledCountText) {
            filledCountText.textContent = String(filledCount);
        }
    }

    function applyFilters() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const nameCode = row.getAttribute('data-name') || '';
            const itemType = row.getAttribute('data-type') || '';
            const filled = isRowFilled(row);

            let matchesSearch = query === '' || nameCode.indexOf(query) !== -1;
            let matchesPill = true;

            if (currentFilter === 'filled') {
                matchesPill = filled;
            } else if (currentFilter === 'unfilled') {
                matchesPill = !filled;
            } else if (currentFilter === 'raw_material') {
                matchesPill = itemType === 'raw_material';
            } else if (currentFilter === 'wip') {
                matchesPill = itemType === 'wip';
            }

            const show = matchesSearch && matchesPill;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (visibleItemCount) {
            visibleItemCount.textContent = String(visible);
        }
    }

    rows.forEach(function (row) {
        const input = row.querySelector('.physical-stock-input');
        if (input) {
            input.addEventListener('input', function () {
                updateRowState(row);
                updateFilledCounter();
            });
        }

        row.querySelectorAll('.qty-step-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!input) return;
                const step = parseFloat(btn.getAttribute('data-step') || '1');
                const current = input.value.trim() === '' ? 0 : parseFloat(input.value);
                const nextVal = Math.max(0, (isNaN(current) ? 0 : current) + step);
                input.value = String(Math.round(nextVal * 100) / 100);
                input.dispatchEvent(new Event('input'));
            });
        });

        const clearRowBtn = row.querySelector('.clear-opname-row-btn');
        if (clearRowBtn) {
            clearRowBtn.addEventListener('click', function () {
                if (input) input.value = '';
                row.querySelectorAll('select, input[type="text"]').forEach(function (el) { el.value = ''; });
                updateRowState(row);
                updateFilledCounter();
            });
        }

        const toggleBtn = row.querySelector('.toggle-mobile-extra');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                row.querySelectorAll('.mobile-reason-panel').forEach(function (panel) {
                    panel.classList.toggle('is-open');
                });
            });
        }

        updateRowState(row);
    });

    if (blindToggle) {
        blindToggle.addEventListener('change', updateBlindVisibility);
        updateBlindVisibility();
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            applyFilters();
        });
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(function (b) {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('active', 'btn-primary');
            currentFilter = btn.getAttribute('data-filter') || 'all';
            applyFilters();
        });
    });

    const resetAllBtn = document.getElementById('resetAllOpnameInputsBtn');
    if (resetAllBtn) {
        resetAllBtn.addEventListener('click', function () {
            rows.forEach(function (row) {
                const input = row.querySelector('.physical-stock-input');
                if (input) input.value = '';
                row.querySelectorAll('select, input[type="text"]').forEach(function (el) { el.value = ''; });
                updateRowState(row);
            });
            updateFilledCounter();
            applyFilters();
        });
    }

    const quickJumpSelect = document.getElementById('quickJumpOpnameSelect');
    if (quickJumpSelect) {
        quickJumpSelect.addEventListener('change', function () {
            const targetId = quickJumpSelect.value;
            if (!targetId) return;
            const targetRow = rows.find(function (r) { return r.getAttribute('data-item-id') === String(targetId); });
            if (targetRow) {
                targetRow.style.display = '';
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                const inp = targetRow.querySelector('.physical-stock-input');
                if (inp) inp.focus();
            }
        });
    }

    updateFilledCounter();
});
</script>
@endpush
