@extends('backend.v_layouts.app')

@section('content')
<div class="alert alert-info">Menyetujui (Approve) Daily Operation akan otomatis memproses seluruh mutasi stok Stok In (+), Produksi (+), Waste (-), dan Stock Opname pada shift ini ke <code>inventory_stocks</code>.</div>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <div><strong>Tanggal:</strong> {{ $operation->operation_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $operation->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $operation->division->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge badge-info">{{ $operation->status }}</span></div>
        <div><strong>Dibuka:</strong> {{ $operation->opened_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->opener->name ?? '-' }}</div>
        <div><strong>Submitted:</strong> {{ $operation->submitted_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->submitter->name ?? '-' }}</div>
        <div><strong>Approved:</strong> {{ $operation->approved_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->approver->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $operation->notes ?? '-' }}</div>

        @if($operation->status === 'submitted')
            <form method="post" action="{{ route('backend.daily-operations.approve', $operation) }}" class="mt-3">
                @csrf
                <button class="btn btn-success">Approve Status</button>
            </form>
        @endif
    </div>
</div>

@include('partials.daily-operation-summary', ['summary' => $summary, 'panel' => 'backend'])

<a href="{{ route('backend.daily-operations.index') }}" class="btn btn-secondary">Kembali</a>
@endsection
