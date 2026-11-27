@extends($layout)

@section('content')
@include('partials.searchable-select-assets')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Preview &amp; Mapping Impor Pemakaian ESB</h4>
        <small class="text-muted">
            Outlet: <strong>{{ $outlet->name }}</strong> &middot;
            Tanggal Laporan: <strong>{{ \Carbon\Carbon::parse($reportDate)->format('d/m/Y') }}</strong>
            @if($reportPeriod)
                &middot; Periode File: <strong>{{ $reportPeriod }}</strong>
            @endif
        </small>
    </div>
    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Kembali / Unggah Ulang</a>
</div>

@if($duplicateImport)
    <div class="alert alert-warning border-warning">
        <strong>Peringatan Duplikasi:</strong> File <code>{{ $fileName }}</code> untuk outlet <strong>{{ $outlet->name }}</strong> pada tanggal <strong>{{ \Carbon\Carbon::parse($reportDate)->format('d/m/Y') }}</strong> sudah pernah diimpor sebelumnya (Batch #{{ $duplicateImport->id }} pada {{ $duplicateImport->created_at?->format('d/m/Y H:i') }}). Eksekusi pemotongan stok dikunci untuk mencegah pemotongan ganda.
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Baris ESB (Qty &gt; 0)</div>
                <div class="fs-3 fw-bold">{{ number_format($totalRows) }}</div>
                <div class="small text-muted text-truncate" title="{{ $fileName }}">{{ $fileName }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small">Item Otomatis Cocok</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($matchedCount) }}</div>
                <div class="small text-muted">Siap dipotong via mutasi <code>esb_sales_out</code></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
            <div class="card-body">
                <div class="text-muted small">Item Belum Cocok / Belum Terdaftar</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($unmatchedCount) }}</div>
                <div class="small text-muted">Petakan manual di bawah atau biarkan kosong untuk dilewati.</div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route($routePrefix . '.execute') }}" method="POST" onsubmit="return confirm('Lanjutkan pemotongan stok outlet {{ $outlet->name }} berdasarkan daftar item yang dipilih?');">
    @csrf
    <input type="hidden" name="preview_token" value="{{ $previewToken }}">

    @if(count($unmatchedRows) > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning-subtle py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Item Belum Cocok ({{ count($unmatchedRows) }})</h6>
                    <small class="text-muted">Pilih master item SIPASTOK yang sesuai untuk memetakan manual, atau biarkan <em>Lewati (Skip)</em>.</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Baris</th>
                                <th>Produk ESB</th>
                                <th>Kategori ESB</th>
                                <th class="text-end">Qty Pemakaian</th>
                                <th style="min-width: 320px;">Mapping ke Item SIPASTOK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unmatchedRows as $row)
                                @php
                                    $rowUnitCost = ((float) $row['qty'] > 0 && (float) ($row['amount'] ?? 0) > 0)
                                        ? round((float) $row['amount'] / (float) $row['qty'], 4)
                                        : 0;
                                @endphp
                                <tr data-unmatched-row="{{ $row['index'] }}">
                                    <td class="text-muted small">#{{ $row['row_number'] }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $row['product_name'] }}</div>
                                        @if($row['product_code'] !== '')
                                            <small class="text-muted">Kode ESB: {{ $row['product_code'] }}</small>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $row['category'] ?: '-' }}</span></td>
                                    <td class="text-end fw-semibold text-danger">
                                        -{{ number_format((float) $row['qty'], 2, ',', '.') }} {{ $row['unit'] }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 align-items-start flex-wrap">
                                            <div class="flex-grow-1" style="min-width: 220px;">
                                                <select name="mappings[{{ $row['index'] }}]" class="form-select form-select-sm sipastok-searchable-select esb-mapping-select" data-row-index="{{ $row['index'] }}" data-search-placeholder="Cari item SIPASTOK...">
                                                    <option value="">-- Lewati (Skip) --</option>
                                                    @foreach($inventoryItems as $invItem)
                                                        <option value="{{ $invItem->id }}">{{ $invItem->name }} ({{ $invItem->base_unit }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm text-nowrap esb-quick-add-btn"
                                                data-row-index="{{ $row['index'] }}"
                                                data-name="{{ $row['product_name'] }}"
                                                data-code="{{ $row['product_code'] }}"
                                                data-unit="{{ $row['unit'] }}"
                                                data-category="{{ $row['category'] }}"
                                                data-unit-cost="{{ $rowUnitCost }}"
                                            >
                                                + Tambah ke Master
                                            </button>
                                        </div>
                                        <div class="small mt-1 esb-quick-add-status text-muted"></div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold">Item Cocok dengan Master SIPASTOK ({{ count($matchedRows) }})</h6>
                <small class="text-muted">Hapus centang jika ada item tertentu yang tidak ingin dipotong pada batch ini.</small>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="toggleAllMatched" checked>
                <label class="form-check-label small fw-semibold" for="toggleAllMatched">Pilih Semua</label>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 48px;" class="text-center">Pilih</th>
                            <th>Produk ESB</th>
                            <th>Item SIPASTOK</th>
                            <th class="text-end">Stok Saat Ini</th>
                            <th class="text-end">Qty Dipotong</th>
                            <th class="text-end">Estimasi Stok Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matchedRows as $row)
                            @php
                                $willHitZero = ((float) $row['current_stock'] - (float) $row['qty']) < 0;
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input esb-matched-check" name="include[{{ $row['index'] }}]" value="1" checked>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $row['product_name'] }}</div>
                                    <small class="text-muted">{{ $row['category'] ?: '-' }} &middot; Satuan ESB: {{ $row['unit'] ?: '-' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-primary">{{ $row['matched_item_name'] }}</div>
                                    <small class="text-muted">{{ $row['matched_item_code'] }} &middot; Satuan: {{ $row['matched_item_unit'] }}</small>
                                </td>
                                <td class="text-end">
                                    {{ number_format((float) $row['current_stock'], 2, ',', '.') }} {{ $row['matched_item_unit'] }}
                                </td>
                                <td class="text-end fw-semibold text-danger">
                                    -{{ number_format((float) $row['qty'], 2, ',', '.') }} {{ $row['unit'] }}
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $willHitZero ? 'text-warning' : 'text-success' }}">
                                        {{ number_format((float) $row['estimated_after'], 2, ',', '.') }} {{ $row['matched_item_unit'] }}
                                    </span>
                                    @if($willHitZero)
                                        <div><small class="text-warning">Ditahan di 0 (cap at zero)</small></div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada item yang cocok secara otomatis. Gunakan tabel pemetaan manual di atas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="notes" class="form-label fw-semibold">Catatan Batch Impor (Opsional)</label>
                    <input type="text" name="notes" id="notes" class="form-control" placeholder="Contoh: Sinkronisasi penjualan ESB periode 1-27 November 2026" maxlength="500">
                </div>
                <div class="col-md-4 d-flex justify-content-end gap-2">
                    <a href="{{ route($routePrefix . '.index') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-success px-4" @disabled((bool) $duplicateImport)>
                        Eksekusi Pemotongan Stok
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleAll = document.getElementById('toggleAllMatched');
    if (toggleAll) {
        toggleAll.addEventListener('change', function () {
            document.querySelectorAll('.esb-matched-check').forEach(function (cb) {
                cb.checked = toggleAll.checked;
            });
        });
    }

    const quickCreateUrl = @json(route($routePrefix . '.quick-create-item'));
    const csrfToken = @json(csrf_token());

    document.querySelectorAll('.esb-quick-add-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const row = btn.closest('tr');
            const select = row ? row.querySelector('select.esb-mapping-select') : null;
            const statusEl = row ? row.querySelector('.esb-quick-add-status') : null;

            btn.disabled = true;
            const originalLabel = btn.textContent;
            btn.textContent = 'Menyimpan...';

            try {
                const response = await fetch(quickCreateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        name: btn.dataset.name || '',
                        esb_code: btn.dataset.code || '',
                        unit: btn.dataset.unit || 'PCS',
                        category: btn.dataset.category || '',
                        unit_cost: parseFloat(btn.dataset.unitCost || '0') || 0,
                    }),
                });

                const data = await response.json();
                if (!response.ok || !data.item) {
                    throw new Error(data.message || 'Gagal menyimpan item baru.');
                }

                const labelText = data.item.name + ' (' + data.item.base_unit + ')';

                document.querySelectorAll('select.esb-mapping-select').forEach(function (sel) {
                    if (!sel.querySelector('option[value="' + data.item.id + '"]')) {
                        const opt = document.createElement('option');
                        opt.value = String(data.item.id);
                        opt.textContent = labelText;
                        sel.appendChild(opt);
                    }
                });

                if (select) {
                    select.value = String(data.item.id);
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }

                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-success');
                btn.textContent = 'Tersimpan';
                if (statusEl) {
                    statusEl.className = 'small mt-1 esb-quick-add-status text-success fw-semibold';
                    statusEl.textContent = data.message + ' (' + data.item.code + ')';
                }
            } catch (err) {
                btn.disabled = false;
                btn.textContent = originalLabel;
                if (statusEl) {
                    statusEl.className = 'small mt-1 esb-quick-add-status text-danger';
                    statusEl.textContent = err.message || 'Gagal menambahkan item.';
                }
            }
        });
    });
});
</script>
@endsection
