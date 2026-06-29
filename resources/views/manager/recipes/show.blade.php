@extends('manager.layouts.app')

@section('content')
<div class="alert alert-warning">Recipe/BOM saat ini hanya master data. Belum mengurangi stok dan belum terhubung ke POS.</div>

<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3">Detail Recipe / BOM</h4>
        <div><strong>Kode:</strong> {{ $recipe->recipe_code ?? '-' }}</div>
        <div><strong>Nama:</strong> {{ $recipe->name }}</div>
        <div><strong>Product:</strong> {{ $recipe->product->name ?? '-' }}</div>
        <div><strong>Output Item:</strong> {{ $recipe->outputInventoryItem->name ?? '-' }}</div>
        <div><strong>Output:</strong> {{ number_format((float) $recipe->output_qty, 2, ',', '.') }} {{ $recipe->output_unit }}</div>
        <div><strong>Divisi:</strong> {{ $recipe->division->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge bg-{{ $recipe->is_active ? 'success' : 'secondary' }}">{{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
        <div><strong>Catatan:</strong> {{ $recipe->notes ?? '-' }}</div>
        <a href="{{ route('manager.recipes.index') }}" class="btn btn-secondary mt-3">Kembali</a>
    </div>
</div>

@include('partials.recipe-items-table', ['recipe' => $recipe])
@endsection
