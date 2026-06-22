<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BreadLab Staff</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('breadlab/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="{{ route('staff.dashboard') }}">BreadLab Staff</a>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('staff.dashboard') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
                <a href="{{ route('staff.daily-operations.index') }}" class="btn btn-outline-secondary btn-sm">Operasional Hari Ini</a>
                <a href="{{ route('staff.opening-alerts.index') }}" class="btn btn-outline-secondary btn-sm">Opening Alert</a>
                <a href="{{ route('staff.material-requests.index') }}" class="btn btn-outline-secondary btn-sm">Take Bahan</a>
                <a href="{{ route('staff.waste.index') }}" class="btn btn-outline-secondary btn-sm">Waste</a>
                <a href="{{ route('staff.production.index') }}" class="btn btn-outline-secondary btn-sm">Produksi / WIP</a>
                <a href="{{ route('staff.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm">Closing Stock</a>
                @if(auth()->user()?->hasAnyRole(['admin', 'super_admin']))
                    <a href="{{ route('backend.dashboard') }}" class="btn btn-outline-primary btn-sm">Backend</a>
                @endif
                <form action="{{ route('logout') }}" method="post" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
