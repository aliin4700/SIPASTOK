@extends('staff.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <div>
                <h4 class="mb-1">Produksi / WIP</h4>
                <div class="text-muted">Catatan output produksi per outlet dan divisi.</div>
            </div>
            @unless($message)
                <a href="{{ route('staff.production.create', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">Input Produksi</a>
            @endunless
        </div>

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @else
            @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form method="get" class="row mb-3">
                    <div class="col-md-4">
                        <select name="outlet_id" class="form-control">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="division_id" class="form-control">
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
                </form>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No Produksi</th>
                            <th>Tanggal</th>
                            <th>Outlet</th>
                            <th>Divisi</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Total Item</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $record->production_number ?? '-' }}</td>
                                <td>{{ $record->production_date?->format('d/m/Y') }}</td>
                                <td>{{ $record->outlet->name ?? '-' }}</td>
                                <td>{{ $record->division->name ?? '-' }}</td>
                                <td>{{ str_replace('_', ' ', $record->production_type) }}</td>
                                <td><span class="badge bg-info">{{ $record->status }}</span></td>
                                <td>{{ $record->items->count() }}</td>
                                <td><a href="{{ route('staff.production.show', $record) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Belum ada production record.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $records->links() }}
        @endif
    </div>
</div>
@endsection
