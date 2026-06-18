<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->name }} - BreadLab</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bread-primary: #8B4513;
            --bread-secondary: #F5DEB3;
            --bread-accent: #D2691E;
        }

        body {
            background: #fffaf3;
            color: #24160f;
            font-family: Poppins, sans-serif;
        }

        .detail-card {
            background: #fff;
            border: 1px solid #f0e4d3;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(139, 69, 19, .08);
        }

        .detail-image {
            aspect-ratio: 1 / 1;
            border-radius: 8px;
            object-fit: cover;
            width: 100%;
        }

        .btn-bread {
            background: var(--bread-primary);
            border-color: var(--bread-primary);
            color: #fff;
        }

        .btn-bread:hover {
            background: var(--bread-accent);
            border-color: var(--bread-accent);
            color: #fff;
        }

        .category-pill {
            background: var(--bread-secondary);
            border-radius: 999px;
            color: var(--bread-primary);
            display: inline-block;
            font-size: 13px;
            padding: 6px 12px;
        }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <a href="{{ route('produk.all') }}" class="text-decoration-none">&larr; Kembali ke katalog</a>

        <div class="detail-card p-4 p-lg-5 mt-3">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="detail-image">
                </div>
                <div class="col-lg-6">
                    <span class="category-pill mb-3">{{ $product->category->nama_kategori ?? 'BreadLab' }}</span>
                    <h1 class="display-6 fw-bold">{{ $product->name }}</h1>
                    <p class="lead fw-semibold text-danger">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                    <p class="text-muted">Stok tersedia: {{ $product->stock }}</p>
                    <div class="mb-4">{!! nl2br(e($product->description)) !!}</div>

                    @auth
                    @if((string) Auth::user()->role === 'customer')
                    <form action="{{ route('cart.store', $product) }}" method="post" class="d-flex gap-2">
                        @csrf
                        <input type="number" name="qty" value="1" min="1" max="{{ max(1, $product->stock) }}" class="form-control form-control-lg" style="max-width: 110px;">
                        <button type="submit" class="btn btn-bread btn-lg" @disabled($product->stock < 1)>Tambah ke Keranjang</button>
                    </form>
                    @else
                    <a href="{{ route('backend.produk.index') }}" class="btn btn-bread btn-lg">Kelola Produk</a>
                    @endif
                    @else
                    <a href="{{ route('login') }}" class="btn btn-bread btn-lg">Login untuk Belanja</a>
                    @endauth
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
