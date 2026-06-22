<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\StockOpname;
use App\Models\UserDivisionAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $opnames = $message
            ? StockOpname::whereRaw('1 = 0')->paginate(15)
            : StockOpname::with(['outlet', 'division', 'counter', 'items'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->latest()
                ->paginate(15)
                ->withQueryString();

        return view('staff.stock-opnames.index', [
            'message' => $message,
            'opnames' => $opnames,
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
        $search = $request->string('search')->toString();
        $itemType = $request->string('item_type')->toString();

        $items = collect();
        $stocks = collect();
        if (! $message && $operation?->status === 'open') {
            $items = InventoryItem::where('is_active', true)
                ->where('division_id', $divisionId)
                ->when($search, fn ($query) => $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                }))
                ->when($itemType, fn ($query) => $query->where('item_type', $itemType))
                ->orderBy('name')
                ->get();

            $stocks = InventoryStock::where('outlet_id', $outletId)
                ->whereIn('inventory_item_id', $items->pluck('id'))
                ->get()
                ->keyBy('inventory_item_id');
        }

        return view('staff.stock-opnames.create', [
            'message' => $message,
            'operation' => $operation,
            'items' => $items,
            'stocks' => $stocks,
            'itemTypes' => InventoryItem::TYPES,
            'outlets' => Outlet::where('is_active', true)->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(),
            'selectedOutletId' => $outletId,
            'selectedDivisionId' => $divisionId,
            'search' => $search,
            'itemType' => $itemType,
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
            return back()->withInput()->with('error', 'Buka Operasional Hari Ini terlebih dahulu sebelum Closing Stock Opname.');
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.physical_stock' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $rows = collect($data['items'])
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && $row['physical_stock'] !== null && $row['physical_stock'] !== '')
            ->values();

        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'Isi minimal satu physical stock.');
        }

        $ids = $rows->pluck('inventory_item_id')->map(fn ($id) => (int) $id);
        if ($ids->duplicates()->isNotEmpty()) {
            return back()->withInput()->with('error', 'Item tidak boleh duplikat dalam satu stock opname.');
        }

        $validItemIds = InventoryItem::where('is_active', true)
            ->where('division_id', $divisionId)
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            if (! in_array($id, $validItemIds, true)) {
                return back()->withInput()->with('error', 'Item harus aktif dan sesuai divisi.');
            }
        }

        $stockOpname = DB::transaction(function () use ($request, $rows, $operation, $outletId, $divisionId, $data) {
            $stockOpname = StockOpname::create([
                'division_daily_operation_id' => $operation->id,
                'outlet_id' => $outletId,
                'division_id' => $divisionId,
                'counted_by' => $request->user()->id,
                'opname_date' => today(),
                'opname_type' => 'closing',
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $outletCode = $operation->outlet?->code ?? 'OUT';
            $divisionCode = $operation->division?->code ?? 'DIV';
            $stockOpname->update([
                'opname_number' => sprintf('SO-%s-%s-%s-%03d', $outletCode, $divisionCode, today()->format('Ymd'), $stockOpname->id),
            ]);

            foreach ($rows as $row) {
                $item = InventoryItem::find($row['inventory_item_id']);
                $stock = InventoryStock::where('outlet_id', $outletId)->where('inventory_item_id', $item->id)->first();
                $systemStock = (float) ($stock?->current_stock ?? 0);
                $physicalStock = (float) $row['physical_stock'];

                $stockOpname->items()->create([
                    'inventory_item_id' => $item->id,
                    'system_stock' => $systemStock,
                    'physical_stock' => $physicalStock,
                    'difference_qty' => $physicalStock - $systemStock,
                    'unit' => $stock?->unit ?? $item->base_unit,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $stockOpname;
        });

        return redirect()->route('staff.stock-opnames.show', $stockOpname)->with('success', 'Closing Stock Opname berhasil dibuat.');
    }

    public function show(Request $request, StockOpname $stockOpname)
    {
        abort_unless($this->canAccess($request, $stockOpname), 403);

        return view('staff.stock-opnames.show', [
            'stockOpname' => $stockOpname->load(['outlet', 'division', 'counter', 'reviewer', 'dailyOperation', 'items.item']),
        ]);
    }

    public function submit(Request $request, StockOpname $stockOpname)
    {
        abort_unless($this->canAccess($request, $stockOpname), 403);

        if ($stockOpname->status !== 'draft') {
            return back()->with('error', 'Hanya draft yang bisa disubmit.');
        }

        $stockOpname->update(['status' => 'submitted', 'submitted_at' => now()]);

        return back()->with('success', 'Closing Stock Opname berhasil disubmit.');
    }

    public function cancel(Request $request, StockOpname $stockOpname)
    {
        abort_unless($this->canAccess($request, $stockOpname), 403);

        if (! in_array($stockOpname->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Stock opname ini tidak bisa dibatalkan.');
        }

        $stockOpname->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return back()->with('success', 'Closing Stock Opname berhasil dibatalkan.');
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

        $access = UserDivisionAccess::where('user_id', $user->id)
            ->where('can_input_stock', true)
            ->first();

        if (! $access) {
            return [null, null, 'Akun staff belum memiliki akses outlet/divisi.'];
        }

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

    private function canAccess(Request $request, StockOpname $stockOpname): bool
    {
        return $request->user()->hasAnyRole(['admin', 'super_admin'])
            || $request->user()->hasDivisionAccess($stockOpname->outlet_id, $stockOpname->division_id, 'can_input_stock');
    }
}
