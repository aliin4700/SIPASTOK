<?php

namespace App\Services\Inventory;

use App\Models\DivisionDailyOperation;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\ProductionRecord;
use App\Models\ProductionRecordItem;
use App\Models\Recipe;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\WasteRecord;
use App\Models\WasteRecordItem;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Service terpusat untuk persetujuan (approval) dokumen operasional harian
 * lintas role (Manager & Backend/Admin) agar saldo inventory_stocks selalu
 * ter-update secara real-time, transaksional, dan idempotent.
 */
class OperationalApprovalService
{
    public function __construct(
        private StockMovementService $stockMovementService,
        private StockOpnameReviewService $stockOpnameReviewService
    ) {
    }

    /**
     * Setujui Stok In & terapkan mutasi stok ke inventory_stocks.
     */
    public function approveMaterialRequest(
        MaterialRequest $materialRequest,
        User $user,
        string $mode = 'restock_in'
    ): array {
        return DB::transaction(function () use ($materialRequest, $user, $mode) {
            $locked = MaterialRequest::with(['items.item'])
                ->lockForUpdate()
                ->findOrFail($materialRequest->id);

            if (! in_array($locked->status, ['submitted', 'approved', 'issued'], true)) {
                throw new DomainException('Hanya dokumen Stok In berstatus submitted yang dapat disetujui.');
            }

            if ($locked->items->isEmpty()) {
                throw new DomainException('Dokumen Stok In belum memiliki item.');
            }

            foreach ($locked->items as $item) {
                if (! $item->item || $this->resolveMaterialRequestQty($item) <= 0) {
                    throw new DomainException('Item Stok In tidak valid. Pastikan item inventori terdaftar dan jumlah lebih dari 0.');
                }
            }

            $summary = ['processed' => 0, 'duplicate' => 0];

            foreach ($locked->items as $item) {
                $qty = $this->resolveMaterialRequestQty($item);

                if ($mode === 'issue_out') {
                    $result = $this->stockMovementService->decreaseStock([
                        'outlet_id' => $locked->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'quantity' => $qty,
                        'movement_type' => 'material_issue_out',
                        'reference_type' => MaterialRequestItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $user->id,
                        'cap_at_zero' => true,
                        'notes' => 'Pengeluaran bahan dari Stok In ' . ($locked->request_number ?? $locked->id),
                    ]);
                } else {
                    $result = $this->stockMovementService->increaseStock([
                        'outlet_id' => $locked->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'quantity' => $qty,
                        'movement_type' => 'transfer_in',
                        'reference_type' => MaterialRequestItem::class,
                        'reference_id' => $item->id,
                        'user_id' => $user->id,
                        'notes' => 'Stok In / restock divisi ' . ($locked->request_number ?? $locked->id),
                    ]);
                }

                $item->update([
                    'approved_qty' => $qty,
                    'issued_qty' => $qty,
                    'received_qty' => $qty,
                ]);

                $result['duplicate'] ? $summary['duplicate']++ : $summary['processed']++;
            }

            $locked->update([
                'status' => 'approved',
            ]);

            return $summary;
        });
    }

