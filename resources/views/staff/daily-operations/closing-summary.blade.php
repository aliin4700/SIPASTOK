@extends('staff.layouts.app')

@section('content')
<h4 class="mb-3">Closing Summary Operasional</h4>

@if($message)
    <div class="alert alert-warning">{{ $message }}</div>
@elseif(! $operation)
    <div class="alert alert-warning">Buka Operasional Hari Ini terlebih dahulu sebelum melihat closing summary.</div>
    <a href="{{ route('staff.daily-operations.index', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-primary">Operasional Hari Ini</a>
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

    <div class="alert alert-warning">Menutup operasional harian belum mengubah stok. Stock movement akan dibuat pada tahap berikutnya.</div>

    <div class="card mb-3">
        <div class="card-body">
            <div><strong>Outlet:</strong> {{ $operation->outlet->name ?? '-' }}</div>
            <div><strong>Divisi:</strong> {{ $operation->division->name ?? '-' }}</div>
            <div><strong>Tanggal:</strong> {{ $operation->operation_date?->format('d/m/Y') }}</div>
            <div><strong>Status:</strong> <span class="badge bg-info">{{ $operation->status }}</span></div>
            <div><strong>Dibuka:</strong> {{ $operation->opened_at?->format('d/m/Y H:i') ?? '-' }}</div>
        </div>
    </div>

    @php
        $draftDocsCount = $summary['materialRequests']->where('status', 'draft')->count()
            + $summary['productionRecords']->where('status', 'draft')->count()
            + $summary['wasteRecords']->where('status', 'draft')->count()
            + $summary['stockOpnames']->where('status', 'draft')->count();
    @endphp
    @if($draftDocsCount > 0)
        <div class="alert alert-danger">
            <strong>Perhatian:</strong> Masih terdapat <strong>{{ $draftDocsCount }} dokumen berstatus DRAFT</strong> pada shift hari ini. Silakan buka dokumen tersebut lalu klik <strong>Submit</strong> atau <strong>Cancel</strong> sebelum menutup operasional.
        </div>
    @endif
    @if($summary['stockOpnamesReady']->isEmpty())
        <div class="alert alert-danger">Belum ada Closing Stock Opname yang berstatus <strong>submitted/reviewed</strong> hari ini. Ini wajib dilakukan sebelum tutup operasional.</div>
    @endif

    @include('partials.daily-operation-summary', ['summary' => $summary, 'panel' => 'staff'])

    @if($operation->status === 'open')
        <form method="post" action="{{ route('staff.daily-operations.submit-closing') }}" class="card">
            @csrf
            <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
            <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">
            <div class="card-body">
                <div class="form-group mb-3">
                    <label>Closing Notes</label>
                    <textarea name="closing_notes" class="form-control" rows="3">{{ old('closing_notes', $operation->notes) }}</textarea>
                </div>
                <button class="btn btn-success" @disabled($requireStockOpnameBeforeSubmit && $summary['stockOpnamesReady']->isEmpty())>Submit / Tutup Operasional</button>
            </div>
        </form>
    @endif
@endif
@endsection
