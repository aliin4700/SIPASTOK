@extends('backend.v_layouts.app')

@section('content')
<style>
    .breadlab-admin-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        padding: 24px;
    }

    .breadlab-product-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 8px;
    }

    .breadlab-badge {
        background: #17bfe5;
        border-radius: 999px;
        color: #fff;
        display: inline-block;
        font-size: 12px;
        padding: 5px 10px;
    }
</style>

<div class="breadlab-admin-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">{{ $judul }}</h3>
        <a href="{{ route('backend.produk.create') }}" class="btn btn-primary">Tambah Produk</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="thead-light">
                <tr>
                    <th>No</th>
                    <th>Foto</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Detail</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>{{ $products->firstItem() + $loop->index }}</td>
                    <td><img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="breadlab-product-thumb"></td>
                    <td class="font-weight-bold">{{ $product->name }}</td>
                    <td>{{ $product->category->nama_kategori ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 56) }}</td>
                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td><span class="breadlab-badge">{{ $product->stock }}</span></td>
                    <td>
                        <a href="{{ route('backend.produk.edit', $product) }}" class="btn btn-warning btn-sm">Edit</a>
                        <form action="{{ route('backend.produk.destroy', $product) }}" method="post" class="d-inline">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-danger btn-sm show_confirm" data-konf-delete="{{ $product->name }}">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">Belum ada produk.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $products->links() }}
    </div>
</div>
@endsection
