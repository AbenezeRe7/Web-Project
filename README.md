# ElectroMart – PHP/MySQL Electronics Store

ElectroMart is a full-featured electronics e-commerce website built with **PHP 7.4+/8.x, MySQL/MariaDB, Bootstrap 5.3 and Font Awesome 6**. It includes a customer storefront and a separate admin dashboard.

---

## 1. Requirements

- **XAMPP** (or WAMP/MAMP) with Apache + MySQL
- PHP 7.4 or newer with the `mysqli` extension (enabled by default in XAMPP)
- A modern web browser (internet connection needed for Bootstrap/Font Awesome CDNs and product images)

## 2. Setup Instructions (XAMPP)

1. Copy the `electromart` folder into your XAMPP `htdocs` directory:
   - Windows: `C:\xampp\htdocs\electromart`
   - macOS: `/Applications/XAMPP/htdocs/electromart`
   - Linux: `/opt/lampp/htdocs/electromart`
2. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. **Visit the installer first:** <http://localhost/electromart/setup.php>
   - It creates the `electromart` database, all tables and sample data (7 categories, 20 products, reviews, sample orders, users).
   - It is safe to run again at any time — it drops and recreates the tables to reset the demo data.
4. Open the store: <http://localhost/electromart/>
5. Open the admin panel: <http://localhost/electromart/admin/login.php>

### Database credentials

Defined in `config/db.php`:

```php
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASS = ''          // XAMPP default: empty password
DB_NAME = 'electromart'
```

Change these if your MySQL uses a different user/password.

### Base URL

`BASE_URL` is auto-detected from the folder location (default `/electromart`). If you put the project in a different folder or links break, set it manually in `config/db.php`.

## 3. Login Credentials

| Role     | Login                        | Password      | URL                      |
|----------|------------------------------|---------------|--------------------------|
| Admin    | `admin`                      | `admin123`    | `/electromart/admin/login.php` |
| Customer | `john@example.com`           | `password123` | `/electromart/login.php` |
| Customer | `priya@example.com`          | `password123` | `/electromart/login.php` |
| Customer | `rahul@example.com`          | `password123` | `/electromart/login.php` |

You can also register a new customer account from the Register page.

## 4. Features

### Customer storefront
- Home page with hero banner, category cards, featured products and deals
- Shop page with category filter, price range, search, sorting and pagination
- Product detail page with image zoom, specifications table, stock status, star reviews
- AJAX add-to-cart with live navbar badge; guest cart stored in session and **merged into the database cart on login**
- AJAX wishlist toggle (login required)
- Cart page with quantity +/- controls, remove item, subtotal and shipping (free above ₹999, otherwise ₹49)
- Checkout with shipping form and payment method (Cash on Delivery / UPI – demo, no real payment), stock validation and decrement
- Order success page, order history with status badges, cancel pending orders (stock restored)
- Account page: edit profile, change password
- Product reviews (1–5 stars, one review per customer per product)
- Contact page and newsletter subscription

### Admin dashboard
- Stats: total revenue (excluding cancelled), orders, users, products
- Recent orders table and low-stock alerts (stock < 5)
- Products: list, search/filter, add, edit, delete (specs entered as `Key: Value` lines, stored as JSON)
- Orders: list with status filter, order detail with status update
- Users: list with search and detail view
- Categories: add/delete with product counts

### Security
- Prepared statements (mysqli) for all queries — no SQL injection
- Passwords hashed with `password_hash()` / `password_verify()`
- CSRF tokens on all forms and AJAX requests
- Output escaped with `htmlspecialchars()`
- Session-based authentication with a separate admin session; `session_regenerate_id()` on customer and admin login
- Flash messages for user feedback

## 5. File Structure

```
electromart/
├── config/
│   └── db.php                 # DB constants, connection, BASE_URL, session start
├── includes/
│   ├── header.php             # Navbar with cart badge, search, user dropdown, flash messages
│   ├── footer.php             # Footer links + scripts
│   └── functions.php          # Helpers: e(), url(), redirect(), flash, CSRF, cart, auth
├── admin/
│   ├── includes/
│   │   ├── admin_header.php   # Admin layout: sidebar + top bar
│   │   ├── admin_footer.php
│   │   └── product_form.php   # Shared add/edit product form
│   ├── index.php              # Dashboard
│   ├── login.php / logout.php
│   ├── products.php           # Product list
│   ├── add_product.php / edit_product.php
│   ├── orders.php / order_detail.php
│   ├── users.php
│   └── categories.php
├── api/
│   ├── cart_action.php        # AJAX: add / update / remove cart items
│   ├── wishlist_action.php    # AJAX: toggle wishlist
│   └── review_action.php      # POST: submit review
├── assets/
│   ├── css/style.css          # Navy (#1a1f36) + orange (#f59e0b) theme
│   ├── js/main.js             # Cart AJAX, wishlist, qty controls, zoom, flash auto-dismiss
│   └── img/no-image.svg       # Fallback product image
├── index.php                  # Home
├── products.php               # Shop / search
├── product.php                # Product detail
├── cart.php
├── checkout.php
├── order_success.php
├── login.php / register.php / logout.php
├── account.php
├── orders.php                 # Customer order history
├── wishlist.php
├── contact.php
├── setup.php                  # Database installer (rerunnable)
└── README.md
```

## 6. Database Tables

`admins`, `users`, `categories`, `products`, `cart`, `wishlist`, `orders`, `order_items`, `reviews`

## 7. Troubleshooting

- **"Database connection failed"** – make sure MySQL is running in XAMPP and the credentials in `config/db.php` are correct, then run `setup.php`.
- **Broken links/CSS** – check that the folder is named `electromart` inside `htdocs`, or set `BASE_URL` manually.
- **Product images not showing** – images load from Unsplash; an internet connection is required (a local placeholder is shown otherwise).
