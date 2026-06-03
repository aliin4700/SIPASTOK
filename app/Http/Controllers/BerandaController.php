<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Product;

class BerandaController extends Controller
{
    public function berandaBackend()
    {
        return view('backend.v_beranda.index', [
            'judul' => 'Beranda',
            'sub' => 'Halaman Beranda',
        ]);
    }

    public function index()
    {
        $products = Product::with('category')->latest()->take(6)->get();
        $categories = Kategori::orderBy('nama_kategori')->take(6)->get();

        return view('v_beranda.index', [
            'judul' => 'BreadLab Bakery',
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
