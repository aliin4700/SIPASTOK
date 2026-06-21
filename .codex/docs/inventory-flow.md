# BreadLab Inventory & Stock Opname Flow

## Goal

Inventory module supports multi-outlet and multi-division stock control.

Outlets:
- Mejasem
- Slawi

Divisions:
- Kitchen
- Pastry
- Bakery
- Bar
- Floor
- Packaging

## Main Modules

- Outlet
- Division
- Supplier
- Inventory Item
- Inventory Stock
- Stock Movement
- Stock Opname
- Waste Record
- Purchase Request
- Purchase Order
- Goods Receipt

## Inventory Rules

1. Every stock belongs to outlet.
2. Every item belongs to a division or category.
3. Staff can only input stock for assigned outlet and division.
4. Manager can only monitor by default.
5. Admin can setup master item, supplier, minimum stock, and stock settings.
6. Every stock change must create stock movement.
7. Never update stock silently without stock movement.

## Stock Opname Flow

1. Staff login
2. System detects user's outlet and division access
3. Staff opens stock opname page
4. Staff inputs final stock
5. Staff inputs stock in, stock out, and waste if needed
6. System calculates used quantity
7. Staff submits stock opname
8. Admin/Manager reviews
9. Data is approved and locked
10. Stock movement is created

## Important Formula

Used Qty:

opening_stock + stock_in - final_stock - waste_qty

## Warning Rules

- If final_stock is negative, block submission.
- If used_qty is negative, show warning.
- If final_stock > opening_stock and stock_in is empty, show warning.
- If current stock <= minimum stock, mark as critical stock.

## Stock Movement Types

- purchase_in
- sales_out
- waste_out
- stock_opname_adjustment
- transfer_in
- transfer_out
- manual_adjustment
- return_in

## Required Tables

- outlets
- divisions
- suppliers
- inventory_items
- inventory_stocks
- stock_movements
- stock_opnames
- stock_opname_items
- waste_records
- purchase_requests
- purchase_request_items
- purchase_orders
- purchase_order_items
- goods_receipts
- goods_receipt_items