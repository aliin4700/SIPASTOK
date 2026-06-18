@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        padding: 24px;
    }

    .breadlab-preview {
        width: 100%;
        max-width: 280px;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border: 1px solid #eef0f4;
        border-radius: 8px;
        background: #f8fafc;
    }

    .breadlab-photo-thumb {
        width: 96px;
        height: 96px;
        object-fit: cover;
        border-radius: 8px;
    }
</style>

<div class="breadlab-admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ $judul }}</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('backend.product.edit', $product) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('backend.product.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-4 mb-4">
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="breadlab-preview">
        </div>
        <div class="col-md-8">
            <dl class="row">
                <dt class="col-sm-4">Nama Produk</dt>
                <dd class="col-sm-8">{{ $product->name }}</dd>
                <dt class="col-sm-4">Kategori</dt>
                <dd class="col-sm-8">{{ $product->category->name ?? '-' }}</dd>
                <dt class="col-sm-4">Harga</dt>
                <dd class="col-sm-8">Rp {{ number_format($product->price, 0, ',', '.') }}</dd>
                <dt class="col-sm-4">Stok</dt>
                <dd class="col-sm-8">{{ $product->stock }}</dd>
                <dt class="col-sm-4">Berat</dt>
                <dd class="col-sm-8">{{ $product->weight }} gram</dd>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">{{ ucfirst($product->status) }}</dd>
                <dt class="col-sm-4">Detail</dt>
                <dd class="col-sm-8">{{ $product->description ?: '-' }}</dd>
            </dl>
        </div>
    </div>

    <hr>

    <h4 class="h5 mb-3">Foto Tambahan</h4>
    <div class="d-flex flex-wrap gap-3 mb-4">
        @forelse($product->photos as $photo)
        <div>
            <img src="{{ asset('storage/' . $photo->photo) }}" alt="{{ $product->name }}" class="breadlab-photo-thumb mb-2">
            <form action="{{ route('backend.product.photo.destroy', $photo) }}" method="post">
                @csrf
                @method('delete')
                <button type="submit" class="btn btn-danger btn-sm show_confirm" data-konf-delete="foto produk">Hapus</button>
            </form>
        </div>
        @empty
        <p class="text-muted mb-0">Belum ada foto tambahan.</p>
        @endforelse
    </div>

    <form action="{{ route('backend.product.photo.store', $product) }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label>Tambah Foto</label>
            <input type="file" name="photos[]" multiple class="form-control @error('photos') is-invalid @enderror @error('photos.*') is-invalid @enderror">
            @error('photos')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @error('photos.*')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary">Simpan Foto</button>
    </form>
</div>
@endsection
