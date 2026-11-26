<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\StockMovement;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MaterialRequestController extends Controller
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

    public function index(Request $request)
    {
        return view('backend.material-requests.index', [
            'judul' => 'Material Request',
            'requests' => MaterialRequest::with(['outlet', 'division', 'requester', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['outlet', 'division', 'requester', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', MaterialRequestItem::class)
            ->whereIn('reference_id', $materialRequest->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('backend.material-requests.show', [
            'judul' => 'Detail Material Request',
            'materialRequest' => $materialRequest,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function issueStock(Request $request, MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'submitted') {
            return back()->with('error', 'Hanya material request submitted yang bisa issue stock.');
        }

        $materialRequest->load(['items.item']);

        if ($materialRequest->items->isEmpty()) {
            return back()->with('error', 'Material request belum memiliki item.');
        }

        foreach ($materialRequest->items as $item) {
            if (! $item->item || $this->issueQuantity($item) <= 0) {
                return back()->with('error', 'Item material request tidak valid. Pastikan inventory item ada dan qty lebih dari 0.');
            }
        }

        $mode = $request->input('mode', 'issue_out');

        try {
            $summary = DB::transaction(function () use ($request, $materialRequest, $mode) {
                $summary = ['issued' => 0, 'duplicate' => 0];

                foreach ($materialRequest->items as $item) {
                    $qty = $this->issueQuantity($item);

                    if ($mode === 'restock_in') {
                        $result = $this->stockMovementService->increaseStock([
                            'outlet_id' => $materialRequest->outlet_id,
                            'inventory_item_id' => $item->inventory_item_id,
                            'quantity' => $qty,
                            'movement_type' => 'transfer_in',
                            'reference_type' => MaterialRequestItem::class,
                            'reference_id' => $item->id,
                            'user_id' => $request->user()->id,
                            'notes' => 'Barang masuk / restock divisi dari Take Bahan ' . ($materialRequest->request_number ?? $materialRequest->id),
                        ]);
                    } else {
                        $result = $this->stockMovementService->decreaseStock([
                            'outlet_id' => $materialRequest->outlet_id,
                            'inventory_item_id' => $item->inventory_item_id,
                            'quantity' => $qty,
                            'movement_type' => 'material_issue_out',
                            'reference_type' => MaterialRequestItem::class,
                            'reference_id' => $item->id,
                            'user_id' => $request->user()->id,
                            'notes' => 'Material issue from material request ' . ($materialRequest->request_number ?? $materialRequest->id),
                        ]);
                    }

                    $result['duplicate'] ? $summary['duplicate']++ : $summary['issued']++;
                }

                $materialRequest->update(['status' => 'issued']);

                return $summary;
            });
        } catch (Throwable $exception) {
            return back()->with('error', 'Issue stock gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Material issue applied. Issued: {$summary['issued']}, duplicate ignored: {$summary['duplicate']}.");
    }

    private function issueQuantity(MaterialRequestItem $item): float
    {
        foreach (['issued_qty', 'approved_qty', 'requested_qty'] as $field) {
            if (is_numeric($item->{$field}) && (float) $item->{$field} > 0) {
                return (float) $item->{$field};
            }
        }

        return 0.0;
    }
}
