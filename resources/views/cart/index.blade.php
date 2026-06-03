<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} - BreadLab</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fffaf3; color: #24160f; font-family: Poppins, sans-serif; }
        .panel { background: #fff; border: 1px solid #f0e4d3; border-radius: 8px; box-shadow: 0 10px 24px rgba(139, 69, 19, .08); }
        .item-img { width: 72px; height: 72px; border-radius: 8px; object-fit: cover; }
        .btn-bread { background: #8B4513; border-color: #8B4513; color: #fff; }
        .btn-bread:hover { background: #D2691E; border-color: #D2691E; color: #fff; }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Keranjang</h1>
                <p class="text-muted mb-0">Periksa produk sebelum checkout.</p>
            </div>
            <a href="{{ route('produk.all') }}" class="btn btn-outline-dark">Lanjut Belanja</a>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-4">
            <section class="col-lg-8">
                <div class="panel p-3">
                    @forelse($cart->items as $item)
                    <div class="d-flex gap-3 align-items-center border-bottom py-3">
                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="item-img">
                        <div class="flex-grow-1">
                            <h2 class="h6 mb-1">{{ $item->product->name }}</h2>
                            <p class="text-muted small mb-2">{{ $item->product->category->nama_kategori ?? 'BreadLab' }}</p>
                            <strong>Rp {{ number_format($item->product->price, 0, ',', '.') }}</strong>
                        </div>
                        <form action="{{ route('cart.update', $item) }}" method="post" class="d-flex align-items-center gap-2">
                            @csrf
                            @method('patch')
                            <input type="number" name="qty" value="{{ $item->qty }}" min="1" max="{{ max(1, $item->product->stock) }}" class="form-control" style="width: 86px;">
                            <button class="btn btn-outline-secondary btn-sm" type="submit">Update</button>
                        </form>
                        <div class="text-end" style="min-width: 130px;">
                            <div class="fw-semibold">Rp {{ number_format($item->subtotal(), 0, ',', '.') }}</div>
                            <form action="{{ route('cart.destroy', $item) }}" method="post" class="mt-2">
                                @csrf
                                @method('delete')
                                <button class="btn btn-link text-danger p-0" type="submit">Hapus</button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <h2 class="h5">Keranjang masih kosong</h2>
                        <p class="text-muted">Pilih produk dari katalog untuk mulai checkout.</p>
                        <a href="{{ route('produk.all') }}" class="btn btn-bread">Buka Katalog</a>
                    </div>
                    @endforelse
                </div>
            </section>

            <aside class="col-lg-4">
                <div class="panel p-4">
                    <h2 class="h5 mb-3">Ringkasan</h2>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Item</span>
                        <strong>{{ $cart->items->sum('qty') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span>Total Harga</span>
                        <strong>Rp {{ number_format($cart->total(), 0, ',', '.') }}</strong>
                    </div>
                    <a href="{{ route('checkout.create') }}" class="btn btn-bread w-100 @if($cart->items->isEmpty()) disabled @endif">Checkout</a>
                </div>
            </aside>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
