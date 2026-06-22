@extends('staff.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <div>
                <h4 class="mb-1">Take Bahan</h4>
                <div class="text-muted">Permintaan bahan untuk outlet dan divisi hari ini.</div>
            </div>
            @unless($message)
                <a href="{{ route('staff.material-requests.create', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">Buat Take Bahan</a>
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
                            <th>No Request</th>
                            <th>Tanggal</th>
                            <th>Outlet</th>
                            <th>Divisi</th>
                            <th>Status</th>
                            <th>Total Item</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                            <tr>
                                <td>{{ $requestItem->request_number ?? '-' }}</td>
                                <td>{{ $requestItem->request_date?->format('d/m/Y') }}</td>
                                <td>{{ $requestItem->outlet->name ?? '-' }}</td>
                                <td>{{ $requestItem->division->name ?? '-' }}</td>
                                <td><span class="badge bg-info">{{ $requestItem->status }}</span></td>
                                <td>{{ $requestItem->items->count() }}</td>
                                <td><a href="{{ route('staff.material-requests.show', $requestItem) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Belum ada Take Bahan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        @endif
    </div>
</div>
@endsection
