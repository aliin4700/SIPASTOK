# Stock Movement Service

## Purpose

`App\Services\Inventory\StockMovementService` is the single gateway for future inventory stock changes.

Stock Opname review, backend Waste Record stock deduction, backend Production Record stock increase, backend Material Request issue stock, backend Goods Receipt purchase stock increase, and POS recipe stock deduction are connected to this service. Daily Operation Closing, Checkout, Payment, Shipment, and Finance still behave as before.

## Methods

- `increaseStock(array $data)`
- `decreaseStock(array $data)`
- `adjustStock(array $data)`
- `moveStock(array $data)`

## Input Rules

Common fields:

- `outlet_id`
- `inventory_item_id`
- `quantity` for increase/decrease/move
- `new_stock` for adjust
- `movement_type`
- `reference_type` nullable
- `reference_id` nullable
- `user_id` nullable
- `notes` nullable
- `allow_duplicate` default false
- `allow_negative` default false for decreases

## Supported Movement Types

- `purchase_in`
- `material_issue_out`
- `pos_sale_out`
- `production_in`
- `production_out`
- `waste_out`
- `sales_out`
- `stock_opname_adjustment`
- `manual_adjustment`
- `transfer_in`
- `transfer_out`
- `return_in`

The current `stock_movements` table stores one positive `qty` plus `stock_before` and `stock_after`. It does not have `qty_in` or `qty_out`; direction is inferred from `movement_type` and before/after values.

## Idempotency Rule

When `reference_type` and `reference_id` are provided and `allow_duplicate` is false, the service checks for an existing movement with:

- `outlet_id`
- `inventory_item_id`
- `movement_type`
- `reference_type`
- `reference_id`

If found, it returns the existing movement and does not apply stock again.

## Negative Stock Rule

Negative stock is blocked by default for `decreaseStock()`.

Set `allow_negative => true` only for explicitly approved future workflows.

## Example Usage

```php
app(\App\Services\Inventory\StockMovementService::class)->adjustStock([
    'outlet_id' => $outletId,
    'inventory_item_id' => $itemId,
    'new_stock' => 850,
    'movement_type' => 'stock_opname_adjustment',
    'reference_type' => \App\Models\StockOpnameItem::class,
    'reference_id' => $stockOpnameItemId,
    'user_id' => auth()->id(),
    'notes' => 'Adjustment from reviewed closing stock opname',
]);
```

Goods receipt purchase stock increase uses `increaseStock()` with `movement_type = purchase_in` and `reference_type = GoodsReceiptItem::class`. The reference idempotency rule prevents a goods receipt item from increasing stock twice.

## Warning

Do not update `inventory_stocks.current_stock` directly from controllers. Future stock-changing prompts should call this service and create auditable `stock_movements`.
