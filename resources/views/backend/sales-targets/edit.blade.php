@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">Edit Sales Target</h4>
        <form action="{{ route('backend.sales-targets.update', $salesTarget) }}" method="post">
            @method('put')
            @include('backend.sales-targets.form')
        </form>
    </div>
</div>
@endsection
