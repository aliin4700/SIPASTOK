<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

/**
 * Menampilkan halaman beranda publik BreadLab.
 */
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
        // Ambil produk dan kategori terbaru sebagai highlight halaman depan.
        $products = Product::with('category')->latest()->take(6)->get();
        $categories = Category::orderBy('name')->take(6)->get();

        return view('v_beranda.index', [
            'judul' => 'BreadLab Bakery',
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
