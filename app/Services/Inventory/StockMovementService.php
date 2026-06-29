<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\StockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
            $duplicate = $this->duplicateMovement($stock, $movementType, $data);

            if ($duplicate) {
                return $this->result($stock, $duplicate, true);
            }

            $before = (float) $stock->current_stock;
            $difference = $newStock - $before;

            if (abs($difference) < 0.00001) {
                return $this->result($stock, null, false, true);
            }

            $this->updateStock($stock, $newStock);

            $movement = $this->createMovement($stock, $item, [
                ...$data,
                'movement_type' => $movementType,
                'quantity' => abs($difference),
                'stock_before' => $before,
                'stock_after' => $newStock,
            ]);

            return $this->result($stock->fresh(), $movement);
        });
    }

    public function moveStock(array $data): array
    {
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
                return $this->result($stock, $duplicate, true);
            }

            $before = (float) $stock->current_stock;
            $after = $direction === 'increase' ? $before + $quantity : $before - $quantity;

            if ($after < 0 && ! ($data['allow_negative'] ?? false)) {
                throw new DomainException("Insufficient stock for item {$item->name}. Current stock: {$before}, requested: {$quantity}.");
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
