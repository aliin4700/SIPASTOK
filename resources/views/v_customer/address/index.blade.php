@extends('v_layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <a href="{{ route('customer.account') }}" class="text-decoration-none">&larr; Kembali ke akun</a>
        <h1 class="h3 mt-3 mb-1">Daftar Alamat</h1>
        <p class="text-muted mb-0">Simpan alamat pengiriman untuk checkout lebih cepat.</p>
    </div>
    <a href="{{ route('customer.address.create') }}" class="btn btn-bread">Tambah Alamat</a>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@forelse($addresses as $address)
<div class="bread-card p-4 mb-3">
    <div class="d-flex flex-wrap justify-content-between gap-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <h2 class="h5 mb-0">{{ $address->label ?: 'Alamat Pengiriman' }}</h2>
                @if($address->is_default)
                <span class="badge text-bg-success">Utama</span>
                @endif
            </div>
            <div class="fw-semibold">{{ $address->receiver_name }} &middot; {{ $address->receiver_phone }}</div>
            <div class="text-muted mt-1">{{ $address->snapshotAddress() }}</div>
            @if($address->city_id)
            <div class="small text-muted mt-1">City ID RajaOngkir: {{ $address->city_id }}</div>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-start">
            @unless($address->is_default)
            <form action="{{ route('customer.address.setDefault', $address) }}" method="post">
                @csrf
                @method('patch')
                <button type="submit" class="btn btn-outline-dark btn-sm">Jadikan Utama</button>
            </form>
            @endunless
            <a href="{{ route('customer.address.edit', $address) }}" class="btn btn-outline-dark btn-sm">Edit</a>
            <form action="{{ route('customer.address.destroy', $address) }}" method="post" onsubmit="return confirm('Hapus alamat ini?')">
                @csrf
                @method('delete')
                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
            </form>
        </div>
    </div>
</div>
@empty
<div class="bread-card p-4 text-center">
    <h2 class="h5">Belum Ada Alamat</h2>
    <p class="text-muted">Tambahkan alamat pengiriman agar bisa dipakai saat checkout shipping.</p>
    <a href="{{ route('customer.address.create') }}" class="btn btn-bread">Tambah Alamat Pertama</a>
</div>
@endforelse
@endsection
