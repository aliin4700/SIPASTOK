<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\DivisionDailyOperation;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MaterialRequest;
use App\Models\Outlet;
use App\Models\UserDivisionAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialRequestController extends Controller
{
    public function index(Request $request)
    {
        [$outletId, $divisionId, $message] = $this->context($request);

        $requests = $message
            ? MaterialRequest::whereRaw('1 = 0')->paginate(15)
            : MaterialRequest::with(['outlet', 'division', 'requester', 'items'])
                ->where('outlet_id', $outletId)
                ->where('division_id', $divisionId)
                ->latest()
                ->paginate(15)
                ->withQueryString();

        return view('staff.material-requests.index', [
            'message' => $message,
            'requests' => $requests,
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
                ->whereIn('item_type', ['raw_material', 'wip', 'packaging'])
                ->orderBy('name')
                ->get();

            $stocks = InventoryStock::where('outlet_id', $outletId)
                ->whereIn('inventory_item_id', $items->pluck('id'))
                ->get()
                ->keyBy('inventory_item_id');
        }

        return view('staff.material-requests.create', [
            'message' => $message,
            'operation' => $operation,
            'items' => $items,
            'stocks' => $stocks,
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
            return back()->withInput()->with('error', 'Buka Operasional Hari Ini terlebih dahulu sebelum membuat Take Bahan.');
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.requested_qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $rows = collect($data['items'])
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && ! empty($row['requested_qty']))
            ->values();

        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'Isi minimal satu item Take Bahan.');
        }

        $validItemIds = InventoryItem::where('is_active', true)
            ->where('division_id', $divisionId)
            ->whereIn('item_type', ['raw_material', 'wip', 'packaging'])
            ->pluck('id')
            ->all();

        foreach ($rows as $row) {
            if (! in_array((int) $row['inventory_item_id'], $validItemIds, true)) {
                return back()->withInput()->with('error', 'Item harus aktif dan sesuai divisi.');
            }
        }

        $materialRequest = DB::transaction(function () use ($request, $rows, $operation, $outletId, $divisionId, $data) {
            $materialRequest = MaterialRequest::create([
                'division_daily_operation_id' => $operation->id,
                'outlet_id' => $outletId,
                'division_id' => $divisionId,
                'requested_by' => $request->user()->id,
                'request_date' => today(),
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            $outletCode = $operation->outlet?->code ?? 'OUT';
            $divisionCode = $operation->division?->code ?? 'DIV';
            $materialRequest->update([
                'request_number' => sprintf('MR-%s-%s-%s-%03d', $outletCode, $divisionCode, today()->format('Ymd'), $materialRequest->id),
            ]);

            foreach ($rows as $row) {
                $item = InventoryItem::find($row['inventory_item_id']);
                $materialRequest->items()->create([
                    'inventory_item_id' => $item->id,
                    'requested_qty' => $row['requested_qty'],
                    'unit' => $row['unit'] ?: $item->base_unit,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            return $materialRequest;
        });

        return redirect()->route('staff.material-requests.show', $materialRequest)->with('success', 'Take Bahan berhasil dibuat.');
    }

    public function show(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        return view('staff.material-requests.show', [
            'materialRequest' => $materialRequest->load(['outlet', 'division', 'requester', 'dailyOperation', 'items.item']),
        ]);
    }

    public function submit(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        if ($materialRequest->status !== 'draft') {
            return back()->with('error', 'Hanya draft yang bisa disubmit.');
        }

        $materialRequest->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Take Bahan berhasil disubmit.');
    }

    public function cancel(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        if (! in_array($materialRequest->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Take Bahan ini tidak bisa dibatalkan.');
        }

        $materialRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Take Bahan berhasil dibatalkan.');
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
                $query->where('can_request_order', true)
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

    private function canAccess(Request $request, MaterialRequest $materialRequest): bool
    {
        return $request->user()->hasAnyRole(['admin', 'super_admin'])
            || $request->user()->hasDivisionAccess($materialRequest->outlet_id, $materialRequest->division_id, 'can_request_order')
            || $request->user()->hasDivisionAccess($materialRequest->outlet_id, $materialRequest->division_id, 'can_input_stock');
    }
}
