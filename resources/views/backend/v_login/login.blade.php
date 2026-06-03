<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'Login Admin' }} - BreadLab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark">
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="card border-0 shadow">
                        <div class="card-body p-4 p-md-5">
                            <div class="mb-4 text-center">
                                <img src="{{ asset('breadlab/logo.png') }}" alt="BreadLab Bakery" class="img-fluid mb-3" style="max-height: 72px;">
                                <h1 class="h4 mb-1">Login Admin</h1>
                                <p class="text-muted mb-0">Masuk untuk mengelola toko.</p>
                            </div>

                            @if(session('error'))
                            <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                            @endif

                            <form action="{{ route('backend.login') }}" method="post">
                                @csrf
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                                    @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                    @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('beranda') }}" class="link-light text-decoration-none">Kembali ke beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
