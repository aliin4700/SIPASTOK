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
            <a href="{{ route('product.index') }}" class="btn btn-bread">Belanja Lagi</a>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Tabel riwayat hanya berisi order milik customer yang sedang login. --}}
        <div class="panel p-3 p-lg-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Tanggal</th>
                            <th>Transaksi</th>
                            <th>Pembayaran</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="fw-bold">{{ $order->order_number }}</td>
                            <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $order->transaction_method)) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</td>
                            <td><span class="status-badge status-{{ $order->paymentStatus() }}">{{ str_replace('_', ' ', $order->paymentStatus()) }}</span></td>
                            <td><span class="status-badge status-{{ $order->normalizedStatus() }}">{{ str_replace('_', ' ', $order->normalizedStatus()) }}</span></td>
                            <td>Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                            <td><a href="{{ route('order.detail', $order) }}" class="btn btn-sm btn-bread">Detail</a></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Belum ada pesanan. <a href="{{ route('product.index') }}">Buka katalog</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
