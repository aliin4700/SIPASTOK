# UI Design - Breadlab

Breadlab uses Bootstrap and Blade views.

The UI follows the e-commerce flow from Modul Web Programming III, but the folder naming follows Option B: Laravel convention.

## UI Naming Rule

Use English view folder naming.

Correct:

- v_product
- v_category
- v_cart
- v_checkout
- v_order
- v_payment
- v_chat

Avoid:

- v_produk
- v_kategori
- v_pesanan

Exception:

- v_beranda is allowed because it matches the homepage concept from the module and remains easy to understand.
- v_customer is allowed because customer naming is already English and consistent.

## Main Layout

Frontend layout:

- resources/views/v_layouts/app.blade.php

Backend layout:

- resources/views/backend/v_layouts/app.blade.php

The layout should keep:

- header
- navigation
- category menu
- account menu
- cart menu
- footer
- responsive design

## Frontend View Structure

resources/views/
├── v_layouts/
│   └── app.blade.php
├── v_beranda/
│   └── index.blade.php
├── v_product/
│   ├── index.blade.php
│   ├── category.blade.php
│   └── detail.blade.php
├── v_category/
├── v_customer/
│   └── account.blade.php
├── v_cart/
│   └── index.blade.php
├── v_checkout/
│   └── index.blade.php
├── v_order/
│   ├── history.blade.php
│   └── detail.blade.php
├── v_payment/
│   ├── upload-proof.blade.php
│   └── status.blade.php
└── v_chat/
    └── show.blade.php

## Backend View Structure

resources/views/backend/
├── v_layouts/
│   └── app.blade.php
├── v_dashboard/
│   └── index.blade.php
├── v_product/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── v_category/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── v_customer/
│   ├── index.blade.php
│   └── show.blade.php
├── v_order/
│   ├── index.blade.php
│   └── show.blade.php
├── v_payment/
│   ├── index.blade.php
│   └── show.blade.php
└── v_chat/
    ├── index.blade.php
    └── show.blade.php

## Homepage UI

View:

- v_beranda/index.blade.php

Purpose:

Menampilkan landing page Breadlab dan produk terbaru.

Main components:

- hero banner
- brand message
- category menu
- latest products
- product cards
- call-to-action button
- footer

Product card should show:

- product photo
- category
- product name
- price
- detail button
- add to cart button

## Product Catalog UI

Views:

- v_product/index.blade.php
- v_product/category.blade.php

Purpose:

Menampilkan semua produk dan produk berdasarkan kategori.

Main components:

- category filter
- product grid
- pagination
- product card
- add to cart button
- product detail button

Rules:

- product image uses products.main_photo
- product category uses categories.name
- product price uses products.price
- product status must be published or active

## Product Detail UI

View:

- v_product/detail.blade.php

Purpose:

Menampilkan detail produk.

Main components:

- main product photo
- additional photos
- product name
- category
- price
- stock
- weight
- description
- add to cart button

Rules:

- use products table
- use product_photos table for additional images
- do not use old foto_produk structure

## Customer Account UI

View:

- v_customer/account.blade.php

Purpose:

Customer dapat melihat dan mengubah data akun.

Main components:

- profile photo
- name
- email
- phone
- address
- postal code
- update button

Rules:

- users table stores login data
- customers table stores customer profile detail

## Cart UI

View:

- v_cart/index.blade.php

Purpose:

Menampilkan keranjang customer.

Main components:

- cart item list
- product image
- product name
- product price
- quantity input
- update quantity button
- remove item button
- subtotal
- total weight
- checkout button

Rules:

- cart data comes from carts and cart_items
- cart is not an order
- checkout button goes to checkout.index

## Checkout UI

View:

- v_checkout/index.blade.php

Purpose:

Customer memilih metode transaksi dan metode pembayaran.

Main components:

- order summary
- product summary
- subtotal
- total weight
- transaction method selection
- payment method selection
- shipping form
- local delivery location form
- checkout submit button

Transaction method options:

- pickup
- local_delivery
- shipping

Payment method options:

- cash
- bank_transfer
- midtrans

Rules:

- if pickup selected, hide delivery/shipping fields
- if local_delivery selected, show share location section
- if shipping selected, show RajaOngkir section
- cash should only be available for pickup and local_delivery
- bank_transfer and midtrans can be available based on checkout rules

## Local Delivery UI

Location:

- v_checkout/index.blade.php

Purpose:

Customer membagikan lokasi untuk local delivery.

Main components:

- "Gunakan Lokasi Saya" button
- Leaflet map
- OpenStreetMap tile layer
- marker
- hidden latitude input
- hidden longitude input
- delivery note textarea
- optional address detail

Rules:

- use HTML5 Geolocation API
- use Leaflet for map display
- use OpenStreetMap tile layer
- save latitude and longitude to shipments
- customer should still fill delivery note or address detail
- do not use Google Maps API unless explicitly requested

## Shipping UI with RajaOngkir

Location:

- v_checkout/index.blade.php

Purpose:

Customer memilih ongkos kirim untuk shipping.

