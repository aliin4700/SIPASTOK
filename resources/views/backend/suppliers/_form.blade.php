@csrf
@if($supplier->exists)
    @method('PUT')
@endif

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Nama Supplier</label>
            <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="form-control @error('name') is-invalid @enderror" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Kode</label>
            <input type="text" name="code" value="{{ old('code', $supplier->code) }}" class="form-control @error('code') is-invalid @enderror">
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Status</label>
            <select name="is_active" class="form-control">
                <option value="1" @selected(old('is_active', $supplier->is_active) == 1)>Aktif</option>
                <option value="0" @selected(old('is_active', $supplier->is_active) == 0)>Nonaktif</option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control @error('email') is-invalid @enderror">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label>Alamat</label>
    <textarea name="address" class="form-control" rows="3">{{ old('address', $supplier->address) }}</textarea>
</div>

<div class="form-group">
    <label>Catatan</label>
    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $supplier->notes) }}</textarea>
</div>

<button class="btn btn-primary">Simpan</button>
<a href="{{ route('backend.suppliers.index') }}" class="btn btn-secondary">Kembali</a>
