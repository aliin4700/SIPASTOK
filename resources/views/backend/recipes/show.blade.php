@extends('backend.v_layouts.app')

@section('content')
<div class="alert alert-info">Recipe/BOM ini digunakan untuk pemotongan bahan baku otomatis saat Produksi / WIP disetujui.</div>

<div class="card mb-3">
    <div class="card-body">
        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>Kode:</strong> {{ $recipe->recipe_code ?? '-' }}</div>
        <div><strong>Nama:</strong> {{ $recipe->name }}</div>
        <div><strong>Output Item:</strong> {{ $recipe->outputInventoryItem->name ?? '-' }}</div>
        <div><strong>Output:</strong> {{ number_format((float) $recipe->output_qty, 2, ',', '.') }} {{ $recipe->output_unit }}</div>
        <div><strong>Divisi:</strong> {{ $recipe->division->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge badge-{{ $recipe->is_active ? 'success' : 'secondary' }}">{{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
        <div><strong>Created By:</strong> {{ $recipe->createdBy->name ?? '-' }}</div>
        <div><strong>Updated By:</strong> {{ $recipe->updatedBy->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $recipe->notes ?? '-' }}</div>
        <div class="mt-3">
            <a href="{{ route('backend.recipes.edit', $recipe) }}" class="btn btn-cyan">Edit</a>
            <a href="{{ route('backend.recipes.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>

@include('partials.recipe-items-table', ['recipe' => $recipe])
@endsection
