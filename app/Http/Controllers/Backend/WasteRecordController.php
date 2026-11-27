<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\WasteRecord;
use App\Models\WasteRecordItem;
use App\Services\Inventory\OperationalApprovalService;
use Illuminate\Http\Request;
use Throwable;

class WasteRecordController extends Controller
{
    public function __construct(private OperationalApprovalService $operationalApprovalService)
    {
    }

    public function index()
    {
        return view('backend.waste-records.index', [
            'judul' => 'Waste Record',
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

        return view('backend.waste-records.show', [
            'judul' => 'Detail Waste Record',
            'wasteRecord' => $wasteRecord,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function applyStock(Request $request, WasteRecord $wasteRecord)
    {
        try {
            $summary = $this->operationalApprovalService->approveWasteRecord(
                $wasteRecord,
                $request->user()
            );
        } catch (Throwable $exception) {
            return back()->with('error', 'Apply stock gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Potongan stok Waste berhasil disetujui & diterapkan (-{$summary['deducted']} item, duplikat diabaikan: {$summary['duplicate']}).");
    }
}
