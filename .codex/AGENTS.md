# BreadLab Agent Instructions

You are working on **BreadLab**, a Laravel 10 bakery business management system.

BreadLab started as an online bakery store and is now being developed into a full outlet operation system for bakery/cafe operations.

The system must support:

* Customer Website
* Admin Backend
* Cashier/POS Panel
* Staff Outlet Panel
* Manager Monitoring Panel
* Multi-outlet operations
* Division-based stock opname
* Inventory control
* Purchasing workflow
* Waste tracking
* Sales target
* Operational expenses
* Gross profit and net profit reporting

---

## 1. Core Project Identity

BreadLab is built with:

* Laravel 10
* Blade
* Bootstrap
* MySQL
* Role-based access
* Multi-outlet context
* Multi-division operations

Existing modules must be preserved unless explicitly instructed otherwise.

Current existing modules include:

* Product
* Category
* Product Photo
* Customer Auth
* Google Login
* Cart
* Checkout
* Orders
* Payments
* Shipments
* Chat
* Admin Dashboard

Do not remove or rewrite working modules without approval.

---

## 2. Main Development Principle

Always work in **small, safe, reviewable steps**.

Before changing code:

1. Inspect existing migrations.
2. Inspect existing models.
3. Inspect existing controllers.
4. Inspect existing routes.
5. Inspect existing middleware.
6. Inspect existing views.
7. Inspect existing seeders.
8. Explain the implementation plan first if the requested change is large.

Do not perform large refactors without explicit confirmation.

Prefer incremental improvements over full rewrites.

---

## 3. Important Rules

Follow these rules at all times:

* Preserve existing UI style and Bootstrap layout.
* Preserve existing route names when possible.
* Preserve existing database data when possible.
* Do not edit old migrations if they may have already been migrated.
* Use new incremental migrations for structural changes.
* Do not mix customer, admin, cashier, staff, and manager workflows.
* Do not expose finance, net profit, operational expense, salary, electricity, gas, rent, or sensitive company financial reports to cashier, staff_outlet, or customer.
* Manager is read-only by default.
* Cashier must have a separate POS panel.
* Staff outlet must only access assigned outlet and assigned division.
* Admin handles setup and configuration.
* Super admin has full access.
* All stock changes must be traceable.
* All financial and inventory changes should be auditable.
* Do not silently update stock without creating stock movement.
* Do not delete important business records; use status, void, cancel, refund, or soft delete where appropriate.

---

## 4. Core Roles

BreadLab uses flexible roles.

Main roles:

* super_admin
* admin
* manager
* staff_outlet
* cashier
* customer

Do not create rigid main roles such as:

* staff_kitchen
* staff_bar
* staff_pastry
* staff_bakery
* staff_floor
* staff_packaging

Instead, use:

* role = staff_outlet
* division access = kitchen, bar, pastry, bakery, floor, packaging
* outlet access = Mejasem, Slawi, or other outlet

This keeps the system flexible and scalable.

---

## 5. Role Responsibilities

### Super Admin

Super admin has full access to all modules, all outlets, all reports, all users, and all system settings.

Super admin can:

* manage users
* manage roles
* manage permissions
* manage outlets
* manage divisions
* manage products
* manage inventory
* manage purchasing
* manage finance
* view all reports
* approve critical actions
* perform system configuration

---

### Admin

Admin is responsible for setup and operational configuration.

Admin can:

* manage product master
* manage category master
* manage outlet master
* manage division master
* manage supplier master
* manage inventory item master
* manage minimum stock
* manage unit conversion
* manage payment settings
* manage delivery settings
* manage shipping settings
* manage sales target
* manage operational expenses
* view operational reports
* view financial reports

Admin should not be treated as a cashier by default.

---

### Manager

Manager is a monitoring role by default.

Manager can view:

* dashboard
* sales reports
* cashier shift reports
* cash flow reports
* stock reports
* stock opname reports
* waste reports
* purchase request reports
* outlet performance
* sales target achievement
* gross profit
* net profit
* operational expenses

Manager must not modify operational data by default.

Manager cannot:

* input stock opname
* input POS transactions
* edit master product
* edit master inventory
* edit supplier
* delete transactions
* change system setup
* change user roles

If approval permissions are needed later, implement them explicitly through permissions, not by default.

