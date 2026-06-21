@extends('manager.layouts.app')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Operational Expenses</h1>
                <p class="text-muted mb-0">Read-only operational expense.</p>
            </div>
            <a href="{{ route('manager.finance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Finance</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Outlet</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operationalExpenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date?->format('d M Y') }}</td>
                            <td>{{ $expense->outlet->name ?? 'Semua Outlet' }}</td>
                            <td>{{ str_replace('_', ' ', $expense->category) }}</td>
                            <td>{{ $expense->description ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $expense->status }}</span></td>
                            <td class="text-end">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada expense.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $operationalExpenses->links() }}
    </div>
</div>
@endsection
