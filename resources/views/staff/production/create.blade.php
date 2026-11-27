@extends('staff.layouts.app')

@section('content')
@include('partials.searchable-select-assets')

@php
    $groupedItems = $groupedItems ?? [
        'alert' => collect(),
        'wip_and_recipe' => collect(),
        'other' => collect(),
    ];
    $openingAlerts = $openingAlerts ?? collect();
    $alertMap = $alertMap ?? [];
    $recipes = $recipes ?? collect();
    $recipesByItemId = $recipesByItemId ?? collect();
    $initialRowCount = max(10, count(old('items', [])));
@endphp

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1 fw-bold">Input Produksi / WIP</h4>
                <div class="text-muted small">Catat hasil produksi WIP, produk olahan, atau output resep divisi hari ini.</div>
            </div>
            <a href="{{ route('staff.production.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @elseif(! $operation || $operation->status !== 'open')
            <div class="alert alert-warning mb-3">Buka Operasional Hari Ini terlebih dahulu sebelum mencatat Produksi / WIP.</div>
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
                    <div class="col-12 col-md-2">
                        <button class="btn btn-primary w-100">Tampilkan</button>
                    </div>
                </form>
            @endif

            <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    Outlet: <strong>{{ $operation->outlet->name ?? '-' }}</strong> |
                    Divisi: <strong>{{ $operation->division->name ?? '-' }}</strong> |
                    Status Shift: <strong class="text-uppercase">{{ $operation->status }}</strong>
                </div>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-danger">Prioritas Alert: {{ $groupedItems['alert']->count() }}</span>
                    <span class="badge bg-primary">WIP &amp; Produk Jadi Lainnya: {{ $groupedItems['wip_and_recipe']->count() }}</span>
                    <span class="badge bg-dark">Total Item: {{ $items->count() }}</span>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-bold mb-1">Periksa kembali input produksi Anda:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Panel 1: Rekomendasi Prioritas dari Opening Alert --}}
            @if($openingAlerts->isNotEmpty())
                <div class="card border-warning mb-3" style="background-color: #fffbeb;">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                    <span class="badge bg-warning text-dark">PRIORITAS OPENING ALERT</span>
                                    <span>Rekomendasi Item Perlu Diproduksi ({{ $openingAlerts->count() }} Item)</span>
                                </h6>
                                <div class="text-muted small">
                                    Klik <strong>+ Pilih</strong> untuk memasukkan item stok kritis ke tabel produksi, atau pilih item WIP / Produk Jadi lainnya langsung pada dropdown di bawah.
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" id="fillAllAlertsBtn" class="btn btn-sm btn-warning fw-semibold">
                                    + Masukkan Prioritas Alert ke Form
                                </button>
                                @if($openingAlerts->count() > 6)
                                    <button type="button" id="toggleAllAlertsBtn" class="btn btn-sm btn-outline-dark">
                                        Lihat Semua ({{ $openingAlerts->count() }})
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="row g-2" id="openingAlertsGrid">
                            @foreach($openingAlerts as $idx => $alert)
                                @php
                                    $alertItem = $alert['item'];
                                    $alertRecipe = $alert['recipe'];
                                    $isHiddenAlert = $idx >= 6;
                                @endphp
                                <div class="col-12 col-md-6 col-lg-4 alert-recommend-card {{ $isHiddenAlert ? 'd-none extra-alert-card' : '' }}">
                                    <div class="bg-white border rounded p-2 h-100 d-flex justify-content-between align-items-center gap-2">
                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                                                <span class="badge {{ $alert['category'] === 'WIP_EMPTY' ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                                    {{ $alert['badge_label'] }}
                                                </span>
                                                <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                                    {{ strtoupper($alertItem->item_type) }}
                                                </span>
                                                @if($alertRecipe)
                                                    <span class="badge bg-success" style="font-size: 0.7rem;">Resep BOM</span>
                                                @endif
                                            </div>
                                            <div class="fw-semibold text-dark small text-truncate" title="{{ $alertItem->name }}">
                                                {{ $alertItem->name }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                Stok: <strong>{{ number_format((float) $alert['current_stock'], 2, ',', '.') }}</strong>
                                                / Min: <strong>{{ number_format((float) $alert['min_stock'], 2, ',', '.') }}</strong>
                                                {{ $alert['unit'] }}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary flex-shrink-0 quick-pick-item-btn"
                                            data-item-id="{{ $alertItem->id }}"
                                            data-unit="{{ $alert['unit'] }}"
                                            data-qty="{{ $alert['suggested_qty'] }}"
                                            data-note="{{ $alertRecipe ? ('Resep: ' . $alertRecipe->name) : 'Prioritas Opening Alert' }}"
                                        >
                                            + Pilih
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Panel 2: Pilihan Cepat dari Resep / BOM Aktif (output_inventory_item_id) --}}
            @if($recipes->isNotEmpty())
                <div class="card border-info mb-3" style="background-color: #f0f9ff;">
                    <div class="card-body py-3">
                        <h6 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <span class="badge bg-info text-dark">RESEP / BOM AKTIF</span>
                            <span>Pilih Cepat dari Resep Produksi Divisi ({{ $recipes->count() }} Resep)</span>
                        </h6>
                        <div class="text-muted small mb-2">
                            Memilih resep di bawah akan otomatis memilih item hasil produksi (<code>output_inventory_item_id</code>) beserta kuantitas dan satuan output resep.
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($recipes as $recipe)
                                @if($recipe->outputInventoryItem)
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-info bg-white text-dark fw-semibold quick-pick-item-btn"
                                        data-item-id="{{ $recipe->output_inventory_item_id }}"
                                        data-unit="{{ $recipe->output_unit ?: $recipe->outputInventoryItem->base_unit }}"
                                        data-qty="{{ (float) $recipe->output_qty }}"
                                        data-note="Resep: {{ $recipe->name }}"
                                    >
                                        + {{ $recipe->name }} &rarr; {{ $recipe->outputInventoryItem->name }}
                                        ({{ number_format((float) $recipe->output_qty, 2, ',', '.') }} {{ $recipe->output_unit ?: $recipe->outputInventoryItem->base_unit }})
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <form method="post" action="{{ route('staff.production.store') }}" id="productionForm">
                @csrf
                <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">

                <div class="row g-3 mb-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Tipe Produksi</label>
                        <select name="production_type" class="form-select" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(old('production_type', 'wip') === $type)>
                                    {{ strtoupper(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-8">
                        <label class="form-label fw-semibold">Filter &amp; Cari Cepat Daftar Item pada Dropdown</label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input
                                type="search"
                                id="itemDropdownSearch"
                                class="form-control"
                                style="max-width: 260px;"
                                placeholder="Ketik nama/kode item..."
                            >
                            <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Filter kategori item">
                                <button type="button" class="btn btn-dark active" data-filter-group="all">
                                    Semua Item ({{ $items->count() }})
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-filter-group="alert">
                                    Opening Alert ({{ $groupedItems['alert']->count() }})
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-filter-group="wip">
                                    Non-Alert ({{ $groupedItems['wip_and_recipe']->count() }})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div class="fw-semibold text-dark">Daftar Item Hasil Produksi</div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" id="addProductionRowBtnTop" class="btn btn-sm btn-outline-primary fw-semibold">
                            + Tambah Baris Item
                        </button>
                        <button type="button" id="resetProductionRowsBtn" class="btn btn-sm btn-outline-danger">
                            Reset / Kosongkan Form
                        </button>
                    </div>
                </div>

                <div class="table-responsive mb-2">
                    <table class="table table-bordered align-middle" id="productionItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 280px; width: 36%">Item Output (WIP / Produk Jadi)</th>
                                <th style="min-width: 115px;">Qty Produksi</th>
                                <th style="min-width: 100px;">Unit</th>
                                <th style="min-width: 140px;">Estimasi Nilai (Rp)</th>
                                <th style="min-width: 160px;">Catatan</th>
                                <th class="text-center" style="width: 90px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="productionRowsBody">
                            @for($i = 0; $i < $initialRowCount; $i++)
                                @php
                                    $selectedItem = old("items.$i.inventory_item_id");
                                @endphp
                                <tr class="production-item-row">
                                    <td>
                                        <select name="items[{{ $i }}][inventory_item_id]" class="form-select production-item-select sipastok-searchable-select">
                                            <option value="">-- Pilih Item --</option>

                                            @if($groupedItems['alert']->isNotEmpty())
                                                <optgroup label="Prioritas (Stok Kritis)" data-optgroup="alert">
                                                    @foreach($groupedItems['alert'] as $item)
                                                        @php
                                                            $stock = $stocks->get($item->id);
                                                            $alert = $alertMap[$item->id] ?? null;
                                                            $recipe = $recipesByItemId->get($item->id);
                                                            $unit = $recipe?->output_unit ?: ($stock?->unit ?? $item->base_unit);
                                                        @endphp
                                                        <option
                                                            value="{{ $item->id }}"
                                                            data-group="alert"
                                                            data-unit="{{ $unit }}"
                                                            data-stock="{{ number_format((float) ($stock?->current_stock ?? 0), 2, ',', '.') }}"
                                                            data-default-qty="{{ $alert['suggested_qty'] ?? ($recipe?->output_qty ?? '') }}"
                                                            data-search="{{ strtolower($item->name . ' ' . ($item->code ?? '')) }}"
                                                            @selected((string) $selectedItem === (string) $item->id)
                                                        >{{ $item->name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif

                                            @if($groupedItems['wip_and_recipe']->isNotEmpty())
                                                <optgroup label="Item Lainnya" data-optgroup="wip_and_recipe">
                                                    @foreach($groupedItems['wip_and_recipe'] as $item)
                                                        @php
                                                            $stock = $stocks->get($item->id);
                                                            $recipe = $recipesByItemId->get($item->id);
                                                            $unit = $recipe?->output_unit ?: ($stock?->unit ?? $item->base_unit);
                                                        @endphp
                                                        <option
                                                            value="{{ $item->id }}"
                                                            data-group="wip_and_recipe"
                                                            data-unit="{{ $unit }}"
                                                            data-stock="{{ number_format((float) ($stock?->current_stock ?? 0), 2, ',', '.') }}"
                                                            data-default-qty="{{ $recipe?->output_qty ?? '' }}"
                                                            data-search="{{ strtolower($item->name . ' ' . ($item->code ?? '')) }}"
                                                            @selected((string) $selectedItem === (string) $item->id)
                                                        >{{ $item->name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                        <div class="small text-muted mt-1 production-item-info"></div>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" inputmode="decimal" name="items[{{ $i }}][qty]" value="{{ old("items.$i.qty") }}" class="form-control production-qty-input" placeholder="0">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $i }}][unit]" value="{{ old("items.$i.unit") }}" class="form-control production-unit-input" placeholder="auto">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" inputmode="decimal" name="items[{{ $i }}][estimated_value]" value="{{ old("items.$i.estimated_value") }}" class="form-control" placeholder="Opsional">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $i }}][notes]" value="{{ old("items.$i.notes") }}" class="form-control production-note-input" placeholder="Opsional">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-production-row-btn" title="Hapus / Reset Baris">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="mb-3">
                    <button type="button" id="addProductionRowBtn" class="btn btn-sm btn-outline-primary fw-semibold">
                        + Tambah Baris Item Produksi
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan Produksi</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan shift produksi hari ini (opsional)">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary fw-semibold px-4" style="min-height: 44px;">Simpan Draft</button>
                    <a href="{{ route('staff.production.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-secondary d-inline-flex align-items-center" style="min-height: 44px;">Kembali</a>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
(function () {
    const tbody = document.getElementById('productionRowsBody');
    if (!tbody) {
        return;
    }

    const searchInput = document.getElementById('itemDropdownSearch');
    const filterButtons = document.querySelectorAll('[data-filter-group]');
    const addRowBtn = document.getElementById('addProductionRowBtn');
    const addRowBtnTop = document.getElementById('addProductionRowBtnTop');
    const resetRowsBtn = document.getElementById('resetProductionRowsBtn');
    const toggleAllAlertsBtn = document.getElementById('toggleAllAlertsBtn');
    const fillAllAlertsBtn = document.getElementById('fillAllAlertsBtn');
    let activeGroupFilter = 'all';

    function bindRowEvents(row) {
        const select = row.querySelector('.production-item-select');
        const unitInput = row.querySelector('.production-unit-input');
        const qtyInput = row.querySelector('.production-qty-input');
        const removeBtn = row.querySelector('.remove-production-row-btn');

        if (!select) {
            return;
        }

        select.addEventListener('change', function () {
            const selectedOption = select.options[select.selectedIndex];
            const infoEl = row.querySelector('.production-item-info');
            if (!selectedOption || !selectedOption.value) {
                if (infoEl) {
                    infoEl.textContent = '';
                }
                return;
            }

            const defaultUnit = selectedOption.getAttribute('data-unit') || '';
            const defaultQty = selectedOption.getAttribute('data-default-qty') || '';
            const currentStock = selectedOption.getAttribute('data-stock') || '0';

            if (infoEl) {
                infoEl.textContent = 'Stok saat ini: ' + currentStock + ' ' + defaultUnit;
            }

            if (unitInput && defaultUnit) {
                unitInput.value = defaultUnit;
            }
            if (qtyInput && !qtyInput.value && defaultQty) {
                qtyInput.placeholder = defaultQty;
            }
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                window.SipastokItemForm.removeOrClearRow(row, tbody, '.production-item-row');
            });
        }

        if (window.SipastokSearchableSelect) {
            window.SipastokSearchableSelect.init(row);
        }
    }

    document.querySelectorAll('.production-item-row').forEach(bindRowEvents);

    function applyDropdownFilter() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        const selects = tbody.querySelectorAll('.production-item-select');

        selects.forEach(function (select) {
            const currentVal = select.value;

            select.querySelectorAll('option').forEach(function (opt) {
                if (!opt.value) {
                    opt.hidden = false;
                    return;
                }

                // Jangan sembunyikan opsi yang sedang terpilih pada baris tersebut
                if (opt.value === currentVal) {
                    opt.hidden = false;
                    return;
                }

                const optGroup = opt.getAttribute('data-group') || '';
                const searchText = opt.getAttribute('data-search') || opt.textContent.toLowerCase();

                let groupMatch = true;
                if (activeGroupFilter === 'alert') {
                    groupMatch = optGroup === 'alert';
                } else if (activeGroupFilter === 'wip') {
                    groupMatch = optGroup === 'wip_and_recipe';
                }

                const textMatch = !query || searchText.includes(query);
                opt.hidden = !(groupMatch && textMatch);
            });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyDropdownFilter);
    }

    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeGroupFilter = btn.getAttribute('data-filter-group') || 'all';
            filterButtons.forEach(function (b) {
                b.classList.remove('active', 'btn-dark');
                if (!b.className.includes('btn-outline-')) {
                    b.classList.add('btn-outline-secondary');
                }
            });
            btn.classList.add('active');
            applyDropdownFilter();
        });
    });

    function addNewRow() {
        const rows = tbody.querySelectorAll('.production-item-row');
        const nextIndex = rows.length;
        const templateRow = rows[0].cloneNode(true);

        templateRow.querySelectorAll('.sipastok-ss-container').forEach(function (el) {
            el.remove();
        });

        templateRow.querySelectorAll('select, input').forEach(function (field) {
            const name = field.getAttribute('name') || '';
            field.setAttribute('name', name.replace(/items\[\d+\]/, 'items[' + nextIndex + ']'));
            field.value = '';
        });

        tbody.appendChild(templateRow);
        const clonedInfo = templateRow.querySelector('.production-item-info');
        if (clonedInfo) {
            clonedInfo.textContent = '';
        }
        bindRowEvents(templateRow);
        applyDropdownFilter();
        return templateRow;
    }

    if (addRowBtn) {
        addRowBtn.addEventListener('click', function () {
            addNewRow();
        });
    }
    if (addRowBtnTop) {
        addRowBtnTop.addEventListener('click', function () {
            addNewRow();
        });
    }
    if (resetRowsBtn) {
        resetRowsBtn.addEventListener('click', function () {
            window.SipastokItemForm.resetAllRows(tbody, '.production-item-row');
        });
    }

    function pickItemIntoForm(itemId, unit, qty, note) {
        const rows = Array.from(tbody.querySelectorAll('.production-item-row'));

        // Cek apakah item sudah ada di salah satu baris
        let targetRow = rows.find(function (row) {
            const sel = row.querySelector('.production-item-select');
            return sel && String(sel.value) === String(itemId);
        });

        // Jika belum ada, cari baris pertama yang kosong
        if (!targetRow) {
            targetRow = rows.find(function (row) {
                const sel = row.querySelector('.production-item-select');
                return sel && !sel.value;
            });
        }

        // Jika semua baris penuh, tambahkan baris baru
        if (!targetRow) {
            targetRow = addNewRow();
        }

        const select = targetRow.querySelector('.production-item-select');
        const qtyInput = targetRow.querySelector('.production-qty-input');
        const unitInput = targetRow.querySelector('.production-unit-input');
        const noteInput = targetRow.querySelector('.production-note-input');

        if (select) {
            select.value = String(itemId);
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (unitInput && unit) {
            unitInput.value = unit;
        }
        if (qtyInput && (!qtyInput.value || Number(qtyInput.value) <= 0) && qty) {
            qtyInput.value = qty;
        }
        if (noteInput && !noteInput.value && note) {
            noteInput.value = note;
        }

        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (qtyInput) {
            qtyInput.focus();
        }
    }

    document.querySelectorAll('.quick-pick-item-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pickItemIntoForm(
                btn.getAttribute('data-item-id'),
                btn.getAttribute('data-unit'),
                btn.getAttribute('data-qty'),
                btn.getAttribute('data-note')
            );
        });
    });

    if (fillAllAlertsBtn) {
        fillAllAlertsBtn.addEventListener('click', function () {
            document.querySelectorAll('#openingAlertsGrid .quick-pick-item-btn').forEach(function (btn) {
                pickItemIntoForm(
                    btn.getAttribute('data-item-id'),
                    btn.getAttribute('data-unit'),
                    btn.getAttribute('data-qty'),
                    btn.getAttribute('data-note')
                );
            });
        });
    }

    if (toggleAllAlertsBtn) {
        let expanded = false;
        toggleAllAlertsBtn.addEventListener('click', function () {
            expanded = !expanded;
            document.querySelectorAll('.extra-alert-card').forEach(function (card) {
                card.classList.toggle('d-none', !expanded);
            });
            toggleAllAlertsBtn.textContent = expanded
                ? 'Sembunyikan Sebagian'
                : 'Lihat Semua ({{ $openingAlerts->count() }})';
        });
    }
})();
</script>
@endsection
