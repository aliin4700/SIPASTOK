@extends('backend.v_layouts.app')

@section('content')
@include('backend.v_product.form', [
    'action' => route('backend.product.update', $product),
    'method' => 'put',
    'button' => 'Perbarui Produk',
])
@endsection
