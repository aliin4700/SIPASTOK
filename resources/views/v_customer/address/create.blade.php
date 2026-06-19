@extends('v_layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('customer.address.index') }}" class="text-decoration-none">&larr; Kembali ke daftar alamat</a>
    <h1 class="h3 mt-3 mb-1">Tambah Alamat</h1>
    <p class="text-muted mb-0">Lengkapi alamat penerima untuk pengiriman.</p>
</div>

<div class="bread-card p-4">
    <form action="{{ route('customer.address.store') }}" method="post">
        @include('v_customer.address.form')
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-bread">Simpan Alamat</button>
            <a href="{{ route('customer.address.index') }}" class="btn btn-outline-dark">Batal</a>
        </div>
    </form>
</div>
@endsection
