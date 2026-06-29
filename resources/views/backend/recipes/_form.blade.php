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

<div class="alert alert-warning">Recipe/BOM saat ini hanya master data. Belum mengurangi stok dan belum terhubung ke POS.</div>

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
    <div class="col-md-4">
        <div class="form-group">
            <label>Linked Product</label>
            <select name="product_id" class="form-control">
                <option value="">Tanpa Product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(old('product_id', $recipe->product_id) == $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Output Inventory Item</label>
            <select name="output_inventory_item_id" class="form-control">
                <option value="">Tanpa Output Item</option>
                @foreach($inventoryItems as $item)
                    <option value="{{ $item->id }}" @selected(old('output_inventory_item_id', $recipe->output_inventory_item_id) == $item->id)>{{ $item->name }} ({{ $item->base_unit }})</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-2">
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
    <div class="col-md-2">
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

<h6 class="mt-4">Material / BOM</h6>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th style="min-width:240px">Inventory Item</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Waste %</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < 15; $i++)
                @php($row = $rows[$i] ?? [])
                <tr>
                    <td>
                        <select name="items[{{ $i }}][inventory_item_id]" class="form-control">
                            <option value="">Pilih Item</option>
                            @foreach($inventoryItems as $item)
                                <option value="{{ $item->id }}" @selected(($row['inventory_item_id'] ?? null) == $item->id)>{{ $item->name }} ({{ $item->base_unit }})</option>
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
                </tr>
            @endfor
        </tbody>
    </table>
</div>

<button class="btn btn-primary">Simpan</button>
<a href="{{ route('backend.recipes.index') }}" class="btn btn-secondary">Kembali</a>
