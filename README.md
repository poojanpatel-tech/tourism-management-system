# Patel Travels — Tourism Management System

> A luxury travel management system built with **PHP 8+**, **MySQL (PDO)**, **HTML5**, **CSS3**, and **Bootstrap 5.3**.
> Designed for college presentation, academic review, and local travel agency operations.

---

## 1. Project Overview

The **Tourism Management System (TMS)** provides an end-to-end platform for operating a boutique travel agency:
- **Curated Tour Packages**: Itineraries, pricing tiers, duration (days/nights), capacity limits, highlights, and photo galleries.
- **Destinations**: Domestic and international destination catalogs with highlights and descriptions.
- **CRM Enquiry Pipeline**: 7-stage lead management pipeline (`new`, `contacted`, `in_discussion`, `quoted`, `confirmed`, `closed`, `cancelled`).
- **Dashboard & Business Analytics**: Real-time KPI metrics, package availability meters, destination performance, and database-backed Confirmed Booking Value calculation.
- **Centralized Business Settings**: Global company details, operating hours, contact numbers, and social links manageable via the admin portal.
- **Public Client Experience**: High-end editorial aesthetic featuring responsive layouts, package booking workflows, and contact forms.

---

## 2. Technology Stack

- **Backend**: Vanilla PHP 8.0+ (Clean procedural & functional architecture without heavy frameworks, ideal for code review)
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB (via standard XAMPP or MySQL Server)
- **Database Layer**: PHP PDO with 100% Prepared Statements (SQL Injection protected)
- **Frontend**: HTML5, Vanilla CSS3, Bootstrap 5.3.3, Bootstrap Icons 1.11.3
- **Authentication**: PHP Session-based authentication with bcrypt hashing (`password_hash` / `password_verify`), strict cookie mode, and CSRF protection

---

## 3. Database Architecture (`tourism_management`)

### Primary Tables
1. **`admins`**: Stores administrative accounts with bcrypt-hashed passwords.
2. **`destinations`**: Destinations available for tour packages (`destination_id`, name, country, status).
3. **`packages`**: Travel plans tied to destinations (`package_id`, code, name, duration_days, duration_nights, price, capacity, status).
4. **`plan_itinerary`**: Day-by-day travel schedules linked to packages.
5. **`plan_highlights`**: Curated key feature bullets for tour packages.
6. **`plan_inclusions`**: Service items marked as included or excluded.
7. **`plan_images`**: Multi-photo image galleries for packages.
8. **`enquiries`**: Customer booking requests and leads with the 7-stage CRM lifecycle.
9. **`customers`**: Registered customer profiles and demographics.
10. **`reservations`**: Formal booking records.
11. **`payments`**: Payment ledger records.
12. **`settings`**: Key-value table for business settings (phone, email, hours, address, socials).

---

## 4. Setup & Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ / 8.0+ or MariaDB (XAMPP recommended on Windows)
- Apache or PHP built-in CLI server

### Step 1: Clone or Open Workspace
Ensure the project is located in your desired directory (e.g., `htdocs/POOJAN_PHP_WEBSITE` or custom folder).

### Step 2: Database Initialization

#### Option A: Fresh Database Installation
1. Start MySQL in XAMPP or your database service.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Create a database named `tourism_management`:
   ```sql
   CREATE DATABASE tourism_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Import `database.sql`:
   ```bash
   mysql -u root -p tourism_management < database.sql
   ```

#### Option B: Upgrading an Existing Installation
If you already have an older copy of the database, run `migration_fix.sql` to safely update columns and add missing tables without data loss:
```bash
mysql -u root -p tourism_management < migration_fix.sql
```

### Step 3: Database Configuration

Inspect `config/database.php` and verify connection parameters:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'tourism_management');
define('DB_USER', 'root');
define('DB_PASS', '');
```
For production hosting (e.g., Docker, cloud VM), credentials can also be passed via environment variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.

---

## 5. Running the Application Locally

The project includes specialized dual routers for development:

### 1. Admin Management Portal (Port 8000)
```bash
php -S localhost:8000 admin_server.php
```
- **Login URL**: `http://localhost:8000/auth/login.php`
- **Dashboard**: `http://localhost:8000/admin/dashboard.php`
- **Initial Setup Credentials**:
  - **Username**: `admin`
  - **Password**: `admin123` *(pre-configured in fresh-install database.sql; change in production)*

### 2. Public Client Website (Port 9000)
```bash
php -S localhost:9000 client_server.php
```
- **Website URL**: `http://localhost:9000/`
- Explore destinations, browse luxury packages, and submit booking enquiries.

---

## 6. Key Features & Business Logic

### CRM Enquiry Lifecycle
Enquiries move through 7 defined business stages:
1. `new` — Customer submitted an enquiry via the website.
2. `contacted` — Admin acknowledged or called the customer.
3. `in_discussion` — Customizing itinerary, dates, or passenger requirements.
4. `quoted` — Formal package price quote provided to customer.
5. `confirmed` — Customer accepted quote; trip is confirmed.
6. `closed` — Lead closed or lost.
7. `cancelled` — Customer cancelled request.

### Confirmed Booking Value KPI
The admin dashboard calculates estimated booking value dynamically from the database:
$$\text{Confirmed Booking Value} = \sum (\text{Package Unit Price} \times \text{Traveller Count})$$
*Only enquiries with status `'confirmed'` are included. Pending, in-discussion, or cancelled requests are strictly excluded from confirmed revenue.*

---

## 7. Security Standards Implemented

1. **Prepared Statements**: 100% of dynamic SQL queries use PDO prepared statements with parameter binding.
2. **Session Security**: Session cookies use `use_only_cookies`, `use_strict_mode`, and separate session namespaces (`TOURISM_ADMIN_SESSION` and `TOURISM_CLIENT_SESSION`).
3. **Cross-Site Request Forgery (CSRF)**: Cryptographic tokens validate all state-changing POST requests.
4. **XSS Protection**: HTML entities escaped with `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`.
5. **Upload Protection**: File uploads in `assets/images/packages/` are protected with MIME-type verification, extension validation, randomized filenames, and `.htaccess` script execution prevention.
6. **Defensive Error Handling**: Database errors are logged to `error_log` rather than leaked to public responses.

---

## 8. Deployment Notes & Storage Considerations

- **XAMPP / Traditional Apache / Nginx**: Supported out-of-the-box. Local image uploads are persisted to `assets/images/packages/`.
- **Vercel / AWS Lambda (Serverless)**: Serverless environments have read-only or ephemeral filesystems (`/tmp`). For persistent public uploads in serverless production, configure an external object store such as AWS S3 or Cloudinary.
