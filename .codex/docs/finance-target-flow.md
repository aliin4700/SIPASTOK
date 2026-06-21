# BreadLab Finance, Target & Net Profit Flow

## Goal

Finance module tracks daily target, monthly target, operational expenses, gross profit, and net profit.

This module is restricted.

Allowed roles:
- super_admin
- admin
- manager

Not allowed:
- cashier
- staff_outlet
- customer

## Main Features

- daily sales target
- monthly sales target
- target achievement
- operational expenses
- gross sales
- net sales
- COGS
- gross profit
- net profit
- net profit margin
- outlet performance

## Sales Target Table

### sales_targets

- id
- outlet_id
- period_type
- target_date
- target_month
- target_year
- target_amount
- notes
- created_by
- created_at
- updated_at

Period type:
- daily
- monthly
- yearly

## Operational Expense Table

### operational_expenses

- id
- outlet_id
- expense_date
- expense_month
- expense_year
- category
- description
- amount
- payment_method
- recorded_by
- approved_by
- status
- notes
- created_at
- updated_at

Expense categories:
- staff_salary
- electricity
- gas
- water
- rent
- internet
- packaging
- maintenance
- delivery_operational
- marketing
- cleaning
- equipment
- other

## Profit Formula

Gross Sales:
total sales before discount and refund

Net Sales:
gross_sales - discount_total - refund_total

COGS:
cost of goods sold

Gross Profit:
net_sales - cogs

Operational Expense:
sum of approved operational expenses

Net Profit:
net_sales - cogs - operational_expense

Net Profit Margin:
net_profit / net_sales * 100

Target Achievement:
actual_sales / target_amount * 100

## Optional Summary Table

### daily_financial_summaries

- id
- outlet_id
- summary_date
- gross_sales
- discount_total
- refund_total
- net_sales
- cogs_total
- gross_profit
- operational_expense
- net_profit
- target_amount
- target_achievement
- created_at
- updated_at

## Dashboard Cards

- Sales Today
- Daily Target Achievement
- Monthly Target Achievement
- Gross Profit
- Operational Expense
- Net Profit
- Net Profit Margin
- Best Outlet
- Under Target Outlet

## Finance Permission

- finance.view
- finance.view_profit
- finance.manage_target
- finance.manage_expense
- finance.export_report