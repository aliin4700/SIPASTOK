@extends('manager.layouts.app')

@section('content')
@if($operation->status === 'approved')
    <div class="alert alert-success">Operasional harian ini telah disetujui (Approved).</div>
@elseif($operation->status === 'submitted')
    <div class="alert alert-warning">Operasional harian ini berstatus <strong>SUBMITTED</strong> dan siap disetujui oleh Manager.</div>
@endif

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <h4 class="mb-1 fw-bold">Detail Daily Operation</h4>
            <span class="badge {{ $operation->status === 'approved' ? 'bg-success' : ($operation->status === 'submitted' ? 'bg-warning text-dark' : 'bg-info') }} text-uppercase fs-6">
                {{ $operation->status }}
            </span>
        </div>
        <hr>
        <div><strong>Tanggal:</strong> {{ $operation->operation_date?->format('d/m/Y') }}</div>
        <div><strong>Outlet:</strong> {{ $operation->outlet->name ?? '-' }}</div>
        <div><strong>Divisi:</strong> {{ $operation->division->name ?? '-' }}</div>
        <div><strong>Dibuka:</strong> {{ $operation->opened_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->opener->name ?? '-' }}</div>
        <div><strong>Submitted:</strong> {{ $operation->submitted_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->submitter->name ?? '-' }}</div>
        <div><strong>Approved:</strong> {{ $operation->approved_at?->format('d/m/Y H:i') ?? '-' }} oleh {{ $operation->approver->name ?? '-' }}</div>
        <div><strong>Catatan:</strong> {{ $operation->notes ?? '-' }}</div>

        @if($operation->status === 'submitted')
            <form method="post" action="{{ route('manager.daily-operations.approve', $operation) }}" class="mt-3" onsubmit="return confirm('Setujui penutupan operasional harian divisi ini?');">
                @csrf
                <button type="submit" class="btn btn-success fw-bold">
                    &#10003; Approve Penutupan Operasional Harian
                </button>
            </form>
        @endif
    </div>
</div>

@include('partials.daily-operation-summary', ['summary' => $summary, 'panel' => 'manager'])

<a href="{{ route('manager.daily-operations.index') }}" class="btn btn-secondary">Kembali</a>
@endsection
