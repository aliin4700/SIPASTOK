<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        return view('backend.suppliers.index', [
            'judul' => 'Supplier',
            'suppliers' => Supplier::query()
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                }))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('backend.suppliers.create', [
            'judul' => 'Tambah Supplier',
            'supplier' => new Supplier(['is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('backend.suppliers.index')->with('success', 'Supplier berhasil dibuat.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->loadCount(['purchaseOrders', 'goodsReceipts']);

        return view('backend.suppliers.show', [
            'judul' => 'Detail Supplier',
            'supplier' => $supplier,
        ]);
    }

    public function edit(Supplier $supplier)
    {
        return view('backend.suppliers.edit', [
            'judul' => 'Edit Supplier',
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('backend.suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('suppliers', 'code')->ignore($supplier)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }
}
