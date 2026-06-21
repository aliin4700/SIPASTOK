<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body {
            color: #111;
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 16px;
        }

        .receipt {
            margin: 0 auto;
            max-width: 360px;
        }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-muted { color: #666; }
        .fw-semibold, strong { font-weight: 700; }
        .small { font-size: 12px; }
        .fs-5 { font-size: 18px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-3 { margin-bottom: 12px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .pb-3 { padding-bottom: 12px; }
        .pt-3 { padding-top: 12px; }
        .border-bottom { border-bottom: 1px dashed #999; }
        .border-top { border-top: 1px dashed #999; }
        .row { display: flex; flex-wrap: wrap; }
        .col-6 { width: 50%; }
        .col-12 { width: 100%; }
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 5px 0; }
        thead { border-bottom: 1px dashed #999; }

        @media print {
            button { display: none; }
            body { padding: 0; }
        }
    </style>
</head>

<body>
    <div class="receipt">
        <div class="text-center border-bottom pb-3 mb-3">
            <h1 class="mb-1">BreadLab</h1>
            <div class="text-muted">{{ $order->outlet->name ?? 'Outlet' }}</div>
        </div>

        @include('cashier.receipts.partials.receipt-body', ['order' => $order])

        <p class="text-center small mt-3">Terima kasih.</p>
        <p class="text-center"><button onclick="window.print()">Print</button></p>
    </div>
</body>

</html>
