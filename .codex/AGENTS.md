# Breadlab Codex Instructions

Breadlab adalah project website e-commerce toko roti berbasis Laravel 10 dan Bootstrap.

Project ini mengikuti alur pembelajaran Modul Web Programming III, tetapi struktur teknisnya menggunakan Option B: Laravel convention agar lebih konsisten, mudah di-debug, dan lebih siap dikembangkan.

Modul digunakan sebagai acuan alur fitur, bukan sebagai acuan penamaan teknis secara persis. Alur modul mencakup frontend produk, login customer, akun customer, route group dan middleware, cart, RajaOngkir, checkout, manajemen pesanan, grafik pesanan, dan Midtrans. :contentReference[oaicite:0]{index=0}

---

# Main Rule

Do not recreate the project from scratch.

Breadlab is an existing Laravel 10 project.

Continue, refactor, and improve the existing project carefully.

Do not delete working features unless explicitly required for database cleanup or naming refactor.

Do not implement all features at once.

Always work step by step.

---

# Option B Final Decision

Breadlab uses Option B.

Use Laravel convention for:

- table names
- model names
- controller names
- route names
- Blade view folder names

The project must use English technical naming for Laravel files and database structure.

The project may keep `BerandaController` and `v_beranda` because they are easy to understand and still match the module homepage concept.

The project must keep `RajaOngkirController` because it matches the module topic and makes debugging easier.

---

# Module Flow Reference

Breadlab follows this feature flow:

1. Homepage / frontend landing page
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

Additional Breadlab features:

1. Pickup transaction
2. Local delivery transaction
3. Shipping transaction
4. OpenStreetMap and Leaflet for local delivery
5. Manual bank transfer proof upload
6. Midtrans automatic payment callback
7. Customer-admin chat using database and AJAX polling

---

# Final Table Names

Use only these table names:

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

Do not create or use these old or duplicate table names:

- user
- customer
- kategori
- produk
- foto_produk
- order
- order_item
- products and produk together
- orders and order together
- order_items and order_item together

If old duplicate tables still exist and the user has confirmed the data can be deleted, clean them through a planned database reset or migration cleanup.

---

# Final Model Names

Use these model names:

- User
- Customer
- Category
- Product
- ProductPhoto
- Cart
- CartItem
- Order
- OrderItem
- Payment
- Shipment
- Conversation
- Message

Do not create mixed naming such as:

- Produk
- Kategori
- FotoProduk
- Pesanan

---

# Final Controller Names

Use these controller names:

- BerandaController
- ProductController
- CategoryController
- CustomerController
- CartController
- CheckoutController
- RajaOngkirController
- OrderController
- AdminOrderController
- PaymentController
- MidtransController
- DashboardController
- ConversationController
- MessageController

Do not create these old or mixed controller names:

- ProdukController
- KategoriController
- PesananController

Controller responsibility:

- BerandaController: homepage and latest products
- ProductController: frontend product catalog, product detail, and backend product management
- CategoryController: backend category management
- CustomerController: customer login, customer account, and customer profile
- CartController: cart and cart items
- CheckoutController: checkout process
- RajaOngkirController: RajaOngkir API only
- OrderController: customer order history and order detail
- AdminOrderController: admin order list, admin order detail, and order status update
- PaymentController: cash and manual bank transfer payment
- MidtransController: Midtrans transaction, snap token, and notification callback
- DashboardController: admin dashboard and charts
- ConversationController: customer-admin chat conversation
- MessageController: chat messages

Do not put all logic into OrderController.

Keep responsibilities separated for easier debugging.

---

# Final Blade View Folder Names

Frontend views:

- resources/views/v_layouts
- resources/views/v_beranda
- resources/views/v_product
- resources/views/v_category
- resources/views/v_customer
- resources/views/v_cart
- resources/views/v_checkout
- resources/views/v_order
- resources/views/v_payment
- resources/views/v_chat

Backend views:

- resources/views/backend/v_layouts
- resources/views/backend/v_dashboard
- resources/views/backend/v_product
- resources/views/backend/v_category
- resources/views/backend/v_customer
- resources/views/backend/v_order
- resources/views/backend/v_payment
- resources/views/backend/v_chat

Do not create these old or duplicate folders:

- v_produk
- v_kategori
- v_pesanan
- backend/v_produk
- backend/v_kategori
- backend/v_pesanan

Correct mapping:

- ProductController → Product model → products table → v_product
- CategoryController → Category model → categories table → v_category
- CartController → Cart model → carts and cart_items tables → v_cart
- CheckoutController → orders, order_items, payments, shipments → v_checkout
- OrderController → orders, order_items, payments, shipments → v_order
- AdminOrderController → backend/v_order

---

# UI Preservation Rule

Preserve the existing Breadlab visual design.

Do not redesign the frontend or backend UI unless explicitly requested.

Do not replace the existing frontend template.

Do not replace the existing backend template.

Do not change colors, button style, product card style, sidebar style, dashboard layout, spacing, or overall visual theme unless explicitly requested.

The UI documentation is used mainly to standardize:

- view folder names
- Blade file placement
- UI responsibility
- route usage
- debugging structure

Allowed UI changes:

- rename view folders according to Option B
- move Blade files to correct folders
- update route names
- update variable names
- update model/table references
- add missing UI sections required by features
- add form fields needed for checkout, payment, shipment, or chat
- keep current styling as much as possible

Not allowed unless explicitly requested:

- redesigning homepage
- changing the theme
- replacing the admin template
- replacing the frontend template
- changing the product card style drastically
- changing sidebar design drastically
- changing dashboard visual style drastically

When refactoring Blade views, preserve the existing visual appearance as much as possible.

---

# Route Naming Standard

Use clear route names.

Public frontend:

- beranda
- product.index
- product.category
- product.detail

Customer:

- customer.account
- customer.account.update

Cart:

- cart.index
- cart.add
- cart.update
- cart.remove

Checkout:

- checkout.index
- checkout.process

Customer order:

- order.history
- order.detail

Payment:

- payment.uploadProof
- payment.storeProof
- payment.status

RajaOngkir:

- rajaongkir.provinces
- rajaongkir.cities
- rajaongkir.cost

Midtrans:

- midtrans.create
- midtrans.callback
- midtrans.finish
- midtrans.unfinish
- midtrans.error

Chat:

- chat.show
- chat.send
- chat.fetch

Backend:

- backend.dashboard
- backend.product.index
- backend.product.create
- backend.product.store
- backend.product.edit
- backend.product.update
- backend.product.destroy
- backend.category.index
- backend.category.create
- backend.category.store
- backend.category.edit
- backend.category.update
- backend.category.destroy
- backend.customer.index
- backend.customer.show
- backend.order.index
- backend.order.show
- backend.order.updateStatus
- backend.payment.index
- backend.payment.show
- backend.payment.verify
- backend.payment.reject
- backend.chat.index
- backend.chat.show
- backend.chat.send

---

# Database Refactor Rule

The user has confirmed that old data can be deleted if necessary because the project data is still small and can be uploaded again.

Therefore, it is allowed to perform database cleanup or reset when needed.

Preferred command during full database reset:

php artisan migrate:fresh --seed

Important rules:

- Do not keep duplicate old and new tables.
- Do not create `produk` if `products` exists.
- Do not create `kategori` if `categories` exists.
- Do not create `order` if `orders` exists.
- Do not create `order_item` if `order_items` exists.
- Do not create both module-style and Laravel convention tables.
- Use Laravel convention only.

Before database reset:

- explain which tables will be removed
- explain which migrations will be used
- explain seeders needed
- explain what data must be uploaded again

---

# Final Database Structure

The final database should use these main tables:

## users

Stores login accounts for super admin, admin, and customer.

## customers

Stores customer profile details related to users.

## categories

Stores product categories.

## products

Stores Breadlab bakery products.

## product_photos

Stores additional product photos.

## carts

Stores active customer cart.

## cart_items

Stores products inside cart.

## orders

Stores confirmed order after checkout.

## order_items

Stores purchased products in an order.

## payments

Stores payment details for cash, bank transfer, and Midtrans.

## shipments

Stores pickup, local delivery, and shipping details.

## conversations

Stores customer-admin chat conversations.

## messages

Stores chat messages.

---

# Transaction Methods

Breadlab supports three transaction methods:

