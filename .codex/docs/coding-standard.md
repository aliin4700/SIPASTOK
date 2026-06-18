## Final Project Structure Standard

Breadlab uses Laravel convention for naming and file placement.

The project follows the module flow, but uses English naming for controller, model, table, route, and view folder.

## Controller Structure

app/Http/Controllers/
├── BerandaController.php
├── ProductController.php
├── CategoryController.php
├── CustomerController.php
├── CartController.php
├── CheckoutController.php
├── RajaOngkirController.php
├── OrderController.php
├── AdminOrderController.php
├── PaymentController.php
├── MidtransController.php
├── DashboardController.php
├── ConversationController.php
└── MessageController.php

## Model Structure

app/Models/
├── User.php
├── Customer.php
├── Category.php
├── Product.php
├── ProductPhoto.php
├── Cart.php
├── CartItem.php
├── Order.php
├── OrderItem.php
├── Payment.php
├── Shipment.php
├── Conversation.php
└── Message.php

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

## Route Naming Standard

Use clear route names:

Public frontend:

* beranda
* product.index
* product.category
* product.detail

Customer:

* customer.account
* customer.account.update
* cart.index
* cart.add
* cart.update
* cart.remove
* checkout.index
* checkout.process
* order.history
* order.detail
* payment.uploadProof
* chat.show
* chat.send

RajaOngkir:

* rajaongkir.provinces
* rajaongkir.cities
* rajaongkir.cost

Midtrans:

* midtrans.create
* midtrans.callback
* midtrans.finish
* midtrans.unfinish
* midtrans.error

Backend:

* backend.dashboard
* backend.product.index
* backend.product.create
* backend.product.edit
* backend.category.index
* backend.customer.index
* backend.order.index
* backend.order.show
* backend.order.updateStatus
* backend.payment.index
* backend.payment.verify
* backend.chat.index
* backend.chat.show

## Important Rule

Do not mix Indonesian and English technical naming.

Correct:

ProductController → Product → products → v_product

Incorrect:

ProductController → Produk → produk → v_produk

Correct:

CategoryController → Category → categories → v_category

Incorrect:

KategoriController → Category → kategori → v_kategori
