<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} - BreadLab</title>
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

        .filter-panel,
        .product-card {
            background: #fff;
            border: 1px solid #f0e4d3;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(139, 69, 19, .08);
        }

        .product-image {
            aspect-ratio: 4 / 3;
            background: #f7ead8;
            border-radius: 8px 8px 0 0;
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
            font-size: 12px;
            padding: 5px 10px;
        }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-4">
        <form action="{{ route('product.index') }}" method="get" class="d-flex mb-4">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-lg" placeholder="Cari roti, cake, pastry, cookies...">
            <button class="btn btn-bread ms-2 px-4" type="submit">Cari</button>
        </form>

        <div class="row g-4">
            <aside class="col-lg-3">
                <form action="{{ route('product.index') }}" method="get" class="filter-panel p-3">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">Kategori</h2>
                        <a href="{{ route('product.index') }}" class="small text-decoration-none">Reset</a>
                    </div>

                    @foreach($categories as $category)
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="category" value="{{ $category->id }}" id="category-{{ $category->id }}" @checked((string) ($selectedCategory->id ?? request('category')) === (string) $category->id)>
                        <label class="form-check-label" for="category-{{ $category->id }}">{{ $category->nama_kategori }}</label>
                    </div>
                    @endforeach

                    <hr>

                    <h2 class="h6 mb-3">Harga</h2>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" class="form-control" placeholder="Min">
                        </div>
                        <div class="col-6">
                            <input type="number" name="max_price" value="{{ request('max_price') }}" class="form-control" placeholder="Max">
                        </div>
                    </div>

                    <button class="btn btn-bread w-100 mt-3" type="submit">Terapkan</button>
                </form>
            </aside>

            <section class="col-lg-9">
                <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-2">
                    <div>
                        <h1 class="h3 mb-1">Produk {{ $selectedCategory->name ?? 'Kategori' }}</h1>
                        <p class="text-muted mb-0">{{ $products->total() }} produk ditemukan</p>
                    </div>
                </div>

                <div class="row g-4">
                    @forelse($products as $product)
                    <div class="col-sm-6 col-xl-4">
                        <article class="product-card h-100 overflow-hidden">
                            <a href="{{ route('product.detail', $product->slug) }}">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-image">
                            </a>
                            <div class="p-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <span class="category-pill">{{ $product->category->nama_kategori ?? 'BreadLab' }}</span>
                                    <span class="text-muted small">Stok {{ $product->stock }}</span>
                                </div>
                                <h2 class="h6 mb-2">
                                    <a href="{{ route('product.detail', $product->slug) }}" class="text-dark text-decoration-none">{{ $product->name }}</a>
                                </h2>
                                <p class="small text-muted mb-3">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 76) }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                                    <div class="d-flex gap-2">
                                        @auth
                                        @if((string) Auth::user()->role === 'customer')
                                        <form action="{{ route('cart.add', $product) }}" method="post">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-dark" type="submit" @disabled($product->stock < 1)>Cart</button>
                                        </form>
                                        @endif
                                        @endauth
                                        <a href="{{ route('product.detail', $product->slug) }}" class="btn btn-sm btn-bread">Detail</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="filter-panel p-5 text-center">
                            <h2 class="h5">Produk belum tersedia</h2>
                            <p class="text-muted mb-0">Tambahkan produk dari dashboard admin untuk mengisi katalog.</p>
                        </div>
                    </div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
