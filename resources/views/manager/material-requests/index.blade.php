@extends('manager.layouts.app')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h4 class="mb-0 fw-bold">Daftar Stok In (Monitoring)</h4>
            <form id="bulkApproveForm" method="post" action="{{ route('manager.material-requests.bulk-approve') }}" onsubmit="return confirm('Setujui semua dokumen Stok In yang terpilih sekaligus (+ Restock ke stok)?');">
                @csrf
                <button type="submit" id="bulkApproveBtn" class="btn btn-success btn-sm fw-semibold" disabled>
                    &#10003; Approve Terpilih (<span id="bulkSelectedCount">0</span>)
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="bulkSelectAll" class="form-check-input" title="Pilih semua yang menunggu persetujuan">
                        </th>
                        <th>No Request</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Requester</th>
                        <th>Status</th>
                        <th>Total Item</th>
                        <th>Bukti Nota</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        <tr>
                            <td class="text-center">
                                @if($requestItem->status === 'submitted')
                                    <input type="checkbox" name="ids[]" value="{{ $requestItem->id }}" form="bulkApproveForm" class="form-check-input bulk-item-check">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $requestItem->request_number ?? '-' }}</td>
                            <td>{{ $requestItem->request_date?->format('d/m/Y') }}</td>
                            <td>{{ $requestItem->outlet->name ?? '-' }}</td>
                            <td>{{ $requestItem->division->name ?? '-' }}</td>
                            <td>{{ $requestItem->requester->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ in_array($requestItem->status, ['approved', 'issued'], true) ? 'bg-success' : ($requestItem->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info text-dark') }} text-uppercase">
                                    {{ $requestItem->status }}
                                </span>
                            </td>
                            <td>{{ $requestItem->items->count() }}</td>
                            <td class="text-center">
                                @include('partials.material-request-receipt-badge', ['materialRequest' => $requestItem])
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('manager.material-requests.show', $requestItem) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                    @if($requestItem->status === 'submitted')
                                        <form method="post" action="{{ route('manager.material-requests.issue-stock', $requestItem) }}" class="d-inline" onsubmit="return confirm('Setujui Stok In ini dan tambahkan ke saldo stok (+ Restock)?');">
                                            @csrf
                                            <input type="hidden" name="mode" value="restock_in">
                                            <button type="submit" class="btn btn-sm btn-success fw-semibold">&#10003; Approve</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">Belum ada data Stok In.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('bulkSelectAll');
    const checkboxes = Array.from(document.querySelectorAll('.bulk-item-check'));
    const btn = document.getElementById('bulkApproveBtn');
    const countEl = document.getElementById('bulkSelectedCount');

    function syncBulkState() {
        const checked = checkboxes.filter(function (cb) { return cb.checked; }).length;
        if (countEl) countEl.textContent = String(checked);
        if (btn) btn.disabled = checked === 0;
        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = checked === checkboxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
            syncBulkState();
        });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', syncBulkState);
    });
    syncBulkState();
});
</script>
@endsection