- pickup
- local_delivery
- shipping

Rules:

## pickup

- does not require delivery address
- does not require courier
- does not require tracking number
- shipping cost must be 0
- can use cash
- can use bank_transfer
- can use midtrans

## local_delivery

- uses customer shared location
- uses HTML5 Geolocation API
- uses Leaflet and OpenStreetMap
- stores latitude and longitude in shipments
- customer should still add delivery note or address detail
- delivery fee can be flat/manual in first implementation
- can use cash
- can use bank_transfer
- can use midtrans

## shipping

- uses RajaOngkir
- requires destination address
- requires courier and service
- stores shipping cost and estimated delivery
- admin can input tracking number
- should not use cash unless explicitly requested
- can use bank_transfer
- can use midtrans

---

# Payment Methods

Breadlab supports three payment methods:

- cash
- bank_transfer
- midtrans

## cash

- manual payment
- allowed for pickup and local_delivery
- admin confirms payment manually
- stored in payments table

## bank_transfer

- manual payment
- customer uploads payment proof
- admin verifies payment proof
- admin can accept or reject payment proof
- stored in payments table

## midtrans

- online payment gateway
- system creates Midtrans transaction
- customer pays through Midtrans
- Midtrans sends notification callback
- system updates payment automatically
- stored in payments table

---

# Status Rule

Keep order status and payment status separate.

Do not mix order process status and payment verification status.

## Order status

Stored in:

- orders.status

Allowed values:

- pending
- waiting_payment
- paid
- processing
- ready_for_pickup
- delivering
- shipped
- completed
- cancelled

Meaning:

- pending: order created but not yet processed
- waiting_payment: waiting for customer payment
- paid: payment received
- processing: admin is preparing order
- ready_for_pickup: order ready to be picked up
- delivering: local delivery in progress
- shipped: shipping courier has received the order
- completed: order finished
- cancelled: order cancelled

## Payment status

Stored in:

- payments.payment_status

Allowed values:

- pending
- waiting_confirmation
- paid
- failed
- expired
- cancelled
- rejected

Meaning:

- pending: payment created but not completed
- waiting_confirmation: waiting admin verification for bank transfer
- paid: payment successful
- failed: payment failed
- expired: payment expired
- cancelled: payment cancelled
- rejected: manual proof rejected

orders.payment_status may exist only as a summary field for quick display, but the main payment record must be in payments.

---

# Midtrans Rule

Midtrans logic must be handled in MidtransController.

MidtransController responsibilities:

- create transaction
- generate snap token
- receive notification callback
- verify notification
- update payments
- update orders

When Midtrans notification is successful:

- update payments.payment_status to paid
- update payments.paid_at
- update payments.midtrans_transaction_id if available
- update orders.payment_status to paid if summary field exists
- update orders.status to paid if current status is pending or waiting_payment

Admin should not manually verify successful Midtrans payment.

Admin may only manually correct Midtrans status if explicitly required.

---

# RajaOngkir Rule

RajaOngkir logic must be handled in RajaOngkirController.

RajaOngkirController responsibilities:

- get provinces
- get cities
- get shipping cost

RajaOngkir is only for shipping.

Do not use RajaOngkir for pickup.

Do not use RajaOngkir for local_delivery.

CheckoutController may use RajaOngkir data, but direct API request logic should stay in RajaOngkirController or a dedicated service.

---

# OpenStreetMap and Leaflet Rule

OpenStreetMap and Leaflet are only for local_delivery.

Use:

- HTML5 Geolocation API
- Leaflet
- OpenStreetMap tile layer

Store local delivery data in shipments:

- delivery_latitude
- delivery_longitude
- delivery_address
- delivery_note
- delivery_distance

First implementation rule:

- do not implement complex routing
- do not implement distance matrix
- do not use Google Maps API unless explicitly requested
- delivery fee can be flat/manual

Admin order detail should show customer location through map or location link.

Customer order detail may also show saved delivery location.

---

# Chat Rule

Customer-admin chat should be connected to order.

Use tables:

- conversations
- messages

Use controllers:

- ConversationController
- MessageController

Initial implementation:

- database-based chat
- AJAX polling
- no WebSocket
- no Laravel Reverb
- no Pusher

