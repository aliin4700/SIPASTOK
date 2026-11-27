@extends('staff.layouts.app')

@section('content')
@include('partials.searchable-select-assets')

@php
    $existingItems = $materialRequest->items->values();
    $rowCount = max(5, $existingItems->count() + 2, count(old('items', [])));
@endphp

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1 fw-bold">Edit Draft Stok In</h4>
                <div class="text-muted small">{{ $materialRequest->request_number ?? ('Draft #' . $materialRequest->id) }}</div>
            </div>
            <a href="{{ route('staff.material-requests.show', $materialRequest) }}" class="btn btn-outline-secondary btn-sm">Kembali ke Detail</a>
        </div>

        <div class="alert alert-info">
            Outlet: <strong>{{ $materialRequest->outlet->name ?? '-' }}</strong> |
            Divisi: <strong>{{ $materialRequest->division->name ?? '-' }}</strong> |
            Tanggal: <strong>{{ $materialRequest->request_date?->format('d/m/Y') }}</strong>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <div class="fw-bold mb-1">Periksa kembali input Anda:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('staff.material-requests.update', $materialRequest) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="fw-semibold text-dark">Daftar Bahan Baku (Raw Material)</div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="addMrRowBtn" class="btn btn-sm btn-outline-primary fw-semibold">
                        + Tambah Baris Item
                    </button>
                    <button type="button" id="resetMrRowsBtn" class="btn btn-sm btn-outline-danger">
                        Reset / Kosongkan Form
                    </button>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 240px; width: 38%;">Item Bahan</th>
                            <th style="min-width: 110px;">Qty</th>
                            <th style="min-width: 100px;">Unit</th>
                            <th style="min-width: 160px;">Catatan Item</th>
                            <th class="text-center" style="width: 90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="mrRowsBody">
                        @for($i = 0; $i < $rowCount; $i++)
                            @php
                                $existingRow = $existingItems->get($i);
                                $selectedItemId = old("items.$i.inventory_item_id", $existingRow?->inventory_item_id);
                                $qtyValue = old("items.$i.requested_qty", $existingRow ? (float) $existingRow->requested_qty : null);
                                $unitValue = old("items.$i.unit", $existingRow?->unit);
                                $notesValue = old("items.$i.notes", $existingRow?->notes);
                            @endphp
                            <tr class="mr-item-row">
                                <td>
                                    <select name="items[{{ $i }}][inventory_item_id]" class="form-select mr-item-select sipastok-searchable-select">
                                        <option value="">-- Pilih Item --</option>
                                        @foreach($items as $item)
                                            @php
                                                $stock = $stocks->get($item->id);
                                            @endphp
                                            <option value="{{ $item->id }}"
                                                data-stock="{{ number_format((float) ($stock?->current_stock ?? 0), 2, ',', '.') }}"
                                                data-unit="{{ $stock?->unit ?? $item->base_unit }}"
                                                data-search="{{ strtolower($item->name . ' ' . ($item->code ?? '')) }}"
                                                @selected($selectedItemId == $item->id)>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="small text-muted mt-1 mr-item-info"></div>
                                </td>
                                <td><input type="number" step="0.01" min="0.01" inputmode="decimal" name="items[{{ $i }}][requested_qty]" value="{{ $qtyValue }}" class="form-control mr-qty-input" placeholder="0"></td>
                                <td><input type="text" name="items[{{ $i }}][unit]" value="{{ $unitValue }}" class="form-control mr-unit-input" placeholder="auto"></td>
                                <td><input type="text" name="items[{{ $i }}][notes]" value="{{ $notesValue }}" class="form-control" placeholder="Opsional"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-mr-row-btn" title="Hapus / Reset Baris">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Catatan Request</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)">{{ old('notes', $materialRequest->notes) }}</textarea>
            </div>

            @include('partials.material-request-receipt-input', ['materialRequest' => $materialRequest])

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary fw-semibold px-4" style="min-height: 44px;">Simpan Perubahan</button>
                <a href="{{ route('staff.material-requests.show', $materialRequest) }}" class="btn btn-secondary d-inline-flex align-items-center" style="min-height: 44px;">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const tbody = document.getElementById('mrRowsBody');
    if (!tbody) return;

    function bindMrRow(row) {
        const select = row.querySelector('.mr-item-select');
        const info = row.querySelector('.mr-item-info');
        const unitInput = row.querySelector('.mr-unit-input');
        const removeBtn = row.querySelector('.remove-mr-row-btn');

        function refresh() {
            if (!select || !info) return;
            const opt = select.options[select.selectedIndex];
            if (opt && opt.value) {
                const unit = opt.getAttribute('data-unit') || '';
                info.textContent = 'Stok saat ini: ' + (opt.getAttribute('data-stock') || '0') + ' ' + unit;
                if (unitInput && !unitInput.value && unit) {
                    unitInput.value = unit;
                }
            } else {
                info.textContent = '';
            }
        }

        if (select) {
            select.addEventListener('change', function () {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.value && unitInput) {
                    unitInput.value = opt.getAttribute('data-unit') || '';
                }
                refresh();
            });
            refresh();
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                window.SipastokItemForm.removeOrClearRow(row, tbody, '.mr-item-row');
            });
        }

        if (window.SipastokSearchableSelect) {
            window.SipastokSearchableSelect.init(row);
        }
    }

    tbody.querySelectorAll('.mr-item-row').forEach(bindMrRow);

    const addBtn = document.getElementById('addMrRowBtn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const rows = tbody.querySelectorAll('.mr-item-row');
            const clone = rows[0].cloneNode(true);
            clone.querySelectorAll('.sipastok-ss-container').forEach(function (el) { el.remove(); });
            clone.querySelectorAll('select, input').forEach(function (el) { el.value = ''; });
            const info = clone.querySelector('.mr-item-info');
            if (info) info.textContent = '';
            tbody.appendChild(clone);
            window.SipastokItemForm.reindexRows(tbody, '.mr-item-row');
            bindMrRow(clone);
        });
    }

    const resetBtn = document.getElementById('resetMrRowsBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            window.SipastokItemForm.resetAllRows(tbody, '.mr-item-row');
        });
    }
})();
</script>
@endsection
