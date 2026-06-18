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
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h1 class="h3 mb-1">Riwayat Pesanan</h1>
                <p class="text-muted mb-0">Pantau pesanan BreadLab yang pernah Anda buat.</p>
            </div>
            <a href="{{ route('produk.all') }}" class="btn btn-bread">Belanja Lagi</a>
        </div>

        <div class="panel p-3 p-lg-4">
            @forelse($orders as $order)
            <article class="border-bottom py-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <div class="fw-bold">Pesanan #{{ $order->id }}</div>
                        <div class="text-muted small">{{ $order->created_at->format('d M Y H:i') }}</div>
                        <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $order->transaction_method ?? 'shipping')) }} &middot; {{ ucwords(str_replace('_', ' ', $order->payment_method ?? '-')) }}</div>
                        <span class="badge text-bg-warning mt-2">{{ str_replace('_', ' ', $order->status) }}</span>
                    </div>
                    <div class="text-lg-end">
                        <div class="fw-bold">Rp {{ number_format($order->total_price, 0, ',', '.') }}</div>
                        <div class="text-muted small">{{ $order->items->sum('qty') }} item</div>
                    </div>
                </div>
                <div class="mt-3">
                    @foreach($order->items as $item)
                    <div class="d-flex justify-content-between small">
                        <span>{{ $item->product->name ?? 'Produk' }} x {{ $item->qty }}</span>
                        <span>Rp {{ number_format($item->price * $item->qty, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
            </article>
            @empty
            <div class="text-center py-5">
                <h2 class="h5">Belum ada pesanan</h2>
                <p class="text-muted">Mulai belanja dari katalog BreadLab.</p>
                <a href="{{ route('produk.all') }}" class="btn btn-bread">Buka Katalog</a>
            </div>
            @endforelse

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
