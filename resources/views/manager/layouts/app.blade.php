<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Panel Monitoring Manager — SIPASTOK Medja Cerita' }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('breadlab/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .manager-navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .manager-nav-link {
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.45rem 0.75rem !important;
            color: #495057;
            transition: all 0.15s ease;
        }
        .manager-nav-link:hover {
            background-color: #f1f3f5;
            color: #0d6efd;
        }
        .manager-nav-link.active {
            background-color: #0d6efd;
            color: #fff !important;
            font-weight: 600;
        }
        @media (max-width: 991.98px) {
            .manager-nav-collapse {
                padding-top: 0.75rem;
                margin-top: 0.75rem;
                border-top: 1px solid #dee2e6;
            }
            .manager-nav-link {
                display: block;
                width: 100%;
                margin-bottom: 0.25rem;
            }
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top manager-navbar">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('manager.dashboard') }}">
                <img src="{{ asset('breadlab/logo.png') }}" alt="Logo" style="height: 28px;" onerror="this.style.display='none'">
                <span>SIPASTOK <span class="text-primary">Manager</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#managerNavbarCollapse" aria-controls="managerNavbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse manager-nav-collapse" id="managerNavbarCollapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a href="{{ route('manager.dashboard') }}" class="nav-link manager-nav-link {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}">
                            Dashboard Monitoring
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('manager.stock-opnames.index') }}" class="nav-link manager-nav-link {{ request()->routeIs('manager.stock-opnames.*') ? 'active' : '' }}">
                            Stock Opname
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('manager.inventory.index') }}" class="nav-link manager-nav-link {{ request()->routeIs('manager.inventory.*') ? 'active' : '' }}">
                            Stok Outlet
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('manager.daily-operations.index') }}" class="nav-link manager-nav-link {{ request()->routeIs('manager.daily-operations.*') ? 'active' : '' }}">
                            Operasional Harian
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('manager.opening-alerts.index') }}" class="nav-link manager-nav-link {{ request()->routeIs('manager.opening-alerts.*') ? 'active' : '' }}">
                            Opening Alerts / Stok Kritis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('manager.material-requests.index') }}" class="nav-link manager-nav-link {{ request()->routeIs('manager.material-requests.*') ? 'active' : '' }}">
                            Take Bahan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('manager.waste-records.index') }}" class="nav-link manager-nav-link {{ request()->routeIs('manager.waste-records.*') ? 'active' : '' }}">
                            Catatan Waste
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle manager-nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Modul Lainnya
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('manager.production-records.index') }}">Produksi / WIP</a></li>
                            <li><a class="dropdown-item" href="{{ route('manager.recipes.index') }}">Recipes / BOM</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('manager.suppliers.index') }}">Suppliers</a></li>
                            <li><a class="dropdown-item" href="{{ route('manager.purchase-orders.index') }}">Purchase Orders</a></li>
                            <li><a class="dropdown-item" href="{{ route('manager.goods-receipts.index') }}">Goods Receipts</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('manager.cashier-shifts.index') }}">Cashier Shifts</a></li>
                            <li><a class="dropdown-item" href="{{ route('manager.finance.dashboard') }}">Finance Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('manager.sales-targets.index') }}">Sales Targets</a></li>
                            <li><a class="dropdown-item" href="{{ route('manager.operational-expenses.index') }}">Operational Expenses</a></li>
                        </ul>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if(auth()->user()?->hasAnyRole(['staff_outlet', 'admin', 'super_admin']))
                        <a href="{{ route('staff.dashboard') }}" class="btn btn-outline-secondary btn-sm">Panel Staff</a>
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

    <main class="container-fluid py-4">
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
