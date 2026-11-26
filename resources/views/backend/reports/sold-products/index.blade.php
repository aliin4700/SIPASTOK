@extends('backend.v_layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <form class="form-horizontal" action="{{ route('backend.reports.sold-products.print') }}" method="post" target="_blank">
                @csrf

                <div class="card-body">
                    <h4 class="card-title">{{ $judul }}</h4>
                    <p class="text-muted mb-4">Laporan ini menghitung produk yang sudah masuk ke order customer berdasarkan data order items.</p>

                    <div class="form-group">
                        <label>Tanggal Awal</label>
                        <input type="date" name="tanggal_awal" value="{{ old('tanggal_awal') }}" class="form-control @error('tanggal_awal') is-invalid @enderror">
                        @error('tanggal_awal')
                        <span class="invalid-feedback alert-danger" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" value="{{ old('tanggal_akhir') }}" class="form-control @error('tanggal_akhir') is-invalid @enderror">
                        @error('tanggal_akhir')
                        <span class="invalid-feedback alert-danger" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Status Order</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="">Semua Status Non-Cancelled</option>
                            @foreach($orderStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                        @error('status')
                        <span class="invalid-feedback alert-danger" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Metode Transaksi / Channel</label>
                        <select name="transaction_method" class="form-control @error('transaction_method') is-invalid @enderror">
                            <option value="">Semua Channel</option>
                            @foreach($transactionMethods as $method)
                            <option value="{{ $method }}" @selected(old('transaction_method') === $method)>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                            @endforeach
                        </select>
                        @error('transaction_method')
                        <span class="invalid-feedback alert-danger" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <br>
                    <button type="submit" class="btn btn-primary">Cetak</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
