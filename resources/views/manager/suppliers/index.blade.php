@extends('manager.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Supplier Monitoring</h4>
        <form method="get" class="row mb-3">
            <div class="col-md-5">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama, kode, atau phone">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('manager.suppliers.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->code ?? '-' }}</td>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                            <td><span class="badge bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td><a href="{{ route('manager.suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada supplier.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $suppliers->links() }}
    </div>
</div>
@endsection
