# Roadmap - Breadlab

Breadlab roadmap follows Modul Web Programming III flow, but uses Laravel convention and additional features for a more complete bakery e-commerce project.

## Current Direction

Breadlab uses Option B:

- Laravel convention for table names
- Laravel convention for model names
- English controller names
- English view folder names
- clean route naming
- separated modules for easier debugging

## Phase 1 - Project Audit and Refactor Preparation

Goal:

Memastikan Codex memahami kondisi project yang sudah ada sebelum melakukan perubahan besar.

Tasks:

- audit existing routes
- audit existing controllers
- audit existing models
- audit existing migrations
- audit existing Blade views
- identify duplicate tables
- identify old naming such as produk, kategori, order, order_item
- identify files that need migration to Option B
- prepare refactor plan

Expected result:

- clear list of existing features
- clear list of files to change
- clear list of old files/tables to remove
- no coding feature before audit is complete

## Phase 2 - Database Reset and Laravel Convention Refactor

Goal:

Menghilangkan struktur database tumpang tindih dan memakai Laravel convention.

Final tables:

- users
- customers
- categories
- products
- product_photos
- carts
- cart_items
- orders
- order_items
- payments
- shipments
- conversations
- messages

Tasks:

- remove old duplicate tables
- create clean migrations
- create model relations
- create seeders
- create default admin user
- create sample categories
- create sample products if needed

Deprecated tables:

- user
- customer
- kategori
- produk
- foto_produk
- order
- order_item

Expected result:

- database is clean
- no duplicate table naming
- all models follow Laravel convention

## Phase 3 - Core Product and Frontend

Goal:

Membangun ulang atau menyesuaikan fitur produk agar memakai struktur baru.

Tasks:

- update ProductController
- update CategoryController
- update Product model relation
- update ProductPhoto relation
- update frontend product catalog
- update product detail
- update category filter
- update product image path
- update backend product management

Views:

- v_beranda
- v_product
- backend/v_product
- backend/v_category

Expected result:

- products show correctly on frontend
- product detail works
- category filter works
- admin can manage products

## Phase 4 - Customer and Authentication

Goal:

Menyesuaikan customer login dan account management dengan users and customers tables.

Tasks:

- update CustomerController
- update User model
- update Customer model
- update Google login if used
- update customer account page
- update customer middleware
- update customer profile update

Views:

- v_customer

Expected result:

- customer can login
- customer can manage account
- admin and customer role are separated

## Phase 5 - Cart Management

Goal:

Mengelola cart menggunakan carts and cart_items.

Tasks:

- create or update CartController
- add product to cart
- update cart quantity
- remove cart item
- calculate subtotal
- calculate total weight
- prepare checkout button

Views:

- v_cart

Expected result:

- customer can manage cart
- cart uses carts and cart_items tables
- cart is not mixed with orders

## Phase 6 - Checkout Core

Goal:

Mengubah cart menjadi order dengan struktur orders, order_items, payments, and shipments.

Tasks:

- create or update CheckoutController
- create checkout page
- validate checkout input
- create order
- create order_items
- create payment
- create shipment
- clear cart after checkout

Views:

- v_checkout

Expected result:

- checkout works
- orders are created correctly
- order_items are copied from cart_items
- payments and shipments are created

## Phase 7 - Transaction Methods

Goal:

Menambahkan pilihan transaksi Breadlab.

Transaction methods:

- pickup
- local_delivery
- shipping

Tasks:

- add transaction method selection
- add validation rules
- show/hide fields based on selected method
- set shipping cost based on method

Rules:

- pickup shipping cost is 0
- local_delivery uses OpenStreetMap
- shipping uses RajaOngkir

Expected result:

- checkout supports pickup, local_delivery, and shipping

## Phase 8 - Local Delivery with OpenStreetMap

Goal:

Menambahkan fitur share location untuk local delivery.

Tasks:

- add share location button
- use HTML5 Geolocation API
- show map using Leaflet
- use OpenStreetMap tile layer
- store latitude and longitude in shipments
- add delivery note field
- show location in admin order detail
- show location in customer order detail

Views:

- v_checkout
- v_order/detail
- backend/v_order/show

Expected result:

