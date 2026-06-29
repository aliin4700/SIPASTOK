<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class RecipeController extends Controller
{
    private const UNITS = ['pcs', 'butir', 'gr', 'kg', 'ml', 'liter', 'cm', 'pack', 'box', 'tray', 'botol', 'cup', 'slice', 'loaf', 'porsi'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        return view('backend.recipes.index', [
            'judul' => 'Recipe / BOM',
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

    public function create()
    {
        return view('backend.recipes.create', $this->formData(new Recipe(['is_active' => true, 'output_qty' => 1])) + [
            'judul' => 'Tambah Recipe / BOM',
        ]);
    }

    public function store(Request $request)
    {
        [$data, $rows] = $this->validatedPayload($request);

        $recipe = DB::transaction(function () use ($request, $data, $rows) {
            $recipe = Recipe::create($data + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->syncItems($recipe, $rows);

            return $recipe;
        });

        return redirect()->route('backend.recipes.show', $recipe)->with('success', 'Recipe/BOM berhasil dibuat.');
    }

    public function show(Recipe $recipe)
    {
        return view('backend.recipes.show', [
            'judul' => 'Detail Recipe / BOM',
            'recipe' => $recipe->load(['product', 'outputInventoryItem', 'division', 'createdBy', 'updatedBy', 'items.item']),
        ]);
    }

    public function edit(Recipe $recipe)
    {
        return view('backend.recipes.edit', $this->formData($recipe->load('items')) + [
            'judul' => 'Edit Recipe / BOM',
        ]);
    }

    public function update(Request $request, Recipe $recipe)
    {
        [$data, $rows] = $this->validatedPayload($request, $recipe);

        DB::transaction(function () use ($request, $recipe, $data, $rows) {
            $recipe->update($data + ['updated_by' => $request->user()->id]);
            $this->syncItems($recipe, $rows);
        });

        return redirect()->route('backend.recipes.show', $recipe)->with('success', 'Recipe/BOM berhasil diperbarui.');
    }

    private function formData(Recipe $recipe): array
    {
        return [
            'recipe' => $recipe,
            'products' => Product::orderBy('name')->get(),
            'inventoryItems' => InventoryItem::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'units' => self::UNITS,
        ];
    }

    private function validatedPayload(Request $request, ?Recipe $recipe = null): array
    {
        $data = $request->validate([
            'recipe_code' => ['nullable', 'string', 'max:255', Rule::unique('recipes', 'recipe_code')->ignore($recipe)],
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['nullable', 'exists:products,id'],
            'output_inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'output_qty' => ['required', 'numeric', 'min:0.01'],
            'output_unit' => ['nullable', 'string', 'max:50'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.waste_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
        ]) + ['is_active' => false];

        $rows = collect($data['items'] ?? [])
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && is_numeric($row['qty'] ?? null) && (float) $row['qty'] > 0)
            ->values()
            ->all();

        if ($rows === []) {
            throw ValidationException::withMessages(['items' => 'Minimal satu material recipe harus diisi.']);
        }

        $materialIds = collect($rows)->pluck('inventory_item_id')->map(fn ($id) => (int) $id);
        if ($materialIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => 'Material inventory item tidak boleh duplikat dalam satu recipe.']);
        }

        if (! empty($data['output_inventory_item_id']) && $materialIds->contains((int) $data['output_inventory_item_id'])) {
            throw ValidationException::withMessages(['items' => 'Material tidak boleh sama dengan output inventory item.']);
        }

        unset($data['items']);

        return [$data, $rows];
    }

    private function syncItems(Recipe $recipe, array $rows): void
    {
        $recipe->items()->delete();

        foreach ($rows as $row) {
            $recipe->items()->create([
                'inventory_item_id' => $row['inventory_item_id'],
                'qty' => $row['qty'],
                'unit' => $row['unit'] ?? null,
                'waste_percentage' => $row['waste_percentage'] ?? 0,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }
}
