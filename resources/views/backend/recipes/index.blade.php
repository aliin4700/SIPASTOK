@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5 class="card-title mb-2">{{ $judul }}</h5>
            <a href="{{ route('backend.recipes.create') }}" class="btn btn-primary btn-sm mb-2">Tambah Recipe</a>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

        <form method="get" class="border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Kode, nama, produk, output">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Divisi</label>
                    <select name="division_id" class="form-control">
                        <option value="">Semua Divisi</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end mb-2">
                    <button class="btn btn-primary mr-1">Filter</button>
                    <a href="{{ route('backend.recipes.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Produk</th>
                        <th>Output Item</th>
                        <th>Output</th>
                        <th>Divisi</th>
                        <th>Status</th>
                        <th>Material</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recipes as $recipe)
                        <tr>
                            <td>{{ $recipe->recipe_code ?? '-' }}</td>
                            <td>{{ $recipe->name }}</td>
                            <td>{{ $recipe->product->name ?? '-' }}</td>
                            <td>{{ $recipe->outputInventoryItem->name ?? '-' }}</td>
                            <td>{{ number_format((float) $recipe->output_qty, 2, ',', '.') }} {{ $recipe->output_unit }}</td>
                            <td>{{ $recipe->division->name ?? '-' }}</td>
                            <td><span class="badge badge-{{ $recipe->is_active ? 'success' : 'secondary' }}">{{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>{{ $recipe->items->count() }}</td>
                            <td>
                                <a href="{{ route('backend.recipes.show', $recipe) }}" class="btn btn-info btn-sm">Detail</a>
                                <a href="{{ route('backend.recipes.edit', $recipe) }}" class="btn btn-cyan btn-sm">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada recipe/BOM.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $recipes->links() }}
    </div>
</div>
@endsection
