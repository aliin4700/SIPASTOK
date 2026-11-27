<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\Recipe;
use App\Models\UserDivisionAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductionRecordController extends Controller
{
    /** Hanya WIP dan Produk Jadi yang boleh diproduksi. */
    private const PRODUCTION_ITEM_TYPES = ['wip', 'finished_goods'];

    public function index(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $records = $message
            ? ProductionRecord::whereRaw('1 = 0')->paginate(15)
            : ProductionRecord::with(['outlet', 'division', 'producer', 'items'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->latest()
                ->paginate(15)
                ->withQueryString();

        return view('staff.production.index', [
            'message' => $message,
            'records' => $records,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
        ]);
    }

    public function create(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);
        $operation = $this->openOperation($outletId, $divisionId);

        $items = collect();
        $groupedItems = [
            'alert' => collect(),
            'wip_and_recipe' => collect(),
            'other' => collect(),
        ];
        $stocks = collect();
        $recipes = collect();
        $recipesByItemId = collect();
        $openingAlerts = collect();
        $alertMap = [];

        if (! $message && $operation?->status === 'open') {
            $recipes = $this->activeDivisionRecipes((int) $divisionId);
            $recipesByItemId = $recipes->keyBy('output_inventory_item_id');

            $allDivisionItems = $this->selectableDivisionItems((int) $divisionId, $recipes);

            $stocks = InventoryStock::where('outlet_id', $outletId)
                ->whereIn('inventory_item_id', $allDivisionItems->pluck('id'))
                ->get()
                ->keyBy('inventory_item_id');

            foreach ($allDivisionItems as $item) {
                $stock = $stocks->get($item->id);
                $currentStock = (float) ($stock?->current_stock ?? 0);
                $minStock = (float) ($item->min_stock ?? 0);
                $isProductionType = true;

                // Item masuk prioritas Opening Alert jika:
                // 1) memiliki min_stock > 0 dan current_stock <= min_stock, atau
                // 2) merupakan item WIP/Finished Goods/Output Resep yang stoknya habis (<= 0).
                $isBelowMinThreshold = $minStock > 0 && $currentStock <= $minStock;
                $isZeroWipOrRecipe = $isProductionType && $currentStock <= 0;

                if ($isBelowMinThreshold || $isZeroWipOrRecipe) {
                    $recipe = $recipesByItemId->get($item->id);
                    $shortage = $minStock > $currentStock
                        ? round($minStock - $currentStock, 2)
                        : (float) ($recipe?->output_qty ?: 1);

                    $category = $currentStock <= 0 ? 'WIP_EMPTY' : 'WIP_LOW';
                    $alertEntry = [
                        'item' => $item,
                        'stock' => $stock,
                        'recipe' => $recipe,
                        'category' => $category,
                        'badge_label' => $currentStock <= 0 ? 'STOK HABIS (0)' : 'STOK KRITIS',
                        'has_min_threshold' => $isBelowMinThreshold,
                        'current_stock' => $currentStock,
                        'min_stock' => $minStock,
                        'unit' => $recipe?->output_unit ?: ($stock?->unit ?: $item->base_unit),
                        'suggested_qty' => max(1, $shortage),
                    ];

                    $alertMap[$item->id] = $alertEntry;
                }
            }

            // Urutkan Opening Alert: item dengan min_stock > 0 di paling atas, lalu berdasarkan nama
            $openingAlerts = collect($alertMap)
                ->sortBy([
                    fn (array $a, array $b) => ($b['has_min_threshold'] <=> $a['has_min_threshold']),
                    fn (array $a, array $b) => ($a['current_stock'] <=> $b['current_stock']),
                    fn (array $a, array $b) => strcmp($a['item']->name, $b['item']->name),
                ])
                ->values();

            foreach ($openingAlerts as $alertEntry) {
                $groupedItems['alert']->push($alertEntry['item']);
            }

            foreach ($allDivisionItems as $item) {
                if (isset($alertMap[$item->id])) {
                    continue;
                }

                $groupedItems['wip_and_recipe']->push($item);
            }

            // Gabungkan seluruh item: Prioritas Opening Alert -> WIP & Resep Aktif -> Seluruh Item Divisi Lainnya
            $items = $groupedItems['alert']
                ->concat($groupedItems['wip_and_recipe'])
                ->concat($groupedItems['other'])
                ->values();
        }

        return view('staff.production.create', [
            'message' => $message,
            'operation' => $operation,
            'items' => $items,
            'groupedItems' => $groupedItems,
            'stocks' => $stocks,
            'recipes' => $recipes,
            'recipesByItemId' => $recipesByItemId,
            'openingAlerts' => $openingAlerts,
            'alertMap' => $alertMap,
            'types' => ProductionRecord::TYPES,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
        ]);
    }

    public function store(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request, true);
        if ($message || ! $outletId || ! $divisionId) {
            return back()->withInput()->with('error', 'Akun staff belum memiliki akses outlet/divisi.');
        }

        $operation = $this->openOperation($outletId, $divisionId);
        if (! $operation || $operation->status !== 'open') {
            return back()->withInput()->with('error', 'Buka Operasional Hari Ini terlebih dahulu sebelum mencatat Produksi / WIP.');
        }

        $data = $request->validate([
            'production_type' => ['required', Rule::in(ProductionRecord::TYPES)],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.recipe_id' => ['nullable', 'exists:recipes,id'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $recipes = $this->activeDivisionRecipes((int) $divisionId);
        $recipesById = $recipes->keyBy('id');
        $recipesByItemId = $recipes->keyBy('output_inventory_item_id');

        $rows = collect($data['items'])
            ->map(function (array $row) use ($recipesById) {
                if (empty($row['inventory_item_id']) && ! empty($row['recipe_id'])) {
                    $recipe = $recipesById->get((int) $row['recipe_id']);
                    if ($recipe && $recipe->output_inventory_item_id) {
                        $row['inventory_item_id'] = $recipe->output_inventory_item_id;
                        $row['unit'] = $row['unit'] ?? $recipe->output_unit;
                    }
                }

                return $row;
            })
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && ! empty($row['qty']))
            ->values();

        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'Isi minimal satu item produksi.');
        }

        $validItemIds = $this->selectableDivisionItems((int) $divisionId, $recipes)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($rows as $row) {
            if (! in_array((int) $row['inventory_item_id'], $validItemIds, true)) {
                return back()->withInput()->with('error', 'Item produksi harus berkategori WIP atau Produk Jadi, aktif, dan terdaftar pada divisi ini.');
            }
        }

        $productionRecord = DB::transaction(function () use ($request, $rows, $operation, $outletId, $divisionId, $data, $recipesByItemId) {
            $productionRecord = ProductionRecord::create([
                'division_daily_operation_id' => $operation->id,
                'outlet_id' => $outletId,
                'division_id' => $divisionId,
                'produced_by' => $request->user()->id,
                'production_date' => today(),
                'production_type' => $data['production_type'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $outletCode = $operation->outlet?->code ?? 'OUT';
            $divisionCode = $operation->division?->code ?? 'DIV';
            $productionRecord->update([
                'production_number' => sprintf('PRD-%s-%s-%s-%03d', $outletCode, $divisionCode, today()->format('Ymd'), $productionRecord->id),
            ]);

            $itemsById = InventoryItem::whereIn('id', $rows->pluck('inventory_item_id'))->get()->keyBy('id');

            foreach ($rows as $row) {
                $item = $itemsById->get((int) $row['inventory_item_id']);
                if (! $item) {
                    continue;
                }

                $recipe = $recipesByItemId->get($item->id);
                $defaultUnit = $recipe?->output_unit ?: $item->base_unit;

                $productionRecord->items()->create([
                    'inventory_item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_type' => $item->item_type,
                    'qty' => $row['qty'],
                    'unit' => ! empty($row['unit']) ? $row['unit'] : $defaultUnit,
                    'estimated_value' => $row['estimated_value'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $productionRecord;
        });

        return redirect()->route('staff.production.show', $productionRecord)->with('success', 'Production record berhasil dibuat.');
    }

    public function show(Request $request, ProductionRecord $productionRecord)
    {
        abort_unless($this->canAccess($request, $productionRecord), 403);

        return view('staff.production.show', [
            'productionRecord' => $productionRecord->load(['outlet', 'division', 'producer', 'dailyOperation', 'items.item']),
        ]);
    }

    public function submit(Request $request, ProductionRecord $productionRecord)
    {
        abort_unless($this->canAccess($request, $productionRecord), 403);

        if ($productionRecord->status !== 'draft') {
            return back()->with('error', 'Hanya draft yang bisa disubmit.');
        }

        $productionRecord->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Production record berhasil disubmit.');
    }

    public function cancel(Request $request, ProductionRecord $productionRecord)
    {
        abort_unless($this->canAccess($request, $productionRecord), 403);

        if (! in_array($productionRecord->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Production record ini tidak bisa dibatalkan.');
        }

        $productionRecord->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Production record berhasil dibatalkan.');
    }

    /**
     * Ambil seluruh resep aktif untuk divisi ini (atau yang output item-nya milik divisi ini).
     */
    private function activeDivisionRecipes(int $divisionId): Collection
    {
        return Recipe::with(['outputInventoryItem', 'items.item'])
            ->where('is_active', true)
            ->whereNotNull('output_inventory_item_id')
            ->where(function ($query) use ($divisionId) {
                $query->where('division_id', $divisionId)
                    ->orWhereHas('outputInventoryItem', fn ($itemQuery) => $itemQuery
                        ->where('division_id', $divisionId)
                        ->where('is_active', true));
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Ambil seluruh item aktif yang boleh dipilih pada form produksi divisi:
     * mencakup WIP, Finished Goods, output dari Resep aktif, serta bahan/item aktif di divisi tersebut.
     */
    private function selectableDivisionItems(int $divisionId, Collection $recipes): Collection
    {
        $recipeOutputIds = $recipes
            ->pluck('output_inventory_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return InventoryItem::where('is_active', true)
            ->where(function ($query) use ($divisionId, $recipeOutputIds) {
                $query->where(function ($inner) use ($divisionId) {
                    $inner->where('division_id', $divisionId);
                });

                if (! empty($recipeOutputIds)) {
                    $query->orWhereIn('id', $recipeOutputIds);
                }
            })
            ->whereIn('item_type', self::PRODUCTION_ITEM_TYPES)
            ->orderBy('name')
            ->get();
    }

    private function context(Request $request, bool $validateAdminInput = false): array
    {
        $user = $request->user();

        if ($user->hasAnyRole(['admin', 'super_admin'])) {
            if ($validateAdminInput) {
                $data = $request->validate([
                    'outlet_id' => ['required', 'exists:outlets,id'],
                    'division_id' => ['required', 'exists:divisions,id'],
                ]);

                return [(int) $data['outlet_id'], (int) $data['division_id'], null];
            }

            return [
                $request->integer('outlet_id') ?: Outlet::where('is_active', true)->value('id'),
                $request->integer('division_id') ?: Division::where('is_active', true)->value('id'),
                null,
            ];
        }

        $accesses = UserDivisionAccess::where('user_id', $user->id)
            ->where('can_input_stock', true)
            ->get();

        if ($accesses->isEmpty()) {
            return [null, null, 'Akun staff belum memiliki akses outlet/divisi.'];
        }

        $reqOutlet = $request->integer('outlet_id');
        $reqDivision = $request->integer('division_id');

        $access = $accesses->first(fn ($a) => ($reqOutlet ? (int) $a->outlet_id === $reqOutlet : true)
            && ($reqDivision ? (int) $a->division_id === $reqDivision : true)) ?? $accesses->first();

        return [$access->outlet_id, $access->division_id, null];
    }

    private function openOperation(?int $outletId, ?int $divisionId): ?DivisionDailyOperation
    {
        if (! $outletId || ! $divisionId) {
            return null;
        }

        return DivisionDailyOperation::with(['outlet', 'division'])
            ->where('outlet_id', $outletId)
            ->where('division_id', $divisionId)
            ->whereDate('operation_date', today())
            ->first();
    }

    private function canAccess(Request $request, ProductionRecord $productionRecord): bool
    {
        return $request->user()->hasAnyRole(['admin', 'super_admin'])
            || $request->user()->hasDivisionAccess($productionRecord->outlet_id, $productionRecord->division_id, 'can_input_stock');
    }
}
