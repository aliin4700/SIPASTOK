<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index()
    {
        return redirect()->route('backend.produk.index');
    }

    public function create()
    {
        return redirect()->route('backend.produk.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('backend.produk.index');
    }

    public function show(string $id)
    {
        return redirect()->route('backend.produk.show', $id);
    }

    public function edit(string $id)
    {
        return redirect()->route('backend.produk.edit', $id);
    }

    public function update(Request $request, string $id)
    {
        return redirect()->route('backend.produk.index');
    }

    public function destroy($id)
    {
        return redirect()->route('backend.produk.index');
    }

    public function storeFoto(Request $request)
    {
        return back()->with('error', 'Manajemen foto produk akan dirapikan pada refactor product berikutnya.');
    }

    public function destroyFoto($id)
    {
        return back()->with('error', 'Manajemen foto produk akan dirapikan pada refactor product berikutnya.');
    }

    public function formProduk()
    {
        return redirect()->route('backend.produk.index');
    }

    public function cetakProduk(Request $request)
    {
        return redirect()->route('backend.produk.index');
    }

    public function detail($id)
    {
        return redirect()->route('produk.detail', $id);
    }

    public function produkKategori($id)
    {
        return redirect()->route('produk.kategori', $id);
    }
}
