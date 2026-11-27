@php
    $materialRoute = $panel === 'backend' ? 'backend.material-requests.show' : ($panel === 'manager' ? 'manager.material-requests.show' : 'staff.material-requests.show');
    $productionRoute = $panel === 'backend' ? 'backend.production-records.show' : ($panel === 'manager' ? 'manager.production-records.show' : 'staff.production.show');
    $wasteRoute = $panel === 'backend' ? 'backend.waste-records.show' : ($panel === 'manager' ? 'manager.waste-records.show' : 'staff.waste.show');
    $stockRoute = $panel === 'backend' ? 'backend.stock-opnames.show' : ($panel === 'manager' ? 'manager.stock-opnames.show' : 'staff.stock-opnames.show');
@endphp

<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Stok In Submitted</div>
            <div class="h3 mb-0">{{ $summary['materialRequests']->where('status', 'submitted')->count() }}</div>
        </div></div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Production Submitted</div>
            <div class="h3 mb-0">{{ $summary['productionRecords']->where('status', 'submitted')->count() }}</div>
        </div></div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Waste Submitted</div>
            <div class="h3 mb-0">{{ $summary['wasteRecords']->where('status', 'submitted')->count() }}</div>
        </div></div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card h-100"><div class="card-body">
            <div class="text-muted small">Stock Opname Ready</div>
            <div class="h3 mb-0">{{ $summary['stockOpnames']->whereIn('status', ['submitted', 'reviewed'])->count() }}</div>
        </div></div>
    </div>
</div>

@foreach([
    ['title' => 'Stok In', 'rows' => $summary['materialRequests'], 'number' => 'request_number', 'route' => $materialRoute, 'extra' => null, 'approve_route' => $panel === 'backend' ? 'backend.material-requests.issue-stock' : ($panel === 'manager' ? 'manager.material-requests.issue-stock' : null)],
    ['title' => 'Production / WIP', 'rows' => $summary['productionRecords'], 'number' => 'production_number', 'route' => $productionRoute, 'extra' => 'production_type', 'approve_route' => $panel === 'backend' ? 'backend.production-records.apply-stock' : ($panel === 'manager' ? 'manager.production-records.apply-stock' : null)],
    ['title' => 'Waste', 'rows' => $summary['wasteRecords'], 'number' => 'waste_number', 'route' => $wasteRoute, 'extra' => 'estimated', 'approve_route' => $panel === 'backend' ? 'backend.waste-records.apply-stock' : ($panel === 'manager' ? 'manager.waste-records.apply-stock' : null)],
    ['title' => 'Closing Stock Opname', 'rows' => $summary['stockOpnames'], 'number' => 'opname_number', 'route' => $stockRoute, 'extra' => 'difference', 'approve_route' => $panel === 'backend' ? 'backend.stock-opnames.review' : ($panel === 'manager' ? 'manager.stock-opnames.review' : null)],
] as $section)
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">{{ $section['title'] }}</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Status</th>
                            <th>Total Item</th>
                            <th>Info</th>
                            <th>Submitted/Reviewed</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($section['rows'] as $row)
                            <tr>
                                <td>{{ $row->{$section['number']} ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ in_array($row->status, ['approved', 'issued', 'applied', 'reviewed'], true) ? 'bg-success text-white' : ($row->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info text-dark') }} text-uppercase">
                                        {{ $row->status }}
                                    </span>
                                </td>
                                <td>{{ $row->items->count() }}</td>
                                <td>
                                    @if($section['extra'] === 'production_type')
                                        {{ str_replace('_', ' ', $row->production_type) }}
                                    @elseif($section['extra'] === 'estimated')
                                        Rp {{ number_format((float) $row->items->sum('estimated_value'), 0, ',', '.') }}
                                    @elseif($section['extra'] === 'difference')
                                        +{{ number_format((float) $row->items->where('difference_qty', '>', 0)->sum('difference_qty'), 2, ',', '.') }}
                                        / {{ number_format((float) $row->items->where('difference_qty', '<', 0)->sum('difference_qty'), 2, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $row->submitted_at?->format('d/m/Y H:i') ?? $row->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route($section['route'], $row) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                        @if($row->status === 'submitted' && !empty($section['approve_route']))
                                            <form method="post" action="{{ route($section['approve_route'], $row) }}" class="d-inline" onsubmit="return confirm('Setujui dokumen ini dan perbarui saldo stok sekarang?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success fw-semibold">&#10003; Approve</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
