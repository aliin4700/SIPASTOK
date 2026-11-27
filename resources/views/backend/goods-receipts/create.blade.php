@extends('backend.v_layouts.app')

@section('content')
@php
    $selectedRows = $selectedPurchaseOrder
        ? $selectedPurchaseOrder->items->map(fn ($poItem) => [
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $poItem->inventory_item_id,
            'item_name' => $poItem->item->name ?? '',
            'received_qty' => max(0, (float) $poItem->ordered_qty - (float) $poItem->received_qty),
            'unit' => $poItem->unit ?: ($poItem->item->base_unit ?? null),
            'unit_cost' => $poItem->unit_cost,
            'notes' => $poItem->notes,
        ])->values()->all()
        : [];
    $oldRows = old('items', $selectedRows);
    $initialRowCount = max(3, count($oldRows));
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

        <form method="post" action="{{ route('backend.goods-receipts.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Purchase Order</label>
                        <select name="purchase_order_id" id="grPurchaseOrderId" class="form-control">
                            <option value="">Standalone (Tanpa PO)</option>
                            @foreach($purchaseOrders as $purchaseOrder)
                                <option value="{{ $purchaseOrder->id }}" @selected(old('purchase_order_id', $selectedPurchaseOrder?->id) == $purchaseOrder->id)>{{ $purchaseOrder->po_number }}</option>
                            @endforeach
                        </select>
                        <small id="poLoadStatus" class="text-muted">Memilih PO akan memuat daftar item secara otomatis.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" id="grSupplierId" class="form-control">
                            <option value="">Tanpa Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id', $selectedPurchaseOrder?->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Outlet</label>
                        <select name="outlet_id" id="grOutletId" class="form-control @error('outlet_id') is-invalid @enderror" required>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $selectedPurchaseOrder?->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        @error('outlet_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tanggal Terima</label>
                        <input type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Catatan</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-2">
                <div>
                    <h6 class="mb-0">Item Diterima</h6>
                    <small class="text-muted">Pilih nomor PO di atas untuk prefill otomatis, atau ketik nama/kode bahan secara langsung.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="addGrRowBtn" class="btn btn-sm btn-outline-primary">+ Tambah Baris Item</button>
                    <button type="button" id="resetGrRowsBtn" class="btn btn-sm btn-outline-danger">Reset / Kosongkan Form</button>
                </div>
            </div>

            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="min-width:260px">Inventory Item</th>
                            <th style="width:120px">Qty Terima</th>
                            <th style="width:110px">Unit</th>
                            <th style="width:140px">Unit Cost (Rp)</th>
                            <th>Catatan</th>
                            <th class="text-center" style="width:90px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="grRowsBody">
                        @for($i = 0; $i < $initialRowCount; $i++)
                            @php
                                $row = $oldRows[$i] ?? [];
                                $selectedItemId = $row['inventory_item_id'] ?? '';
                                $prefilledName = $row['item_name'] ?? ($selectedItemId ? ($prefilledItems->get((int) $selectedItemId)?->name ?? '') : '');
                            @endphp
                            <tr class="gr-item-row">
                                <td>
                                    <div class="remote-item-picker">
                                        <input type="hidden" name="items[{{ $i }}][purchase_order_item_id]" value="{{ $row['purchase_order_item_id'] ?? '' }}" class="gr-po-item-id-input">
                                        <input type="hidden" name="items[{{ $i }}][inventory_item_id]" value="{{ $selectedItemId }}" class="gr-item-id-input">
                                        <input type="text" class="form-control gr-item-search-input" value="{{ $prefilledName }}" placeholder="Ketik nama / kode item..." autocomplete="off">
                                        <div class="remote-item-dropdown"></div>
                                    </div>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][received_qty]" value="{{ $row['received_qty'] ?? '' }}" class="form-control gr-qty-input"></td>
                                <td><input type="text" name="items[{{ $i }}][unit]" value="{{ $row['unit'] ?? '' }}" class="form-control gr-unit-input"></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_cost]" value="{{ $row['unit_cost'] ?? '' }}" class="form-control gr-cost-input"></td>
                                <td><input type="text" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}" class="form-control gr-notes-input"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-gr-row-btn">Hapus</button>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <button class="btn btn-primary">Simpan Goods Receipt</button>
            <a href="{{ route('backend.goods-receipts.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<script>
