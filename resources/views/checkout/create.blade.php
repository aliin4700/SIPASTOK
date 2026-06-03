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
        .btn-bread { background: #8B4513; border-color: #8B4513; color: #fff; }
        .btn-bread:hover { background: #D2691E; border-color: #D2691E; color: #fff; }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <div class="mb-4">
            <a href="{{ route('cart.index') }}" class="text-decoration-none">&larr; Kembali ke keranjang</a>
            <h1 class="h3 mt-3 mb-1">Checkout</h1>
            <p class="text-muted mb-0">Isi alamat pengiriman dan pilih metode pembayaran.</p>
        </div>

        <form action="{{ route('checkout.store') }}" method="post">
            @csrf
            <div class="row g-4">
                <section class="col-lg-8">
                    <div class="panel p-4 mb-4">
                        <h2 class="h5 mb-3">Alamat Pengiriman</h2>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Alamat Lengkap</label>
                            <textarea name="shipping_address" id="shipping_address" rows="5" class="form-control @error('shipping_address') is-invalid @enderror" required>{{ old('shipping_address') }}</textarea>
                            @error('shipping_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label for="shipping_postal_code" class="form-label">Kode Pos</label>
                            <input type="text" name="shipping_postal_code" id="shipping_postal_code" value="{{ old('shipping_postal_code') }}" class="form-control @error('shipping_postal_code') is-invalid @enderror" required>
                            @error('shipping_postal_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="panel p-4">
                        <h2 class="h5 mb-3">Metode Pembayaran</h2>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="midtrans" value="midtrans" @checked(old('payment_method', 'midtrans') === 'midtrans')>
                            <label class="form-check-label" for="midtrans">Midtrans</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer" @checked(old('payment_method') === 'bank_transfer')>
                            <label class="form-check-label" for="bank_transfer">Transfer Bank</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" @checked(old('payment_method') === 'cod')>
                            <label class="form-check-label" for="cod">Bayar di Tempat</label>
                        </div>
                        @error('payment_method')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </section>

                <aside class="col-lg-4">
                    <div class="panel p-4">
                        <h2 class="h5 mb-3">Ringkasan Pesanan</h2>
                        @foreach($cart->items as $item)
                        <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ $item->product->name }}</div>
                                <small class="text-muted">{{ $item->qty }} x Rp {{ number_format($item->product->price, 0, ',', '.') }}</small>
                            </div>
                            <strong>Rp {{ number_format($item->subtotal(), 0, ',', '.') }}</strong>
                        </div>
                        @endforeach

                        <div class="d-flex justify-content-between mt-3 mb-4">
                            <span>Total</span>
                            <strong>Rp {{ number_format($cart->total(), 0, ',', '.') }}</strong>
                        </div>
                        <button type="submit" class="btn btn-bread w-100">Buat Pesanan</button>
                    </div>
                </aside>
            </div>
        </form>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
