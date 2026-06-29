@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Detail Supplier</h4>
        <div><strong>Nama:</strong> {{ $supplier->name }}</div>
        <div><strong>Kode:</strong> {{ $supplier->code ?? '-' }}</div>
        <div><strong>Phone:</strong> {{ $supplier->phone ?? '-' }}</div>
        <div><strong>Email:</strong> {{ $supplier->email ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
        <div><strong>Total PO:</strong> {{ $supplier->purchase_orders_count }}</div>
        <div><strong>Total Goods Receipt:</strong> {{ $supplier->goods_receipts_count }}</div>
        <div class="mt-2"><strong>Alamat:</strong><br>{{ $supplier->address ?? '-' }}</div>
        <div class="mt-2"><strong>Catatan:</strong><br>{{ $supplier->notes ?? '-' }}</div>
        <a href="{{ route('manager.suppliers.index') }}" class="btn btn-secondary mt-3">Kembali</a>
    </div>
</div>
@endsection
