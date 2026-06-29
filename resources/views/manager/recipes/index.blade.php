@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Recipe / BOM Monitoring</h4>

        <form method="get" class="row mb-3">
            <div class="col-md-4 mb-2">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Kode, nama, produk, output">
            </div>
            <div class="col-md-3 mb-2">
                <select name="division_id" class="form-control">
                    <option value="">Semua Divisi</option>
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <select name="status" class="form-control">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <button class="btn btn-primary">Filter</button>
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
                            <td><span class="badge bg-{{ $recipe->is_active ? 'success' : 'secondary' }}">{{ $recipe->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>{{ $recipe->items->count() }}</td>
                            <td><a href="{{ route('manager.recipes.show', $recipe) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
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