Do not implement WebSocket unless explicitly requested.

Chat locations:

Frontend:

- v_chat/show.blade.php
- can be embedded in v_order/detail.blade.php

Backend:

- backend/v_chat/index.blade.php
- backend/v_chat/show.blade.php
- can be embedded in backend/v_order/show.blade.php

Chat should allow:

- customer sends message to admin
- admin replies to customer
- messages are stored
- unread status can be tracked
- conversation can be open or closed

---

# Development Order

Do not implement all features at once.

Recommended order:

1. Audit existing project without changing files
2. Refactor database and naming to Option B
3. Fix Product and Category modules
4. Fix Customer module
5. Fix Cart module
6. Fix Checkout core
7. Add pickup, local_delivery, and shipping
8. Add OpenStreetMap for local_delivery
9. Add RajaOngkir for shipping
10. Add cash and bank transfer payment
11. Add admin order management
12. Add Midtrans automatic callback
13. Add customer order history
14. Add customer-admin chat using AJAX polling
15. Add dashboard charts
16. Final testing and presentation cleanup

---

# Before Editing Files

Before editing files, always inspect:

- .codex/docs/project-overview.md
- .codex/docs/database.md
- .codex/docs/modules.md
- .codex/docs/workflow.md
- .codex/docs/roadmap.md
- .codex/docs/ui-design.md
- .codex/docs/coding-standard.md
- routes/web.php
- existing controllers
- existing models
- existing migrations
- existing Blade views

Do not assume file names.

Do not assume old module-style names still apply.

Follow Option B.

---

# After Editing Files

After editing, always explain:

- files created
- files modified
- files removed if any
- migrations created
- routes added
- routes changed
- artisan commands to run
- manual testing steps
- possible risks or notes

---

# Debugging Map

Use this map when debugging:

- Homepage problem:
  - BerandaController
  - v_beranda
  - v_layouts

- Product problem:
  - ProductController
  - Product model
  - products table
  - product_photos table
  - v_product
  - backend/v_product

- Category problem:
  - CategoryController
  - Category model
  - categories table
  - v_category
  - backend/v_category

- Customer problem:
  - CustomerController
  - User model
  - Customer model
  - users table
  - customers table
  - v_customer
  - backend/v_customer

- Cart problem:
  - CartController
  - Cart model
  - CartItem model
  - carts table
  - cart_items table
  - v_cart

- Checkout problem:
  - CheckoutController
  - Order model
  - OrderItem model
  - Payment model
  - Shipment model
  - orders table
  - order_items table
  - payments table
  - shipments table
  - v_checkout

- RajaOngkir problem:
  - RajaOngkirController
  - shipments table
  - v_checkout

- Local delivery problem:
  - CheckoutController
  - Leaflet script
  - OpenStreetMap tile layer
  - shipments table
  - backend/v_order/show.blade.php

- Customer order problem:
  - OrderController
  - Order model
  - OrderItem model
  - orders table
  - order_items table
  - v_order

- Admin order problem:
  - AdminOrderController
  - backend/v_order
  - orders table
  - order_items table
  - payments table
  - shipments table

- Payment problem:
  - PaymentController
  - Payment model
  - payments table
  - v_payment
  - backend/v_payment

- Midtrans problem:
  - MidtransController
  - Payment model
  - Order model
  - payments table
  - orders table

- Chat problem:
  - ConversationController
  - MessageController
  - Conversation model
  - Message model
  - conversations table
  - messages table
  - v_chat
  - backend/v_chat

- Dashboard problem:
  - DashboardController
  - backend/v_dashboard
  - orders table
  - order_items table
  - payments table
  - products table

---

# Presentation Rule

The project should remain easy to present.

Use this explanation during presentation:

"Project Breadlab mengikuti alur Modul Web Programming III, mulai dari produk, customer, keranjang, checkout, RajaOngkir, manajemen pesanan, grafik, hingga Midtrans. Namun, pada implementasi Breadlab, penamaan tabel, model, controller, route, dan folder view menggunakan Laravel convention agar struktur lebih konsisten, mudah di-debug, dan siap dikembangkan."

Do not make unnecessary changes that make presentation harder.

Do not over-engineer features unless requested.