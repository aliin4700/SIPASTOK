@extends('staff.layouts.app')

@section('content')
<h4 class="mb-3">Opening Alert</h4>

@if($message)
    <div class="alert alert-warning">{{ $message }}</div>
@else
    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
        <form method="get" class="row mb-3">
            <div class="col-md-4">
                <select name="outlet_id" class="form-control">
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected($selectedOutletId == $outlet->id)>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="division_id" class="form-control">
                    @foreach($divisions as $division)
                        <option value="{{ $division->id }}" @selected($selectedDivisionId == $division->id)>{{ $division->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
        </form>
    @endif

    @include('staff.opening-alerts.partials.alert-table', ['title' => 'WIP Perlu Produksi', 'alerts' => $wipAlerts])
    @include('staff.opening-alerts.partials.alert-table', ['title' => 'Raw Material Kosong/Hampir Habis', 'alerts' => $rawAlerts])
    @include('staff.opening-alerts.partials.alert-table', ['title' => 'Packaging Kosong/Hampir Habis', 'alerts' => $packagingAlerts])

    <div class="card">
        <div class="card-body">
            <h5>Barang Dalam Pemesanan</h5>
            <div class="alert alert-info mb-0">Data barang dalam pemesanan akan aktif setelah modul purchasing dibuat.</div>
        </div>
    </div>
@endif
@endsection
