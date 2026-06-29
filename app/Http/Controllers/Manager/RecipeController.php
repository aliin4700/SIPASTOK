<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        return view('manager.recipes.index', [
            'recipes' => Recipe::with(['product', 'outputInventoryItem', 'division', 'items'])
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('recipe_code', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('outputInventoryItem', fn ($item) => $item->where('name', 'like', "%{$search}%"));
                }))
                ->when($filters['division_id'] ?? null, fn ($query, $divisionId) => $query->where('division_id', $divisionId))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(Recipe $recipe)
    {
        return view('manager.recipes.show', [
            'recipe' => $recipe->load(['product', 'outputInventoryItem', 'division', 'createdBy', 'updatedBy', 'items.item']),
        ]);
    }
}
