<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('beranda') }}">
            <img src="{{ asset('breadlab/logo.png') }}" alt="BreadLab Bakery" style="height: 46px;">
            <span>BreadLab Bakery</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#breadlabNav" aria-controls="breadlabNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="breadlabNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="{{ route('beranda') }}">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('product.index') }}">Produk</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('beranda') }}#kategori">Kategori</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('cart.index') }}">Keranjang</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ Auth::check() && (string) Auth::user()->role === 'customer' ? route('orders.history') : route('login') }}">Riwayat Pesanan</a></li>
                @auth
                @if((string) Auth::user()->role === 'customer')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ Auth::user()->name }}</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('customer.account') }}">Akun Saya</a></li>
                        <li><a class="dropdown-item" href="{{ route('orders.history') }}">Riwayat Pesanan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="post">
                                @csrf
                                <button class="dropdown-item" type="submit">Logout</button>
                            </form>
                        </li>
                    </ul>
                </li>
                @else
                <li class="nav-item"><a class="btn btn-outline-dark" href="{{ route('backend.beranda') }}">Dashboard Admin</a></li>
                @endif
                @else
                <li class="nav-item"><a class="btn btn-outline-dark" href="{{ route('login') }}">Login/Register</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
