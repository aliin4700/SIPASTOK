@csrf
@if($item->exists)
    @method('PUT')
@endif

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Nama Item</label>
            <input type="text" name="name" value="{{ old('name', $item->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Kode</label>
            <input type="text" name="code" value="{{ old('code', $item->code) }}" class="form-control @error('code') is-invalid @enderror">
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Status</label>
            <select name="is_active" class="form-control">
                <option value="1" @selected(old('is_active', $item->is_active) == 1)>Aktif</option>
                <option value="0" @selected(old('is_active', $item->is_active) == 0)>Nonaktif</option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Divisi</label>
            <select name="division_id" class="form-control">
                <option value="">Umum</option>
                @foreach($divisions as $division)
                    <option value="{{ $division->id }}" @selected(old('division_id', $item->division_id) == $division->id)>{{ $division->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Tipe Item</label>
            <select name="item_type" class="form-control @error('item_type') is-invalid @enderror" required>
                @foreach(['raw_material' => 'Raw Material', 'wip' => 'WIP', 'finished_goods' => 'Finished Goods', 'packaging' => 'Packaging'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('item_type', $item->item_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('item_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Satuan Dasar</label>
            <select name="base_unit" class="form-control @error('base_unit') is-invalid @enderror" required>
                <option value="">Pilih Satuan</option>
                @foreach($units as $unit)
                    <option value="{{ $unit }}" @selected(old('base_unit', $item->base_unit) === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
            @error('base_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Purchase Unit</label>
            <select name="purchase_unit" class="form-control @error('purchase_unit') is-invalid @enderror">
                <option value="">Pilih Satuan</option>
                @foreach($units as $unit)
                    <option value="{{ $unit }}" @selected(old('purchase_unit', $item->purchase_unit) === $unit)>{{ $unit }}</option>
                @endforeach
            </select>
            @error('purchase_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Konversi</label>
            <input type="number" step="0.0001" min="0" name="conversion_value" value="{{ old('conversion_value', $item->conversion_value) }}" class="form-control">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Minimum Stock</label>
            <input type="number" step="0.01" min="0" name="min_stock" value="{{ old('min_stock', $item->min_stock ?? 0) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Maximum Stock</label>
            <input type="number" step="0.01" min="0" name="max_stock" value="{{ old('max_stock', $item->max_stock) }}" class="form-control">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Lead Time (hari)</label>
            <input type="number" min="0" name="lead_time_days" value="{{ old('lead_time_days', $item->lead_time_days ?? 0) }}" class="form-control">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Supplier Default</label>
            <input type="text" name="default_supplier_name" value="{{ old('default_supplier_name', $item->default_supplier_name) }}" class="form-control">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Harga Beli Terakhir</label>
            <input type="number" step="0.01" min="0" name="last_purchase_price" value="{{ old('last_purchase_price', $item->last_purchase_price) }}" class="form-control">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Catatan</label>
    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $item->notes) }}</textarea>
</div>

<button type="submit" class="btn btn-primary">Simpan</button>
<a href="{{ route('backend.inventory-items.index') }}" class="btn btn-secondary">Kembali</a>
