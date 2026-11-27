<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'Login SIPASTOK' }} — Medja Cerita</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('sipastok/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <div class="mb-4 text-center">
                                <img src="{{ asset('sipastok/logo.png') }}" alt="Logo SIPASTOK" class="img-fluid mb-3" style="max-height: 72px;">
                                <h1 class="h4 fw-bold mb-1">Login SIPASTOK</h1>
                                <p class="text-muted small mb-0">Sistem Informasi Stok Opname Bahan Baku — Medja Cerita</p>
                            </div>

                            @if(session('error'))
                            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                            @endif

                            @if(session('msgError'))
                            <div class="alert alert-warning" role="alert">{{ session('msgError') }}</div>
                            @endif

                            @if(session('success'))
                            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                            @endif

                            <form action="{{ route('login.store') }}" method="post">
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="nama@medjacerita.com">
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Masukkan password">
                                    @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" @checked(old('remember'))>
                                    <label class="form-check-label" for="remember">Ingat saya</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">Masuk ke SIPASTOK</button>
                            </form>
                        </div>
                    </div>

                    <div class="text-center text-muted small mt-3">
                        &copy; {{ date('Y') }} SIPASTOK &bull; Medja Cerita
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
