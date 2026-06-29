@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <form method="post" action="{{ route('backend.suppliers.update', $supplier) }}">
            @include('backend.suppliers._form')
        </form>
    </div>
</div>
@endsection
