<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return view('manager.suppliers.index', [
            'suppliers' => Supplier::query()
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                }))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Supplier $supplier)
    {
        return view('manager.suppliers.show', [
            'supplier' => $supplier->loadCount(['purchaseOrders', 'goodsReceipts']),
        ]);
    }
}
