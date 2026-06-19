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
            <a href="{{ route('payment.show', $order) }}" class="text-decoration-none">&larr; Kembali ke pembayaran</a>
            <h1 class="h3 mt-3 mb-1">Upload Bukti Transfer</h1>
            <p class="text-muted mb-0">{{ $order->order_number }}</p>
        </div>

        <div class="panel p-4">
            <form action="{{ route('payment.storeProof', $order) }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="payment_proof" class="form-label">File Bukti Pembayaran</label>
                    <input type="file" name="payment_proof" id="payment_proof" class="form-control @error('payment_proof') is-invalid @enderror" accept="image/*,.pdf" required>
                    <div class="form-text">Format: JPG, PNG, WEBP, atau PDF. Maksimal 4 MB.</div>
                    @error('payment_proof')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-bread">Upload Bukti</button>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
