@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Edit Operational Expense</h4>
        <form action="{{ route('backend.operational-expenses.update', $operationalExpense) }}" method="post">
            @method('put')
            @include('backend.operational-expenses.form')
        </form>
    </div>
</div>
@endsection