---

### Staff Outlet

Staff outlet handles operational input based on assigned outlet and division.

Staff outlet can:

* input stock opname for assigned outlet and division
* input waste for assigned outlet and division
* create purchase request for assigned outlet and division
* view own division stock
* view own stock opname history

Staff outlet cannot:

* access cashier POS
* view company net profit
* view operational expenses
* edit master item
* edit product price
* edit supplier
* access all outlet data
* approve purchase request unless explicitly given permission

---

### Cashier

Cashier handles offline outlet transactions through a separate POS panel.

Cashier can:

* access cashier panel
* open cashier shift
* create POS orders
* handle dine in orders
* handle pickup orders
* handle local delivery orders
* handle shipping orders
* process cash payment
* process manual transfer payment
* process manual QRIS payment
* print receipts
* close cashier shift
* view own shift transactions
* record cash in and cash out if allowed

Cashier cannot:

* access admin setup
* edit product master
* edit inventory master
* edit supplier
* input stock opname
* view net profit
* view salary expense
* view operational expense
* view full company finance report
* perform refund or void without approval if approval is enabled

---

### Customer

Customer can:

* browse products
* register
* login
* add products to cart
* checkout
* choose pickup, local delivery, or shipping
* upload payment proof
* view order history
* chat with admin

Customer cannot access any backend, cashier, staff, manager, inventory, or finance module.

---

## 6. Access Control Structure

Use flexible access tables.

Recommended tables:

### user_outlet_access

Purpose: controls which outlet a user can access.

Recommended columns:

* id
* user_id
* outlet_id
* can_monitor
* can_cashier
* can_manage_stock
* can_manage_purchase
* can_manage_finance
* created_at
* updated_at

### user_division_access

Purpose: controls which division a staff user can access.

Recommended columns:

* id
* user_id
* outlet_id
* division_id
* can_input_stock
* can_input_waste
* can_request_order
* created_at
* updated_at

Use these tables instead of creating many rigid staff roles.

---

## 7. Main Panels and Route Boundaries

BreadLab must separate panels clearly.

### Customer Website

For customers.

Suggested routes:

* /
* /products
* /products/{product}
* /cart
* /checkout
* /orders
* /payments
* /chat
* /account

---

### Admin Backend

For super_admin and admin.

Suggested route prefix:

* /backend

Main purpose:

* setup
* configuration
* master data
* operational control
* finance setup
* report management

Examples:

* /backend/dashboard
* /backend/products
* /backend/categories
* /backend/customers
* /backend/orders
* /backend/payments
* /backend/shipments
* /backend/outlets
* /backend/divisions
* /backend/suppliers
* /backend/inventory-items
* /backend/settings
* /backend/finance
* /backend/reports

---

### Cashier/POS Panel

For cashier.

Suggested route prefix:

* /cashier

Main purpose:

* POS transactions
* cashier shift
* payment handling
* receipt printing
* cash movement

Examples:

* /cashier
* /cashier/shift
* /cashier/orders
* /cashier/cash-flow
* /cashier/receipts

Cashier routes must not be mixed with backend admin routes.

---

### Staff Outlet Panel

For staff_outlet.

Suggested route prefix:

* /staff

Main purpose:

* stock opname
* waste input
* purchase request
* own division stock monitoring

Examples:

* /staff/dashboard
* /staff/stock-opnames
* /staff/waste
* /staff/purchase-requests
* /staff/stocks

---

### Manager Panel

For manager.

Suggested route prefix:

* /manager

Main purpose:

* monitoring only
* read-only dashboard
* outlet performance
* finance overview
* sales target
* net profit report

Examples:

* /manager/dashboard
* /manager/sales
* /manager/cashier-shifts
* /manager/stock
* /manager/waste
* /manager/purchasing
* /manager/finance

Manager panel must be read-only by default.

---

## 8. Order Channels and Service Types

Every order should have a clear source.

Recommended order channels:

* online
* cashier_pos
* admin_manual

Recommended service types:

* dine_in
* pickup
* local_delivery
* shipping

This separation is required for clear reports.

The system must be able to answer:

* How much sales came from online orders?
* How much sales came from cashier/POS?
* How much was dine in?
* How much was pickup?
* How much was local delivery?
* How much was shipping?
* How much was paid by cash?
* How much was paid by transfer?
* How much was paid by QRIS?
* How much was paid by Midtrans later?

