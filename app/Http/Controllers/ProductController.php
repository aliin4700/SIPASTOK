<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(10);

        return view('backend.products.index', [
            'judul' => 'Data Produk',
            'products' => $products,
        ]);
    }

    public function create()
    {
        return view('backend.products.create', [
            'judul' => 'Tambah Produk',
            'product' => new Product(),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('backend.produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $produk)
    {
        return redirect()->route('backend.produk.edit', $produk);
    }

    public function edit(Product $produk)
    {
        return view('backend.products.edit', [
            'judul' => 'Edit Produk',
            'product' => $produk,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Product $produk)
    {
        $validated = $this->validatedData($request, $produk);

        if ($produk->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $produk);
        }

        if ($request->hasFile('image')) {
            if ($produk->image) {
                Storage::disk('public')->delete($produk->image);
            }

            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $produk->update($validated);

        return redirect()->route('backend.produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $produk)
    {
        if ($produk->image) {
            Storage::disk('public')->delete($produk->image);
        }

        $produk->delete();

        return redirect()->route('backend.produk.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function catalog(Request $request)
    {
        $products = Product::with('category')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category'));
            })
            ->when($request->filled('min_price'), function ($query) use ($request) {
                $query->where('price', '>=', $request->integer('min_price'));
            })
            ->when($request->filled('max_price'), function ($query) use ($request) {
                $query->where('price', '<=', $request->integer('max_price'));
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('products.catalog', [
            'judul' => 'Katalog Produk',
            'products' => $products,
            'categories' => $this->categories(),
        ]);
    }

    public function category(int $id)
    {
        return redirect()->route('produk.all', ['category' => $id]);
    }

    public function detail(string $product)
    {
        $row = Product::with('category')
            ->where('slug', $product)
            ->orWhere('id', $product)
            ->firstOrFail();

        return view('products.detail', [
            'judul' => $row->name,
            'product' => $row,
        ]);
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($product)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => [$product ? 'nullable' : 'required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ]);
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Product::where('slug', $slug)->when($product, fn ($query) => $query->whereKeyNot($product->id))->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function categories()
    {
        return Category::orderBy('name')->get();
    }
}
