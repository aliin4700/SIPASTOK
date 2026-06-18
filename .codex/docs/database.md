# Database Design - Breadlab

Breadlab uses Laravel convention for database table names.

The project follows the concept of Modul Web Programming III, but table names are standardized using Laravel plural naming convention.

## Final Tables

Use these tables as the official Breadlab database structure:

1. users
2. customers
3. categories
4. products
5. product_photos
6. carts
7. cart_items
8. orders
9. order_items
10. payments
11. shipments
12. conversations
13. messages

## Deprecated Tables

Do not use these old module-style or duplicate tables:

* user
* customer
* kategori
* produk
* foto_produk
* order
* order_item

If these old tables still exist and the data is not important, they may be removed during database reset.

## Table Mapping from Module to Breadlab

| Module Table | Breadlab Laravel Convention |
| ------------ | --------------------------- |
| user         | users                       |
| customer     | customers                   |
| kategori     | categories                  |
| produk       | products                    |
| foto_produk  | product_photos              |
| order        | orders                      |
| order_item   | order_items                 |

## users

Stores all application accounts, including super admin, admin, and customer.

Columns:

* id
* name
* email
* password
* role
* phone
* photo
* status
* email_verified_at
* remember_token
* created_at
* updated_at

Recommended role values:

* super_admin
* admin
* customer

Recommended status values:

* active
* inactive
* blocked

## customers

Stores additional customer profile data.

Columns:

* id
* user_id
* google_id
* google_token
* address
* postal_code
* created_at
* updated_at

Relations:

* customers.user_id references users.id

## categories

Stores product categories.

Columns:

* id
* name
* slug
* status
* created_at
* updated_at

Recommended status values:

* active
* inactive

## products

Stores Breadlab bakery products.

Columns:

* id
* category_id
* name
* slug
* description
* price
* stock
* weight
* main_photo
* status
* created_at
* updated_at

Relations:

* products.category_id references categories.id

Recommended status values:

* draft
* published
* inactive

## product_photos

Stores additional product photos.

Columns:

* id
* product_id
* photo
* created_at
* updated_at

Relations:

* product_photos.product_id references products.id

## carts

Stores active cart per customer.

Columns:

* id
* customer_id
* created_at
* updated_at

Relations:

* carts.customer_id references customers.id

## cart_items

Stores product items inside cart.

Columns:

* id
* cart_id
* product_id
* quantity
* price
* created_at
* updated_at

Relations:

* cart_items.cart_id references carts.id
* cart_items.product_id references products.id

## orders

Stores confirmed order after checkout.

Columns:

* id
* customer_id
* order_number
* status
* transaction_method
* payment_method
* payment_status
* subtotal
* shipping_cost
* total_weight
* total_price
* address
* postal_code
* notes
* created_at
* updated_at

Relations:

* orders.customer_id references customers.id

Allowed transaction_method values:

* pickup
* local_delivery
* shipping

Allowed payment_method values:

* cash
* bank_transfer
* midtrans

Allowed order status values:

* pending
* waiting_payment
* paid
* processing
* ready_for_pickup
* delivering
* shipped
* completed
* cancelled

Allowed payment_status values:

* pending
* waiting_confirmation
* paid
* failed
* expired
* cancelled
* rejected

Important:

* orders.status is for order process status.
* orders.payment_status is a summary value for quick display.
* payments.payment_status is the detailed payment status source.

## order_items

Stores purchased products in an order.

Columns:

* id
* order_id
* product_id
* product_name
* quantity
* price
* subtotal
* created_at
* updated_at

Relations:

* order_items.order_id references orders.id
* order_items.product_id references products.id

Important:

* product_name is stored to preserve order history even if product name changes later.
* price is stored to preserve transaction value even if product price changes later.

## payments

Stores payment details for cash, bank transfer, and Midtrans.

Columns:

* id
* order_id
* payment_method
* payment_status
* payment_proof
* midtrans_order_id
* midtrans_transaction_id
* midtrans_snap_token
* paid_at
* created_at
* updated_at

Relations:

* payments.order_id references orders.id

Payment method values:

* cash
* bank_transfer
* midtrans

Payment status values:

* pending
* waiting_confirmation
* paid
* failed
* expired
* cancelled
* rejected

Rules:

* cash can be verified manually by admin.
* bank_transfer requires proof upload and admin verification.
* midtrans is updated automatically by Midtrans notification callback.

## shipments

Stores pickup, local delivery, and shipping details.

Columns:

* id
* order_id
* shipping_type
* courier
* service
* tracking_number
* shipping_cost
* estimated_delivery
* delivery_latitude
* delivery_longitude
* delivery_address
* delivery_note
* delivery_distance
* created_at
* updated_at

Relations:

* shipments.order_id references orders.id

Shipping type values:

* pickup
* local_delivery
* shipping

Rules:

* pickup has shipping_cost = 0
* local_delivery uses Leaflet and OpenStreetMap location
* shipping uses RajaOngkir courier and service

## conversations

Stores chat thread between customer and admin.

Columns:

* id
* order_id
* customer_id
* admin_id
* status
* created_at
* updated_at

Relations:

* conversations.order_id references orders.id
* conversations.customer_id references customers.id
* conversations.admin_id references users.id, nullable

Status values:

* open
* closed

## messages

Stores chat messages.

Columns:

* id
* conversation_id
* sender_id
* sender_type
* message
* is_read
* created_at
* updated_at

Relations:

* messages.conversation_id references conversations.id
* messages.sender_id references users.id

Sender type values:

* customer
* admin

## Database Reset Policy

Because the project is still in development and data is still small, it is allowed to reset the database and upload product data again.

Recommended reset command:

php artisan migrate:fresh --seed

Important:

* Do not keep duplicate tables.
* Do not create both products and produk.
* Do not create both orders and order.
* Do not create both order_items and order_item.
* Always use Laravel convention table names.
