# Project Overview - Breadlab

Breadlab adalah project website e-commerce toko roti berbasis Laravel 10 dan Bootstrap.

Project ini mengikuti alur pembelajaran dari Modul Web Programming III, tetapi struktur teknisnya menggunakan Laravel convention agar lebih konsisten, mudah di-debug, dan lebih siap dikembangkan.

## Project Direction

Breadlab tidak dibuat ulang dari nol.

Project ini dikembangkan dari project Laravel e-commerce yang sudah berjalan, lalu dirapikan agar:

- tidak ada tabel duplikat
- tidak ada penamaan campuran seperti produk/products
- tidak ada penamaan campuran seperti order/orders
- struktur controller lebih jelas
- struktur view lebih mudah dicari
- fitur tambahan tetap tidak melenceng dari modul

## Module Reference

Breadlab tetap mengikuti alur utama Modul Web Programming III:

1. Frontend landing page
2. Product catalog
3. Product detail
4. Customer login
5. Customer account management
6. Route group and middleware
7. Cart management
8. RajaOngkir shipping cost integration
9. Checkout and ordering process
10. Admin order management
11. Order chart/dashboard
12. Payment gateway with Midtrans

## Option B Decision

Breadlab menggunakan Opsi B.

Artinya:

- database menggunakan Laravel convention
- model menggunakan Laravel convention
- controller menggunakan English Laravel convention
- route menggunakan nama yang jelas
- folder view menggunakan English naming
- konsep fitur tetap mengikuti modul

## Final Technical Naming

Use this mapping:

| Module Concept | Breadlab Implementation |
|---|---|
| Beranda | BerandaController, v_beranda |
| Produk | ProductController, Product model, products table, v_product |
| Kategori | CategoryController, Category model, categories table, v_category |
| Customer | CustomerController, Customer model, customers table, v_customer |
| Keranjang | CartController, carts, cart_items, v_cart |
| Checkout | CheckoutController, orders, order_items, payments, shipments, v_checkout |
| RajaOngkir | RajaOngkirController, shipments, v_checkout |
| Pemesanan | OrderController, AdminOrderController, v_order, backend/v_order |
| Payment Gateway | PaymentController, MidtransController, payments |
| Grafik Pesanan | DashboardController, backend/v_dashboard |
| Chat Tambahan | ConversationController, MessageController, conversations, messages |

## Project Goals

Customer can:

- view homepage
- browse products
- filter products by category
- view product detail
- login as customer
- manage customer account
- add products to cart
- update cart
- checkout products
- choose pickup, local delivery, or shipping
- pay using cash, bank transfer, or Midtrans
- upload bank transfer proof
- view order history
- view order detail
- chat with admin based on order

Admin can:

- login to backend
- manage dashboard
- manage categories
- manage products
- manage customers
- manage orders
- verify manual payment
- view Midtrans payment result
- update order status
- input tracking number
- view local delivery location
- reply customer chat
- view order charts and reports

## Transaction Methods

Breadlab supports three transaction methods:

1. pickup
2. local_delivery
3. shipping

Rules:

- pickup does not require delivery address
- pickup has shipping cost 0
- local_delivery uses customer location sharing with Leaflet and OpenStreetMap
- shipping uses RajaOngkir

## Payment Methods

Breadlab supports three payment methods:

1. cash
2. bank_transfer
3. midtrans

Rules:

- cash is allowed for pickup and local_delivery
- bank_transfer requires proof upload and admin verification
- midtrans uses automatic callback to update payment status

## Status Separation

Order status and payment status must be separated.

Order status belongs to orders.

Payment status belongs to payments.

Do not mix order process status with payment verification status.

## Additional Features

Breadlab adds extra features beyond the module:

- local delivery with OpenStreetMap
- Midtrans automatic payment callback
- customer-admin chat
- AJAX polling chat
- separated payments table
- separated shipments table

These features must not break the original module flow.

## Development Rule

Before adding new features, Codex must:

1. read .codex/docs
2. read routes
3. read controllers
4. read models
5. read migrations
6. read Blade views
7. follow Option B naming
8. avoid duplicate tables and folders
9. avoid recreating the project from scratch

## Presentation Note

Saat presentasi, jelaskan:

"Project Breadlab mengikuti alur Modul Web Programming III, mulai dari produk, customer, keranjang, checkout, RajaOngkir, manajemen pesanan, grafik, hingga Midtrans. Namun, pada implementasi Breadlab, penamaan tabel, model, controller, route, dan folder view menggunakan Laravel convention agar struktur lebih konsisten, mudah di-debug, dan siap dikembangkan."