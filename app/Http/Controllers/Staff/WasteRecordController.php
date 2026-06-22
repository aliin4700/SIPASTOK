<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Outlet;
use App\Models\UserDivisionAccess;
use App\Models\WasteRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WasteRecordController extends Controller
{
    public function index(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $records = $message
            ? WasteRecord::whereRaw('1 = 0')->paginate(15)
            : WasteRecord::with(['outlet', 'division', 'recorder', 'items'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->latest()
                ->paginate(15)
                ->withQueryString();

        return view('staff.waste.index', [
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
                ->whereIn('item_type', InventoryItem::TYPES)
                ->orderBy('name')
                ->get();

            $stocks = InventoryStock::where('outlet_id', $outletId)
                ->whereIn('inventory_item_id', $items->pluck('id'))
                ->get()
                ->keyBy('inventory_item_id');
        }

        return view('staff.waste.create', [
            'message' => $message,
            'operation' => $operation,
            'items' => $items,
            'stocks' => $stocks,
            'reasons' => WasteRecord::REASONS,
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
            return back()->withInput()->with('error', 'Buka Operasional Hari Ini terlebih dahulu sebelum mencatat Waste.');
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.estimated_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', Rule::in(WasteRecord::REASONS)],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $rows = collect($data['items'])
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && ! empty($row['qty']))
            ->values();

        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'Isi minimal satu item waste.');
        }

        $validItemIds = InventoryItem::where('is_active', true)
            ->where('division_id', $divisionId)
            ->whereIn('item_type', InventoryItem::TYPES)
            ->pluck('id')
            ->all();

        foreach ($rows as $row) {
            if (! in_array((int) $row['inventory_item_id'], $validItemIds, true) || empty($row['reason'])) {
                return back()->withInput()->with('error', 'Item harus aktif, sesuai divisi, dan alasan waste wajib diisi.');
            }
        }

        $wasteRecord = DB::transaction(function () use ($request, $rows, $operation, $outletId, $divisionId, $data) {
            $wasteRecord = WasteRecord::create([
                'division_daily_operation_id' => $operation->id,
                'outlet_id' => $outletId,
                'division_id' => $divisionId,
                'recorded_by' => $request->user()->id,
                'waste_date' => today(),
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $outletCode = $operation->outlet?->code ?? 'OUT';
            $divisionCode = $operation->division?->code ?? 'DIV';
            $wasteRecord->update([
                'waste_number' => sprintf('WR-%s-%s-%s-%03d', $outletCode, $divisionCode, today()->format('Ymd'), $wasteRecord->id),
            ]);

            foreach ($rows as $row) {
                $item = InventoryItem::find($row['inventory_item_id']);
                $wasteRecord->items()->create([
                    'inventory_item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_type' => $item->item_type,
                    'qty' => $row['qty'],
                    'unit' => $row['unit'] ?: $item->base_unit,
                    'estimated_value' => $row['estimated_value'] ?? null,
                    'reason' => $row['reason'],
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $wasteRecord;
        });

        return redirect()->route('staff.waste.show', $wasteRecord)->with('success', 'Waste record berhasil dibuat.');
    }

    public function show(Request $request, WasteRecord $wasteRecord)
    {
        abort_unless($this->canAccess($request, $wasteRecord), 403);

        return view('staff.waste.show', [
            'wasteRecord' => $wasteRecord->load(['outlet', 'division', 'recorder', 'dailyOperation', 'items.item']),
        ]);
    }

    public function submit(Request $request, WasteRecord $wasteRecord)
    {
        abort_unless($this->canAccess($request, $wasteRecord), 403);

        if ($wasteRecord->status !== 'draft') {
            return back()->with('error', 'Hanya draft yang bisa disubmit.');
        }

        $wasteRecord->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Waste record berhasil disubmit.');
    }

    public function cancel(Request $request, WasteRecord $wasteRecord)
    {
        abort_unless($this->canAccess($request, $wasteRecord), 403);

        if (! in_array($wasteRecord->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Waste record ini tidak bisa dibatalkan.');
        }

        $wasteRecord->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Waste record berhasil dibatalkan.');
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
            ->where(function ($query) {
                $query->where('can_input_waste', true)
                    ->orWhere('can_input_stock', true);
            })
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

    private function canAccess(Request $request, WasteRecord $wasteRecord): bool
    {
        return $request->user()->hasAnyRole(['admin', 'super_admin'])
            || $request->user()->hasDivisionAccess($wasteRecord->outlet_id, $wasteRecord->division_id, 'can_input_waste')
            || $request->user()->hasDivisionAccess($wasteRecord->outlet_id, $wasteRecord->division_id, 'can_input_stock');
    }
}
