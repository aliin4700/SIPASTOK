@extends('v_layouts.app')
@section('content')
<!-- template -->

@php
    $user = $customer->user;
    $photo = $user->photo;
    $photoUrl = $photo
        ? (str_contains($photo, '/') ? asset('storage/' . $photo) : asset('storage/img-customer/' . $photo))
        : asset('storage/img-user/img-default.jpg');
@endphp

<div class="row">
    <div class="col-md-12">
        <div class=" billing-details">
            <div class="section-title">
                <h3 class="title"> {{$judul}} </h3>
            </div>
            <div class="mb-3">
                <a href="{{ route('customer.address.index') }}" class="primary-btn">Daftar Alamat</a>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <!-- msgError -->
                    @if(session()->has('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <strong>{{ session('success') }}</strong>
                    </div>
                    @endif
                    <!-- end msgError -->
                    <!-- msgError -->
                    @if(session()->has('msgError'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <strong>{{ session('msgError') }}</strong>
                    </div>
                    @endif
                    <!-- end msgError -->
                </div>
                <form action="{{ route('customer.account.update') }}" method="post" enctype="multipart/form-data">
                    @method('put')
                    @csrf

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Foto</label>
                            {{-- view image --}}
                            <img src="{{ $photoUrl }}" class="foto-preview" width="100%">
                            <p></p>
                            {{-- file foto --}}
                            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" onchange="previewFoto()">
                            @error('photo')
                            <div class="invalid-feedback alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-8">

                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Masukkan Nama">
                            @error('name')
                            <span class="invalid-feedback alert-danger" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" placeholder="Masukkan Email">
                            @error('email')
                            <span class="invalid-feedback alert-danger" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>HP</label>
                            <input type="text" onkeypress="return hanyaAngka(event)" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="Masukkan Nomor HP">
                            @error('phone')
                            <span class="invalid-feedback alert-danger" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Alamat</label><br>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                            <span class="invalid-feedback alert-danger" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Kode Pos</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $customer->postal_code) }}" class="form-control @error('postal_code') is-invalid @enderror" placeholder="Masukkan Kode Pos">
                            @error('postal_code')
                            <span class="invalid-feedback alert-danger" role="alert">
                                {{ $message }}
                            </span>
                            @enderror
                        </div>
                    </div>

                    <br>
                    <div class="col-md-12">
                        <br>
                        <div class="pull-left">
                            <button type="submit" class="primary-btn">Simpan</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
</div>

<!-- end template-->
<script>
    function previewFoto() {
        const foto = document.querySelector('input[name="photo"]');
        const fotoPreview = document.querySelector('.foto-preview');

        if (!foto.files.length) {
            return;
        }

        fotoPreview.style.display = 'block';
        const fotoReader = new FileReader();
        fotoReader.readAsDataURL(foto.files[0]);
        fotoReader.onload = function(fotoEvent) {
            fotoPreview.src = fotoEvent.target.result;
            fotoPreview.style.width = '100%';
        }
    }

    function hanyaAngka(event) {
        const angka = event.which ? event.which : event.keyCode;

        return !(angka > 31 && (angka < 48 || angka > 57));
    }
</script>
@endsection
