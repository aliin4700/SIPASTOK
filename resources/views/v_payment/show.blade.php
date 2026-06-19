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
        .status-pending, .status-waiting_confirmation { background: #fff3cd; color: #8a6400; }
        .status-paid { background: #d1e7dd; color: #0f5132; }
        .status-rejected, .status-failed, .status-expired, .status-cancelled { background: #f8d7da; color: #842029; }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        <div class="mb-4">
            <a href="{{ route('order.detail', $order) }}" class="text-decoration-none">&larr; Kembali ke pesanan</a>
            <h1 class="h3 mt-3 mb-1">Pembayaran {{ $order->order_number }}</h1>
            <p class="text-muted mb-0">Ringkasan pembayaran pesanan BreadLab.</p>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-4">
            <section class="col-lg-8">
                <div class="panel p-4">
                    <h2 class="h5 mb-3">Status Pembayaran</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Metode</dt>
                        <dd class="col-sm-8">{{ ucwords(str_replace('_', ' ', $order->payment->payment_method ?? $order->payment_method)) }}</dd>
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8"><span class="status-badge status-{{ $order->paymentStatus() }}">{{ str_replace('_', ' ', $order->paymentStatus()) }}</span></dd>
                        <dt class="col-sm-4">Dibayar Pada</dt>
                        <dd class="col-sm-8">{{ $order->payment?->paid_at?->format('d M Y H:i') ?? '-' }}</dd>
                        <dt class="col-sm-4">Bukti Transfer</dt>
                        <dd class="col-sm-8">
                            @if($order->payment?->payment_proof)
                            <a href="{{ asset('storage/' . $order->payment->payment_proof) }}" target="_blank">Lihat bukti pembayaran</a>
                            @else
                            -
                            @endif
                        </dd>
                    </dl>
                </div>

                <div class="panel p-4 mt-4">
                    <h2 class="h5 mb-3">Catatan</h2>
                    @if($order->payment_method === 'cash')
                    <p class="mb-0">Pembayaran cash akan dikonfirmasi oleh admin setelah pembayaran diterima.</p>
                    @elseif($order->payment_method === 'bank_transfer')
                    <p class="mb-0">Upload bukti transfer agar admin dapat memverifikasi pembayaran.</p>
                    @else
                    <p class="mb-0">Pembayaran Midtrans masih placeholder dan akan diaktifkan pada tahap berikutnya.</p>
                    @endif
                </div>
            </section>

            <aside class="col-lg-4">
                <div class="panel p-4">
                    <h2 class="h5 mb-3">Ringkasan</h2>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <strong>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Ongkir</span>
                        <strong>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between pt-3 border-top mb-4">
                        <span>Total</span>
                        <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong>
                    </div>
                    @if($order->payment_method === 'bank_transfer' && $order->paymentStatus() !== 'paid')
                    <a href="{{ route('payment.uploadProof', $order) }}" class="btn btn-bread w-100">Upload Bukti Transfer</a>
                    @endif
                </div>
            </aside>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
