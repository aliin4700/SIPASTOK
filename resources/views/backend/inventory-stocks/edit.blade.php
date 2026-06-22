@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <div class="alert alert-info">
            {{ $stock->outlet->name ?? '-' }} / {{ $stock->item->name ?? '-' }}
        </div>
        <form action="{{ route('backend.inventory-stocks.update', $stock) }}" method="post">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="number" step="0.01" min="0" name="current_stock" value="{{ old('current_stock', $stock->current_stock) }}" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Reserved Stock</label>
                        <input type="number" step="0.01" min="0" name="reserved_stock" value="{{ old('reserved_stock', $stock->reserved_stock) }}" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Available Stock</label>
                        <input type="number" step="0.01" min="0" name="available_stock" value="{{ old('available_stock', $stock->available_stock) }}" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Satuan</label>
                        <input type="text" name="unit" value="{{ old('unit', $stock->unit) }}" class="form-control">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('backend.inventory-stocks.index') }}" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>
@endsection
