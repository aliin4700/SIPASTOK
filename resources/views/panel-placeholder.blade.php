<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'BreadLab Panel' }} - BreadLab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="min-vh-100 d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5 text-center">
                            <img src="{{ asset('breadlab/logo.png') }}" alt="BreadLab Bakery" class="img-fluid mb-3" style="max-height: 72px;">
                            <h1 class="h4 mb-2">{{ $title ?? 'BreadLab Panel' }}</h1>
                            <p class="text-muted mb-4">{{ $message ?? 'Panel sedang disiapkan.' }}</p>
                            <form action="{{ route('logout') }}" method="post" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">Logout</button>
                            </form>
                            @if(auth()->user()?->hasAnyRole(['super_admin', 'admin']))
                                <a href="{{ route('backend.dashboard') }}" class="btn btn-primary ms-2">Backend</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>
