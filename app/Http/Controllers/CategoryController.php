<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * CRUD kategori produk backend.
 */
class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        return view('backend.v_category.index', [
            'judul' => 'Data Kategori',
            'index' => $categories,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return view('backend.v_category.create', [
            'judul' => 'Kategori',
        ]);
    }

    public function store(Request $request)
    {
        // Nama kategori dibuat unik agar slug katalog tidak bentrok.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('backend.category.index')->with('success', 'Data berhasil tersimpan');
    }

    public function edit(Category $category)
    {
        return view('backend.v_category.edit', [
            'judul' => 'Kategori',
            'edit' => $category,
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => $category->name === $validated['name'] ? $category->slug : $this->uniqueSlug($validated['name'], $category),
            'status' => $validated['status'] ?? $category->status,
        ]);

        return redirect()->route('backend.category.index')->with('success', 'Data berhasil diperbaharui');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('backend.category.index')->with('success', 'Data berhasil dihapus');
    }

    private function uniqueSlug(string $name, ?Category $category = null): string
    {
        // Slug dipakai untuk URL kategori produk.
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Category::where('slug', $slug)->when($category, fn ($query) => $query->whereKeyNot($category->id))->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
