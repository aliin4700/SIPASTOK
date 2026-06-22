# BreadLab Cashier / POS Flow

## Goal

Cashier/POS must be separated from the admin backend.

Routes should use:

- /cashier
- /cashier/shift
- /cashier/orders
- /cashier/cash-flow
- /cashier/receipts

Do not mix cashier workflow with admin setup workflow.

## Service Types

Cashier can create orders for:

- dine_in
- pickup
- local_delivery
- shipping

## Order Channels

Every order must have order_channel:

- online
- cashier_pos
- admin_manual

## Cashier Shift Flow

1. Cashier login
2. Cashier opens shift
3. Input opening cash
4. Create POS transactions
5. Record cash in / cash out if needed
6. Close shift
7. Input actual cash
8. System calculates expected cash
9. System calculates cash difference
10. Manager/Admin reviews shift
11. Shift is locked

## Payment Methods

Initial payment methods:

- cash
- bank_transfer
- qris_manual

Future payment methods:

- midtrans
- edc_debit
- edc_credit

## Required Tables

### cashier_shifts

- id
- outlet_id
- cashier_id
- shift_code
- opened_at
- closed_at
- opening_cash
- expected_cash
- actual_cash
- cash_difference
- status
- notes
- created_at
- updated_at

### cashier_cash_movements

- id
- cashier_shift_id
- outlet_id
- cashier_id
- type
- category
- amount
- description
- created_at
- updated_at

## Shift Status

- open
- closed
- reviewed
- locked

## Cash Movement Type

- cash_in
- cash_out

## Cash Movement Category

- opening_cash
- sales_cash
- petty_cash
- refund
- delivery_fee
- other

## POS UI Requirements

Cashier page must be simple and fast.

Left side:
- product search
- category filter
- product cards

Right side:
- cart
- qty controls
- service type
- customer info
- payment method
- paid amount
- change amount
- submit payment
- print receipt

## Restriction

Cashier cannot:
- edit product master
- edit inventory master
- view net profit
- access finance dashboard
- edit supplier
- change system settings