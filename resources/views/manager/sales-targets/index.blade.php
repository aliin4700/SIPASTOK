@extends('manager.layouts.app')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Sales Targets</h1>
                <p class="text-muted mb-0">Read-only target penjualan.</p>
            </div>
            <a href="{{ route('manager.finance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Finance</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Outlet</th>
                        <th>Period</th>
                        <th>Date/Month</th>
                        <th class="text-end">Target</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesTargets as $target)
                        <tr>
                            <td>{{ $target->outlet->name ?? 'Semua Outlet' }}</td>
                            <td>{{ $target->period_type }}</td>
                            <td>{{ $target->target_date?->format('d M Y') ?? trim(($target->target_month ?: '-') . ' / ' . ($target->target_year ?: '-')) }}</td>
                            <td class="text-end">Rp {{ number_format((float) $target->target_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada target.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $salesTargets->links() }}
    </div>
</div>
@endsection