---

## 9. Cashier/POS Rules

Cashier must open a shift before creating transactions if shift enforcement is enabled.

Cashier shift flow:

1. Cashier login.
2. Cashier selects assigned outlet.
3. Cashier opens shift.
4. Cashier inputs opening cash.
5. Cashier creates POS transactions.
6. Cashier records cash in/cash out when needed.
7. Cashier closes shift.
8. Cashier inputs actual cash.
9. System calculates expected cash.
10. System calculates cash difference.
11. Manager or admin reviews shift.
12. Shift is locked.

Recommended cashier shift status:

* open
* closed
* reviewed
* locked

Recommended cashier payment methods for initial version:

* cash
* bank_transfer
* qris_manual

Future payment methods:

* midtrans
* edc_debit
* edc_credit

---

## 10. Cash Flow Rules

Cash flow must not be mixed between online, POS, and manual admin orders.

Use clear separation:

* order_channel
* service_type
* payment_method
* cashier_shift_id
* outlet_id

Cashier cash flow should track:

* opening cash
* cash sales
* cash in
* cash out
* expected cash
* actual cash
* cash difference

Do not delete cashier transactions.

For mistakes, use:

* void
* cancel
* refund
* adjustment

Void/refund should require approval if approval setting is enabled.

---

## 11. Finance, Target and Net Profit Rules

Finance module is restricted.

Allowed roles:

* super_admin
* admin
* manager

Not allowed:

* cashier
* staff_outlet
* customer

Finance module should support:

* daily sales target
* monthly sales target
* gross sales
* net sales
* COGS
* gross profit
* operational expenses
* net profit
* net profit margin
* outlet performance
* target achievement

Basic formulas:

Gross Sales:

```text
total sales before discount and refund
```

Net Sales:

```text
gross_sales - discount_total - refund_total
```

Gross Profit:

```text
net_sales - cogs
```

Net Profit:

```text
net_sales - cogs - operational_expense
```

Net Profit Margin:

```text
net_profit / net_sales * 100
```

Target Achievement:

```text
actual_sales / target_amount * 100
```

Operational expense categories may include:

* staff_salary
* electricity
* gas
* water
* rent
* internet
* packaging
* maintenance
* delivery_operational
* marketing
* cleaning
* equipment
* other

Sensitive finance data must never be shown to cashier, staff_outlet, or customer.

---

## 12. Inventory and Stock Opname Rules

Inventory must support multi-outlet and multi-division stock.

Initial outlets:

* Mejasem
* Slawi

Initial divisions:

* Kitchen
* Pastry
* Bakery
* Bar
* Floor
* Packaging

Rules:

* Every stock belongs to an outlet.
* Every inventory item belongs to a division or category.
* Staff can only input stock for assigned outlet and division.
* Manager can only monitor by default.
* Admin can setup master item, supplier, minimum stock, and stock settings.
* Every stock change must create stock movement.
* Never update stock silently without stock movement.

Stock movement types:

* purchase_in
* sales_out
* waste_out
* stock_opname_adjustment
* transfer_in
* transfer_out
* manual_adjustment
* return_in

Stock opname flow:

1. Staff login.
2. System detects outlet and division access.
3. Staff opens stock opname page.
4. Staff inputs final stock.
5. Staff inputs stock in, stock out, and waste if needed.
6. System calculates used quantity.
7. Staff submits stock opname.
8. Admin or manager reviews.
9. Data is approved and locked.
10. Stock movement is created.

Used quantity formula:

```text
opening_stock + stock_in - final_stock - waste_qty
```

Warning rules:

* If final_stock is negative, block submission.
* If used_qty is negative, show warning.
* If final_stock > opening_stock and stock_in is empty, show warning.
* If current_stock <= minimum_stock, mark as critical stock.

---

## 13. Purchasing Rules

Purchasing workflow should support:

* supplier master
* purchase request
* purchase order
* goods receipt
* stock update
* supplier monitoring

Recommended flow:

1. Staff or admin creates purchase request.
2. Admin reviews request.
3. Admin or purchasing creates purchase order.
4. Supplier sends goods.
5. Staff/admin records goods receipt.
6. Inventory stock increases.
7. Stock movement is created.
8. Purchase order status is updated.

Recommended purchase request status:

* draft
* submitted
* approved
* rejected
* ordered
* partially_received
* received
* closed

Recommended purchase order status:

* draft
* ordered
* partially_received
* received
* cancelled
* closed

---

## 14. Waste Rules

Waste must be tracked as operational cost and inventory movement.

Waste record should include:

* outlet
* division
* inventory item
* date
* quantity
* unit
* estimated value
* reason
* recorded by
* notes

Waste reasons may include:

* expired
* over_production
* damaged
* wrong_process
* customer_return
* trial_menu
* human_error
* other

Every waste record should reduce stock and create stock movement.

Waste should be visible in manager/admin reports.

---

## 15. Recipe, BOM and COGS Rules

Future product costing should support recipe/BOM.

Use recipe/BOM to connect sellable products with inventory items.

Example:

Product: Kopi Susu

Ingredients:

* Fresh Milk 150 ml
* Espresso 30 ml
* Gula Aren 20 ml
* Cup 16 oz 1 pcs
* Lid 1 pcs

When a product is sold, the system can deduct raw materials automatically.

COGS should eventually be calculated from recipe/BOM.

Before recipe/BOM is ready, COGS can use a default percentage setting.

Example:

```text
DEFAULT_COGS_PERCENTAGE=40
```

---

## 16. Daily Closing Rules

BreadLab should eventually support daily outlet closing.

Daily closing flow:

1. All cashier shifts are closed.
2. All POS and online transactions are reviewed.
3. Stock opname is submitted.
4. Waste is recorded.
5. Operational expenses are recorded.
6. Daily sales summary is calculated.
7. Target achievement is calculated.
8. Net profit is calculated.
9. Manager/admin reviews.
10. Daily closing is locked.

After daily closing is locked, important records should not be changed without approval or audit log.

---

## 17. Audit Log Rules

Important actions should be auditable.

Audit log should track:

* user_id
* action
* module
* record_id
* old_value
* new_value
* reason
* created_at

Actions that should be audited:

* changing product price
* changing stock
* editing payment status
* voiding transaction
* refunding transaction
* deleting or cancelling order
* changing user role
* changing outlet access
* changing division access
* approving stock opname
* approving purchase request
* editing operational expense
* editing sales target

---

## 18. UI and UX Rules

Use the existing BreadLab visual direction.

Do not redesign the whole system unless explicitly asked.

Use:

* Blade
* Bootstrap
* existing layout style
* clean tables
* cards for dashboards
* simple forms
* responsive layout

Panel UX should match role needs:

### Admin

Full sidebar and setup-focused layout.

### Manager

Read-only dashboard and reports.

### Cashier

Fast POS layout with minimum distraction.

Suggested POS layout:

* Left side: product categories, product search, product cards.
* Right side: cart, service type, customer info, payment, total, paid amount, change amount, submit button.

### Staff Outlet

Simple operational forms for stock opname, waste, and purchase request.

### Customer

Clean storefront and checkout flow.

---

## 19. Laravel Coding Standards

Follow Laravel conventions.

Use:

* Models for business entities
* Controllers grouped by module
* Form Requests for validation when useful
* Middleware for access control
* Policies when module access becomes complex
* Blade components when reusable
* Route names consistently
* Eloquent relationships
* Seeders for required default data
* Incremental migrations

Do not hardcode sensitive values.

Use `.env` for configurable settings.

After route changes, run:

```bash
php artisan route:list
```

After migration changes, run:

```bash
php artisan migrate
```

After seeder changes, run:

```bash
php artisan db:seed
```

When debugging config, use:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 20. Database Rules

Use clear table names.

Prefer additive migrations.

Do not modify existing migrations if the project has already been migrated.

For major changes, create new migrations.

Existing important tables:

* users
* customers
* categories
* products
* product_photos
* carts
* cart_items
* orders
* order_items
* payments
* shipments
* conversations
* messages

Planned future tables:

* roles
* permissions
* role_permissions
* outlets
* divisions
* user_outlet_access
* user_division_access
* cashier_shifts
* cashier_cash_movements
* sales_targets
* operational_expenses
* daily_financial_summaries
* inventory_items
* inventory_stocks
* stock_movements
* stock_opnames
* stock_opname_items
* suppliers
* purchase_requests
* purchase_request_items
* purchase_orders
* purchase_order_items
* goods_receipts
* goods_receipt_items
* waste_records
* product_recipes
* product_recipe_items
* audit_logs

---

## 21. RajaOngkir and Shipping Rules

BreadLab supports:

* pickup
* local delivery
* shipping

Do not mix local delivery and shipping.

Local delivery is for nearby outlet delivery.

Shipping is for long-distance courier delivery.

RajaOngkir/Komerce may be used for shipping cost calculation.

Important environment variables may include:

```env
RAJAONGKIR_MODE=komerce
RAJAONGKIR_BASE_URL=https://rajaongkir.komerce.id/api/v1
RAJAONGKIR_API_KEY=
RAJAONGKIR_KEY_HEADER=key
RAJAONGKIR_ORIGIN_CITY_ID=
RAJAONGKIR_ORIGIN_DISTRICT_ID=
RAJAONGKIR_DEFAULT_WEIGHT=1000
LOCAL_DELIVERY_FEE=10000
LOCAL_DELIVERY_MAX_DISTANCE_KM=5
```

If RajaOngkir fails, show a clear error message and do not break checkout.

---

## 22. Payment Rules

Initial payment methods:

* cash
* bank_transfer
* qris_manual

Future payment methods:

* midtrans
* edc_debit
* edc_credit

Payment statuses may include:

* unpaid
* waiting_confirmation
* paid
* settled
* failed
* refunded
* cancelled

Manual transfer flow:

1. Customer uploads proof.
2. Admin verifies.
3. Payment becomes paid.
4. Order can be processed.

Cash POS flow:

1. Cashier receives payment.
2. Payment becomes paid immediately.
3. Payment is linked to cashier shift.
4. Cash flow is updated.

---

## 23. Testing and Verification Rules

After implementation, always explain:

* files changed
* migrations added
* models added or updated
* controllers added or updated
* routes added or updated
* views added or updated
* seeders added or updated
* commands that should be run

Recommended verification:

```bash
php artisan migrate
php artisan db:seed
php artisan route:list
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

When possible, test:

* login
* role redirect
* sidebar visibility
* route access
* CRUD pages
* form validation
* dashboard loading
* checkout flow
* cashier flow
* stock flow
* finance visibility restrictions

---

## 24. Development Roadmap

Current roadmap:

1. Role, Access & Outlet Context
2. Cashier/POS Foundation
3. Cash Flow, Shift & Closing Report
4. Finance Target & Net Profit
5. Inventory Outlet Foundation
6. Stock Opname per Division
7. Purchasing Workflow
8. Waste, Recipe/BOM & COGS
9. Daily Closing
10. Audit Log
11. Advanced Reports
12. Midtrans Integration
13. Advanced RajaOngkir/Shipping
14. Product Availability per Outlet
15. Kitchen/Production Display

Do not jump to later phases unless explicitly instructed.

---

## 25. Current Priority

The current priority is:

```text
Role, Access & Outlet Context
```

Before coding this phase:

1. Inspect current user role implementation.
2. Inspect login and redirect logic.
3. Inspect admin middleware.
4. Inspect sidebar rendering.
5. Inspect existing routes.
6. Propose safe changes.
7. Then implement incrementally.

Goal of this phase:

* Add flexible role foundation.
* Add outlet access.
* Add division access.
* Keep one login form.
* Redirect users based on role.
* Prepare separate cashier panel.
* Prepare staff outlet panel.
* Prepare manager monitoring panel.
* Protect finance access.
* Preserve existing modules.

---

## 26. Output Style When Working

When completing a task, always summarize:

```text
Implemented:
- ...

Files changed:
- ...

Database changes:
- ...

Routes added:
- ...

How to test:
- ...

Notes:
- ...
```

If something cannot be completed safely, explain why and ask for confirmation.

Do not assume destructive changes are allowed.

---

## 27. Final Reminder

BreadLab must become:

```text
Customer Website + Cashier POS + Outlet Operations + Inventory + Purchasing + Finance Reporting
```

The system must remain:

* safe
* modular
* scalable
* easy for outlet staff
* clear for cashier
* read-only for manager by default
* configurable by admin
* fully controlled by super admin
