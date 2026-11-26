<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\WasteRecord;
use App\Models\WasteRecordItem;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WasteRecordController extends Controller
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

    public function index()
    {
        return view('manager.waste-records.index', [
            'records' => WasteRecord::with(['outlet', 'division', 'recorder', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(WasteRecord $wasteRecord)
    {
        $wasteRecord->load(['outlet', 'division', 'recorder', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', WasteRecordItem::class)
            ->whereIn('reference_id', $wasteRecord->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('manager.waste-records.show', [
            'wasteRecord' => $wasteRecord,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function applyStock(Request $request, WasteRecord $wasteRecord)
    {
        if ($wasteRecord->status !== 'submitted') {
            return back()->with('error', 'Hanya waste record submitted yang bisa apply stock.');
        }

        $wasteRecord->load(['items.item']);

        if ($wasteRecord->items->isEmpty()) {
            return back()->with('error', 'Waste record belum memiliki item.');
        }

        foreach ($wasteRecord->items as $item) {
            if (! $item->item || ! is_numeric($item->qty) || (float) $item->qty <= 0) {
                return back()->with('error', 'Item waste tidak valid. Pastikan inventory item ada dan qty lebih dari 0.');
            }
        }

        try {
            $summary = DB::transaction(function () use ($request, $wasteRecord) {
                $summary = ['deducted' => 0, 'duplicate' => 0];

                foreach ($wasteRecord->items as $item) {
                    $result = $this->stockMovementService->decreaseStock([
                        'outlet_id' => $wasteRecord->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'quantity' => $item->qty,
                        'movement_type' => 'waste_out',
                        'reference_type' => WasteRecordItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $request->user()->id,
                        'notes' => 'Waste deduction from waste record ' . ($wasteRecord->waste_number ?? $wasteRecord->id),
                    ]);

                    $result['duplicate'] ? $summary['duplicate']++ : $summary['deducted']++;
                }

                $wasteRecord->update(['status' => 'applied']);

                return $summary;
            });
        } catch (Throwable $exception) {
            return back()->with('error', 'Apply stock gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Potongan stok Waste berhasil diterapkan. Deducted: {$summary['deducted']}, duplicate ignored: {$summary['duplicate']}.");
    }
}
