<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} - Fresh Bakery Store</title>
    <link rel="icon" href="{{ asset('breadlab/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bread-primary: #8B4513;
            --bread-secondary: #F5DEB3;
            --bread-accent: #D2691E;
            --bread-ink: #24160f;
        }

        body {
            background: #fffaf3;
            color: var(--bread-ink);
            font-family: Poppins, sans-serif;
        }

        .hero {
            min-height: 620px;
            background:
                linear-gradient(90deg, rgba(36, 22, 15, .82), rgba(36, 22, 15, .38)),
                url("{{ asset('breadlab/hero-banner.jpg') }}") center / cover no-repeat;
            color: #fff;
            display: flex;
            align-items: center;
        }

        .btn-bread {
            background: var(--bread-primary);
            border-color: var(--bread-primary);
            color: #fff;
            border-radius: 12px;
            font-weight: 700;
        }

        .btn-bread:hover {
            background: var(--bread-accent);
            border-color: var(--bread-accent);
            color: #fff;
        }

        .section-title {
            font-weight: 800;
            letter-spacing: 0;
        }

        .feature-card,
        .product-card,
        .promo-band {
            background: #fff;
            border: 1px solid #f0e4d3;
            border-radius: 8px;
            box-shadow: 0 14px 34px rgba(139, 69, 19, .08);
        }

        .product-img {
            aspect-ratio: 4 / 3;
            border-radius: 8px 8px 0 0;
            object-fit: cover;
            width: 100%;
        }

        .category-chip {
            background: var(--bread-secondary);
            border-radius: 999px;
            color: var(--bread-primary);
            display: inline-flex;
            font-weight: 700;
            padding: 10px 16px;
            text-decoration: none;
        }

        .cream-band {
            background: #f9ead2;
        }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <header class="hero">
        <div class="container py-5">
            <div class="col-lg-7">
                <span class="badge rounded-pill text-bg-warning mb-3">Fresh from the oven</span>
                <h1 class="display-3 fw-bold mb-3">BreadLab Bakery</h1>
                <p class="lead mb-4">Roti, pastry, cake, dan cookies segar untuk momen harian yang terasa lebih hangat.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('produk.all') }}" class="btn btn-bread btn-lg px-4">Belanja Produk</a>
                    <a href="#kategori" class="btn btn-outline-light btn-lg px-4">Lihat Kategori</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="container py-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card h-100 p-4">
                        <h2 class="h5 fw-bold">Roti Segar</h2>
                        <p class="text-muted mb-0">Dipanggang harian dengan bahan pilihan dan tekstur lembut.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100 p-4">
                        <h2 class="h5 fw-bold">Pesan Online</h2>
                        <p class="text-muted mb-0">Pilih produk, masukkan keranjang, lalu checkout dari rumah.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card h-100 p-4">
                        <h2 class="h5 fw-bold">Siap Dikirim</h2>
                        <p class="text-muted mb-0">Status pesanan bisa dipantau setelah checkout selesai.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="kategori" class="cream-band py-5">
            <div class="container">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-1">Kategori</p>
                        <h2 class="section-title h1 mb-0">Pilihan Bakery</h2>
                    </div>
                    <a href="{{ route('produk.all') }}" class="btn btn-outline-dark">Semua Produk</a>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    @forelse($categories as $category)
                    <a class="category-chip" href="{{ route('produk.kategori', $category->id) }}">{{ $category->nama_kategori }}</a>
                    @empty
                    @foreach(['Bread', 'Cake', 'Pastry', 'Cookies'] as $category)
                    <a class="category-chip" href="{{ route('produk.all') }}">{{ $category }}</a>
                    @endforeach
                    @endforelse
                </div>
            </div>
        </section>

        <section class="container py-5">
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <p class="text-uppercase fw-bold text-muted mb-1">Best Seller</p>
                    <h2 class="section-title h1 mb-0">Produk Favorit</h2>
                </div>
                <a href="{{ route('produk.all') }}" class="btn btn-bread">Buka Katalog</a>
            </div>

            <div class="row g-4">
                @forelse($products as $product)
                <div class="col-md-6 col-xl-4">
                    <article class="product-card h-100 overflow-hidden">
                        <a href="{{ route('produk.detail', $product->slug) }}">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-img">
                        </a>
                        <div class="p-4">
                            <span class="category-chip mb-3">{{ $product->category->nama_kategori ?? 'BreadLab' }}</span>
                            <h3 class="h5 fw-bold">{{ $product->name }}</h3>
                            <p class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 90) }}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                                <a href="{{ route('produk.detail', $product->slug) }}" class="btn btn-sm btn-bread">Detail</a>
                            </div>
                        </div>
                    </article>
                </div>
                @empty
                <div class="col-12">
                    <div class="product-card p-5 text-center">
                        <h3 class="h5 fw-bold">Katalog BreadLab siap diisi</h3>
                        <p class="text-muted mb-0">Tambahkan produk bakery dari dashboard admin agar tampil di halaman ini.</p>
                    </div>
                </div>
                @endforelse
            </div>
        </section>

        <section class="container pb-5">
            <div class="promo-band p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
                <div>
                    <p class="text-uppercase fw-bold text-muted mb-1">Promo</p>
                    <h2 class="h1 section-title mb-2">Paket sarapan hangat untuk keluarga</h2>
                    <p class="text-muted mb-0">Pilih roti favorit, tambahkan pastry, lalu checkout dalam satu alur cepat.</p>
                </div>
                <a href="{{ route('produk.all') }}" class="btn btn-bread btn-lg px-4">Pesan Sekarang</a>
            </div>
        </section>
    </main>

    <footer class="bg-dark text-white py-4">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-3">
            <span>&copy; {{ date('Y') }} BreadLab Bakery</span>
            <span>Fresh bakery, crafted with care.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
