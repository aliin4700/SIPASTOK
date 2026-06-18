<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class KategoriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kategori = Category::orderBy('name', 'asc')->get();
        return view('backend.v_kategori.index', [
            'judul' => 'Data Kategori',
            'index' => $kategori
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.v_kategori.create', [
            'judul' => 'Kategori',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request);
        $validatedData = $request->validate([
            'nama_kategori' => 'required|max:255|unique:categories,name',
        ]);
        Category::create([
            'name' => $validatedData['nama_kategori'],
            'slug' => Str::slug($validatedData['nama_kategori']),
            'status' => 'active',
        ]);
        return redirect()->route('backend.kategori.index')->with('success', 'Data berhasil tersimpan');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $kategori = Category::find($id);
        return view('backend.v_kategori.edit', [
            'judul' => 'Kategori',
            'edit' => $kategori
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $rules = [
            'nama_kategori' => 'required|max:255|unique:categories,name,' . $id,
        ];
        $validatedData = $request->validate($rules);
        Category::where('id', $id)->update([
            'name' => $validatedData['nama_kategori'],
            'slug' => Str::slug($validatedData['nama_kategori']),
        ]);
        return redirect()->route('backend.kategori.index')->with('success', 'Data berhasil diperbaharui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Category::findOrFail($id);
        $user->delete();
        return redirect()->route('backend.kategori.index')->with('success', 'Data berhasil dihapus');
    }
}
