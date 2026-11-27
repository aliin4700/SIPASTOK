@include('partials.searchable-select-assets')

@csrf
@if($recipe->exists)
    @method('PUT')
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

@php
    $recipeRows = $recipe->exists
        ? $recipe->items->map(fn ($item) => [
            'inventory_item_id' => $item->inventory_item_id,
            'qty' => $item->qty,
            'unit' => $item->unit,
            'waste_percentage' => $item->waste_percentage,
            'notes' => $item->notes,
        ])->values()->all()
        : [];
    $rows = old('items', $recipeRows);
@endphp

<div class="alert alert-info">Recipe/BOM ini digunakan sebagai acuan otomatis pemotongan bahan baku saat Produksi / WIP disetujui.</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Kode Recipe</label>
            <input type="text" name="recipe_code" value="{{ old('recipe_code', $recipe->recipe_code) }}" class="form-control">
        </div>
    </div>
    <div class="col-md-5">
        <div class="form-group">
            <label>Nama Recipe</label>
            <input type="text" name="name" value="{{ old('name', $recipe->name) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label>Output Qty</label>
            <input type="number" step="0.01" min="0.01" name="output_qty" value="{{ old('output_qty', $recipe->output_qty ?? 1) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label>Output Unit</label>
            <select name="output_unit" class="form-control">
                <option value="">Pilih Unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit }}" @selected(old('output_unit', $recipe->output_unit) === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Output Inventory Item (WIP / Hasil Produksi)</label>
            @php
                $wipOutputItems = $inventoryItems->filter(fn ($it) => in_array($it->item_type, ['wip', 'finished_goods'], true));
                $otherOutputItems = $inventoryItems->reject(fn ($it) => in_array($it->item_type, ['wip', 'finished_goods'], true));
            @endphp
            <select name="output_inventory_item_id" class="form-control sipastok-searchable-select">
                <option value="">Tanpa Output Item</option>
                @if($wipOutputItems->isNotEmpty())
                    <optgroup label="Item WIP & Produk Jadi">
                        @foreach($wipOutputItems as $item)
                            <option value="{{ $item->id }}" @selected(old('output_inventory_item_id', $recipe->output_inventory_item_id) == $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </optgroup>
                @endif
                @if($otherOutputItems->isNotEmpty())
                    <optgroup label="Item Inventori Lainnya">
                        @foreach($otherOutputItems as $item)
                            <option value="{{ $item->id }}" @selected(old('output_inventory_item_id', $recipe->output_inventory_item_id) == $item->id)>{{ $item->name }}</option>
                        @endforeach
                    </optgroup>
                @endif
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Divisi</label>
            <select name="division_id" class="form-control">
                <option value="">Tanpa Divisi</option>
                @foreach($divisions as $division)
                    <option value="{{ $division->id }}" @selected(old('division_id', $recipe->division_id) == $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Status</label>
            <select name="is_active" class="form-control">
                <option value="1" @selected(old('is_active', $recipe->is_active) == 1)>Aktif</option>
                <option value="0" @selected(old('is_active', $recipe->is_active) == 0)>Nonaktif</option>
            </select>
        </div>
    </div>
</div>

<div class="form-group">
    <label>Catatan</label>
    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $recipe->notes) }}</textarea>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-2">
    <h6 class="mb-0">Material / BOM</h6>
    <div class="d-flex gap-2">
        <button type="button" id="addRecipeRowBtn" class="btn btn-sm btn-outline-primary">+ Tambah Baris Material</button>
        <button type="button" id="resetRecipeRowsBtn" class="btn btn-sm btn-outline-danger">Reset / Kosongkan Form</button>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="min-width:240px">Inventory Item</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Waste %</th>
                <th>Catatan</th>
                <th class="text-center" style="width:90px">Aksi</th>
            </tr>
        </thead>
        <tbody id="recipeRowsBody">
            @for($i = 0; $i < 15; $i++)
                @php
                    $row = $rows[$i] ?? [];
                @endphp
                <tr class="recipe-item-row">
                    <td>
                        <select name="items[{{ $i }}][inventory_item_id]" class="form-control sipastok-searchable-select">
                            <option value="">-- Pilih Item --</option>
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}" @selected(($row['inventory_item_id'] ?? null) == $item->id)>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.0001" min="0" name="items[{{ $i }}][qty]" value="{{ $row['qty'] ?? '' }}" class="form-control"></td>
                    <td>
                        <select name="items[{{ $i }}][unit]" class="form-control">
                            <option value="">Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit }}" @selected(($row['unit'] ?? '') === $unit)>{{ $unit }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $i }}][waste_percentage]" value="{{ $row['waste_percentage'] ?? 0 }}" class="form-control"></td>
                    <td><input type="text" name="items[{{ $i }}][notes]" value="{{ $row['notes'] ?? '' }}" class="form-control"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-recipe-row-btn">Hapus</button>
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>

<script>
(function () {
    const tbody = document.getElementById('recipeRowsBody');
    if (!tbody) return;
    function bindRow(row) {
        const remBtn = row.querySelector('.remove-recipe-row-btn');
        if (remBtn) {
            remBtn.addEventListener('click', function () {
                window.SipastokItemForm.removeOrClearRow(row, tbody, '.recipe-item-row');
            });
        }
        if (window.SipastokSearchableSelect) window.SipastokSearchableSelect.init(row);
    }
    tbody.querySelectorAll('.recipe-item-row').forEach(bindRow);
    const addBtn = document.getElementById('addRecipeRowBtn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            const clone = tbody.querySelector('.recipe-item-row').cloneNode(true);
            clone.querySelectorAll('.sipastok-ss-container').forEach(function (el) { el.remove(); });
            clone.querySelectorAll('select, input').forEach(function (el) {
                el.value = el.name && el.name.includes('waste_percentage') ? '0' : '';
            });
            tbody.appendChild(clone);
            window.SipastokItemForm.reindexRows(tbody, '.recipe-item-row');
            bindRow(clone);
        });
    }
    const resetBtn = document.getElementById('resetRecipeRowsBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            window.SipastokItemForm.resetAllRows(tbody, '.recipe-item-row');
        });
    }
})();
</script>

<button class="btn btn-primary">Simpan</button>
<a href="{{ route('backend.recipes.index') }}" class="btn btn-secondary">Kembali</a>
