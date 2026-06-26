@extends('manager.layouts.app')

@section('content')
<div class="alert alert-warning">Monitoring ini read-only. Manager tidak mengubah status atau stok dari halaman ini.</div>

<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-3">Detail Daily Operation</h4>
        <div><strong>Tanggal:</strong> {{ $operation->operation_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $operation->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $operation->division->name ?? '-' }}</div>
        <div><strong>Status:</strong> <span class="badge bg-info">{{ $operation->status }}</span></div>
        <div><strong>Dibuka:</strong> {{ $operation->opened_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->opener->name ?? '-' }}</div>
        <div><strong>Submitted:</strong> {{ $operation->submitted_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->submitter->name ?? '-' }}</div>
        <div><strong>Approved:</strong> {{ $operation->approved_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->approver->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $operation->notes ?? '-' }}</div>
    </div>
</div>

@include('partials.daily-operation-summary', ['summary' => $summary, 'panel' => 'manager'])

<a href="{{ route('manager.daily-operations.index') }}" class="btn btn-secondary">Kembali</a>
@endsection
