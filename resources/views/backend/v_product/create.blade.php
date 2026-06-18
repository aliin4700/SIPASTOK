@extends('backend.v_layouts.app')

@section('content')
@include('backend.v_product.form', [
    'action' => route('backend.product.store'),
    'method' => 'post',
    'button' => 'Simpan Produk',
])
@endsection
