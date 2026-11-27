@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ $judul }}</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Outlet</th>
                        <th>Divisi</th>
                        <th>Item</th>
                        <th>Tipe</th>
                        <th>Qty</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->outlet->name ?? '-' }}</td>
                            <td>{{ $movement->division->name ?? '-' }}</td>
                            <td>{{ $movement->item->name ?? '-' }}</td>
                            <td>{{ $movement->movement_type_label }}</td>
                            <td>{{ number_format((float) $movement->qty, 2, ',', '.') }} {{ $movement->unit }}</td>
                            <td>{{ $movement->creator->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada stock movement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $movements->links() }}
    </div>
</div>
@endsection
