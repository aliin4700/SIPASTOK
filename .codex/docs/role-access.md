# BreadLab Role & Access Design

## Core Roles

BreadLab uses flexible role-based and outlet-based access.

Main roles:

- super_admin
- admin
- manager
- staff_outlet
- cashier
- customer

Do not create rigid roles such as staff_kitchen, staff_bar, staff_pastry, or staff_bakery as main roles.

Division access should be managed through user_division_access.

## Role Responsibilities

### Super Admin
Full access to all modules, all outlets, all users, all reports, and system configuration.

### Admin
Responsible for system setup and operational configuration:
- manage outlets
- manage divisions
- manage users
- manage products
- manage inventory items
- manage suppliers
- manage minimum stock
- manage settings
- manage operational expenses
- view all operational reports

### Manager
Monitoring-only role by default:
- view dashboard
- view outlet sales
- view stock reports
- view stock opname
- view waste reports
- view finance target reports
- view net profit reports
- view cashier shift reports

Manager must not input stock, change master data, delete transactions, or change system setup unless explicitly given extra permission.

### Staff Outlet
Operational division staff:
- input stock opname according to outlet and division access
- input waste according to outlet and division access
- create purchase request according to outlet and division access
- view own division stock

### Cashier
POS role:
- access cashier panel
- open cashier shift
- create POS orders
- handle dine in, pickup, local delivery, and shipping
- receive cash, transfer, QRIS manual, or other configured payments
- close cashier shift
- print receipt
- view own shift transactions

Cashier must not access inventory setup, master item setup, finance profit reports, or system settings.

### Customer
Frontend buyer:
- browse products
- cart
- checkout
- payment
- order history
- chat

## Access Structure

Use:

- roles
- permissions
- user_outlet_access
- user_division_access

## Recommended Tables

### user_outlet_access

- id
- user_id
- outlet_id
- can_monitor
- can_cashier
- can_manage_stock
- can_manage_purchase
- created_at
- updated_at

### user_division_access

- id
- user_id
- outlet_id
- division_id
- can_input_stock
- can_input_waste
- can_request_order
- created_at
- updated_at

## Protected Finance Access

Only these roles can access finance and profit modules:

- super_admin
- admin
- manager

Cashier, staff_outlet, and customer must not see net profit, operational expenses, salary, electricity, gas, rent, or company financial reports.