# Tourism Management System

> A mid-level college project built with **PHP 8+**, **MySQL (PDO)**, **HTML5**, **CSS3**, and **Bootstrap 5**.

---

## 1. Project Overview

The **Tourism Management System (TMS)** is designed to manage:
- **Tour Packages**: Itineraries, pricing, duration, maximum seat capacity, travel dates, and inclusions/exclusions.
- **Destinations**: Global and domestic tourist spots associated with packages.
- **Customers**: Traveler directory, contact details, and location data.
- **Reservations**: Booking numbers, passenger headcounts, total calculated amounts, travel dates, and status workflows (`pending`, `confirmed`, `cancelled`).
- **Package Availability**: Real-time evaluation of booked traveler headcounts vs. maximum package capacity.
- **Operations & Reports**: Financial reporting, booking conversion distributions, and top destinations.

---

## 2. Technology Stack

- **Backend**: Vanilla PHP 8+ (No heavy frameworks; easy to explain during college review)
- **Database**: MySQL 5.7+ / 8.0+ or MariaDB (via standard XAMPP)
- **Database Access**: PHP PDO with Prepared Statements (SQL-Injection protected)
- **Frontend**: HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons
- **Authentication**: PHP Session-based authentication with `password_hash()` and `password_verify()`

---

## 3. Database Architecture (`tourism_management`)

### Entity Relationship Model

```
destinations (1)
      │
      ▼ (N)
   packages (1)
      │
      ▼ (N)
 reservations (N) ◄────── (1) customers
```

### Core Tables

1. **`admins`**: Stores administrative credentials and profile details.
2. **`destinations`**: Tourist locations and countries available for package assignments.
3. **`packages`**: Tour packages tied to destinations with pricing and capacity constraints.
4. **`customers`**: Tourist customer directory with contact information.
5. **`reservations`**: Bookings linking customers and packages with travel dates and headcount.

---

## 4. How to Set Up & Run the Project on XAMPP

### Step 1: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel**.
2. Click the **Start** button next to **Apache**.
3. Click the **Start** button next to **MySQL**.
4. Ensure both modules turn green.

---

### Step 2: Create the Database & Import `database.sql`

#### Method A: Using phpMyAdmin (Recommended for College Review)
1. Open your web browser and go to:
   ```
   http://localhost/phpmyadmin
   ```
2. Click on the **Import** tab on the top navigation bar.
3. Click **Choose File** / **Browse** and select the `database.sql` file located in the project's root folder:
   ```
   c:\Users\dixit\OneDrive\Desktop\COLLEGE_PROJECT\POOJAN_PHP_WEBSITE\database.sql
   ```
4. Scroll to the bottom and click the **Go** button.
5. phpMyAdmin will automatically execute the script, create the database `tourism_management`, create all 5 tables with foreign keys, and seed all sample data.

#### Method B: Using MySQL Command Line (Alternative)
```bash
# Navigate to XAMPP MySQL bin directory if not on PATH
cd C:\xampp\mysql\bin
mysql -u root -p < "c:\Users\dixit\OneDrive\Desktop\COLLEGE_PROJECT\POOJAN_PHP_WEBSITE\database.sql"
```

---

### Step 3: Configure Database Credentials (If Needed)

The project is pre-configured for standard XAMPP defaults in [`config/database.php`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/POOJAN_PHP_WEBSITE/config/database.php):

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'tourism_management');
define('DB_USER', 'root');
define('DB_PASS', '');
```

If your MySQL server uses a different port or password, adjust those constants in [`config/database.php`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/POOJAN_PHP_WEBSITE/config/database.php).

---

### Step 4: Accessing the Application

#### Option 1: Running inside XAMPP `htdocs`
If the project folder is placed in or symlinked to `C:\xampp\htdocs\tourism-management-system`:
- **Public Welcome Portal**: `http://localhost/tourism-management-system/`
- **Admin Login Page**: `http://localhost/tourism-management-system/auth/login.php`
- **Admin Dashboard**: `http://localhost/tourism-management-system/admin/dashboard.php`

#### Option 2: Running via PHP Built-in Server (Instant Demo)
You can also run the project directly from this directory without moving files:
```powershell
& "C:\xampp\php\php.exe" -S localhost:8000
```
Then visit:
- `http://localhost:8000/`

---

## 5. Default Administrator Credentials

| Field | Value |
|---|---|
| **Login URL** | `http://localhost/POOJAN_PHP_WEBSITE/auth/login.php` |
| **Username** | `admin` |
| **Email** | `admin@tourism.local` |
| **Password** | `Admin@12345` |
| **Hashing Algorithm** | BCRYPT via `password_hash()` |

---

## 6. Project Directory Structure

```
POOJAN_PHP_WEBSITE/
│
├── config/
│   └── database.php         # PDO connection & diagnostic handler
│
├── includes/
│   ├── auth.php             # Session management, login guards, helpers
│   ├── header.php           # Global HTML head, Bootstrap 5 CDN & custom styles
│   ├── navbar.php           # Topbar with profile dropdown & date display
│   ├── sidebar.php          # Responsive sidebar navigation with active states
│   └── footer.php           # Layout closing tags, scripts, and attribution
│
├── assets/
│   ├── css/
│   │   └── style.css        # Tourism palette, cards, table styling, responsive layout
│   ├── js/
│   │   └── main.js          # Sidebar toggle, alert timers, confirm hooks
│   └── images/              # Package and destination image assets
│
├── admin/
│   ├── dashboard.php        # Live KPIs, recent bookings table, capacity tracker
│   ├── destinations/
│   │   └── index.php        # Destinations list with package counts & search
│   ├── packages/
│   │   └── index.php        # Tour packages catalog with capacity indicators
│   ├── customers/
│   │   └── index.php        # Customer directory with booking statistics
│   ├── reservations/
│   │   └── index.php        # Reservation directory with status filter tabs
│   └── reports/
│       └── index.php        # Financial KPIs, revenue breakdown, print report
│
├── auth/
│   ├── login.php            # Secure authentication form & credential verification
│   └── logout.php           # Clean session termination & redirect
│
├── index.php                # Welcome portal & live environment diagnostic checks
├── database.sql             # Complete schema, foreign keys, and seed data
└── README.md                # Project documentation & setup instructions
```

---

## 7. Security & Code Quality Standards

1. **Prepared Statements**: All database operations utilize PDO prepared statements (`$stmt->prepare()` with parameter binding) to eliminate SQL injection vulnerabilities.
2. **Session Hardening**: Sessions use strict mode and HTTP-only cookie parameters (`session_regenerate_id(true)` upon login).
3. **Cross-Site Request Forgery (CSRF) Protection**: A robust CSRF token generation and validation mechanism (`verify_csrf_token()`) is implemented for all POST and destructive actions.
4. **Password Security**: Passwords are never stored in plaintext. They are encrypted using industry-standard BCRYPT (`password_hash()`) and verified via `password_verify()`.
5. **Input Sanitization**: User inputs and outputs are sanitized through `htmlspecialchars()` with `ENT_QUOTES` to safeguard against Cross-Site Scripting (XSS).
6. **Referential Integrity Guards**: Application-level checks prevent orphaned records (e.g., stopping deletion of customers or packages that have associated reservations).
7. **No Framework Bloat**: Pure PHP 8+ code ensures students can clearly answer architectural questions during college viva examinations.
