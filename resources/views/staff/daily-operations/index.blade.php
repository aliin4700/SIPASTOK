@extends('staff.layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="mb-3">Operasional Hari Ini</h4>

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

            @if($operation)
                <div class="border rounded p-3 mb-3">
                    <div><strong>Outlet:</strong> {{ $operation->outlet->name ?? '-' }}</div>
                    <div><strong>Divisi:</strong> {{ $operation->division->name ?? '-' }}</div>
                    <div><strong>Tanggal:</strong> {{ $operation->operation_date->format('d/m/Y') }}</div>
                    <div><strong>Status:</strong> <span class="badge bg-info">{{ $operation->status }}</span></div>
                    <div><strong>Dibuka:</strong> {{ $operation->opened_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>

                @if($operation->status === 'open')
                    <a href="{{ route('staff.daily-operations.closing-summary', ['outlet_id' => $selectedOutletId, 'division_id' => $selectedDivisionId]) }}" class="btn btn-success">Lihat Closing Summary</a>
                @else
                    <div class="alert alert-info mb-0">Operasional hari ini sudah {{ $operation->status }}.</div>
                @endif
            @else
                <form action="{{ route('staff.daily-operations.open') }}" method="post">
                    @csrf
                    <input type="hidden" name="outlet_id" value="{{ $selectedOutletId }}">
                    <input type="hidden" name="division_id" value="{{ $selectedDivisionId }}">
                    <button type="submit" class="btn btn-primary">Buka Operasional</button>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection
