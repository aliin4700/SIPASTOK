# Project Overview

## Nama Project

BreadLab

## Deskripsi

BreadLab adalah aplikasi web untuk toko roti, bakery, dan outlet cafe yang menggabungkan fitur penjualan online, POS cashier, manajemen produk, inventory, staff operation, finance, dan monitoring manager dalam satu sistem.

## Tujuan Project

Membangun sistem yang dapat membantu toko roti untuk:

- Menjual produk secara online.
- Mengelola katalog produk.
- Mengelola checkout dan pembayaran.
- Mengelola pengiriman.
- Menjalankan POS untuk transaksi offline.
- Mengelola shift kasir.
- Mencatat operasional outlet harian.
- Mencatat permintaan bahan, produksi, waste, dan stock opname.
- Memantau inventory outlet.
- Memantau target penjualan dan net profit.
- Memberikan akses monitoring untuk manager.

## Pengguna Sistem

| Pengguna | Fungsi |
|---|---|
| Customer | Melihat produk, cart, checkout, upload bukti pembayaran, chat order |
| Super Admin | Kontrol penuh sistem |
| Admin | Mengelola produk, order, payment, inventory, staff operation, finance |
| Manager | Monitoring read-only |
| Cashier | POS, shift kasir, transaksi offline, receipt |
| Staff Outlet | Operasional outlet harian berdasarkan outlet dan divisi |

## Panel Sistem

| Panel | URL | Role |
|---|---|---|
| Customer Website | `/` | customer |
| Backend Admin | `/backend/dashboard` | super_admin, admin |
| Manager Panel | `/manager/dashboard` | manager |
| Cashier POS | `/cashier` | cashier |
| Staff Panel | `/staff/dashboard` | staff_outlet |

## Scope Saat Ini

Project BreadLab saat ini sudah mencakup:

- Auth dan role redirect.
- Customer website.
- Product dan category management.
- Cart dan checkout.
- Manual payment.
- Order management.
- Chat order.
- Admin dashboard.
- Cashier POS.
- Cashier shift.
- Receipt dan shift report.
- Finance foundation.
- Inventory foundation.
- Staff daily operation.
- Material request.
- Waste record.
- Production/WIP record.
- Closing stock opname.
- Daily operation closing summary.
- Manager monitoring.

## Scope Belum Selesai

- Stock movement integration.
- Stock adjustment dari reviewed stock opname.
- Waste stock deduction.
- Production stock increase.
- Material issue deduction.
- Recipe/BOM.
- Raw material auto deduction.
- Purchasing/goods receipt.
- Midtrans payment gateway.
- Advanced finance based on real COGS.

## Prinsip Project

Project ini dikembangkan secara bertahap agar aman.

Prinsip utama:

- Jangan langsung membuat perubahan besar.
- Jangan menjalankan `migrate:fresh` tanpa persetujuan.
- Jangan mengubah modul POS, finance, checkout, payment, atau shipment jika tidak diminta.
- Semua perubahan stok nantinya harus melalui service pusat.
- Semua perubahan stok harus membuat catatan `stock_movements`.
- Manager bersifat read-only.
- Staff hanya boleh mengakses outlet dan divisi yang diberikan.