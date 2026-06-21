@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="card-title mb-1">Operational Expenses</h4>
                <p class="text-muted mb-0">Biaya operasional untuk perhitungan net profit sederhana.</p>
            </div>
            <a href="{{ route('backend.operational-expenses.create') }}" class="btn btn-primary btn-sm">Tambah Expense</a>
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
                        <th></th>
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
                            <td class="text-end">
                                <a href="{{ route('backend.operational-expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('backend.operational-expenses.destroy', $expense) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada expense.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $operationalExpenses->links() }}
    </div>
</div>
@endsection
