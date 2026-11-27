<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\StockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service pusat untuk perubahan stok inventory.
 *
 * Controller modul inventory/purchasing/produksi memakai service ini agar
 * update inventory_stocks dan pencatatan stock_movements selalu konsisten.
 */
class StockMovementService
{
    public function increaseStock(array $data): array
    {
        return $this->apply($data, 'increase');
    }

    public function decreaseStock(array $data): array
    {
        return $this->apply($data, 'decrease');
    }

    public function adjustStock(array $data): array
    {
        // Adjustment dipakai untuk opname/review ketika stok fisik menjadi angka baru.
        return DB::transaction(function () use ($data) {
            $outletId = $this->requiredInt($data, 'outlet_id');
            $itemId = $this->requiredInt($data, 'inventory_item_id');
            $newStock = $this->decimal($data['new_stock'] ?? null, 'new_stock');
            $movementType = $data['movement_type'] ?? 'stock_opname_adjustment';

            if ($newStock < 0) {
                throw new InvalidArgumentException('New stock must be greater than or equal to 0.');
            }

            $item = InventoryItem::findOrFail($itemId);
            $stock = $this->lockedStock($outletId, $item);
            $before = (float) $stock->current_stock;
            $duplicate = $this->duplicateMovement($stock, $movementType, $data);

            if ($duplicate) {
                return [
                    ...$this->result($stock, $duplicate, true),
                    'stock_before' => (float) ($duplicate->stock_before ?? $before),
                    'stock_after' => (float) ($duplicate->stock_after ?? $newStock),
                ];
            }

            $difference = $newStock - $before;

            if ($movementType === 'stock_opname_adjustment') {
                $stock->update(['last_stock_opname_at' => now()]);
            }

            if (abs($difference) < 0.00001) {
                return [
                    ...$this->result($stock->fresh(), null, false, true),
                    'stock_before' => $before,
                    'stock_after' => $newStock,
                ];
            }

            $this->updateStock($stock, $newStock);

            $movement = $this->createMovement($stock, $item, [
                ...$data,
                'movement_type' => $movementType,
                'quantity' => abs($difference),
                'stock_before' => $before,
                'stock_after' => $newStock,
            ]);

            return [
                ...$this->result($stock->fresh(), $movement),
                'stock_before' => $before,
                'stock_after' => $newStock,
            ];
        });
    }

    public function moveStock(array $data): array
    {
        // Transfer dibuat sebagai dua movement: keluar dari outlet asal dan masuk ke outlet tujuan.
        $quantity = $this->decimal($data['quantity'] ?? null, 'quantity');

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than 0.');
        }

        return DB::transaction(function () use ($data, $quantity) {
            $out = $this->decreaseStock([
                ...$data,
                'quantity' => $quantity,
                'outlet_id' => $this->requiredInt($data, 'from_outlet_id'),
                'movement_type' => 'transfer_out',
            ]);

            $in = $this->increaseStock([
                ...$data,
                'quantity' => $quantity,
                'outlet_id' => $this->requiredInt($data, 'to_outlet_id'),
                'movement_type' => 'transfer_in',
            ]);

            return ['out' => $out, 'in' => $in];
        });
    }

    private function apply(array $data, string $direction): array
    {
        // Semua increase/decrease dibungkus transaction agar stok dan movement tidak terpisah.
        return DB::transaction(function () use ($data, $direction) {
            $outletId = $this->requiredInt($data, 'outlet_id');
            $itemId = $this->requiredInt($data, 'inventory_item_id');
            $quantity = $this->decimal($data['quantity'] ?? null, 'quantity');
            $movementType = $data['movement_type'] ?? ($direction === 'increase' ? 'manual_adjustment' : 'material_issue_out');

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Quantity must be greater than 0.');
            }

            $item = InventoryItem::findOrFail($itemId);
            $stock = $this->lockedStock($outletId, $item);
            $duplicate = $this->duplicateMovement($stock, $movementType, $data);

            if ($duplicate) {
                // Reference type/id mencegah proses apply stock yang sama menambah stok dua kali.
                return $this->result($stock, $duplicate, true);
            }

            $before = (float) $stock->current_stock;
            $after = $direction === 'increase' ? $before + $quantity : $before - $quantity;

            if ($after < 0) {
                if ($data['cap_at_zero'] ?? false) {
                    $after = 0.0;
                } elseif (! ($data['allow_negative'] ?? false)) {
                    throw new DomainException("Insufficient stock for item {$item->name}. Current stock: {$before}, requested: {$quantity}.");
                }
            }

            $this->updateStock($stock, $after);

            $movement = $this->createMovement($stock, $item, [
                ...$data,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
            ]);

            return $this->result($stock->fresh(), $movement);
        });
    }

    private function lockedStock(int $outletId, InventoryItem $item): InventoryStock
    {
        // Lock stok per outlet+item supaya transaksi bersamaan tidak saling menimpa.
        $stock = InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $item->id)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        InventoryStock::create([
            'outlet_id' => $outletId,
            'inventory_item_id' => $item->id,
            'current_stock' => 0,
            'reserved_stock' => 0,
            'available_stock' => 0,
            'unit' => $item->base_unit,
        ]);

        return InventoryStock::where('outlet_id', $outletId)
            ->where('inventory_item_id', $item->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function duplicateMovement(InventoryStock $stock, string $movementType, array $data): ?StockMovement
    {
        if (($data['allow_duplicate'] ?? false) || empty($data['reference_type']) || empty($data['reference_id'])) {
            return null;
        }

        return StockMovement::where('outlet_id', $stock->outlet_id)
            ->where('inventory_item_id', $stock->inventory_item_id)
            ->where('movement_type', $movementType)
            ->where('reference_type', $data['reference_type'])
            ->where('reference_id', $data['reference_id'])
            ->lockForUpdate()
            ->first();
    }

    private function updateStock(InventoryStock $stock, float $currentStock): void
    {
        $reserved = (float) ($stock->reserved_stock ?? 0);

        $stock->update([
            'current_stock' => $currentStock,
            'available_stock' => $currentStock - $reserved,
        ]);
    }

    private function createMovement(InventoryStock $stock, InventoryItem $item, array $data): StockMovement
    {
        // Movement menjadi audit trail setiap perubahan stok.
        return StockMovement::create([
            'outlet_id' => $stock->outlet_id,
            'division_id' => $item->division_id,
            'inventory_item_id' => $item->id,
            'movement_type' => $data['movement_type'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'qty' => $data['quantity'],
            'unit' => $stock->unit ?: $item->base_unit,
            'stock_before' => $data['stock_before'],
            'stock_after' => $data['stock_after'],
            'created_by' => $data['user_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    private function requiredInt(array $data, string $key): int
    {
        if (! isset($data[$key]) || (int) $data[$key] <= 0) {
            throw new InvalidArgumentException("{$key} is required.");
        }

        return (int) $data[$key];
    }

    private function decimal(mixed $value, string $key): float
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("{$key} must be numeric.");
        }

        return (float) $value;
    }

    private function result(InventoryStock $stock, ?StockMovement $movement, bool $duplicate = false, bool $noop = false): array
    {
        return [
            'stock' => $stock,
            'movement' => $movement,
            'duplicate' => $duplicate,
            'noop' => $noop,
        ];
    }
}