    /**
     * Setujui Input Produksi / WIP & tambahkan stok hasil produksi (serta potong BOM jika aktif).
     */
    public function approveProductionRecord(
        ProductionRecord $productionRecord,
        User $user,
        bool $deductBom = true
    ): array {
        return DB::transaction(function () use ($productionRecord, $user, $deductBom) {
            $locked = ProductionRecord::with(['items.item'])
                ->lockForUpdate()
                ->findOrFail($productionRecord->id);

            if (! in_array($locked->status, ['submitted', 'approved', 'applied'], true)) {
                throw new DomainException('Hanya dokumen Produksi berstatus submitted yang dapat disetujui.');
            }

            if ($locked->items->isEmpty()) {
                throw new DomainException('Dokumen Produksi belum memiliki item.');
            }

            foreach ($locked->items as $item) {
                if (! $item->item || ! is_numeric($item->qty) || (float) $item->qty <= 0) {
                    throw new DomainException('Item produksi tidak valid. Pastikan item inventori terdaftar dan jumlah lebih dari 0.');
                }
            }

            $summary = ['increased' => 0, 'bom_deducted' => 0, 'duplicate' => 0];

            foreach ($locked->items as $item) {
                $result = $this->stockMovementService->increaseStock([
                    'outlet_id' => $locked->outlet_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => (float) $item->qty,
                    'movement_type' => 'production_in',
                    'reference_type' => ProductionRecordItem::class,
                    'reference_id' => $item->id,
                    'user_id' => $user->id,
                    'notes' => 'Penambahan stok hasil produksi dari ' . ($locked->production_number ?? $locked->id),
                ]);

                if ($result['duplicate']) {
                    $summary['duplicate']++;
                    continue;
                }

                $summary['increased']++;

                if ($deductBom) {
                    $recipe = Recipe::with('items.item')
                        ->where('output_inventory_item_id', $item->inventory_item_id)
                        ->where('is_active', true)
                        ->first();

                    if ($recipe && (float) $recipe->output_qty > 0) {
                        $ratio = (float) $item->qty / (float) $recipe->output_qty;
                        foreach ($recipe->items as $recipeItem) {
                            $wasteFactor = 1 + ((float) ($recipeItem->waste_percentage ?? 0) / 100.0);
                            $requiredQty = round((float) $recipeItem->qty * $ratio * $wasteFactor, 2);

                            if ($requiredQty > 0) {
                                $this->stockMovementService->decreaseStock([
                                    'outlet_id' => $locked->outlet_id,
                                    'inventory_item_id' => $recipeItem->inventory_item_id,
                                    'quantity' => $requiredQty,
                                    'movement_type' => 'production_out',
                                    'reference_type' => ProductionRecordItem::class,
                                    'reference_id' => $item->id,
                                    'user_id' => $user->id,
                                    'cap_at_zero' => true,
                                    'allow_duplicate' => true,
                                    'notes' => 'Auto-deduct BOM (' . $recipe->name . ') untuk produksi ' . ($locked->production_number ?? $locked->id),
                                ]);
                                $summary['bom_deducted']++;
                            }
                        }
                    }
                }
            }

            $locked->update([
                'status' => 'approved',
            ]);

            return $summary;
        });
    }

    /**
     * Setujui Waste Record & kurangi saldo stok pada inventory_stocks.
     */
    public function approveWasteRecord(WasteRecord $wasteRecord, User $user): array
    {
        return DB::transaction(function () use ($wasteRecord, $user) {
            $locked = WasteRecord::with(['items.item'])
                ->lockForUpdate()
                ->findOrFail($wasteRecord->id);

            if (! in_array($locked->status, ['submitted', 'approved', 'applied'], true)) {
                throw new DomainException('Hanya dokumen Waste berstatus submitted yang dapat disetujui.');
            }

            if ($locked->items->isEmpty()) {
                throw new DomainException('Dokumen Waste belum memiliki item.');
            }

            foreach ($locked->items as $item) {
                if (! $item->item || ! is_numeric($item->qty) || (float) $item->qty <= 0) {
                    throw new DomainException('Item waste tidak valid. Pastikan item inventori terdaftar dan jumlah lebih dari 0.');
                }
            }

            $summary = ['deducted' => 0, 'duplicate' => 0];

            foreach ($locked->items as $item) {
                $result = $this->stockMovementService->decreaseStock([
                    'outlet_id' => $locked->outlet_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity' => (float) $item->qty,
                    'movement_type' => 'waste_out',
                    'reference_type' => WasteRecordItem::class,
                    'reference_id' => $item->id,
                    'user_id' => $user->id,
                    'cap_at_zero' => true,
                    'notes' => 'Pemotongan stok waste dari ' . ($locked->waste_number ?? $locked->id),
                ]);

                $result['duplicate'] ? $summary['duplicate']++ : $summary['deducted']++;
            }

            $locked->update([
                'status' => 'approved',
            ]);

            return $summary;
        });
    }

