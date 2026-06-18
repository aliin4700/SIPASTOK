# Modules - Breadlab

Breadlab menggunakan struktur modular agar setiap fitur mudah dicari, di-debug, dan dipresentasikan.

Project mengikuti alur Modul Web Programming III, tetapi memakai Laravel convention untuk nama model, controller, table, route, dan folder view.

## Final Module Naming Rule

Use English Laravel convention.

Correct:

- Product Module
- Category Module
- Cart Module
- Checkout Module
- Order Module
- Payment Module
- Shipment Module
- Chat Module

Avoid mixed technical naming:

- Produk Module
- Kategori Module
- Pesanan Module
- v_produk
- v_kategori

## 1. Public Frontend Module

Purpose:

Menampilkan halaman frontend Breadlab untuk customer dan pengunjung.

Main features:

- homepage
- product catalog
- product detail
- product by category
- store information
- contact section

Controller:

- BerandaController
- ProductController

Views:

- resources/views/v_beranda
- resources/views/v_product

Routes:

- beranda
- product.index
- product.category
- product.detail

Tables:

- products
- categories
- product_photos

## 2. Category Module

Purpose:

Mengelola kategori produk Breadlab.

Main features:

- category list
- create category
- edit category
- delete category
- category status

Controller:

- CategoryController

Model:

- Category

Views:

- resources/views/backend/v_category

Table:

- categories

## 3. Product Module

Purpose:

Mengelola produk toko roti Breadlab.

Main features:

- product list
- create product
- edit product
- delete product
- upload main product photo
- upload additional product photos
- product stock
- product weight
- product status
- product category relation

Controller:

- ProductController

Models:

- Product
- ProductPhoto
- Category

Views:

- resources/views/v_product
- resources/views/backend/v_product

Tables:

- products
- product_photos
- categories

## 4. Customer Module

Purpose:

Mengelola customer frontend.

Main features:

- customer login
- Google login
- customer account
- update customer profile
- customer address
- customer phone
- customer photo

Controller:

- CustomerController

Models:

- User
- Customer

Views:

- resources/views/v_customer
- resources/views/backend/v_customer

Tables:

- users
- customers

Rules:

- users stores login account
- customers stores customer profile detail
- user role customer must be separated from admin role

## 5. Cart Module

Purpose:

Mengelola keranjang belanja customer sebelum checkout.

Main features:

- add product to cart
- view cart
- update quantity
- remove cart item
- calculate subtotal
- calculate total weight

Controller:

- CartController

Models:

- Cart
- CartItem
- Product
- Customer

Views:

- resources/views/v_cart

Tables:

- carts
- cart_items
- products
- customers

Rules:

- cart is not an order yet
- cart_items are converted into order_items during checkout
- product price should be stored in cart_items for temporary calculation

## 6. Checkout Module

Purpose:

Mengubah cart menjadi order.

Main features:

- select transaction method
- select payment method
- validate checkout data
- create order
- create order items
- create shipment data
- create payment data
- clear cart after checkout

Controller:

- CheckoutController

Models:

- Cart
- CartItem
- Order
- OrderItem
- Payment
- Shipment
- Customer
- Product

Views:

- resources/views/v_checkout

Tables:

- carts
- cart_items
- orders
- order_items
- payments
- shipments

Transaction methods:

- pickup
- local_delivery
- shipping

Payment methods:

- cash
- bank_transfer
- midtrans

Rules:

- pickup does not need shipping address
- local_delivery needs latitude and longitude
- shipping needs RajaOngkir data
- cash is allowed only for pickup and local_delivery
- bank_transfer requires proof upload
- midtrans requires Midtrans transaction

## 7. Shipment Module

Purpose:

Menyimpan detail pengambilan, pengiriman lokal, dan shipping.

Controller:

- CheckoutController
- RajaOngkirController
- AdminOrderController

Model:

- Shipment

Views:

- resources/views/v_checkout
- resources/views/backend/v_order

Table:

- shipments

Shipping types:

- pickup
- local_delivery
- shipping

Pickup rules:

- shipping_cost = 0
- no courier
- no tracking number

Local delivery rules:

- uses HTML5 Geolocation API
- uses Leaflet and OpenStreetMap
- stores latitude and longitude
- stores delivery note
- delivery fee can be flat/manual in first version

Shipping rules:

