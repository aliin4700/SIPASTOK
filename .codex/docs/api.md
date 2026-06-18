## API and External Services - Breadlab

# RajaOngkir

RajaOngkir is used only for shipping method.

Not used for:

pickup
local_delivery

Required environment variables:

RAJAONGKIR_API_KEY=
RAJAONGKIR_BASE_URL=

Main endpoints inside Breadlab:

get provinces
get cities
get cost

Controller:

RajaOngkirController

Rules:

Do not mix RajaOngkir logic inside CheckoutController.
CheckoutController may call RajaOngkir results, but API request logic should stay inside RajaOngkirController or a dedicated service.
OpenStreetMap and Leaflet

# OpenStreetMap and Leaflet are used only for local_delivery.

Purpose:

show customer location on checkout page
allow customer to verify location
save latitude and longitude
show location link/map to admin

Required data:

delivery_latitude
delivery_longitude
delivery_address
delivery_note
delivery_distance

Rules:

Use HTML5 Geolocation API to get customer location.
Use Leaflet to display map.
Use OpenStreetMap tile layer.
Do not implement complex routing in first version.
Do not use Google Maps API unless explicitly requested.

# Midtrans

Midtrans is used for online payment.

Required environment variables:

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true

Controller:

MidtransController

Main responsibilities:

create transaction
generate snap token
receive notification callback
verify notification
update payments
update orders

Rules:

Midtrans callback must update payment_status automatically.
Successful Midtrans payment updates payments.payment_status to paid.
Successful Midtrans payment updates orders.status to paid if current status is pending or waiting_payment.
Do not manually change Midtrans payment status from admin unless explicitly needed.