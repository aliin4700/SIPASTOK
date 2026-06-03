<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'BreadLab Bakery' }}</title>
    <link rel="icon" href="{{ asset('breadlab/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #fffaf3;
            color: #24160f;
            font-family: Poppins, sans-serif;
        }

        .btn-bread {
            background: #8B4513;
            border-color: #8B4513;
            color: #fff;
            border-radius: 12px;
            font-weight: 700;
        }

        .btn-bread:hover {
            background: #D2691E;
            border-color: #D2691E;
            color: #fff;
        }

        .bread-card {
            background: #fff;
            border: 1px solid #f0e4d3;
            border-radius: 8px;
            box-shadow: 0 10px 24px rgba(139, 69, 19, .08);
        }
    </style>
</head>

<body>
    @include('partials.breadlab-nav')

    <main class="container py-5">
        @yield('content')
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container d-flex flex-column flex-md-row justify-content-between gap-3">
            <span>&copy; {{ date('Y') }} BreadLab Bakery</span>
            <span>Fresh bakery, crafted with care.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
