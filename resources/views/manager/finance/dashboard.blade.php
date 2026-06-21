@extends('manager.layouts.app')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Finance Dashboard</h1>
                <p class="text-muted mb-0">Read-only finance monitoring.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('manager.sales-targets.index') }}" class="btn btn-outline-secondary btn-sm">Targets</a>
                <a href="{{ route('manager.operational-expenses.index') }}" class="btn btn-outline-secondary btn-sm">Expenses</a>
            </div>
        </div>

        @include('finance.partials.dashboard-filter', ['action' => route('manager.finance.dashboard')])
        @include('finance.partials.summary-cards')
    </div>
</div>
@endsection
