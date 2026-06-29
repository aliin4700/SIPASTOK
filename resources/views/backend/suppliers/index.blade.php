@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5 class="card-title mb-2">{{ $judul }}</h5>
            <a href="{{ route('backend.suppliers.create') }}" class="btn btn-primary btn-sm mb-2">Tambah Supplier</a>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <form method="get" class="border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <label>Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama, kode, atau phone">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end mb-2">
                    <button class="btn btn-primary mr-1">Filter</button>
                    <a href="{{ route('backend.suppliers.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Phone</th>
                        <th>Email</th>
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
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td><span class="badge badge-{{ $supplier->is_active ? 'success' : 'secondary' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <a href="{{ route('backend.suppliers.show', $supplier) }}" class="btn btn-info btn-sm">Detail</a>
                                <a href="{{ route('backend.suppliers.edit', $supplier) }}" class="btn btn-cyan btn-sm">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Belum ada supplier.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $suppliers->links() }}
    </div>
</div>
@endsection
