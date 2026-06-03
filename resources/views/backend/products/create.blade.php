@extends('backend.v_layouts.app')

@section('content')
@include('backend.products.form', [
    'action' => route('backend.produk.store'),
    'method' => 'post',
    'button' => 'Simpan Produk',
])
@endsection
