@extends('staff.layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="mb-1">Dashboard Staff Outlet</h4>
    <div class="text-muted">Ringkasan operasional outlet dan divisi hari ini.</div>
</div>

@if($message)
    <div class="alert alert-warning">{{ $message }}</div>
@else
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Outlet</div>
                    <div class="h5 mb-0">{{ $outlet->name ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Divisi</div>
                    <div class="h5 mb-0">{{ $division->name ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Status Operasional Hari Ini</div>
                    <div class="h5 mb-0">{{ $operation?->status ?? 'Belum dibuka' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">WIP Perlu Produksi</div>
                    <div class="display-4">{{ $counts['wip'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Raw Material Kosong/Hampir Habis</div>
                    <div class="display-4">{{ $counts['raw'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Packaging Kosong/Hampir Habis</div>
                    <div class="display-4">{{ $counts['packaging'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Akses Cepat</h5>
            <a href="{{ route('staff.daily-operations.index') }}" class="btn btn-primary">Operasional Hari Ini</a>
            <a href="{{ route('staff.opening-alerts.index') }}" class="btn btn-outline-primary">Opening Alert</a>
            <a href="{{ route('staff.material-requests.index') }}" class="btn btn-outline-primary">Take Bahan</a>
            <a href="{{ route('staff.waste.index') }}" class="btn btn-outline-primary">Input Waste</a>
            <a href="{{ route('staff.production.index') }}" class="btn btn-outline-primary">Input Produksi / WIP</a>
            <a href="{{ route('staff.stock-opnames.index') }}" class="btn btn-outline-primary">Closing Stock</a>
            <a href="{{ route('staff.daily-operations.closing-summary') }}" class="btn btn-success">Tutup Operasional</a>
        </div>
    </div>
@endif
@endsection
