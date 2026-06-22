<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\ProductionRecord;
use App\Models\UserDivisionAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductionRecordController extends Controller
{
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
        $stocks = collect();
        if (! $message && $operation?->status === 'open') {
            $items = InventoryItem::where('is_active', true)
                ->where('division_id', $divisionId)
                ->whereIn('item_type', ['wip', 'finished_goods'])
                ->orderBy('name')
                ->get();

            $stocks = InventoryStock::where('outlet_id', $outletId)
                ->whereIn('inventory_item_id', $items->pluck('id'))
                ->get()
                ->keyBy('inventory_item_id');
        }

        return view('staff.production.create', [
            'message' => $message,
            'operation' => $operation,
            'items' => $items,
            'stocks' => $stocks,
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
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $rows = collect($data['items'])
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && ! empty($row['qty']))
            ->values();

        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'Isi minimal satu item produksi.');
        }

        $validItemIds = InventoryItem::where('is_active', true)
            ->where('division_id', $divisionId)
            ->whereIn('item_type', ['wip', 'finished_goods'])
            ->pluck('id')
            ->all();

        foreach ($rows as $row) {
            if (! in_array((int) $row['inventory_item_id'], $validItemIds, true)) {
                return back()->withInput()->with('error', 'Item harus aktif, sesuai divisi, dan bertipe WIP/finished goods.');
            }
        }

        $productionRecord = DB::transaction(function () use ($request, $rows, $operation, $outletId, $divisionId, $data) {
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

            foreach ($rows as $row) {
                $item = InventoryItem::find($row['inventory_item_id']);
                $productionRecord->items()->create([
                    'inventory_item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_type' => $item->item_type,
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?: $item->base_unit,
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

    private function canAccess(Request $request, ProductionRecord $productionRecord): bool
    {
        return $request->user()->hasAnyRole(['admin', 'super_admin'])
            || $request->user()->hasDivisionAccess($productionRecord->outlet_id, $productionRecord->division_id, 'can_input_stock');
    }
}