(function () {
    const searchUrl = @json(route('backend.api.inventory-items.search'));
    const poItemsUrlTemplate = @json(route('backend.api.purchase-orders.items', ['purchaseOrder' => '__PO_ID__']));
    const tbody = document.getElementById('grRowsBody');
    const poSelect = document.getElementById('grPurchaseOrderId');
    const supplierSelect = document.getElementById('grSupplierId');
    const outletSelect = document.getElementById('grOutletId');
    const poLoadStatus = document.getElementById('poLoadStatus');
    if (!tbody) return;

    function reindexRows() {
        tbody.querySelectorAll('.gr-item-row').forEach(function (row, idx) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.setAttribute('name', el.getAttribute('name').replace(/items\[\d+\]/, 'items[' + idx + ']'));
            });
        });
    }

    function createRowElement(data) {
        const tr = document.createElement('tr');
        tr.className = 'gr-item-row';
        tr.innerHTML = ''
            + '<td>'
            + '  <div class="remote-item-picker">'
            + '    <input type="hidden" name="items[0][purchase_order_item_id]" class="gr-po-item-id-input">'
            + '    <input type="hidden" name="items[0][inventory_item_id]" class="gr-item-id-input">'
            + '    <input type="text" class="form-control gr-item-search-input" placeholder="Ketik nama / kode item..." autocomplete="off">'
            + '    <div class="remote-item-dropdown"></div>'
            + '  </div>'
            + '</td>'
            + '<td><input type="number" step="0.01" min="0" name="items[0][received_qty]" class="form-control gr-qty-input"></td>'
            + '<td><input type="text" name="items[0][unit]" class="form-control gr-unit-input"></td>'
            + '<td><input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control gr-cost-input"></td>'
            + '<td><input type="text" name="items[0][notes]" class="form-control gr-notes-input"></td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-gr-row-btn">Hapus</button></td>';

        if (data) {
            tr.querySelector('.gr-po-item-id-input').value = data.purchase_order_item_id || '';
            tr.querySelector('.gr-item-id-input').value = data.inventory_item_id || '';
            tr.querySelector('.gr-item-search-input').value = data.name || '';
            tr.querySelector('.gr-qty-input').value = data.remaining_qty !== undefined ? data.remaining_qty : '';
            tr.querySelector('.gr-unit-input').value = data.unit || '';
            tr.querySelector('.gr-cost-input').value = data.unit_cost !== undefined && data.unit_cost !== null ? data.unit_cost : '';
            tr.querySelector('.gr-notes-input').value = data.notes || '';
        }

        return tr;
    }

    function bindRow(row) {
        const poItemIdInput = row.querySelector('.gr-po-item-id-input');
        const idInput = row.querySelector('.gr-item-id-input');
        const searchInput = row.querySelector('.gr-item-search-input');
        const dropdown = row.querySelector('.remote-item-dropdown');
        const unitInput = row.querySelector('.gr-unit-input');
        const costInput = row.querySelector('.gr-cost-input');
        const remBtn = row.querySelector('.remove-gr-row-btn');
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
                if (poItemIdInput) poItemIdInput.value = '';
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
                const rows = tbody.querySelectorAll('.gr-item-row');
                if (rows.length > 1) {
                    row.remove();
                    reindexRows();
                } else {
                    row.querySelectorAll('input').forEach(function (el) { el.value = ''; });
                }
            });
        }
    }

    tbody.querySelectorAll('.gr-item-row').forEach(bindRow);

    if (poSelect) {
        poSelect.addEventListener('change', async function () {
            const poId = poSelect.value;
            if (!poId) {
                if (poLoadStatus) poLoadStatus.textContent = 'Mode Standalone (tanpa PO).';
                return;
            }

            if (poLoadStatus) poLoadStatus.textContent = 'Memuat item PO...';
            try {
                const url = poItemsUrlTemplate.replace('__PO_ID__', encodeURIComponent(poId));
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Gagal memuat item PO.');
                const payload = await res.json();

                if (payload.purchase_order) {
                    if (outletSelect && payload.purchase_order.outlet_id) {
                        outletSelect.value = String(payload.purchase_order.outlet_id);
                    }
                    if (supplierSelect && payload.purchase_order.supplier_id) {
                        supplierSelect.value = String(payload.purchase_order.supplier_id);
                    }
                }

                const items = payload.items || [];
                tbody.innerHTML = '';
                if (items.length) {
                    items.forEach(function (item) {
                        const tr = createRowElement(item);
                        tbody.appendChild(tr);
                        bindRow(tr);
                    });
                } else {
                    const emptyRow = createRowElement(null);
                    tbody.appendChild(emptyRow);
                    bindRow(emptyRow);
                }
                reindexRows();
                if (poLoadStatus) poLoadStatus.textContent = items.length + ' item PO berhasil dimuat.';
            } catch (err) {
                if (poLoadStatus) poLoadStatus.textContent = err.message || 'Gagal memuat item PO.';
            }
        });
    }

    document.getElementById('addGrRowBtn')?.addEventListener('click', function () {
        const tr = createRowElement(null);
        tbody.appendChild(tr);
        reindexRows();
        bindRow(tr);
    });

    document.getElementById('resetGrRowsBtn')?.addEventListener('click', function () {
        tbody.innerHTML = '';
        const tr = createRowElement(null);
        tbody.appendChild(tr);
        reindexRows();
        bindRow(tr);
    });
})();
</script>
@endsection