    /**
     * Setujui penutupan Operasional Harian (DivisionDailyOperation) sekaligus
     * menerapkan seluruh mutasi dokumen (Stok In, Produksi, Waste, dan Stock Opname)
     * yang berstatus submitted/approved pada shift tersebut.
     */
    public function approveDailyOperation(DivisionDailyOperation $dailyOperation, User $user): array
    {
        return DB::transaction(function () use ($dailyOperation, $user) {
            $locked = DivisionDailyOperation::lockForUpdate()->findOrFail($dailyOperation->id);

            if (! in_array($locked->status, ['submitted', 'approved'], true)) {
                throw new DomainException('Hanya operasional harian berstatus submitted yang dapat disetujui.');
            }

            $counts = [
                'material_requests' => 0,
                'productions' => 0,
                'wastes' => 0,
                'stock_opnames' => 0,
            ];

            // 1. Proses Stok In / Restock (+)
            $materialRequests = MaterialRequest::with('items.item')
                ->where('division_daily_operation_id', $locked->id)
                ->whereIn('status', ['submitted', 'approved', 'issued'])
                ->get();

            foreach ($materialRequests as $mr) {
                if ($mr->items->isNotEmpty()) {
                    $res = $this->approveMaterialRequest($mr, $user, 'restock_in');
                    $counts['material_requests'] += $res['processed'];
                }
            }

            // 2. Proses Produksi / WIP (+)
            $productionRecords = ProductionRecord::with('items.item')
                ->where('division_daily_operation_id', $locked->id)
                ->whereIn('status', ['submitted', 'approved', 'applied'])
                ->get();

            foreach ($productionRecords as $prod) {
                if ($prod->items->isNotEmpty()) {
                    $res = $this->approveProductionRecord($prod, $user, true);
                    $counts['productions'] += $res['increased'];
                }
            }

            // 3. Proses Waste (-)
            $wasteRecords = WasteRecord::with('items.item')
                ->where('division_daily_operation_id', $locked->id)
                ->whereIn('status', ['submitted', 'approved', 'applied'])
                ->get();

            foreach ($wasteRecords as $waste) {
                if ($waste->items->isNotEmpty()) {
                    $res = $this->approveWasteRecord($waste, $user);
                    $counts['wastes'] += $res['deducted'];
                }
            }

            // 4. Proses Closing Stock Opname (Adjustment)
            $stockOpnames = StockOpname::with('items.item')
                ->where('division_daily_operation_id', $locked->id)
                ->where('status', 'submitted')
                ->get();

            foreach ($stockOpnames as $opname) {
                if ($opname->items->isNotEmpty()) {
                    $res = $this->stockOpnameReviewService->approveAndAdjust($opname, $user);
                    $counts['stock_opnames'] += $res['adjusted'];
                }
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => $locked->approved_at ?? now(),
            ]);

            return $counts;
        });
    }

    /**
     * Sinkronkan seluruh dokumen yang sudah approved/issued/applied (atau submitted jika diminta)
     * namun belum memiliki catatan mutasi di stock_movements.
     */
    public function syncUnprocessedDocuments(User $user, bool $includeSubmitted = false): array
    {
        $targetStatuses = $includeSubmitted
            ? ['submitted', 'approved', 'issued', 'applied']
            : ['approved', 'issued', 'applied'];

        $summary = [
            'material_requests' => 0,
            'productions' => 0,
            'wastes' => 0,
            'daily_operations' => 0,
        ];

        $mrs = MaterialRequest::with('items.item')
            ->whereIn('status', $targetStatuses)
            ->orderBy('id')
            ->get();

        foreach ($mrs as $mr) {
            if ($mr->items->isNotEmpty()) {
                $res = $this->approveMaterialRequest($mr, $user, 'restock_in');
                $summary['material_requests'] += $res['processed'];
            }
        }

        $prods = ProductionRecord::with('items.item')
            ->whereIn('status', $targetStatuses)
            ->orderBy('id')
            ->get();

        foreach ($prods as $prod) {
            if ($prod->items->isNotEmpty()) {
                $res = $this->approveProductionRecord($prod, $user, true);
                $summary['productions'] += $res['increased'];
            }
        }

        $wastes = WasteRecord::with('items.item')
            ->whereIn('status', $targetStatuses)
            ->orderBy('id')
            ->get();

        foreach ($wastes as $waste) {
            if ($waste->items->isNotEmpty()) {
                $res = $this->approveWasteRecord($waste, $user);
                $summary['wastes'] += $res['deducted'];
            }
        }

        $dailyOps = DivisionDailyOperation::whereIn('status', $includeSubmitted ? ['submitted', 'approved'] : ['approved'])
            ->orderBy('id')
            ->get();

        foreach ($dailyOps as $op) {
            $this->approveDailyOperation($op, $user);
            $summary['daily_operations']++;
        }

        return $summary;
    }

    private function resolveMaterialRequestQty(MaterialRequestItem $item): float
    {
        foreach (['issued_qty', 'approved_qty', 'requested_qty'] as $field) {
            if (is_numeric($item->{$field}) && (float) $item->{$field} > 0) {
                return (float) $item->{$field};
            }
        }

        return 0.0;
    }
}
