@extends('backend.v_layouts.app')

@section('content')
@include('backend.products.form', [
    'action' => route('backend.produk.update', $product),
    'method' => 'put',
    'button' => 'Perbarui Produk',
])
@endsection
