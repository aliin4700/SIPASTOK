@extends('backend.v_layouts.app')
@section('content')
<!-- contentAwal -->

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body border-top">
                <h5 class="card-title"> {{$judul}}</h5>
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading"> Selamat Datang, {{ Auth::user()->name }}</h4>
                    Dashboard BreadLab Bakery dengan hak akses yang anda miliki sebagai
                    <b>
                        @if (Auth::user()->role === 'super_admin')
                        Super Admin
                        @elseif(Auth::user()->role === 'admin')
                        Admin
                        @endif
                    </b>
                    ini adalah halaman utama untuk mengelola produk bakery, pelanggan, pesanan, dan laporan.
                    <hr>
                    <p class="mb-0">Fresh bakery management for BreadLab.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- contentAkhir -->
@endsection
