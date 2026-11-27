<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ProductionRecordItem;
use App\Models\ProductionRecord;
use App\Models\StockMovement;
use App\Services\Inventory\OperationalApprovalService;
use Illuminate\Http\Request;
use Throwable;

class ProductionRecordController extends Controller
{
    public function __construct(private OperationalApprovalService $operationalApprovalService)
    {
    }

    public function index()
    {
        return view('backend.production-records.index', [
            'judul' => 'Production Record',
            'records' => ProductionRecord::with(['outlet', 'division', 'producer', 'items'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(ProductionRecord $productionRecord)
    {
        $productionRecord->load(['outlet', 'division', 'producer', 'dailyOperation', 'items.item']);
        $movementsByItemId = StockMovement::where('reference_type', ProductionRecordItem::class)
            ->whereIn('reference_id', $productionRecord->items->pluck('id'))
            ->get()
            ->keyBy('reference_id');

        return view('backend.production-records.show', [
            'judul' => 'Detail Production Record',
            'productionRecord' => $productionRecord,
            'movementsByItemId' => $movementsByItemId,
        ]);
    }

    public function applyStock(Request $request, ProductionRecord $productionRecord)
    {
        $deductBom = $request->boolean('deduct_bom', true);

        try {
            $summary = $this->operationalApprovalService->approveProductionRecord(
                $productionRecord,
                $request->user(),
                $deductBom
            );
        } catch (Throwable $exception) {
            return back()->with('error', 'Apply stock gagal: ' . $exception->getMessage());
        }

        return back()->with('success', "Stok Produksi/WIP berhasil disetujui & ditambahkan (+{$summary['increased']} item, potongan bahan BOM: {$summary['bom_deducted']}, duplikat diabaikan: {$summary['duplicate']}).");
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:production_records,id'],
        ], [
            'ids.required' => 'Pilih minimal satu dokumen Input Produksi untuk disetujui.',
        ]);

        $records = ProductionRecord::whereIn('id', $validated['ids'])
            ->where('status', 'submitted')
            ->get();

        $approved = 0;
        $errors = [];

        foreach ($records as $record) {
            try {
                $this->operationalApprovalService->approveProductionRecord($record, $request->user(), true);
                $approved++;
            } catch (Throwable $e) {
                $errors[] = ($record->production_number ?? '#' . $record->id) . ': ' . $e->getMessage();
            }
        }

        if ($approved === 0 && $errors !== []) {
            return back()->with('error', 'Gagal menyetujui dokumen terpilih: ' . implode(' | ', array_slice($errors, 0, 3)));
        }

        $msg = "Berhasil menyetujui {$approved} dokumen Input Produksi sekaligus (+ Stok WIP & potong BOM).";
        if ($errors !== []) {
            $msg .= ' (' . count($errors) . ' dokumen gagal diproses).';
        }

        return back()->with('success', $msg);
    }
}
