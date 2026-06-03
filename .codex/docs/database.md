# users

id
name
email
role

# categories

id
name
slug

# products

id
category_id
name
slug
description
price
stock
image

# carts

id
user_id

# cart_items

id
cart_id
product_id
qty

# orders

id
user_id
total_price
status

# order_items

id
order_id
product_id
qty
price