- customer can share location
- admin can view delivery location
- local delivery does not use RajaOngkir

## Phase 9 - RajaOngkir Shipping

Goal:

Mengintegrasikan RajaOngkir untuk shipping.

Tasks:

- update RajaOngkirController
- get provinces
- get cities
- get shipping cost
- store courier
- store service
- store shipping cost
- store estimated delivery

Routes:

- rajaongkir.provinces
- rajaongkir.cities
- rajaongkir.cost

Expected result:

- shipping method can calculate cost using RajaOngkir
- shipping data is stored in shipments

## Phase 10 - Payment Methods

Goal:

Menambahkan cash, bank transfer, dan Midtrans.

Payment methods:

- cash
- bank_transfer
- midtrans

Tasks:

- add payment method selection
- validate payment method based on transaction method
- create payment record
- build upload proof page for bank transfer
- build admin verification for bank transfer
- prepare Midtrans transaction flow

Views:

- v_payment
- backend/v_payment

Expected result:

- cash works for pickup/local_delivery
- bank transfer proof can be uploaded
- admin can verify payment

## Phase 11 - Midtrans Integration

Goal:

Mengintegrasikan Midtrans agar pembayaran online otomatis update status.

Tasks:

- create MidtransController
- create Midtrans transaction
- generate snap token
- add Midtrans callback endpoint
- verify Midtrans notification
- update payments.payment_status
- update payments.paid_at
- update orders.status
- update orders.payment_status summary

Routes:

- midtrans.create
- midtrans.callback
- midtrans.finish
- midtrans.unfinish
- midtrans.error

Expected result:

- successful Midtrans payment automatically becomes paid
- admin does not need manual verification for successful Midtrans payment

## Phase 12 - Customer Order History

Goal:

Customer dapat melihat riwayat dan detail pesanan.

Tasks:

- create OrderController methods
- show order history
- show order detail
- show payment status
- show order status
- show shipment detail
- allow payment proof upload if bank transfer
- show chat panel

Views:

- v_order/history
- v_order/detail

Expected result:

- customer can track order
- customer can view payment and shipment details

## Phase 13 - Admin Order Management

Goal:

Admin dapat mengelola semua pesanan.

Tasks:

- create AdminOrderController
- show order list
- show order detail
- update order status
- verify manual payment
- input tracking number
- view local delivery location
- reply customer chat

Views:

- backend/v_order
- backend/v_payment
- backend/v_chat

Expected result:

- admin can manage orders from backend
- admin can update status clearly
- payment and order status are separated

## Phase 14 - Customer-Admin Chat

Goal:

Menambahkan fitur chat customer-admin berdasarkan order.

Tasks:

- create conversations table
- create messages table
- create ConversationController
- create MessageController
- create customer chat view
- create admin chat view
- add chat panel to order detail
- implement AJAX polling

Views:

- v_chat
- backend/v_chat
- v_order/detail
- backend/v_order/show

Expected result:

- customer can send message to admin
- admin can reply
- chat is connected to order
- chat feels near real-time using AJAX polling

## Phase 15 - Dashboard and Charts

Goal:

Menambahkan dashboard grafik pesanan admin.

Tasks:

- update DashboardController
- total orders
- total revenue
- order status chart
- payment status chart
- best-selling products
- daily order chart

Views:

- backend/v_dashboard

Expected result:

- admin can see order reports and charts

## Phase 16 - Final Testing and Debugging

Goal:

Memastikan semua fitur berjalan dan mudah dipresentasikan.

Testing checklist:

- product catalog works
- product detail works
- login works
- customer account works
- cart works
- checkout pickup works
- checkout local_delivery works
- checkout shipping works
- RajaOngkir works
- OpenStreetMap works
- bank transfer proof works
- Midtrans callback works
- admin order management works
- chat works
- dashboard charts work

Expected result:

- Breadlab ready for presentation
- structure is easy to explain
- code is easier to debug

## Development Priority

Do not implement everything at once.

Recommended order:

1. audit project
2. database refactor
3. product and category
4. customer
5. cart
6. checkout
7. shipment methods
8. payment methods
9. admin order
10. Midtrans
11. chat
12. dashboard