@extends('staff.layouts.app')

@section('content')
@php
    $totalItems = $stockOpname->items->count();
    $matchedItems = $stockOpname->items->filter(fn ($item) => abs((float) $item->difference_qty) < 0.0001)->count();
    $varianceItems = $totalItems - $matchedItems;
    $negativeItems = $stockOpname->items->filter(fn ($item) => (float) $item->difference_qty < -0.0001)->count();
    $totalVarianceValue = (float) ($stockOpname->total_variance_value ?? $stockOpname->items->sum('variance_value'));
    $opnameTypeLabel = \App\Models\StockOpname::TYPES[$stockOpname->opname_type] ?? $stockOpname->opname_type;
    $varianceReasons = \App\Models\StockOpnameItem::VARIANCE_REASONS;
@endphp

<style>
    .row-variance-minus {
        background-color: #fff5f5 !important;
    }
    .row-variance-plus {
        background-color: #f3faf6 !important;
    }
    @media (max-width: 767.98px) {
        .sipastok-detail-table thead {
            display: none;
        }
        .sipastok-detail-table,
        .sipastok-detail-table tbody {
            display: block;
            width: 100%;
        }
        .sipastok-detail-table tr.detail-item-row {
            display: block;
            border: 1px solid #dee2e6;
            border-radius: 0.75rem;
            padding: 0.9rem;
            margin-bottom: 0.75rem;
            background: #fff;
        }
        .sipastok-detail-table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none !important;
            padding: 0.25rem 0 !important;
            text-align: right !important;
        }
        .sipastok-detail-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            font-size: 0.82rem;
            text-align: left;
            margin-right: 0.75rem;
        }
        .sipastok-detail-table td.item-title-cell {
            display: block;
            text-align: left !important;
            border-bottom: 1px solid #eee !important;
            padding-bottom: 0.5rem !important;
            margin-bottom: 0.35rem;
        }
        .sipastok-detail-table td.item-title-cell::before {
            display: none;
        }
    }
    @media print {
        nav.navbar, .no-print {
            display: none !important;
        }
        body {
            background: #fff !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        .print-only {
            display: block !important;
        }
    }
    .print-only {
        display: none;
    }
</style>

<div class="print-only mb-3 text-center">
    <h4 class="mb-0">BERITA ACARA STOCK OPNAME BAHAN BAKU (SIPASTOK)</h4>
    <div class="text-muted">Medja Cerita - {{ $stockOpname->outlet->name ?? '-' }} ({{ $stockOpname->division->name ?? '-' }})</div>
    <hr>
</div>

