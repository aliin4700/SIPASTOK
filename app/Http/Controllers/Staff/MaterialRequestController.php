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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MaterialRequestController extends Controller
{
    /** Stok In hanya untuk Raw Material. */
    private const REQUEST_ITEM_TYPES = ['raw_material'];

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
            [$items, $stocks] = $this->formItemsAndStocks((int) $outletId, (int) $divisionId);
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
            return back()->withInput()->with('error', 'Buka Operasional Hari Ini terlebih dahulu sebelum membuat Stok In.');
        }

        $data = $this->validateRequestPayload($request);
        [$rows, $rowError] = $this->validatedItemRows($data['items'], (int) $divisionId);
        if ($rowError) {
            return back()->withInput()->with('error', $rowError);
        }

        $receiptData = $request->hasFile('receipt')
            ? MaterialRequest::storeUploadedReceipt($request->file('receipt'))
            : ['receipt_path' => null, 'receipt_original_name' => null];

        try {
            $materialRequest = DB::transaction(function () use ($request, $rows, $operation, $outletId, $divisionId, $data, $receiptData) {
                $materialRequest = MaterialRequest::create([
                    'division_daily_operation_id' => $operation->id,
                    'outlet_id' => $outletId,
                    'division_id' => $divisionId,
                    'requested_by' => $request->user()->id,
                    'request_date' => today(),
                    'status' => 'draft',
                    'notes' => $data['notes'] ?? null,
                    'receipt_path' => $receiptData['receipt_path'],
                    'receipt_original_name' => $receiptData['receipt_original_name'],
                ]);

                $outletCode = $operation->outlet?->code ?? 'OUT';
                $divisionCode = $operation->division?->code ?? 'DIV';
                $materialRequest->update([
                    'request_number' => sprintf('MR-%s-%s-%s-%03d', $outletCode, $divisionCode, today()->format('Ymd'), $materialRequest->id),
                ]);

                $this->syncRequestItems($materialRequest, $rows);

                return $materialRequest;
            });
        } catch (Throwable $exception) {
            MaterialRequest::deleteStoredReceipt($receiptData['receipt_path']);
            throw $exception;
        }

        return redirect()->route('staff.material-requests.show', $materialRequest)->with('success', 'Stok In berhasil dibuat.');
    }

    public function show(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        return view('staff.material-requests.show', [
            'materialRequest' => $materialRequest->load(['outlet', 'division', 'requester', 'dailyOperation', 'items.item']),
        ]);
    }

    public function edit(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        if ($materialRequest->status !== 'draft') {
            return redirect()
                ->route('staff.material-requests.show', $materialRequest)
                ->with('error', 'Hanya dokumen berstatus draft yang dapat diedit.');
        }

        $materialRequest->load(['outlet', 'division', 'items.item']);
        [$items, $stocks] = $this->formItemsAndStocks(
            (int) $materialRequest->outlet_id,
            (int) $materialRequest->division_id
        );

        return view('staff.material-requests.edit', [
            'materialRequest' => $materialRequest,
            'items' => $items,
            'stocks' => $stocks,
        ]);
    }

    public function update(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        if ($materialRequest->status !== 'draft') {
            return redirect()
                ->route('staff.material-requests.show', $materialRequest)
                ->with('error', 'Hanya dokumen berstatus draft yang dapat diedit.');
        }

        $data = $this->validateRequestPayload($request, includeRemoveReceipt: true);
        [$rows, $rowError] = $this->validatedItemRows($data['items'], (int) $materialRequest->division_id);
        if ($rowError) {
            return back()->withInput()->with('error', $rowError);
        }

        $oldReceiptPath = $materialRequest->receipt_path;
        $newReceiptData = $request->hasFile('receipt')
            ? MaterialRequest::storeUploadedReceipt($request->file('receipt'))
            : null;
        $removeReceipt = ! $newReceiptData && $request->boolean('remove_receipt');

        try {
            DB::transaction(function () use ($materialRequest, $data, $rows, $newReceiptData, $removeReceipt) {
                $attributes = [
                    'notes' => $data['notes'] ?? null,
                ];

                if ($newReceiptData) {
                    $attributes['receipt_path'] = $newReceiptData['receipt_path'];
                    $attributes['receipt_original_name'] = $newReceiptData['receipt_original_name'];
                } elseif ($removeReceipt) {
                    $attributes['receipt_path'] = null;
                    $attributes['receipt_original_name'] = null;
                }

                $materialRequest->update($attributes);
                $materialRequest->items()->delete();
                $this->syncRequestItems($materialRequest, $rows);
            });
        } catch (Throwable $exception) {
            MaterialRequest::deleteStoredReceipt($newReceiptData['receipt_path'] ?? null);
            throw $exception;
        }

        if ($oldReceiptPath && ($newReceiptData || $removeReceipt)) {
            MaterialRequest::deleteStoredReceipt($oldReceiptPath);
        }

        return redirect()->route('staff.material-requests.show', $materialRequest)->with('success', 'Stok In berhasil diperbarui.');
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

        return back()->with('success', 'Stok In berhasil disubmit.');
    }

    public function cancel(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        if (! in_array($materialRequest->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Stok In ini tidak bisa dibatalkan.');
        }

        $materialRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Stok In berhasil dibatalkan.');
    }

    public function updateReceipt(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        if (! in_array($materialRequest->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Bukti struk tidak dapat diubah pada status ini.');
        }

        $request->validate(
            ['receipt' => MaterialRequest::RECEIPT_REQUIRED_VALIDATION_RULES],
            MaterialRequest::RECEIPT_VALIDATION_MESSAGES
        );

        $materialRequest->replaceReceipt($request->file('receipt'));

        return back()->with('success', 'Bukti struk / nota berhasil disimpan.');
    }

    public function deleteReceipt(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        if (! in_array($materialRequest->status, ['draft', 'submitted'], true)) {
            return back()->with('error', 'Bukti struk tidak dapat dihapus pada status ini.');
        }

        $materialRequest->clearReceipt();

        return back()->with('success', 'Bukti struk / nota berhasil dihapus.');
    }

    public function downloadReceipt(Request $request, MaterialRequest $materialRequest)
    {
        abort_unless($this->canAccess($request, $materialRequest), 403);

        abort_if(
            ! $materialRequest->receipt_path || ! Storage::disk('public')->exists($materialRequest->receipt_path),
            404,
            'File bukti struk tidak ditemukan.'
        );

        return Storage::disk('public')->download(
            $materialRequest->receipt_path,
            $materialRequest->receipt_original_name ?: basename($materialRequest->receipt_path)
        );
    }

    private function validateRequestPayload(Request $request, bool $includeRemoveReceipt = false): array
    {
        $rules = [
            'notes' => ['nullable', 'string'],
            'receipt' => MaterialRequest::RECEIPT_VALIDATION_RULES,
            'items' => ['required', 'array'],
            'items.*.inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'items.*.requested_qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.notes' => ['nullable', 'string'],
        ];

        if ($includeRemoveReceipt) {
            $rules['remove_receipt'] = ['nullable', 'boolean'];
        }

        return $request->validate($rules, MaterialRequest::RECEIPT_VALIDATION_MESSAGES);
    }

    /**
     * @return array{0: Collection, 1: ?string}
     */
    private function validatedItemRows(array $items, int $divisionId): array
    {
        $rows = collect($items)
            ->filter(fn ($row) => ! empty($row['inventory_item_id']) && ! empty($row['requested_qty']))
            ->values();

        if ($rows->isEmpty()) {
            return [$rows, 'Isi minimal satu item Stok In.'];
        }

        $validItemIds = InventoryItem::where('is_active', true)
            ->where('division_id', $divisionId)
            ->whereIn('item_type', self::REQUEST_ITEM_TYPES)
            ->pluck('id')
            ->all();

        foreach ($rows as $row) {
            if (! in_array((int) $row['inventory_item_id'], $validItemIds, true)) {
                return [$rows, 'Item Stok In harus berkategori Raw Material, aktif, dan sesuai divisi.'];
            }
        }

        return [$rows, null];
    }

    private function syncRequestItems(MaterialRequest $materialRequest, Collection $rows): void
    {
        $itemsById = InventoryItem::whereIn('id', $rows->pluck('inventory_item_id'))->get()->keyBy('id');

        foreach ($rows as $row) {
            $item = $itemsById->get((int) $row['inventory_item_id']);
            if (! $item) {
                continue;
            }

            $materialRequest->items()->create([
                'inventory_item_id' => $item->id,
                'requested_qty' => $row['requested_qty'],
                'unit' => ! empty($row['unit']) ? $row['unit'] : $item->base_unit,
                'notes' => $row['notes'] ?? null,
            ]);
        }
    }

    /**
     * @return array{0: Collection, 1: Collection}
     */
    private function formItemsAndStocks(int $outletId, int $divisionId): array
    {
        $items = InventoryItem::where('is_active', true)
            ->where('division_id', $divisionId)
            ->whereIn('item_type', self::REQUEST_ITEM_TYPES)
            ->orderBy('name')
            ->get();

        $stocks = InventoryStock::where('outlet_id', $outletId)
            ->whereIn('inventory_item_id', $items->pluck('id'))
            ->get()
            ->keyBy('inventory_item_id');

        return [$items, $stocks];
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
