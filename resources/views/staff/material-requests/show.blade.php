@extends('staff.layouts.app')

@section('content')
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h4 class="mb-1 fw-bold">{{ $materialRequest->request_number ?? 'Stok In' }}</h4>
                <div class="text-muted small">{{ $materialRequest->request_date?->format('d/m/Y') }}</div>
            </div>
            <span class="badge {{ in_array($materialRequest->status, ['approved', 'issued'], true) ? 'bg-success' : ($materialRequest->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info text-dark') }} text-uppercase fs-6">
                {{ $materialRequest->status }}
            </span>
        </div>
        <hr>
        <div><strong>Outlet:</strong> {{ $materialRequest->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $materialRequest->division->name ?? '-' }}</div>
        <div><strong>Requester:</strong> {{ $materialRequest->requester->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $materialRequest->notes ?? '-' }}</div>
    </div>
</div>

@include('partials.material-request-receipt-viewer', [
    'materialRequest' => $materialRequest,
    'downloadRoute' => route('staff.material-requests.receipt.download', $materialRequest),
    'updateRoute' => route('staff.material-requests.receipt.update', $materialRequest),
    'deleteRoute' => route('staff.material-requests.receipt.destroy', $materialRequest),
    'canManageReceipt' => in_array($materialRequest->status, ['draft', 'submitted'], true),
])

<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="mb-3 fw-bold">Rincian Item</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty Request</th>
                        <th>Unit</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialRequest->items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->item->name ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float) $item->requested_qty, 2, ',', '.') }}</td>
                            <td>{{ strtoupper($item->unit) }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-2">
            @if($materialRequest->status === 'draft')
                <a href="{{ route('staff.material-requests.edit', $materialRequest) }}" class="btn btn-outline-primary">Edit Draft</a>
                <form method="post" action="{{ route('staff.material-requests.submit', $materialRequest) }}">
                    @csrf
                    <button class="btn btn-success fw-semibold">Submit ke Manager</button>
                </form>
            @endif
            @if(in_array($materialRequest->status, ['draft', 'submitted'], true))
                <form method="post" action="{{ route('staff.material-requests.cancel', $materialRequest) }}" onsubmit="return confirm('Batalkan Stok In ini?');">
                    @csrf
                    <button class="btn btn-outline-danger">Cancel</button>
                </form>
            @endif
            <a href="{{ route('staff.material-requests.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@endsection
