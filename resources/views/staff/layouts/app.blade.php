<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIPASTOK Staff Panel — Medja Cerita</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('breadlab/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .staff-navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .staff-nav-link {
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.45rem 0.75rem !important;
            color: #495057;
        }
        .staff-nav-link:hover {
            background-color: #f1f3f5;
            color: #0d6efd;
        }
        .staff-nav-link.active {
            background-color: #0d6efd;
            color: #fff !important;
            font-weight: 600;
        }
        @media (max-width: 991.98px) {
            .staff-nav-collapse {
                padding-top: 0.75rem;
                margin-top: 0.75rem;
                border-top: 1px solid #dee2e6;
            }
            .staff-nav-link {
                display: block;
                width: 100%;
                margin-bottom: 0.25rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top staff-navbar">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('staff.dashboard') }}">
                <img src="{{ asset('breadlab/logo.png') }}" alt="Logo" style="height: 28px;" onerror="this.style.display='none'">
                <span>SIPASTOK <span class="text-primary">Staff</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#staffNavbarCollapse" aria-controls="staffNavbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse staff-nav-collapse" id="staffNavbarCollapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a href="{{ route('staff.dashboard') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('staff.daily-operations.index') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.daily-operations.index') ? 'active' : '' }}">1. Buka Shift</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('staff.opening-alerts.index') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.opening-alerts.*') ? 'active' : '' }}">2. Opening Alert</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('staff.material-requests.index') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.material-requests.*') ? 'active' : '' }}">3. Take Bahan</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('staff.production.index') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.production.*') ? 'active' : '' }}">4. Produksi / WIP</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('staff.waste.index') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.waste.*') ? 'active' : '' }}">5. Waste</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('staff.stock-opnames.index') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.stock-opnames.*') ? 'active' : '' }}">6. Closing Stock Opname</a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('staff.daily-operations.closing-summary') }}" class="nav-link staff-nav-link {{ request()->routeIs('staff.daily-operations.closing-summary') ? 'active' : '' }}">7. Tutup Shift</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if(auth()->user()?->hasAnyRole(['manager', 'admin', 'super_admin']))
                        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-success btn-sm">Panel Manager</a>
                    @endif
                    @if(auth()->user()?->hasAnyRole(['admin', 'super_admin']))
                        <a href="{{ route('backend.dashboard') }}" class="btn btn-outline-primary btn-sm">Panel Admin</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    <main class="container py-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @yield('content')
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
