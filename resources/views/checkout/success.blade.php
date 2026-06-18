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
        <div class="panel p-4 p-lg-5">
            <div class="row g-4">
                <div class="col-lg-7">
                    <span class="badge text-bg-warning mb-3">{{ str_replace('_', ' ', $order->status) }}</span>
                    <h1 class="h3 mb-2">Pesanan berhasil dibuat</h1>
                    <p class="text-muted">Pesanan #{{ $order->id }} masuk ke alur pembayaran dan menunggu konfirmasi.</p>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Metode Transaksi</dt>
                        <dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $order->transaction_method ?? 'shipping')) }}</dd>
                        <dt class="col-sm-4">Metode Bayar</dt>
                        <dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</dd>
                        <dt class="col-sm-4">Referensi</dt>
                        <dd class="col-sm-8">{{ $order->payment_reference ?? '-' }}</dd>
                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $order->shipping_address ?? '-' }}</dd>
                        <dt class="col-sm-4">Kota</dt>
                        <dd class="col-sm-8">{{ $order->shipping_city ?? '-' }}, {{ $order->shipping_province ?? '-' }}</dd>
                        <dt class="col-sm-4">Kode Pos</dt>
                        <dd class="col-sm-8">{{ $order->shipping_postal_code ?? '-' }}</dd>
                        <dt class="col-sm-4">Kurir</dt>
                        <dd class="col-sm-8">{{ $order->shipping_courier ?? '-' }} {{ $order->shipping_service ? '- ' . $order->shipping_service : '' }}</dd>
                        <dt class="col-sm-4">Estimasi</dt>
                        <dd class="col-sm-8">{{ $order->shipping_estimation ? $order->shipping_estimation . ' hari' : '-' }}</dd>
                    </dl>
                </div>

                <div class="col-lg-5">
                    <h2 class="h5 mb-3">Detail Pesanan</h2>
                    @php($productTotal = 0)
                    @foreach($order->items as $item)
                    @php($productTotal += $item->price * $item->qty)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ $item->product->name }} x {{ $item->qty }}</span>
                        <strong>Rp {{ number_format($item->price * $item->qty, 0, ',', '.') }}</strong>
                    </div>
                    @endforeach
                    <div class="d-flex justify-content-between mt-3">
                        <span>Subtotal Produk</span>
                        <strong>Rp {{ number_format($productTotal, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Ongkir</span>
                        <strong>Rp {{ number_format($order->shipping_cost ?? 0, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <span>Total Bayar</span>
                        <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <a href="{{ route('product.index') }}" class="btn btn-bread">Belanja Lagi</a>
                <a href="{{ route('beranda') }}" class="btn btn-outline-dark">Beranda</a>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
