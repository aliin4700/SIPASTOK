@extends('staff.layouts.app')

@section('content')
@include('partials.searchable-select-assets')

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-0 fw-bold">Input Waste</h4>
                <div class="text-muted small">Pencatatan bahan rusak, kedaluwarsa, atau gagal produksi hari ini.</div>
            </div>
            <a href="{{ route('staff.waste.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @elseif(! $operation || $operation->status !== 'open')
            <div class="alert alert-warning mb-3">Buka Operasional Hari Ini terlebih dahulu sebelum mencatat Waste.</div>
            <a href="{{ route('staff.daily-operations.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">Buka Operasional</a>
        @else
            @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form method="get" class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <select name="outlet_id" class="form-select">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <select name="division_id" class="form-select">
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Tampilkan</button></div>
                </form>
            @endif

            <div class="alert alert-info">
                Outlet: <strong>{{ $operation->outlet->name ?? '-' }}</strong> |
                Divisi: <strong>{{ $operation->division->name ?? '-' }}</strong> |
                Status Shift: <strong class="text-uppercase">{{ $operation->status }}</strong>
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

            <form method="post" action="{{ route('staff.waste.store') }}">
                @csrf
                <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="fw-semibold text-dark">Daftar Item Waste</div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="addWasteRowBtn" class="btn btn-sm btn-outline-primary fw-semibold">
                            + Tambah Baris Item
                        </button>
                        <button type="button" id="resetWasteRowsBtn" class="btn btn-sm btn-outline-danger">
                            Reset / Kosongkan Form
                        </button>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 240px; width: 32%">Item</th>
                                <th style="min-width: 110px;">Qty</th>
                                <th style="min-width: 90px;">Unit</th>
                                <th style="min-width: 130px;">Estimasi Nilai</th>
                                <th style="min-width: 150px;">Reason</th>
                                <th style="min-width: 150px;">Catatan</th>
                                <th class="text-center" style="width: 90px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="wasteRowsBody">
                            @for($i = 0; $i < max(5, count(old('items', []))); $i++)
                                <tr class="waste-item-row">
                                    <td>
                                        <select name="items[{{ $i }}][inventory_item_id]" class="form-select waste-item-select sipastok-searchable-select">
                                            <option value="">-- Pilih Item --</option>
                                            @foreach($items as $item)
                                                @php
                                                    $stock = $stocks->get($item->id);
                                                @endphp
                                                <option value="{{ $item->id }}"
                                                    data-stock="{{ number_format((float) ($stock?->current_stock ?? 0), 2, ',', '.') }}"
                                                    data-unit="{{ $stock?->unit ?? $item->base_unit }}"
                                                    data-search="{{ strtolower($item->name . ' ' . ($item->code ?? '')) }}"
                                                    @selected(old("items.$i.inventory_item_id") == $item->id)>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="small text-muted mt-1 waste-item-info"></div>
                                    </td>
                                    <td><input type="number" step="0.01" min="0.01" inputmode="decimal" name="items[{{ $i }}][qty]" value="{{ old("items.$i.qty") }}" class="form-control" placeholder="0"></td>
                                    <td><input type="text" name="items[{{ $i }}][unit]" value="{{ old("items.$i.unit") }}" class="form-control waste-unit-input" placeholder="auto"></td>
                                    <td><input type="number" step="0.01" min="0" inputmode="decimal" name="items[{{ $i }}][estimated_value]" value="{{ old("items.$i.estimated_value") }}" class="form-control" placeholder="Opsional"></td>
                                    <td>
                                        <select name="items[{{ $i }}][reason]" class="form-select">
                                            <option value="">Pilih reason</option>
                                            @foreach($reasons as $reason)
                                                <option value="{{ $reason }}" @selected(old("items.$i.reason") === $reason)>{{ str_replace('_', ' ', $reason) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[{{ $i }}][notes]" value="{{ old("items.$i.notes") }}" class="form-control" placeholder="Opsional"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-waste-row-btn" title="Hapus / Reset Baris">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan Waste</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan umum waste (opsional)">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold px-4" style="min-height: 44px;">Simpan Draft</button>
                    <a href="{{ route('staff.waste.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-secondary d-inline-flex align-items-center" style="min-height: 44px;">Kembali</a>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
(function () {
    const tbody = document.getElementById('wasteRowsBody');
    if (!tbody) return;

    function bindWasteRow(row) {
        const select = row.querySelector('.waste-item-select');
        const info = row.querySelector('.waste-item-info');
        const unitInput = row.querySelector('.waste-unit-input');
        const removeBtn = row.querySelector('.remove-waste-row-btn');

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
                window.SipastokItemForm.removeOrClearRow(row, tbody, '.waste-item-row');
            });
        }

        if (window.SipastokSearchableSelect) {
            window.SipastokSearchableSelect.init(row);
        }
    }

    tbody.querySelectorAll('.waste-item-row').forEach(bindWasteRow);

    const addBtn = document.getElementById('addWasteRowBtn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const rows = tbody.querySelectorAll('.waste-item-row');
            const clone = rows[0].cloneNode(true);
            clone.querySelectorAll('.sipastok-ss-container').forEach(function (el) { el.remove(); });
            clone.querySelectorAll('select, input').forEach(function (el) { el.value = ''; });
            const info = clone.querySelector('.waste-item-info');
            if (info) info.textContent = '';
            tbody.appendChild(clone);
            window.SipastokItemForm.reindexRows(tbody, '.waste-item-row');
            bindWasteRow(clone);
        });
    }

    const resetBtn = document.getElementById('resetWasteRowsBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            window.SipastokItemForm.resetAllRows(tbody, '.waste-item-row');
        });
    }
})();
</script>
@endsection
