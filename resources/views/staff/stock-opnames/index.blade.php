@extends('staff.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <div>
                <h4 class="mb-1">Closing Stock Opname</h4>
                <div class="text-muted">Catatan stok fisik penutupan per outlet dan divisi.</div>
            </div>
            @unless($message)
                <a href="{{ route('staff.stock-opnames.create', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">Input Closing Stock</a>
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
                            <th>No Opname</th>
                            <th>Tanggal</th>
                            <th>Outlet</th>
                            <th>Divisi</th>
                            <th>Status</th>
                            <th>Total Item</th>
                            <th>Total Selisih</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($opnames as $opname)
                            <tr>
                                <td>{{ $opname->opname_number ?? '-' }}</td>
                                <td>{{ $opname->opname_date?->format('d/m/Y') }}</td>
                                <td>{{ $opname->outlet->name ?? '-' }}</td>
                                <td>{{ $opname->division->name ?? '-' }}</td>
                                <td><span class="badge bg-info">{{ $opname->status }}</span></td>
                                <td>{{ $opname->items->count() }}</td>
                                <td>{{ number_format((float) $opname->items->sum('difference_qty'), 2, ',', '.') }}</td>
                                <td><a href="{{ route('staff.stock-opnames.show', $opname) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Belum ada stock opname.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $opnames->links() }}
        @endif
    </div>
</div>
@endsection
