# BreadLab Operational Flow

## Final System Vision

BreadLab is not only an online bakery store.

BreadLab is:

1. Customer Website
2. Cashier/POS System
3. Outlet Operation System
4. Inventory & Stock Opname System
5. Purchasing System
6. Waste Control System
7. Finance Target & Net Profit System
8. Manager Monitoring Dashboard

## Main Panels

### Customer Website

Routes:
- /
- /products
- /cart
- /checkout
- /orders
- /payments
- /chat

### Admin Backend

Routes:
- /backend/dashboard
- /backend/products
- /backend/categories
- /backend/orders
- /backend/payments
- /backend/shipments
- /backend/inventory
- /backend/purchasing
- /backend/finance
- /backend/settings

### Cashier Panel

Routes:
- /cashier
- /cashier/shift
- /cashier/orders
- /cashier/cash-flow
- /cashier/receipts

### Staff Outlet Panel

Routes:
- /staff/dashboard
- /staff/stock-opnames
- /staff/waste
- /staff/purchase-requests
- /staff/stocks

### Manager Panel

Routes:
- /manager/dashboard
- /manager/sales
- /manager/stock
- /manager/waste
- /manager/cashier-shifts
- /manager/finance

## Daily Outlet Flow

1. Cashier opens shift
2. Outlet starts selling
3. POS orders and online orders are processed
4. Staff inputs waste if any
5. Staff inputs stock opname
6. Cashier closes shift
7. Manager/Admin checks daily sales and cash difference
8. System calculates target achievement
9. System calculates daily financial summary
10. Daily closing is locked

## Required Future Modules

- Role & Access
- Cashier/POS
- Cashier Shift
- Cash Flow Report
- Finance Target
- Inventory Foundation
- Stock Opname
- Purchasing
- Waste
- Recipe/BOM
- COGS
- Daily Closing
- Audit Log