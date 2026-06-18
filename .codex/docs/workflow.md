# Workflow - Breadlab

Breadlab follows the e-commerce flow from Modul Web Programming III, but uses Laravel convention and cleaner separation between product, cart, checkout, payment, shipment, order, and chat.

## Main Flow

1. Admin manages categories.
2. Admin manages products.
3. Customer opens homepage.
4. Customer views product catalog.
5. Customer views product detail.
6. Customer logs in.
7. Customer adds product to cart.
8. Customer opens cart.
9. Customer checks out.
10. Customer selects transaction method.
11. Customer selects payment method.
12. System creates order.
13. System creates shipment.
14. System creates payment.
15. Admin manages order.
16. Customer tracks order.
17. Customer and admin can chat based on order.
18. Admin views dashboard and reports.

## Product Flow

1. Admin opens backend product menu.
2. Admin creates category if needed.
3. Admin creates product.
4. Admin uploads main product photo.
5. Admin uploads additional product photos.
6. Admin sets product status to published.
7. Product appears on frontend.
8. Customer opens product detail.

Related controller:

- ProductController
- CategoryController

Related views:

- backend/v_product
- backend/v_category
- v_product

Related tables:

- products
- product_photos
- categories

## Customer Login Flow

1. Customer clicks login.
2. Customer logs in using Google or available login method.
3. System checks user email.
4. If user does not exist, system creates user and customer profile.
5. If user exists, system logs in the user.
6. Customer is redirected to homepage.
7. Customer can access account page.

Related controller:

- CustomerController

Related views:

- v_customer

Related tables:

- users
- customers

## Customer Account Flow

1. Customer opens account page.
2. System displays customer profile.
3. Customer updates name, phone, address, postal code, or photo.
4. System validates input.
5. System updates users and customers data.
6. System redirects customer back to account page.

Related controller:

- CustomerController

Related views:

- v_customer/account.blade.php

Related tables:

- users
- customers

## Cart Flow

1. Customer opens product catalog or product detail.
2. Customer clicks add to cart.
3. System checks active cart for customer.
4. If cart does not exist, system creates cart.
5. System adds product to cart_items.
6. If product already exists in cart, system updates quantity.
7. Customer opens cart.
8. Customer can update quantity.
9. Customer can remove item.
10. System recalculates subtotal and total weight.

Related controller:

- CartController

Related views:

- v_cart/index.blade.php

Related tables:

- carts
- cart_items
- products

## Checkout Flow

1. Customer opens cart.
2. Customer clicks checkout.
3. System displays checkout page.
4. Customer selects transaction method:
   - pickup
   - local_delivery
   - shipping
5. Customer selects payment method:
   - cash
   - bank_transfer
   - midtrans
6. System validates selected transaction and payment combination.
7. System calculates subtotal.
8. System calculates shipping cost if needed.
9. System creates order.
10. System creates order_items from cart_items.
11. System creates shipment record.
12. System creates payment record.
13. System clears cart and cart_items.
14. System redirects customer to order detail or payment page.

Related controller:

- CheckoutController

Related views:

- v_checkout/index.blade.php

Related tables:

- orders
- order_items
- payments
- shipments
- carts
- cart_items

## Pickup Flow

1. Customer selects pickup.
2. System sets shipping cost to 0.
3. Customer selects payment method:
   - cash
   - bank_transfer
   - midtrans
4. System creates order.
5. System creates shipment with shipping_type pickup.
6. Admin processes order.
7. Admin updates order status to processing.
8. Admin updates order status to ready_for_pickup.
9. Customer picks up order.
10. Admin updates order status to completed.

Rules:

- pickup does not require shipping address
- pickup does not require courier
- pickup does not require tracking number

## Local Delivery Flow

1. Customer selects local_delivery.
2. Customer clicks share location.
3. Browser asks for location permission.
4. Browser gets latitude and longitude.
5. Leaflet displays OpenStreetMap map.
6. System places marker on customer location.
7. Customer verifies location.
8. Customer fills delivery note or address detail.
9. System saves local delivery data to shipments.
10. System applies local delivery fee.
11. Admin processes order.
12. Admin updates order status to delivering.
13. Admin completes delivery.
14. Admin updates order status to completed.

Related technologies:

- HTML5 Geolocation API
- Leaflet
- OpenStreetMap

Rules:

- local_delivery does not use RajaOngkir
- local_delivery stores latitude and longitude
- first implementation can use flat/manual delivery fee
- customer should still add delivery note or address detail

## Shipping Flow