- uses RajaOngkir
- stores courier
- stores service
- stores shipping cost
- stores estimated delivery
- admin can input tracking number

## 8. RajaOngkir Module

Purpose:

Mengambil data ongkos kirim untuk shipping luar area local delivery.

Controller:

- RajaOngkirController

Used by:

- CheckoutController

Routes:

- rajaongkir.provinces
- rajaongkir.cities
- rajaongkir.cost

Rules:

- RajaOngkir is only for shipping
- RajaOngkir is not used for pickup
- RajaOngkir is not used for local_delivery
- API request logic should stay in RajaOngkirController

## 9. OpenStreetMap Local Delivery Module

Purpose:

Memberikan fitur share location untuk local delivery.

Used by:

- CheckoutController
- Shipment

Views:

- resources/views/v_checkout/index.blade.php
- resources/views/backend/v_order/show.blade.php

Libraries:

- HTML5 Geolocation API
- Leaflet
- OpenStreetMap tile layer

Stored data:

- delivery_latitude
- delivery_longitude
- delivery_address
- delivery_note
- delivery_distance

Rules:

- do not use Google Maps API unless explicitly requested
- do not implement complex routing in first version
- show map marker to customer
- show location map/link to admin

## 10. Order Module

Purpose:

Mengelola riwayat dan detail pesanan customer.

Controller:

- OrderController

Models:

- Order
- OrderItem
- Payment
- Shipment
- Conversation
- Message

Views:

- resources/views/v_order

Tables:

- orders
- order_items
- payments
- shipments

Customer features:

- order history
- order detail
- payment status
- order status
- shipment status
- chat with admin

## 11. Admin Order Module

Purpose:

Mengelola pesanan dari backend admin.

Controller:

- AdminOrderController

Models:

- Order
- OrderItem
- Payment
- Shipment
- Customer

Views:

- resources/views/backend/v_order

Tables:

- orders
- order_items
- payments
- shipments

Admin features:

- view all orders
- view order detail
- update order status
- verify manual payment
- input tracking number
- view customer data
- view shipment data
- view payment data
- reply customer chat

## 12. Payment Module

Purpose:

Mengelola pembayaran cash dan bank transfer manual.

Controller:

- PaymentController

Model:

- Payment

Views:

- resources/views/v_payment
- resources/views/backend/v_payment

Table:

- payments

Payment methods:

- cash
- bank_transfer
- midtrans

Manual payment features:

- upload proof
- admin verification
- reject payment proof
- mark payment as paid

Rules:

- payment status must be separate from order status
- manual payment verification is handled by admin
- Midtrans payment is handled by MidtransController

## 13. Midtrans Module

Purpose:

Mengelola payment gateway Midtrans.

Controller:

- MidtransController

Model:

- Payment
- Order

Table:

- payments
- orders

Routes:

- midtrans.create
- midtrans.callback
- midtrans.finish
- midtrans.unfinish
- midtrans.error

Main features:

- create Midtrans transaction
- generate snap token
- receive Midtrans notification callback
- update payment status automatically
- update order status automatically after successful payment

Rules:

- successful Midtrans payment updates payments.payment_status to paid
- successful Midtrans payment updates payments.paid_at
- successful Midtrans payment updates orders.status to paid if still pending or waiting_payment
- admin should not manually change Midtrans status unless needed for correction

## 14. Chat Module

Purpose:

Memberikan fitur komunikasi antara customer dan admin berdasarkan order.

Controllers:

- ConversationController
- MessageController

Models:

- Conversation
- Message
- Order
- Customer
- User

Views:

- resources/views/v_chat
- resources/views/backend/v_chat
- resources/views/v_order/detail.blade.php
- resources/views/backend/v_order/show.blade.php

Tables:

- conversations
- messages

Features:

- customer sends message to admin
- admin replies to customer
- chat is connected to order
- message read status
- conversation open/closed status

Rules:

- first version uses database chat
- first version uses AJAX polling
- do not use WebSocket unless explicitly requested

## 15. Dashboard and Report Module

Purpose:

Menampilkan ringkasan dan grafik pesanan admin.

Controller:

- DashboardController

Views:

- resources/views/backend/v_dashboard

Tables:

- orders
- order_items
- payments
- products

Features:

- total orders
- total revenue
- order chart
- best selling products
- payment status summary
- order status summary