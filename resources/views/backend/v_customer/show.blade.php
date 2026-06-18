@extends('backend.v_layouts.app')
@section('content')
<!-- contentAwal -->

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <h5 class="card-title">{{ $judul }} <br><br></h5>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 220px;">Nama</th>
                                <td>{{ $show->user->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>{{ $show->user->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>HP</th>
                                <td>{{ $show->user->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td>{{ $show->address ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kode Pos</th>
                                <td>{{ $show->postal_code ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Total Pesanan</th>
                                <td>{{ $show->orders->count() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <a href="{{ route('backend.customer.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<!-- contentAkhir -->
@endsection
