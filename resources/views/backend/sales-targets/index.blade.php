@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="card-title mb-1">Sales Targets</h4>
                <p class="text-muted mb-0">Target penjualan harian, bulanan, atau tahunan.</p>
            </div>
            <a href="{{ route('backend.sales-targets.create') }}" class="btn btn-primary btn-sm">Tambah Target</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Outlet</th>
                        <th>Period</th>
                        <th>Date/Month</th>
                        <th class="text-end">Target</th>
                        <th>Created By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesTargets as $target)
                        <tr>
                            <td>{{ $target->outlet->name ?? 'Semua Outlet' }}</td>
                            <td>{{ $target->period_type }}</td>
                            <td>{{ $target->target_date?->format('d M Y') ?? trim(($target->target_month ?: '-') . ' / ' . ($target->target_year ?: '-')) }}</td>
                            <td class="text-end">Rp {{ number_format((float) $target->target_amount, 0, ',', '.') }}</td>
                            <td>{{ $target->creator->name ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('backend.sales-targets.edit', $target) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('backend.sales-targets.destroy', $target) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada target.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $salesTargets->links() }}
    </div>
</div>
@endsection
