<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Manager Panel' }} - BreadLab</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('breadlab/logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-semibold" href="{{ route('manager.dashboard') }}">BreadLab Manager</a>
            <div class="d-flex gap-2">
                <a href="{{ route('manager.finance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Finance</a>
                <a href="{{ route('manager.sales-targets.index') }}" class="btn btn-outline-secondary btn-sm">Targets</a>
                <a href="{{ route('manager.operational-expenses.index') }}" class="btn btn-outline-secondary btn-sm">Expenses</a>
                <a href="{{ route('manager.cashier-shifts.index') }}" class="btn btn-outline-secondary btn-sm">Cashier Shifts</a>
                <a href="{{ route('manager.inventory.index') }}" class="btn btn-outline-secondary btn-sm">Inventory</a>
                <a href="{{ route('manager.daily-operations.index') }}" class="btn btn-outline-secondary btn-sm">Daily Ops</a>
                <a href="{{ route('manager.material-requests.index') }}" class="btn btn-outline-secondary btn-sm">Material Request</a>
                <a href="{{ route('manager.suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Suppliers</a>
                <a href="{{ route('manager.purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">Purchase Orders</a>
                <a href="{{ route('manager.goods-receipts.index') }}" class="btn btn-outline-secondary btn-sm">Goods Receipts</a>
                <a href="{{ route('manager.waste-records.index') }}" class="btn btn-outline-secondary btn-sm">Waste</a>
                <a href="{{ route('manager.production-records.index') }}" class="btn btn-outline-secondary btn-sm">Production</a>
                <a href="{{ route('manager.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm">Stock Opname</a>
                @if(auth()->user()?->hasAnyRole(['admin', 'super_admin']))
                    <a href="{{ route('backend.dashboard') }}" class="btn btn-outline-primary btn-sm">Backend</a>
                @endif
            </div>
        </div>
    </nav>

    <main class="container-fluid py-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</body>

</html>
