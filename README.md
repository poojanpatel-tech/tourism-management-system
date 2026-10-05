# Patel Travels - Tourism Management System

> A professional college project built with **PHP 8+**, **MySQL (PDO)**, **HTML5**, **CSS3**, and **Bootstrap 5**.

---

## 1. Project Overview

The **Tourism Management System (TMS)** is designed to manage a modern travel agency:
- **Tour Packages & Journeys**: Itineraries, pricing, duration, highlights, and gallery images.
- **Destinations**: Global and domestic tourist spots associated with packages.
- **Enquiries**: Lead generation system where customers request tailored journeys.
- **Business Settings**: Centralized company information (phone, email, social links).
- **Public Client Interface**: A luxury, editorial-style travel website for customers to explore destinations and curated journeys.

---

## 2. Technology Stack

- **Backend**: Vanilla PHP 8+ (No heavy frameworks; easy to explain during college review)
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB (via standard XAMPP)
- **Database Access**: PHP PDO with Prepared Statements (SQL-Injection protected)
- **Frontend**: HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons, Custom CSS Variables
- **Authentication**: PHP Session-based authentication with password_hash() and password_verify()

---

## 3. Database Architecture (tourism_management)

### Core Tables

1. **admins**: Stores administrative credentials and profile details.
2. **destinations**: Tourist locations and countries available for package assignments.
3. **packages**: Tour packages tied to destinations with pricing and capacity constraints.
4. **plan_itinerary, plan_highlights, plan_inclusions, plan_images**: Granular details for each travel package.
5. **enquiries**: Lead generation table storing customer requests for specific packages.
6. **settings**: Key-value store for global business settings.

---

## 4. How to Set Up & Run the Project on XAMPP

### Step 1: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel**.
2. Click the **Start** button next to **Apache**.
3. Click the **Start** button next to **MySQL**.
4. Ensure both modules turn green.

---

### Step 2: Create the Database & Import database.sql

#### Method A: Using phpMyAdmin (Recommended for College Review)
1. Open your web browser and go to:
   http://localhost/phpmyadmin
2. Click on the **Import** tab on the top navigation bar.
3. Click **Choose File** / **Browse** and select the database.sql file located in the project's root folder.
4. Scroll to the bottom and click the **Go** button.

---

### Step 3: Configure Database Credentials (If Needed)

The project is pre-configured for standard XAMPP defaults in config/database.php:

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'tourism_management');
define('DB_USER', 'root');
define('DB_PASS', '');

If your MySQL server uses a different port or password, adjust those constants. If deploying to production (like Vercel), configure environment variables DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS.

---

### Step 4: Accessing the Application

You can run the project using PHP's built-in servers for local testing.

**Admin Server (Port 8000)**
php -S localhost:8000 admin_server.php

**Client Server (Port 9000)**
php -S localhost:9000 client_server.php

Then visit:
- **Client Website**: http://localhost:9000/
- **Admin Login**: http://localhost:8000/auth/login.php

---

## 5. Administrator Credentials

Administrator credentials are configured locally via the database.sql seed script. (See admins table).
For security reasons, default credentials are not published in this README. If this system is deployed to a live environment, immediately rotate any default credentials.

---

## 6. Security & Code Quality Standards

1. **Prepared Statements**: All database operations utilize PDO prepared statements to eliminate SQL injection vulnerabilities.
2. **Session Hardening**: Sessions use strict mode and HTTP-only cookie parameters upon login.
3. **Cross-Site Request Forgery (CSRF) Protection**: A robust CSRF token generation and validation mechanism is implemented for all POST and destructive actions.
4. **Password Security**: Passwords are never stored in plaintext. They are encrypted using industry-standard BCRYPT and verified securely.
5. **Input Sanitization**: User inputs and outputs are sanitized through htmlspecialchars() with ENT_QUOTES to safeguard against Cross-Site Scripting (XSS).
