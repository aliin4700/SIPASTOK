@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5 class="card-title mb-2">{{ $judul }}</h5>
            <a href="{{ route('backend.inventory-items.create') }}" class="btn btn-primary btn-sm mb-2"><i class="fas fa-plus"></i> Tambah</a>
        </div>

        @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

        <form method="get" action="{{ route('backend.inventory-items.index') }}" class="border rounded p-3 mb-3">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label>Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Nama atau kode item">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label>Divisi</label>
                        <select name="division_id" class="form-control">
                            <option value="">Semua Divisi</option>
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected((string) request('division_id') === (string) $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label>Tipe Item</label>
                        <select name="item_type" class="form-control">
                            <option value="">Semua Tipe</option>
                            @foreach($itemTypes as $type)
                                <option value="{{ $type }}" @selected(request('item_type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end mb-2">
                    <button type="submit" class="btn btn-primary mr-1">Filter</button>
                    <a href="{{ route('backend.inventory-items.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nama Item</th>
                        <th>Kode</th>
                        <th>Divisi</th>
                        <th>Tipe</th>
                        <th>Satuan Dasar</th>
                        <th class="text-right">Unit Cost (Rp)</th>
                        <th>Minimum Stock</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $displayCost = (float) ($item->unit_cost > 0 ? $item->unit_cost : ($item->last_purchase_price ?? 0));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $item->name }}</strong>
                                @if($item->esb_item_name && strcasecmp($item->esb_item_name, $item->name) !== 0)
                                    <div class="text-muted small">ESB: {{ $item->esb_item_name }}</div>
                                @endif
                            </td>
                            <td>{{ $item->code ?? '-' }}</td>
                            <td>{{ $item->division->name ?? 'Umum' }}</td>
                            <td>{{ str_replace('_', ' ', $item->item_type) }}</td>
                            <td>{{ strtoupper($item->base_unit) }}</td>
                            <td class="text-right">Rp {{ number_format($displayCost, 2, ',', '.') }}</td>
                            <td>{{ number_format((float) $item->min_stock, 2, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <a href="{{ route('backend.inventory-items.edit', $item) }}" class="btn btn-cyan btn-sm">Edit</a>
                                <form action="{{ route('backend.inventory-items.destroy', $item) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm show_confirm" data-konf-delete="{{ $item->name }}">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Belum ada inventory item.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            {{ $items->links() }}
        </div>
    </div>
</div>
@endsection