<x-sipastok-status-stepper :status="$stockOpname->status" role="staff" />

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-primary h-100 shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Total Item Dihitung</div>
                <h4 class="mb-0 fw-bold text-primary">{{ $totalItems }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-success h-100 shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Item Sesuai (0 Selisih)</div>
                <h4 class="mb-0 fw-bold text-success">{{ $matchedItems }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-warning h-100 shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Item Selisih</div>
                <h4 class="mb-0 fw-bold text-warning">{{ $varianceItems }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-danger h-100 shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Total Nilai Selisih (Rp)</div>
                <h4 class="mb-0 fw-bold {{ $totalVarianceValue < 0 ? 'text-danger' : 'text-dark' }}">
                    Rp {{ number_format($totalVarianceValue, 2, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">{{ $stockOpname->opname_number ?? 'Stock Opname' }}</h4>
                <div class="text-muted">
                    {{ $stockOpname->opname_date?->format('d/m/Y') }} &bull; {{ $opnameTypeLabel }}
                    @if($stockOpname->is_blind_count)
                        <span class="badge bg-secondary ms-1">Blind Count</span>
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $stockOpname->status === 'reviewed' ? 'bg-success' : ($stockOpname->status === 'submitted' ? 'bg-warning text-dark' : ($stockOpname->status === 'cancelled' ? 'bg-danger' : 'bg-info')) }} text-uppercase fs-6">
                    {{ $stockOpname->status }}
                </span>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark no-print">Cetak Berita Acara</button>
            </div>
        </div>
        <hr>
        <div class="row g-2">
            <div class="col-md-6">
                <div><strong>Outlet:</strong> {{ $stockOpname->outlet->name ?? '-' }}</div>
                <div><strong>Divisi:</strong> {{ $stockOpname->division->name ?? '-' }}</div>
                <div><strong>Jenis Opname:</strong> {{ $opnameTypeLabel }}</div>
            </div>
            <div class="col-md-6">
                <div><strong>Dihitung Oleh:</strong> {{ $stockOpname->counter->name ?? '-' }}</div>
                <div><strong>Direview Oleh:</strong> {{ $stockOpname->reviewer->name ?? '-' }}</div>
                <div><strong>Catatan Opname:</strong> {{ $stockOpname->notes ?? '-' }}</div>
                @if($stockOpname->review_notes)
                    <div><strong>Catatan Review:</strong> {{ $stockOpname->review_notes }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h5 class="mb-0 fw-bold">Rincian Item Opname</h5>
            <div class="d-flex flex-wrap gap-2 no-print" id="auditFilterButtons">
                <button type="button" class="btn btn-sm btn-primary active" data-audit-filter="all">
                    Semua Item ({{ $totalItems }})
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" data-audit-filter="variance">
                    Hanya Selisih (Variance: {{ $varianceItems }})
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-audit-filter="negative">
                    Selisih Minus / Rugi ({{ $negativeItems }})
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle sipastok-detail-table">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Tipe</th>
                        <th class="text-end">Stok Sistem</th>
                        <th class="text-end">Stok Fisik</th>
                        <th class="text-end">Selisih</th>
                        <th>Unit</th>
                        <th class="text-end">Harga Satuan (Rp)</th>
                        <th class="text-end">Nilai Selisih (Rp)</th>
                        <th>Alasan Selisih</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockOpname->items as $item)
                        @php
                            $diff = (float) $item->difference_qty;
                            $isZero = abs($diff) < 0.0001;
                            $rowClass = $isZero ? '' : ($diff < 0 ? 'row-variance-minus' : 'row-variance-plus');
                        @endphp
                        <tr class="detail-item-row {{ $rowClass }}" data-diff="{{ $diff }}">
                            <td class="item-title-cell">
                                <div class="fw-bold">{{ $item->item->name ?? '-' }}</div>
                                @if($item->item?->code)
                                    <small class="text-muted">{{ $item->item->code }}</small>
                                @endif
                            </td>
                            <td data-label="Tipe"><span class="badge bg-secondary text-uppercase">{{ str_replace('_', ' ', $item->item->item_type ?? '-') }}</span></td>
                            <td data-label="Stok Sistem" class="text-end">{{ number_format((float) $item->system_stock, 2, ',', '.') }}</td>
                            <td data-label="Stok Fisik" class="text-end fw-bold">{{ number_format((float) $item->physical_stock, 2, ',', '.') }}</td>
                            <td data-label="Selisih" class="text-end">
                                @if($isZero)
                                    <span class="badge bg-success">Sesuai (0,00)</span>
                                @elseif($diff < 0)
                                    <span class="badge bg-danger">{{ number_format($diff, 2, ',', '.') }}</span>
                                @else
                                    <span class="badge bg-success">+{{ number_format($diff, 2, ',', '.') }}</span>
                                @endif
                            </td>
                            <td data-label="Satuan">{{ strtoupper($item->unit) }}</td>
                            <td data-label="Harga Satuan" class="text-end">Rp {{ number_format((float) $item->unit_cost, 2, ',', '.') }}</td>
                            <td data-label="Nilai Selisih (Rp)" class="text-end {{ (float) $item->variance_value < 0 ? 'text-danger fw-bold' : '' }}">
                                Rp {{ number_format((float) $item->variance_value, 2, ',', '.') }}
                            </td>
                            <td data-label="Alasan Selisih">{{ $varianceReasons[$item->variance_reason] ?? ($item->variance_reason ?: '-') }}</td>
                            <td data-label="Catatan">{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="row mt-5 pt-3 text-center print-only">
            <div class="col-6">
                <p class="mb-5"><strong>Dihitung Oleh (Staff)</strong></p>
                <div class="mt-4 border-top d-inline-block px-4 pt-1">
                    {{ $stockOpname->counter->name ?? '( ............................ )' }}
                </div>
            </div>
            <div class="col-6">
                <p class="mb-5"><strong>Diperiksa & Disetujui Oleh (Manager/Admin)</strong></p>
                <div class="mt-4 border-top d-inline-block px-4 pt-1">
                    {{ $stockOpname->reviewer->name ?? '( ............................ )' }}
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 no-print">
            @if($stockOpname->status === 'draft')
                <form method="post" action="{{ route('staff.stock-opnames.submit', $stockOpname) }}" onsubmit="return confirm('Kirim hasil perhitungan fisik ini ke Manager/Admin untuk direview?');">
                    @csrf
                    <button class="btn btn-success btn-lg fw-semibold">Submit untuk Review</button>
                </form>
            @endif
            @if(in_array($stockOpname->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('staff.stock-opnames.cancel', $stockOpname) }}" onsubmit="return confirm('Batalkan sesi Stock Opname ini?');">
                    @csrf
                    <button class="btn btn-outline-danger">Batalkan Sesi</button>
                </form>
            @endif
            <button type="button" onclick="window.print()" class="btn btn-outline-dark">Cetak Berita Acara</button>
            <a href="{{ route('staff.stock-opnames.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('#auditFilterButtons [data-audit-filter]');
    const rows = document.querySelectorAll('.detail-item-row');

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const mode = btn.getAttribute('data-audit-filter');
            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');

            rows.forEach(function (row) {
                const diff = parseFloat(row.getAttribute('data-diff') || '0');
                let show = true;
                if (mode === 'variance') {
                    show = Math.abs(diff) >= 0.0001;
                } else if (mode === 'negative') {
                    show = diff < -0.0001;
                }
                row.style.display = show ? '' : 'none';
            });
        });
    });
});
</script>
@endpush