1. Customer selects shipping.
2. Customer selects province.
3. System loads city list from RajaOngkir.
4. Customer selects city.
5. Customer selects courier.
6. System requests shipping cost from RajaOngkir.
7. Customer selects service.
8. System saves shipping data to shipments.
9. System creates order.
10. Admin processes order.
11. Admin inputs tracking number.
12. Admin updates order status to shipped.
13. Customer receives order.
14. Admin updates order status to completed.

Related controller:

- RajaOngkirController
- CheckoutController
- AdminOrderController

Related table:

- shipments

Rules:

- shipping uses RajaOngkir
- shipping stores courier, service, cost, and estimated delivery
- tracking number is filled by admin

## Cash Payment Flow

1. Customer selects cash.
2. System creates payment with payment_method cash.
3. Initial payment_status can be pending.
4. Customer pays directly during pickup or local delivery.
5. Admin confirms payment.
6. Admin updates payment_status to paid.
7. System updates order payment summary.

Rules:

- cash is allowed only for pickup and local_delivery
- cash is verified manually by admin

## Bank Transfer Flow

1. Customer selects bank_transfer.
2. System creates payment with payment_method bank_transfer.
3. Payment status becomes waiting_confirmation after customer uploads proof.
4. Customer uploads payment proof.
5. Admin checks payment proof.
6. Admin accepts or rejects payment proof.
7. If accepted, payment_status becomes paid.
8. If rejected, payment_status becomes rejected.

Related controller:

- PaymentController
- AdminOrderController

Related views:

- v_payment/upload-proof.blade.php
- backend/v_payment

Related table:

- payments

## Midtrans Payment Flow

1. Customer selects midtrans.
2. System creates payment with payment_method midtrans.
3. System creates Midtrans transaction.
4. Midtrans returns snap token.
5. Customer pays through Midtrans.
6. Midtrans sends notification callback to Breadlab.
7. System verifies notification.
8. If payment is successful:
   - payments.payment_status becomes paid
   - payments.paid_at is filled
   - orders.status becomes paid if current status is pending or waiting_payment
   - orders.payment_status summary becomes paid
9. Customer is redirected to order detail.
10. Admin sees payment status as paid.

Related controller:

- MidtransController

Related table:

- payments
- orders

Rules:

- Midtrans payment status should be updated automatically
- admin should not manually verify successful Midtrans payments
- callback endpoint must be available for Midtrans notification

## Order Management Flow

1. Admin opens backend order menu.
2. System displays all orders.
3. Admin opens order detail.
4. Admin sees:
   - customer data
   - ordered products
   - payment data
   - shipment data
   - chat panel
5. Admin updates order status.
6. Admin verifies manual payment if needed.
7. Admin inputs tracking number if shipping.
8. Admin replies to customer chat if needed.
9. Admin completes order.

Related controller:

- AdminOrderController

Related views:

- backend/v_order/index.blade.php
- backend/v_order/show.blade.php

Related tables:

- orders
- order_items
- payments
- shipments
- conversations
- messages

## Customer Order History Flow

1. Customer opens order history.
2. System displays customer orders.
3. Customer opens order detail.
4. Customer sees:
   - order status
   - payment status
   - products
   - shipment detail
   - payment detail
   - chat panel
5. Customer can upload payment proof if bank transfer.
6. Customer can chat with admin.

Related controller:

- OrderController

Related views:

- v_order/history.blade.php
- v_order/detail.blade.php

Related tables:

- orders
- order_items
- payments
- shipments
- conversations
- messages

## Chat Flow

1. Customer opens order detail.
2. Customer sends message to admin.
3. System creates conversation if it does not exist.
4. System stores message in messages.
5. Admin opens backend chat or order detail.
6. Admin replies to customer.
7. Customer sees admin reply.
8. AJAX polling checks new messages every few seconds.

Related controllers:

- ConversationController
- MessageController

Related views:

- v_chat/show.blade.php
- backend/v_chat/index.blade.php
- backend/v_chat/show.blade.php

Related tables:

- conversations
- messages

Rules:

- first version uses database chat
- first version uses AJAX polling
- do not use WebSocket unless explicitly requested

## Dashboard and Report Flow

1. Admin opens dashboard.
2. System displays order summary.
3. System displays revenue summary.
4. System displays order status chart.
5. System displays payment status chart.
6. System displays best-selling products.

Related controller:

- DashboardController

Related views:

- backend/v_dashboard/index.blade.php

Related tables:

- orders
- order_items
- payments
- products