Main components:

- province dropdown
- city dropdown
- courier dropdown
- service selection
- shipping cost result
- estimated delivery result

Rules:

- only show this section when transaction_method is shipping
- use RajaOngkirController routes
- save courier, service, shipping cost, and estimated delivery to shipments

## Payment UI

Frontend views:

- v_payment/upload-proof.blade.php
- v_payment/status.blade.php

Backend views:

- backend/v_payment/index.blade.php
- backend/v_payment/show.blade.php

Purpose:

Mengelola tampilan pembayaran.

Payment display should show:

- payment method
- payment status
- total payment
- proof upload form if bank_transfer
- Midtrans payment button if midtrans
- admin verification buttons if manual payment

Rules:

- bank_transfer needs upload proof
- cash is verified manually by admin
- Midtrans status updates automatically from callback

## Customer Order UI

Views:

- v_order/history.blade.php
- v_order/detail.blade.php

Purpose:

Customer melihat riwayat dan detail pesanan.

Order history should show:

- order number
- date
- total price
- order status
- payment status
- detail button

Order detail should show:

- order number
- customer data
- order items
- shipment information
- payment information
- order status
- payment status
- chat panel

## Admin Dashboard UI

View:

- backend/v_dashboard/index.blade.php

Purpose:

Admin melihat ringkasan toko.

Main components:

- total orders card
- total revenue card
- pending orders card
- paid orders card
- order status chart
- payment status chart
- best-selling products
- recent orders

## Admin Product UI

Views:

- backend/v_product/index.blade.php
- backend/v_product/create.blade.php
- backend/v_product/edit.blade.php
- backend/v_product/show.blade.php

Purpose:

Admin mengelola produk.

Main components:

- product table
- create product form
- edit product form
- product image upload
- additional photo management
- status badge
- action buttons

## Admin Category UI

Views:

- backend/v_category/index.blade.php
- backend/v_category/create.blade.php
- backend/v_category/edit.blade.php

Purpose:

Admin mengelola kategori.

Main components:

- category table
- create category form
- edit category form
- status badge
- action buttons

## Admin Customer UI

Views:

- backend/v_customer/index.blade.php
- backend/v_customer/show.blade.php

Purpose:

Admin melihat data customer.

Main components:

- customer table
- customer detail
- name
- email
- phone
- address
- order history summary

## Admin Order UI

Views:

- backend/v_order/index.blade.php
- backend/v_order/show.blade.php

Purpose:

Admin mengelola pesanan.

Order list should show:

- order number
- customer name
- transaction method
- payment method
- payment status
- order status
- total price
- order date
- detail button

Order detail should show:

- order number
- customer information
- ordered products
- payment information
- shipment information
- local delivery map/link if available
- tracking number form if shipping
- update order status form
- chat panel

Rules:

- payment status and order status must be shown separately
- admin order status update should not replace Midtrans payment callback
- tracking number is only for shipping

## Admin Chat UI

Views:

- backend/v_chat/index.blade.php
- backend/v_chat/show.blade.php

Purpose:

Admin melihat dan membalas chat customer.

Chat list should show:

- customer name
- order number
- latest message
- unread count
- status
- open button

Chat detail should show:

- order summary
- message list
- message input
- send button
- close conversation button

Rules:

- first version uses AJAX polling
- do not implement WebSocket unless explicitly requested
- chat must be connected to order

## Customer Chat UI

View:

- v_chat/show.blade.php

Can also be embedded in:

- v_order/detail.blade.php

Purpose:

Customer chat with admin based on order.

Main components:

- message list
- message input
- send button
- order reference

Rules:

- chat is per order
- customer cannot open another customer's chat
- messages are stored in messages table

## Sidebar Menu Standard

Backend sidebar should include:

- Dashboard
- Categories
- Products
- Customers
- Orders
- Payments
- Chat
- Reports
- Logout

## UI Debugging Map

Use this map when debugging UI:

- homepage problem: v_beranda
- product problem: v_product
- category problem: v_category
- cart problem: v_cart
- checkout problem: v_checkout
- order history problem: v_order
- payment problem: v_payment
- chat problem: v_chat
- admin product problem: backend/v_product
- admin order problem: backend/v_order
- admin payment problem: backend/v_payment
- admin chat problem: backend/v_chat
- dashboard problem: backend/v_dashboard

## Important UI Preservation Rule

The existing Breadlab visual design should be preserved.

This document is mainly used to standardize view folder names, Blade file placement, UI responsibility, and debugging structure.

Do not redesign the existing UI unless explicitly requested.

Do not replace the existing frontend or backend template.

Do not change colors, layout style, sidebar design, product card design, button style, or dashboard style unless explicitly requested.

When refactoring view files, keep the current visual appearance as close as possible.

Allowed changes:
- rename view folders according to Option B
- move Blade files to the correct folders
- update variable names
- update route names
- update model/table references
- add missing UI sections required by features

Not allowed unless explicitly requested:
- redesigning the homepage
- changing the theme
- replacing the admin template
- replacing the frontend template
- changing the visual style drastically