<?php

namespace App\Services\Inventory;

use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class StockOpnameReviewService
{
    public function __construct(private StockMovementService $stockMovementService)
    {
    }

    public function approveAndAdjust(
        StockOpname $stockOpname,
        User $reviewer,
        array $itemReviews = [],
        ?string $reviewNotes = null
    ): array {
        if ($stockOpname->status !== 'submitted') {
            throw new DomainException('Hanya stock opname submitted yang bisa direview.');
        }

        $stockOpname->loadMissing(['items.item']);

        if ($stockOpname->items->isEmpty()) {
            throw new DomainException('Stock opname belum memiliki item.');
        }

        return DB::transaction(function () use ($stockOpname, $reviewer, $itemReviews, $reviewNotes) {
            $summary = [
                'adjusted' => 0,
                'noop' => 0,
                'duplicate' => 0,
                'skipped' => 0,
                'total_variance_value' => 0.0,
            ];

            $totalVarianceValue = 0.0;

            foreach ($stockOpname->items as $item) {
                $reviewRow = $itemReviews[$item->id]
                    ?? collect($itemReviews)->firstWhere('id', $item->id)
                    ?? [];

                // Dukungan Spot-Check: jika kolom physical_stock dikosongkan/tidak diisi,
                // item dianggap tidak dihitung (diabaikan / saldo tidak diubah), bukan 0.
                $hasReviewInput = is_array($reviewRow) && array_key_exists('physical_stock', $reviewRow);
                $rawPhysical = $hasReviewInput ? $reviewRow['physical_stock'] : $item->physical_stock;

                if ($rawPhysical === null || $rawPhysical === '') {
                    $summary['skipped']++;
                    continue;
                }

                $physicalStock = (float) $rawPhysical;

                if (! $item->item || ! is_numeric($physicalStock) || $physicalStock < 0) {
                    throw new DomainException('Item opname tidak valid. Pastikan inventory item ada dan physical stock tidak negatif.');
                }

                $varianceReason = array_key_exists('variance_reason', $reviewRow)
                    ? ($reviewRow['variance_reason'] ?: null)
                    : $item->variance_reason;

                $notes = array_key_exists('notes', $reviewRow)
                    ? ($reviewRow['notes'] ?: null)
                    : $item->notes;

                $result = $this->stockMovementService->adjustStock([
                    'outlet_id' => $stockOpname->outlet_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'new_stock' => $physicalStock,
                    'movement_type' => 'stock_opname_adjustment',
                    'reference_type' => StockOpnameItem::class,
                    'reference_id' => $item->id,
                    'user_id' => $reviewer->id,
                    'notes' => 'Adjustment from reviewed stock opname ' . ($stockOpname->opname_number ?? $stockOpname->id),
                ]);

                $systemStock = (float) ($result['stock_before'] ?? $item->system_stock ?? 0);
                $differenceQty = $physicalStock - $systemStock;
                $unitCost = (float) $item->unit_cost > 0
                    ? (float) $item->unit_cost
                    : ((float) ($item->item->unit_cost ?? 0) > 0
                        ? (float) $item->item->unit_cost
                        : (float) ($item->item->last_purchase_price ?? 0));
                $varianceValue = round($differenceQty * $unitCost, 2);

                $item->update([
                    'system_stock' => $systemStock,
                    'physical_stock' => $physicalStock,
                    'difference_qty' => $differenceQty,
                    'unit_cost' => $unitCost,
                    'variance_cost' => $varianceValue,
                    'variance_value' => $varianceValue,
                    'variance_reason' => $varianceReason,
                    'notes' => $notes,
                ]);

                $totalVarianceValue += $varianceValue;

                if ($result['duplicate']) {
                    $summary['duplicate']++;
                } elseif ($result['noop']) {
                    $summary['noop']++;
                } else {
                    $summary['adjusted']++;
                }
            }

            $stockOpname->update([
                'status' => 'reviewed',
                'total_variance_value' => $totalVarianceValue,
                'review_notes' => $reviewNotes ?? $stockOpname->review_notes,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $summary['total_variance_value'] = $totalVarianceValue;

            return $summary;
        });
    }
}
