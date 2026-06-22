@extends('backend.v_layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">{{ $judul }}</h5>
                    <a href="{{ route('backend.user.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="border rounded p-3 mb-3">
                    <strong>{{ $user->name }}</strong>
                    <div class="text-muted">{{ $user->email }}</div>
                    <span class="badge badge-info">{{ str_replace('_', ' ', ucfirst((string) $user->role)) }}</span>
                    @php
                        $roleGuides = [
                            'cashier' => 'Kasir biasanya membutuhkan akses outlet dengan centang Kasir.',
                            'staff_outlet' => 'Staff outlet biasanya membutuhkan akses divisi sesuai outlet kerja.',
                            'manager' => 'Manager biasanya membutuhkan akses outlet Monitor untuk melihat operasional.',
                            'customer' => 'Customer biasanya tidak membutuhkan akses outlet atau divisi.',
                        ];
                    @endphp
                    @if (isset($roleGuides[(string) $user->role]))
                        <div class="alert alert-warning mt-3 mb-0">{{ $roleGuides[(string) $user->role] }}</div>
                    @endif
                </div>

                <form method="POST" action="{{ route('backend.user.access.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <h6 class="font-weight-bold">Akses Outlet</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Outlet</th>
                                    <th class="text-center">Monitor</th>
                                    <th class="text-center">Kasir</th>
                                    <th class="text-center">Kelola Stok</th>
                                    <th class="text-center">Kelola Pembelian</th>
                                    <th class="text-center">Kelola Finance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($outlets as $outlet)
                                    @php($access = $outletAccessMap->get($outlet->id))
                                    <tr>
                                        <td>
                                            <strong>{{ $outlet->name }}</strong>
                                            <div class="text-muted small">{{ $outlet->code }}</div>
                                        </td>
                                        <td class="text-center"><input type="checkbox" name="outlet_access[{{ $outlet->id }}][can_monitor]" value="1" @checked(optional($access)->can_monitor)></td>
                                        <td class="text-center"><input type="checkbox" name="outlet_access[{{ $outlet->id }}][can_cashier]" value="1" @checked(optional($access)->can_cashier)></td>
                                        <td class="text-center"><input type="checkbox" name="outlet_access[{{ $outlet->id }}][can_manage_stock]" value="1" @checked(optional($access)->can_manage_stock)></td>
                                        <td class="text-center"><input type="checkbox" name="outlet_access[{{ $outlet->id }}][can_manage_purchase]" value="1" @checked(optional($access)->can_manage_purchase)></td>
                                        <td class="text-center"><input type="checkbox" name="outlet_access[{{ $outlet->id }}][can_manage_finance]" value="1" @checked(optional($access)->can_manage_finance)></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada outlet aktif.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="font-weight-bold">Akses Divisi</h6>
                    @forelse ($outlets as $outlet)
                        <div class="mb-4">
                            <div class="font-weight-bold mb-2">Outlet: {{ $outlet->name }}</div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Divisi</th>
                                            <th class="text-center">Input Stock Opname</th>
                                            <th class="text-center">Input Waste</th>
                                            <th class="text-center">Request Order</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($divisions as $division)
                                            @php($access = $divisionAccessMap->get($outlet->id . '-' . $division->id))
                                            <tr>
                                                <td>
                                                    <strong>{{ $division->name }}</strong>
                                                    <div class="text-muted small">{{ $division->code }}</div>
                                                </td>
                                                <td class="text-center"><input type="checkbox" name="division_access[{{ $outlet->id }}][{{ $division->id }}][can_input_stock]" value="1" @checked(optional($access)->can_input_stock)></td>
                                                <td class="text-center"><input type="checkbox" name="division_access[{{ $outlet->id }}][{{ $division->id }}][can_input_waste]" value="1" @checked(optional($access)->can_input_waste)></td>
                                                <td class="text-center"><input type="checkbox" name="division_access[{{ $outlet->id }}][{{ $division->id }}][can_request_order]" value="1" @checked(optional($access)->can_request_order)></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada divisi aktif.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info">Belum ada outlet aktif untuk akses divisi.</div>
                    @endforelse

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Akses
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
