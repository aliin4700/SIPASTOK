<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Cashier POS' }} - BreadLab</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('breadlab/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bread-primary: #8B4513;
            --bread-accent: #D2691E;
            --bread-bg: #fffaf3;
            --bread-border: #f0e4d3;
        }

        body {
            background: var(--bread-bg);
            color: #24160f;
        }

        .cashier-shell {
            min-height: 100vh;
        }

        .cashier-sidebar {
            background: #24160f;
            min-height: 100vh;
        }

        .cashier-sidebar a {
            border-radius: 8px;
            color: rgba(255, 255, 255, .78);
            display: block;
            padding: 10px 14px;
            text-decoration: none;
        }

        .cashier-sidebar a.active,
        .cashier-sidebar a:hover {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .cashier-card {
            background: #fff;
            border: 1px solid var(--bread-border);
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(139, 69, 19, .08);
        }

        .btn-bread {
            background: var(--bread-primary);
            border-color: var(--bread-primary);
            color: #fff;
        }

        .btn-bread:hover {
            background: var(--bread-accent);
            border-color: var(--bread-accent);
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="cashier-shell d-lg-flex">
        <aside class="cashier-sidebar p-3 col-lg-2">
            <a href="{{ route('cashier.index') }}" class="d-flex align-items-center gap-2 mb-4 text-white">
                <img src="{{ asset('breadlab/logo.png') }}" alt="BreadLab" style="height: 36px;">
                <strong>BreadLab POS</strong>
            </a>
            <nav class="d-grid gap-2">
                <a href="{{ route('cashier.index') }}" class="{{ request()->routeIs('cashier.index') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('cashier.orders.index') }}" class="{{ request()->routeIs('cashier.orders.*') ? 'active' : '' }}">POS / Orders</a>
                <a href="{{ route('cashier.shift.index') }}" class="{{ request()->routeIs('cashier.shift.index') ? 'active' : '' }}">Shift</a>
                <a href="{{ route('cashier.shift.report') }}" class="{{ request()->routeIs('cashier.shift.report') ? 'active' : '' }}">Shift Report</a>
                <a href="{{ route('cashier.cash-flow.index') }}" class="{{ request()->routeIs('cashier.cash-flow.*') ? 'active' : '' }}">Cash Flow</a>
                <a href="{{ route('cashier.receipts.index') }}" class="{{ request()->routeIs('cashier.receipts.*') ? 'active' : '' }}">Receipts</a>
                @if(auth()->user()?->hasAnyRole(['super_admin', 'admin']))
                    <a href="{{ route('backend.dashboard') }}">Backend</a>
                @endif
            </nav>
        </aside>

        <div class="flex-fill">
            <header class="bg-white border-bottom px-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="h4 mb-0">{{ $title ?? 'Cashier POS' }}</h1>
                    <div class="text-muted small">Panel kasir terpisah dari backend admin.</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small">{{ auth()->user()->name ?? '-' }}</span>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
                    </form>
                </div>
            </header>

            <main class="p-4">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
