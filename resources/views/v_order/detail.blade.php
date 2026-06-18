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
        .status-badge { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 700; padding: 6px 10px; text-transform: capitalize; }
        .status-pending, .status-waiting_payment { background: #fff3cd; color: #8a6400; }
        .status-paid, .status-processing, .status-ready_for_pickup, .status-delivering, .status-shipped, .status-completed { background: #d1e7dd; color: #0f5132; }
        .status-cancelled, .status-failed, .status-rejected { background: #f8d7da; color: #842029; }
        .order-product-img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <a href="{{ route('order.history') }}" class="text-decoration-none">&larr; Kembali ke riwayat</a>
                <h1 class="h3 mt-3 mb-1">{{ $order->order_number }}</h1>
                <p class="text-muted mb-0">{{ $order->created_at->format('d M Y H:i') }}</p>
            </div>
            <div>
                <span class="status-badge status-{{ $order->paymentStatus() }}">Payment: {{ str_replace('_', ' ', $order->paymentStatus()) }}</span>
                <span class="status-badge status-{{ $order->normalizedStatus() }} ms-2">Order: {{ str_replace('_', ' ', $order->normalizedStatus()) }}</span>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-4">
            <section class="col-lg-8">
                <div class="panel p-4 mb-4">
                    <h2 class="h5 mb-3">Produk Pesanan</h2>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                    <th>Harga</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            @if($item->product)
                                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="order-product-img">
                                            @endif
                                            <span class="fw-semibold">{{ $item->product_name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel p-4">
                    <h2 class="h5 mb-3">Informasi Pengiriman</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Tipe</dt>
                        <dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $order->shipment->shipping_type ?? $order->transaction_method)) }}</dd>
                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $order->shipment->delivery_address ?? $order->address ?? '-' }}</dd>
                        <dt class="col-sm-4">Kode Pos</dt>
                        <dd class="col-sm-8">{{ $order->postal_code ?? '-' }}</dd>
                        <dt class="col-sm-4">Kurir</dt>
                        <dd class="col-sm-8">{{ $order->shipment->courier ?? '-' }}</dd>
                        <dt class="col-sm-4">Service</dt>
                        <dd class="col-sm-8">{{ $order->shipment->service ?? '-' }}</dd>
                        <dt class="col-sm-4">Tracking</dt>
                        <dd class="col-sm-8">{{ $order->shipment->tracking_number ?? '-' }}</dd>
                        <dt class="col-sm-4">Catatan</dt>
                        <dd class="col-sm-8">{{ $order->shipment->delivery_note ?? $order->notes ?? '-' }}</dd>
                    </dl>
                </div>
            </section>

            <aside class="col-lg-4">
                <div class="panel p-4 mb-4">
                    <h2 class="h5 mb-3">Ringkasan</h2>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <strong>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Ongkir</span>
                        <strong>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Berat</span>
                        <strong>{{ number_format($order->total_weight, 0, ',', '.') }} gram</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-3 border-top">
                        <span>Total</span>
                        <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="panel p-4">
                    <h2 class="h5 mb-3">Pembayaran</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Metode</dt>
                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $order->payment->payment_method ?? $order->payment_method)) }}</dd>
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">{{ str_replace('_', ' ', $order->paymentStatus()) }}</dd>
                    </dl>
                    @if($order->payment_method === 'bank_transfer')
                    <button class="btn btn-outline-dark w-100 mt-3" type="button" disabled>Upload Bukti Transfer</button>
                    @endif
                </div>
            </aside>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
