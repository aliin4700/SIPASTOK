@extends('backend.v_layouts.app')

@section('content')
@php
    $oldItems = old('items', []);
    $initialRowCount = max(3, count($oldItems));
    $prefilledItems = $prefilledItems ?? collect();
@endphp

<style>
    .remote-item-picker {
        position: relative;
    }
    .remote-item-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1060;
        max-height: 240px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        display: none;
    }
    .remote-item-dropdown.show {
        display: block;
    }
    .remote-item-option {
        padding: 0.45rem 0.65rem;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f5;
        font-size: 0.875rem;
    }
    .remote-item-option:hover {
        background-color: #eef5ff;
    }
</style>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <form method="post" action="{{ route('backend.purchase-orders.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control">
                            <option value="">Tanpa Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Outlet</label>
                        <select name="outlet_id" id="poOutletId" class="form-control @error('outlet_id') is-invalid @enderror" required>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        @error('outlet_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Tanggal Order</label>
                        <input type="date" name="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Estimasi Datang</label>
                        <input type="date" name="expected_date" value="{{ old('expected_date') }}" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-2">
                <div>
                    <h6 class="mb-0">Item Purchase Order</h6>
                    <small class="text-muted">Ketik nama atau kode bahan untuk mencari item secara cepat (AJAX).</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="addPoRowBtn" class="btn btn-sm btn-outline-primary">+ Tambah Baris Item</button>
                    <button type="button" id="resetPoRowsBtn" class="btn btn-sm btn-outline-danger">Reset / Kosongkan Form</button>
                </div>
            </div>

            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="min-width:260px">Inventory Item</th>
                            <th style="width:120px">Qty</th>
                            <th style="width:110px">Unit</th>
                            <th style="width:140px">Unit Cost (Rp)</th>
                            <th>Catatan</th>
                            <th class="text-center" style="width:90px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="poRowsBody">
                        @for($i = 0; $i < $initialRowCount; $i++)
                            @php
                                $row = $oldItems[$i] ?? [];
                                $selectedItemId = $row['inventory_item_id'] ?? '';
                                $prefilled = $selectedItemId ? $prefilledItems->get((int) $selectedItemId) : null;
                            @endphp
                            <tr class="po-item-row">
                                <td>
                                    <div class="remote-item-picker">
                                        <input type="hidden" name="items[{{ $i }}][inventory_item_id]" value="{{ $selectedItemId }}" class="po-item-id-input">
                                        <input type="text" class="form-control po-item-search-input" value="{{ $prefilled ? $prefilled->name : '' }}" placeholder="Ketik nama / kode item..." autocomplete="off">
                                        <div class="remote-item-dropdown"></div>
                                    </div>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][ordered_qty]" value="{{ $row['ordered_qty'] ?? '' }}" class="form-control"></td>
                                <td><input type="text" name="items[{{ $i }}][unit]" value="{{ $row['unit'] ?? '' }}" class="form-control po-unit-input"></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_cost]" value="{{ $row['unit_cost'] ?? '' }}" class="form-control po-cost-input"></td>
                                <td><input type="text" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}" class="form-control"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-po-row-btn">Hapus</button>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary">Simpan PO</button>
            <a href="{{ route('backend.purchase-orders.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<script>
(function () {
    const searchUrl = @json(route('backend.api.inventory-items.search'));
    const tbody = document.getElementById('poRowsBody');
    const outletSelect = document.getElementById('poOutletId');
    if (!tbody) return;

    function reindexRows() {
        tbody.querySelectorAll('.po-item-row').forEach(function (row, idx) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.setAttribute('name', el.getAttribute('name').replace(/items\[\d+\]/, 'items[' + idx + ']'));
            });
        });
    }

    function bindRow(row) {
        const idInput = row.querySelector('.po-item-id-input');
        const searchInput = row.querySelector('.po-item-search-input');
        const dropdown = row.querySelector('.remote-item-dropdown');
        const unitInput = row.querySelector('.po-unit-input');
        const costInput = row.querySelector('.po-cost-input');
        const remBtn = row.querySelector('.remove-po-row-btn');
        let timer = null;

        async function fetchItems(query) {
            const params = new URLSearchParams({ q: query });
            if (outletSelect && outletSelect.value) {
                params.set('outlet_id', outletSelect.value);
            }
            const res = await fetch(searchUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return [];
            const payload = await res.json();
            return payload.data || [];
        }

        function renderOptions(items) {
            dropdown.innerHTML = '';
            if (!items.length) {
                dropdown.innerHTML = '<div class="p-2 text-muted small">Item tidak ditemukan.</div>';
                dropdown.classList.add('show');
                return;
            }
            items.forEach(function (item) {
                const div = document.createElement('div');
                div.className = 'remote-item-option';
                div.innerHTML = '<div class="font-weight-bold">' + item.name + '</div>'
                    + '<div class="text-muted small">' + (item.code || '-') + ' &middot; ' + (item.unit || '') + ' &middot; Rp ' + Number(item.unit_cost || 0).toLocaleString('id-ID') + '</div>';
                div.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    idInput.value = item.id;
                    searchInput.value = item.name;
                    if (unitInput) unitInput.value = item.unit || '';
                    if (costInput && (!costInput.value || Number(costInput.value) === 0)) {
                        costInput.value = item.unit_cost > 0 ? item.unit_cost : '';
                    }
                    dropdown.classList.remove('show');
                });
                dropdown.appendChild(div);
            });
            dropdown.classList.add('show');
        }

        if (searchInput) {
            searchInput.addEventListener('focus', function () {
                clearTimeout(timer);
                timer = setTimeout(async function () {
                    renderOptions(await fetchItems(searchInput.value.trim()));
                }, 120);
            });
            searchInput.addEventListener('input', function () {
                idInput.value = '';
                clearTimeout(timer);
                timer = setTimeout(async function () {
                    renderOptions(await fetchItems(searchInput.value.trim()));
                }, 250);
            });
            searchInput.addEventListener('blur', function () {
                setTimeout(function () { dropdown.classList.remove('show'); }, 150);
            });
        }

        if (remBtn) {
            remBtn.addEventListener('click', function () {
                const rows = tbody.querySelectorAll('.po-item-row');
                if (rows.length > 1) {
                    row.remove();
                    reindexRows();
                } else {
                    row.querySelectorAll('input').forEach(function (el) { el.value = ''; });
                }
            });
        }
    }

    tbody.querySelectorAll('.po-item-row').forEach(bindRow);

    document.getElementById('addPoRowBtn')?.addEventListener('click', function () {
        const clone = tbody.querySelector('.po-item-row').cloneNode(true);
        clone.querySelectorAll('input').forEach(function (el) { el.value = ''; });
        const dd = clone.querySelector('.remote-item-dropdown');
        if (dd) { dd.innerHTML = ''; dd.classList.remove('show'); }
        tbody.appendChild(clone);
        reindexRows();
        bindRow(clone);
    });

    document.getElementById('resetPoRowsBtn')?.addEventListener('click', function () {
        const rows = Array.from(tbody.querySelectorAll('.po-item-row'));
        rows.slice(1).forEach(function (r) { r.remove(); });
        rows[0].querySelectorAll('input').forEach(function (el) { el.value = ''; });
        reindexRows();
    });
})();
</script>
@endsection
