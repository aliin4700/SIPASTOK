@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="card-title mb-1">Finance Dashboard</h4>
                <p class="text-muted mb-0">Ringkasan target, expense, dan estimasi net profit.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('backend.sales-targets.index') }}" class="btn btn-outline-primary btn-sm">Sales Target</a>
                <a href="{{ route('backend.operational-expenses.index') }}" class="btn btn-outline-primary btn-sm">Operational Expense</a>
            </div>
        </div>

        @include('finance.partials.dashboard-filter', ['action' => route('backend.finance.dashboard')])
        @include('finance.partials.summary-cards')
    </div>
</div>
@endsection
