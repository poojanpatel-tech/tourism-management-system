# VERCEL DEPLOYMENT AUDIT REPORT
## Patel Travels — Tourism Management System (College Project)

**Project Name:** Tourism Management System (`POOJAN_PHP_WEBSITE`)  
**GitHub Repository:** [https://github.com/poojanpatel-tech/tourism-management-system.git](https://github.com/poojanpatel-tech/tourism-management-system.git)  
**Local Project Directory:** `C:\Users\dixit\OneDrive\Desktop\COLLEGE_PROJECT\POOJAN_PHP_WEBSITE`  
**Deployment Target:** Vercel (Serverless Edge Platform)  
**Auditor Roles:** Senior PHP Deployment Engineer, Vercel Serverless Architect, MySQL Database Engineer, and Application Security Auditor  
**Audit Date:** October 16, 2026  
**Final Readiness Status:** **READY FOR PREVIEW DEPLOYMENT** *(External MySQL credentials required for live database operations)*

---

## 1. Executive Summary

This comprehensive audit evaluates the technical feasibility of deploying the existing **Patel Travels Tourism Management System** to the **Vercel Serverless Platform** while maintaining 100% of its existing codebase, design aesthetics, administrative dashboard, and local XAMPP compatibility for college project evaluation.

The application is written in **Vanilla PHP 8+, MySQL (PDO), HTML5, CSS3, and Bootstrap 5.3**. It consists of 41 PHP files covering public visitor catalogs, inquiry/booking funnels, customer session management, and an administrative panel with full CRUD operations for destinations, tour packages, galleries, itineraries, CRM lead management, and business settings.

### Key Audit Conclusions:
1. **No Application Rewrite Needed:** The application does **not** need to be rewritten in Node.js, Next.js, or any other framework. It runs on Vercel using the maintained community PHP runtime (`vercel-php@0.8.0`, targeting PHP 8.4).
2. **Serverless Dispatcher Architecture:** Because Vercel free/hobby accounts enforce a strict limit of 12 serverless functions per project (and loose PHP files in the root directory are not automatically invoked as serverless functions), a single high-performance front-controller (`api/index.php`) serves as the serverless bridge.
3. **Database Independence:** Vercel functions cannot connect to `127.0.0.1:3306` (local XAMPP). The application requires an externally hosted, MySQL-compatible cloud database (such as **TiDB Cloud Serverless** or **Aiven for MySQL**). The database layer has been verified to fail gracefully with a safe error screen if environment variables are missing, masking technical SQL exceptions from public visitors.
4. **Filesystem Statelessness:** The Vercel runtime filesystem is strictly read-only except for ephemeral `/tmp`. Session storage has been guarded to use `/tmp`, and bundled assets (including stock package photography) are served through Vercel's global Edge CDN.

---

## 2. Current Project Architecture

### 2.1 Directory & File Layout
```text
POOJAN_PHP_WEBSITE/
├── index.php                         # Public Homepage & Featured Tours
├── about.php                         # Company Profile & Core Values
├── destinations.php                  # Destination Catalog with Search & Filter
├── destination-details.php           # Single Destination View & Associated Tours
├── tours.php                         # Curated Journeys & Packages with Filtering
├── tour-details.php                  # Single Tour Page with Tabbed Accordion
├── contact.php                       # Contact & Direct Lead Inquiry Form
├── book.php                          # Tour Booking & Inquiry Funnel
├── api/
│   └── index.php                     # Vercel Serverless Front-Controller Dispatcher
├── auth/
│   ├── login.php                     # Admin Authentication & CSRF Validation
│   └── logout.php                    # Session Destruction & Cookie Invalidation
├── admin/
│   ├── dashboard.php                 # KPI Statistics & Recent Inquiries
│   ├── settings.php                  # Business Configuration Management
│   ├── destinations/                 # Destination CRUD (index, create, view, edit, delete)
│   ├── packages/                     # Package CRUD (index, create, view, edit, delete, gallery, highlights, inclusions, itinerary)
│   └── enquiries/                    # CRM Lead Management (index, view & status pipeline)
├── config/
│   └── database.php                  # Singleton PDO Connection, Cloud SSL & Error Screen
├── includes/
│   ├── auth.php                      # Admin Session Guard & Helper Utilities
│   ├── public_functions.php          # Public Helpers, Image Resolvers & CSRF Utilities
│   ├── header.php, footer.php        # Admin Layout Partials
│   ├── navbar.php, sidebar.php       # Admin Navigation Components
│   └── public_header.php, public_footer.php # Public Luxury Layout Partials
├── assets/
│   ├── css/ (style.css, public.css)  # Custom Stylesheets & Design Tokens
│   ├── js/ (main.js, public.js)      # Client Interactions & Form Validations
│   ├── images/packages/              # Stock Uploads & .htaccess Execution Barrier
│   └── public/images/fallback/       # Edge CDN Fallback Photography (hero, plan, destination)
├── database.sql                      # Canonical 14-table MySQL Schema & Seed Dump
├── migration_fix.sql                 # Idempotent Database Upgrade Script
├── vercel.json                       # Vercel Runtime & Edge CDN Routing Specification
├── .vercelignore                     # Build Artifact Exclusion Rules
├── .gitignore                        # Git Secret & Audit Exclusion Rules
└── README.md                         # Dual-Mode Development & System Documentation
```

### 2.2 Server-Side Execution Model & Routing
The application utilizes file-based procedural routing. When deployed to Vercel:
- **Static Asset Requests** (`/assets/*`, `/favicon.ico`, `/robots.txt`) are intercepted by Vercel Edge CDN and served immediately without invoking serverless compute.
- **Dynamic Application Requests** (`/`, `/destinations`, `/tour-details.php?id=1`, `/admin/dashboard`, etc.) are routed to `/api/index.php`.
- **The Front Controller (`api/index.php`)**:
  1. Configures `session.save_path` to `/tmp` if writable.
  2. Parses and restores `$_GET` and `$_SERVER['QUERY_STRING']` to ensure deep links and filter queries (`?id=...`, `?status=...`) remain intact across serverless rewrites.
  3. Enforces security guards blocking `.git`, `.env`, `database.sql`, `config/`, and `includes/`.
  4. Resolves the target PHP script using canonical path checking (`realpath`) to prevent directory traversal.
  5. Switches the current working directory (`chdir`) to the target script's directory so all relative `require_once` statements resolve seamlessly.
  6. Dispatches the script via `require`.

---

## 3. Deployment Blockers: Identified & Resolved

| Blocker ID | Severity | Root Cause | Resolution Status | Verified Fix |
| :--- | :---: | :--- | :---: | :--- |
| **BLK-01** | **Critical** | `Dockerfile.vercel` was created based on the assumption that Vercel builds custom Docker containers. | **Resolved** | Vercel Serverless executes lambdas via community runtimes; `Dockerfile.vercel` is excluded in `.vercelignore`. The front-controller adapter handles execution. |
| **BLK-02** | **Critical** | Invalid community runtime specification `vercel-php@0.9.4` in `vercel.json` (npm package does not exist). | **Resolved** | Pinned to verified, published runtime `vercel-php@0.8.0` (PHP 8.4 with Node.js 22 runner). |
| **BLK-03** | **Critical** | Vercel Serverless 12-function limit prevented mapping 41 PHP files as independent serverless functions. | **Resolved** | Unified all dynamic routes into a single serverless function (`api/index.php`) using Vercel routes. |
| **BLK-04** | **Critical** | Local MySQL dependency (`127.0.0.1:3306`). Serverless containers cannot connect to local XAMPP. | **Resolved** | Implemented environment-based database configuration (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_SSL_MODE`) while preserving local XAMPP defaults when `VERCEL` is not set. |
| **BLK-05** | **High** | Cloud MySQL TLS/SSL negotiation failures on databases requiring encrypted transport (e.g. TiDB Cloud, Aiven). | **Resolved** | Added intelligent SSL handling in `config/database.php` that activates `PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT` and supports custom `DB_SSL_CA` or system CA bundles. |
| **BLK-06** | **High** | Ephemeral/Read-Only filesystem preventing session creation and local package photo uploads on Vercel. | **Resolved** | Configured `session.save_path` to `/tmp` in `api/index.php`, `includes/auth.php`, `includes/public_header.php`, `tour-details.php`, and `book.php`. Image resolver uses bundled stock CDN assets with graceful fallbacks. |
| **BLK-07** | **High** | Query string parameters dropped during serverless rewrite proxying (`?id=1` lost on tour detail pages). | **Resolved** | `api/index.php` extracts `$_SERVER['REQUEST_URI']` query parameters and re-populates `$_GET` and `$_SERVER['QUERY_STRING']`. |
| **BLK-08** | **Medium** | Direct web execution of internal layout partials (`/includes/footer.php`) and CLI routers (`/admin_server.php`). | **Resolved** | Added `/includes/`, `admin_server.php`, and `client_server.php` to `$forbiddenPatterns` in `api/index.php` returning HTTP 403 Forbidden. |

---

## 4. Local Code vs. GitHub Repository Synchronization

A comparison between the local workspace and GitHub repository (`https://github.com/poojanpatel-tech/tourism-management-system.git`) reveals:

1. **Remote Repository Status:** The remote `origin/main` branch is at commit `8b03a25` (`feat: configure Vercel serverless PHP routing, cloud MySQL SSL, and audit fixes`).
2. **Current Local Changes:** The local workspace contains 6 modified files with verified serverless resilience fixes ready for review and commit:
   - `vercel.json` (runtime pinned to `vercel-php@0.8.0`)
   - `api/index.php` (session `/tmp` initialization, query parameter preservation, static streaming, security blocks)
   - `config/database.php` (enhanced cloud SSL attribute activation for TiDB/Aiven/Railway)
   - `tour-details.php` (guarded session initialization)
   - `book.php` (guarded session initialization)
   - `.vercelignore` (CLI server scripts and audit reports excluded from bundle)

---

## 5. Required Deployment Configuration

### 5.1 `vercel.json` Specification
```json
{
  "version": 2,
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.8.0"
    }
  },
  "routes": [
    {
      "src": "/assets/(.*)",
      "dest": "/assets/$1"
    },
    {
      "src": "/favicon.ico",
      "dest": "/favicon.ico"
    },
    {
      "src": "/robots.txt",
      "dest": "/robots.txt"
    },
    {
      "src": "/(.*)",
      "dest": "/api/index.php"
    }
  ]
}
```

### 5.2 Build & Execution Settings in Vercel
- **Framework Preset:** `Other`
- **Root Directory:** `./` (leave default)
- **Build Command:** None (pure PHP; no build step required)
- **Output Directory:** None (handled dynamically by community runtime)
- **Install Command:** None (no composer packages required)

---

## 6. Database and Storage Requirements

### 6.1 External MySQL Database Options
Because Vercel serverless functions do not host a local MySQL database, the production deployment requires an externally accessible, MySQL-compatible cloud database:

| Provider | MySQL Compatibility | Free Tier Details | SSL Requirement | Recommended For |
| :--- | :---: | :--- | :---: | :--- |
| **TiDB Cloud (Serverless)** | MySQL 8.0 wire-compatible | **5 GB storage, 50M Request Units/mo free** | **Required** (TLS 1.2+) | **Recommended** (Best free tier, instant setup) |
| **Aiven for MySQL** | Native MySQL 8.0 | Free trial / Paid starting at ~$19/mo | **Required** (CA cert provided) | Production enterprise |
| **Clever Cloud** | Native MySQL 8.0 | Free shared database tier | Optional/Standard | Quick testing |
| **Railway** | Native MySQL 8.0 | $5 monthly credit tier | Standard MySQL | Alternative option |

### 6.2 Environment Variables Required in Vercel Dashboard
In **Vercel Project Settings $\to$ Environment Variables**:

| Variable Name | Required | Example Value | Description |
| :--- | :---: | :--- | :--- |
| `DB_HOST` | **Yes** | `gateway01.us-east-1.prod.aws.tidbcloud.com` | Hostname of external cloud database |
| `DB_PORT` | **Yes** | `4000` (or `3306`) | Connection port |
| `DB_NAME` | **Yes** | `tourism_management` | Database name |
| `DB_USER` | **Yes** | `2xAbc99.root` | Database username |
| `DB_PASS` | **Yes** | `YourSecretPassword123!` | Database password |
| `DB_SSL_MODE` | Optional | `REQUIRED` | Set to `REQUIRED` for TiDB Cloud or SSL-enforced providers |
| `DB_SSL_VERIFY` | Optional | `true` (or `false`) | Set to `false` if provider uses self-signed or internal CA certs |

### 6.3 Media & Image Storage Strategy
- **Static & Bundled Media:** All images in `assets/` (including default package and destination imagery, luxury hero banners, and fallback photos) are deployed as static assets to Vercel's Edge CDN. They load instantly with global edge caching.
- **Admin Image Uploads:** Because Vercel functions have a read-only filesystem, images uploaded through the admin panel (`admin/packages/create.php`, `gallery.php`) cannot be written permanently to the local disk.
- **Recommended Handling:**
  1. *For College Evaluation & Preview:* Admin packages can specify any direct image filename matching bundled assets, or full HTTPS URLs from Unsplash/Cloudinary.
  2. *For Production Persistent Uploads:* Configure an external media store (e.g. Cloudinary or AWS S3) via an adapter.

---

## 7. Security Audit & Preserved Controls

1. **Password Hashing:** Uses PHP's native `password_hash()` and `password_verify()` with `PASSWORD_BCRYPT`. The default administrator account (`admin` / `admin123`) was verified against the bcrypt hash in `database.sql`.
2. **Session Security:** 
   - `session.use_only_cookies = 1`
   - `session.use_strict_mode = 1`
   - Session identifier regenerated on login (`session_regenerate_id(true)`) to mitigate session fixation attacks.
   - Separate session namespaces: `TOURISM_CLIENT_SESSION` and `TOURISM_ADMIN_SESSION`.
3. **Route Protection:** Every one of the 18 admin routes enforces `require_login()`. Unauthenticated requests are immediately redirected to `/auth/login.php` with HTTP 302.
4. **CSRF Defense:** Cryptographic CSRF tokens (`bin2hex(random_bytes(32))`) validated with timing-attack resistant `hash_equals()` on all POST forms.
5. **SQL Injection Prevention:** 100% of user-supplied parameters are bound using PDO prepared statements (`$stmt->prepare()` and `$stmt->execute()`). Zero concatenated SQL statements.
6. **XSS Prevention:** All dynamic database outputs are escaped using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
7. **Production Error Masking:** `config/database.php` catches `PDOException`, logs technical connection errors to server `error_log`, and renders a sanitized, safe notice to public visitors without exposing passwords, hosts, or stack traces.
8. **Credential Protection:** Zero credentials committed to Git or hardcoded in PHP. `.env` and `.env.*` are excluded by `.gitignore` and `.vercelignore`.

---

## 8. Verification Results

| Test Category | Description | Verification Method | Status |
| :--- | :--- | :--- | :---: |
| **PHP Syntax** | Linted all 41 project PHP files | `php -l` automated scan | **PASSED** (41/41) |
| **Vercel Config** | Validated `vercel.json` JSON structure & runtime | Node.js JSON schema parser | **PASSED** |
| **Security Barriers** | Verified access blocked to `.env`, `database.sql`, `config/`, `includes/`, `vercel.json` | Isolated CLI HTTP simulation | **PASSED** (HTTP 403) |
| **404 Handling** | Verified unknown routes return friendly 404 | Simulated invalid route request | **PASSED** (HTTP 404) |
| **Static Streaming** | Verified CSS, JS, and image assets served with proper MIME | Front-controller stream test | **PASSED** (HTTP 200, text/css) |
| **Admin Route Guard** | Unauthenticated access to `/admin/dashboard` | Simulated unauthenticated request | **PASSED** (HTTP 302 Redirect) |
| **Public Pages (9/9)** | Homepage, About, Destinations, Details, Tours, Tour Details, Contact, Booking, Login | Isolated front-controller rendering | **PASSED** (9/9 HTTP 200) |
| **Admin Pages (5/5)** | Dashboard, Settings, Destinations List, Packages List, Enquiries List | Authenticated session simulation | **PASSED** (5/5 HTTP 200) |
| **Database Queries** | Read-only verification of 12 critical queries across 14 tables | Local MySQL test execution | **PASSED** (12/12) |
| **CRUD Operations** | Transactional INSERT & UPDATE for Enquiries, Destinations, Packages, Settings | Isolated transaction with rollback | **PASSED** (Zero dirty data) |
| **Error Screen** | Failure screen when database is offline or unconfigured | Simulated missing DB host on Vercel | **PASSED** (HTTP 500, Masked) |
| **Local Compatibility** | Fallback defaults for local XAMPP (`127.0.0.1:3306`, `root`) | Standalone PHP execution without `VERCEL` | **PASSED** |
| **Remote Vercel Live** | Live deployment to user's personal Vercel dashboard | Requires user's Vercel account and cloud DB | **PENDING USER ACTION** |

---

## 9. Deployment Readiness Verdict

### Current Status: **READY FOR PREVIEW DEPLOYMENT**

The codebase and configuration files are fully prepared and locally verified. To complete deployment:
1. Review and commit the 6 verified files using Git.
2. Provision a free cloud MySQL database (e.g. TiDB Cloud Serverless) and import `database.sql`.
3. Link the GitHub repository in the Vercel dashboard and enter the 5 database environment variables.
