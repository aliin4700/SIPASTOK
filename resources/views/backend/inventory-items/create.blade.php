@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <form action="{{ route('backend.inventory-items.store') }}" method="post">
            @include('backend.inventory-items._form')
        </form>
    </div>
</div>
@endsection
