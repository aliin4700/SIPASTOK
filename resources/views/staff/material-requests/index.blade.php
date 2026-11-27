@extends('staff.layouts.app')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">Daftar Stok In</h4>
                <div class="text-muted">Permintaan bahan untuk outlet dan divisi hari ini.</div>
            </div>
            @unless($message)
                <a href="{{ route('staff.material-requests.create', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">+ Input Stok In</a>
            @endunless
        </div>

        @if($message)
            <div class="alert alert-warning">{{ $message }}</div>
        @else
            @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
                <form method="get" class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <select name="outlet_id" class="form-select">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <select name="division_id" class="form-select">
                            @foreach($divisions as $division)
                                <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Tampilkan</button></div>
                </form>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No Request</th>
                            <th>Tanggal</th>
                            <th>Outlet</th>
                            <th>Divisi</th>
                            <th>Status</th>
                            <th>Total Item</th>
                            <th>Bukti Nota</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                            <tr>
                                <td class="fw-semibold">{{ $requestItem->request_number ?? '-' }}</td>
                                <td>{{ $requestItem->request_date?->format('d/m/Y') }}</td>
                                <td>{{ $requestItem->outlet->name ?? '-' }}</td>
                                <td>{{ $requestItem->division->name ?? '-' }}</td>
                                <td><span class="badge bg-info text-dark text-uppercase">{{ $requestItem->status }}</span></td>
                                <td>{{ $requestItem->items->count() }}</td>
                                <td class="text-center">
                                    @include('partials.material-request-receipt-badge', ['materialRequest' => $requestItem])
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('staff.material-requests.show', $requestItem) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                        @if($requestItem->status === 'draft')
                                            <a href="{{ route('staff.material-requests.edit', $requestItem) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">Belum ada data Stok In.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        @endif
    </div>
</div>
@endsection
