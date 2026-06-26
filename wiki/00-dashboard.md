# BreadLab Wiki Dashboard

## Ringkasan Project

BreadLab adalah sistem website toko roti dan operasional outlet berbasis Laravel 10. Project ini mendukung customer website, admin backend, cashier POS, staff outlet operation, inventory, finance, manager monitoring, dan dokumentasi pengembangan berbasis Codex.

## Panel Utama

| Panel | URL | Pengguna |
|---|---|---|
| Customer Website | `/` | Customer |
| Backend Admin | `/backend/dashboard` | Super Admin, Admin |
| Cashier POS | `/cashier` | Cashier |
| Staff Outlet | `/staff/dashboard` | Staff Outlet |
| Manager | `/manager/dashboard` | Manager |

## Dokumen Utama

- [[01-Project-Overview]]
- [[02-Tech-Stack]]
- [[03-Role-Access]]
- [[04-Database-Schema]]
- [[05-Route-Map]]
- [[06-Module-Status]]

## Flow Operasional

- [[07-Inventory-Flow]]
- [[08-Staff-Daily-Operation-Flow]]
- [[09-Cashier-POS-Flow]]
- [[10-Finance Flow]]
- [[11-Shipping-Payment-Flow]]
- [[16-Chat-System]]

## Development Wiki

- [[12-Testing-Checklist]]
- [[13-Codex-Prompt-Log]]
- [[14-Error-Log]]
- [[15-Next-Development-Roadmap]]
- [[17-Known-Bugs]]
- [[18-Coding-Rules]]
- [[19-Prompt-Library]]
- [[20-Changelog]]

## Templates

- [[_templates/Changelog Template]]
- [[_templates/Error Log Template]]
- [[_templates/Module Template]]
- [[_templates/Prompt Template]]
- [[_templates/Testing Template]]

## Status Singkat Modul

| Area | Status | Catatan |
|---|---|---|
| Customer Website | Berjalan | Produk, cart, checkout, order |
| Admin Backend | Berjalan | Dashboard, produk, order, payment, inventory |
| Cashier POS | Berjalan | Shift, POS cart, finalize order, receipt |
| Staff Outlet Operation | Berjalan | Daily operation, Take Bahan, Production, Waste, Stock Opname |
| Manager Monitoring | Berjalan | Read-only monitoring |
| Finance Foundation | Berjalan | Target, expense, estimasi net profit |
| Inventory Foundation | Berjalan | Item, stock, opening alert, import dataset |
| Daily Operation Closing Summary | Berjalan | Summary dan approval status-only |
| Stock Movement Integration | Belum | Tahap berikutnya |
| Recipe/BOM | Belum | Butuh dataset resep final |
| Purchasing/Goods Receipt | Belum | Belum dibuat |
| Midtrans | Belum | Menunggu API key |

## Status Staff Operation

| Modul Staff | Status | Update Stok |
|---|---|---|
| Daily Operation | Done | Tidak |
| Opening Alert | Done | Tidak |
| Material Request / Take Bahan | Done | Tidak |
| Production / WIP Record | Done | Tidak |
| Waste Record | Done | Tidak |
| Closing Stock Opname | Done | Tidak |
| Closing Summary | Done | Tidak |

## Prioritas Berikutnya

```text
15H-1 Stock Movement Service Foundation
15H-2 Stock Opname Adjustment
15H-3 Waste Deduction
15H-4 Production Increase
15H-5 Material Issue Deduction
16 Purchasing / Goods Receipt
17 Recipe / BOM
18 POS Stock Deduction
```

## Aturan Penting

```text
Jangan update inventory_stocks.current_stock langsung dari controller.
Semua perubahan stok harus melalui service pusat.
Semua perubahan stok harus membuat stock_movements.
Jangan jalankan migrate:fresh tanpa persetujuan.
Jangan ubah POS, finance, checkout, payment, shipment jika prompt tidak membahas area itu.
Manager harus tetap read-only.
Staff hanya boleh akses outlet/divisi yang diberikan.
```

## Quick Links

### Project

- [[01-Project-Overview]]
- [[06-Module-Status]]
- [[15-Next-Development-Roadmap]]

### Inventory & Staff

- [[07-Inventory-Flow]]
- [[08-Staff-Daily-Operation-Flow]]
- [[12-Testing-Checklist]]

### Development

- [[13-Codex-Prompt-Log]]
- [[14-Error-Log]]
- [[17-Known-Bugs]]
- [[18-Coding-Rules]]
- [[19-Prompt-Library]]
- [[20-Changelog]]