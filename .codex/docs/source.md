# BreadLab - Source Reference

## Project Overview

BreadLab adalah website penjualan roti berbasis web yang memungkinkan pelanggan membeli produk bakery secara online dan admin mengelola produk, pesanan, banner, serta laporan penjualan.

---

## Technology Stack

### Backend

* Laravel 10
* PHP 8.2

### Frontend

* Bootstrap 5
* Vite

### Authentication

* Laravel Socialite
* Google OAuth

### Payment Gateway

* Midtrans

### Shipping

* RajaOngkir

### Database

* MySQL

### Development Tools

* OpenAI Codex
* GitHub
* VS Code

---

## Design References

### Landing Page

Reference:

* Bakery website template
* Modern e-commerce layout

Elements adopted:

* Hero banner
* Product showcase section
* Category section

Modified:

* BreadLab branding
* Brown and cream color palette
* Best Seller section
* Promo section

### Catalog Page

Reference:

* Zalora product catalog layout

Elements adopted:

* Product card grid
* Search functionality
* Category filtering

Modified:

* Bakery-specific design
* Bread, Cake, Pastry, Cookies categories

---

## Business Rules

### Customer

Can:

* Register
* Login with Google
* Browse products
* Add products to cart
* Checkout
* View order history

Cannot:

* Access admin dashboard

### Admin

Can:

* Manage products
* Manage categories
* Manage banners
* Manage orders
* View reports

---

## Data Sources

### Product Data

Managed manually by admin.

### Shipping Cost

Retrieved from RajaOngkir API.

### Payment Status

Retrieved from Midtrans API.

### User Authentication

Retrieved from Google OAuth.

---

## Branding

Brand Name:
BreadLab Bakery

Theme:
Modern Bakery Store

Primary Color:
#8B4513

Secondary Color:
#F5DEB3

Font:
Poppins

---

## Future Features

* Product Reviews
* Wishlist
* Discount Coupons
* AI Product Recommendation
* WhatsApp Notification
