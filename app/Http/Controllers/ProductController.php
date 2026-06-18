<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(10);

        return view('backend.v_product.index', [
            'judul' => 'Data Produk',
            'products' => $products,
        ]);
    }

    public function create()
    {
        return view('backend.v_product.create', [
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
            $validated['main_photo'] = $request->file('image')->store('products', 'public');
            unset($validated['image']);
        }

        Product::create($validated);

        return redirect()->route('backend.product.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        return view('backend.v_product.show', [
            'judul' => 'Detail Produk',
            'product' => $product->load('category', 'photos'),
        ]);
    }

    public function edit(Product $product)
    {
        return view('backend.v_product.edit', [
            'judul' => 'Edit Produk',
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validatedData($request, $product);

        if ($product->name !== $validated['name']) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $product);
        }

        if ($request->hasFile('image')) {
            if ($product->main_photo) {
                Storage::disk('public')->delete($product->main_photo);
            }

            $validated['main_photo'] = $request->file('image')->store('products', 'public');
            unset($validated['image']);
        }

        $product->update($validated);

        return redirect()->route('backend.product.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->main_photo) {
            Storage::disk('public')->delete($product->main_photo);
        }

        foreach ($product->photos as $photo) {
            Storage::disk('public')->delete($photo->photo);
        }

        $product->delete();

        return redirect()->route('backend.product.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function catalog(Request $request)
    {
        $products = Product::with('category')
            ->where('status', 'published')
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

        return view('v_product.index', [
            'judul' => 'Katalog Produk',
            'products' => $products,
            'categories' => $this->categories(),
        ]);
    }

    public function category(Category $category)
    {
        $products = Product::with('category')
            ->where('status', 'published')
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('v_product.category', [
            'judul' => 'Produk ' . $category->name,
            'products' => $products,
            'categories' => $this->categories(),
            'selectedCategory' => $category,
        ]);
    }

    public function detail(string $product)
    {
        $row = Product::with('category', 'photos')
            ->where('status', 'published')
            ->where(function ($query) use ($product) {
                $query->where('slug', $product)
                    ->orWhere('id', $product);
            })
            ->firstOrFail();

        return view('v_product.detail', [
            'judul' => $row->name,
            'product' => $row,
        ]);
    }

    public function storePhoto(Request $request, Product $product)
    {
        $validated = $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ]);

        foreach ($validated['photos'] as $photo) {
            $product->photos()->create([
                'photo' => $photo->store('products', 'public'),
            ]);
        }

        return redirect()->route('backend.product.show', $product)->with('success', 'Foto produk berhasil ditambahkan.');
    }

    public function destroyPhoto(ProductPhoto $photo)
    {
        $product = $photo->product;
        Storage::disk('public')->delete($photo->photo);
        $photo->delete();

        return redirect()->route('backend.product.show', $product)->with('success', 'Foto produk berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($product)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
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
