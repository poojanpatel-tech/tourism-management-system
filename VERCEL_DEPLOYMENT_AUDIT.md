# VERCEL DEPLOYMENT AUDIT REPORT
## Patel Travels — Tourism Management System

**Project:** POOJAN_PHP_WEBSITE  
**Repository:** [https://github.com/poojanpatel-tech/tourism-management-system.git](https://github.com/poojanpatel-tech/tourism-management-system.git)  
**Deployment Target:** Vercel (Serverless Edge Platform)  
**Auditor Role:** Senior PHP Deployment Engineer & Serverless Architect  
**Audit Date:** October 15, 2026  

---

## 1. Executive Summary

This audit evaluates the deployment feasibility of the existing Patel Travels Tourism Management System on Vercel. 

The application is built on **Vanilla PHP 8+, MySQL (PDO), HTML5, CSS3, and Bootstrap 5.3**. It consists of 40 PHP files organized across public visitor pages, an authentication module, and an administrative portal with CRUD management for destinations, packages, galleries, itineraries, CRM enquiries, and settings.

### Key Finding:
**The repository in its current state cannot be successfully deployed to Vercel without configuration changes.** 
The existing `vercel.json` contains no runtime definition, `Dockerfile.vercel` is completely ignored by Vercel serverless builds, and the remote GitHub repository is currently out-of-sync with the verified local bug fixes. Furthermore, Vercel functions run in a serverless, read-only container environment without a local MySQL server.

However, by implementing a lightweight front-controller adapter (`api/index.php`), configuring the maintained community PHP runtime (`vercel-php@0.9.4`), supporting cloud MySQL with SSL, and pushing the verified local fixes, the existing application can run on Vercel while preserving 100% of its existing codebase, UI, and local XAMPP compatibility.

---

## 2. Current Project Architecture

### 2.1 File & Directory Layout
```text
POOJAN_PHP_WEBSITE/
├── index.php                     # Public Homepage
├── destinations.php              # Public Destination Catalog
├── destination-details.php       # Single Destination & linked tours
├── tours.php                     # Public Tour Packages with filters
├── tour-details.php              # Single Tour Details & Accordion
├── about.php                     # Company About Page
├── contact.php                   # Contact & Lead Form
├── book.php                      # Package Booking / Enquiry Submission
├── auth/
│   ├── login.php                 # Admin Login Form
│   └── logout.php                # Session Destruction
├── admin/
│   ├── dashboard.php             # KPIs & Recent Inquiries
│   ├── settings.php              # Business Settings Management
│   ├── destinations/             # Destination CRUD (index, create, view, edit)
│   ├── packages/                 # Package CRUD & sub-entities (itinerary, highlights, inclusions, gallery)
│   └── enquiries/                # CRM Lead Management (index, view)
├── config/
│   └── database.php              # PDO Connection Factory & Error Screen
├── includes/
│   ├── auth.php                  # Admin session guard & helpers
│   ├── public_functions.php      # Client session & CSRF helpers
│   ├── header.php, footer.php    # Admin layout partials
│   ├── navbar.php, sidebar.php   # Admin navigation
│   └── public_header.php, public_footer.php # Public layout partials
├── assets/                       # CSS, JS, Images (Static)
│   └── images/packages/          # Package Uploads (.htaccess protected)
├── database.sql                  # Canonical 14-table schema & seed data
├── migration_fix.sql             # Safe upgrade script
├── admin_server.php              # Local PHP CLI dev router (Port 8000)
├── client_server.php             # Local PHP CLI dev router (Port 9000)
├── vercel.json                   # Existing (Incomplete)
└── Dockerfile.vercel             # Existing (Ignored by Vercel)
```

### 2.2 Server-Side Execution Model
- The application uses traditional procedural PHP file-based routing (e.g. `/tours.php`, `/admin/dashboard.php`).
- Includes rely on relative filesystem paths (`__DIR__ . '/../../config/database.php'`).
- Sessions are managed using native PHP sessions with cookie strict mode.
- Database access uses a singleton PDO instance with prepared statements.

---

## 3. Current Deployment Blockers

| Blocker ID | Severity | Description | Impact on Vercel |
| :--- | :--- | :--- | :--- |
| **BLK-01** | **Critical** | `vercel.json` lacks PHP runtime and function mapping. | Vercel treats PHP files as static assets or fails build; server-side code does not execute. |
| **BLK-02** | **Critical** | Attempted container deployment via `Dockerfile.vercel`. | Vercel Serverless platform does not build or run custom Docker containers. `Dockerfile.vercel` is completely ignored. |
| **BLK-03** | **Critical** | Absence of an `api/` directory entry point. | Vercel's serverless build system requires function entry points inside `api/`. Loose PHP files in root are not invoked as serverless functions by default. |
| **BLK-04** | **Critical** | Local Database Dependency (`127.0.0.1:3306`). | Serverless execution containers do not run MySQL. Without an external cloud MySQL database configured, all database queries fail. |
| **BLK-05** | **High** | Cloud MySQL SSL Support Missing in PDO. | Many free cloud MySQL providers (e.g. TiDB Cloud, Aiven, PlanetScale) enforce TLS/SSL. `config/database.php` currently lacks SSL PDO attributes. |
| **BLK-06** | **High** | Ephemeral Serverless Filesystem for Admin Image Uploads. | Vercel serverless functions have a read-only filesystem except for temporary `/tmp`. Images uploaded via `admin/packages/create.php` cannot persist locally across function invocations. |
| **BLK-07** | **High** | Remote GitHub Repository Out of Sync. | The GitHub repository (`origin/main`) currently contains pre-audit bugs (corrupted admin hash, missing settings table, broken column names). Deploying directly from GitHub without committing the verified local fixes will deploy a broken application. |

---

## 4. Differences Between Local Code and GitHub Repository

A Git audit of `HEAD` (`origin/main` commit `dfd4119`) against the local working directory revealed **11 modified files and 2 untracked files** containing verified fixes that have not yet been pushed to GitHub:

| File | Status | Local Fix Present | Missing on GitHub |
| :--- | :---: | :--- | :--- |
| `database.sql` | Modified | Clean UTF-8, valid bcrypt hash for `admin123`, `duration_nights` added, `settings` table included, CRM enum updated. | Corrupted password hash `'/ma2.ycVdVXCdFAPMYlVRna'`, UTF-16LE encoding, missing settings table. |
| `contact.php` | Modified | Defensive `try...catch` fallback for settings queries. | Fatal PDOException if settings missing. |
| `admin/settings.php` | Modified | Removed stray `session_name()` call; normalized includes; masked raw PDO errors. | Triggers `PHP Warning: Session name cannot be changed`. |
| `admin/enquiries/view.php`| Modified | Server-side status validation & try-catch handling. | Unhandled exceptions on invalid transitions. |
| `admin/dashboard.php` | Modified | Database-backed Confirmed Booking Value calculation ($\sum \text{price} \times \text{travellers}$). | Hardcoded static ₹0 placeholder. |
| `includes/auth.php` | Modified | Guarded `session.save_path` for Windows compatibility. | Unconditional `ini_set` for `/tmp`. |
| `includes/public_header.php`| Modified| Guarded `session.save_path` for Windows compatibility. | Unconditional `ini_set` for `/tmp`. |
| `seed.sql` | Modified | Repaired typos in highlights and inclusions table definitions. | `highlight_text` and `	ype` typos present. |
| `README.md` | Modified | Comprehensive dual-server, installation, and deployment documentation. | Outdated setup instructions. |
| `migration_fix.sql` | Untracked| Complete idempotent upgrade script. | File absent from GitHub. |
| `assets/images/packages/.htaccess` | Untracked| Execution prevention inside upload directory. | File absent from GitHub. |

---

## 5. Required Deployment Configuration

### 5.1 Vercel Serverless Architecture
To deploy a multi-page procedural PHP project to Vercel without refactoring 40 files:
1. **Community PHP Runtime**: Use `vercel-php@0.9.4` (the official community runtime maintained by `vercel-community/php`).
2. **Front-Controller Dispatcher (`api/index.php`)**: Create a lightweight, secure router in `api/index.php` that intercepts incoming web requests, validates requested PHP routes, sets appropriate working directories, and executes the targeted PHP file.
3. **Static Asset Bypass**: Route requests matching `/assets/(.*)` directly to static assets, ensuring stylesheets, scripts, and bundled images are served via Vercel's global Edge CDN without invoking serverless functions.
4. **Clean Route Mapping**:
   - `/` $\to$ `index.php`
   - `/destinations` or `/destinations.php` $\to$ `destinations.php`
   - `/auth/login` or `/auth/login.php` $\to$ `auth/login.php`
   - `/admin/dashboard` or `/admin/dashboard.php` $\to$ `admin/dashboard.php`
   - `/admin/packages/edit.php?id=1` $\to$ `admin/packages/edit.php` (with `$_GET` preserved)

### 5.2 Updated `vercel.json` Specification
```json
{
  "version": 2,
  "functions": {
    "api/index.php": {
      "runtime": "vercel-php@0.9.4"
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
      "src": "/(.*)",
      "dest": "/api/index.php"
    }
  ]
}
```

---

## 6. Database and Storage Requirements

### 6.1 External MySQL Database Options
Because Vercel serverless functions cannot connect to `127.0.0.1` (local XAMPP), an external hosted MySQL database is required. Recommended compatible providers with free/affordable tiers:

1. **TiDB Cloud (Serverless)**:
   - Fully MySQL 8.0 wire-compatible.
   - Generous free tier (5GB storage, 50M Request Units/month).
   - Enforces TLS/SSL connection.
   - Low latency serverless connectivity.
2. **Aiven for MySQL**:
   - Standard managed MySQL on AWS/GCP.
   - SSL certificates provided.
3. **Clever Cloud / Railway**:
   - Standard MySQL containerized service.

### 6.2 Required Environment Variables in Vercel
In the Vercel Project Settings $\to$ Environment Variables:
- `DB_HOST`: Hostname of cloud database (e.g. `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`)
- `DB_PORT`: Database port (default `3306` or provider port like `4000`)
- `DB_NAME`: Database name (e.g. `tourism_management`)
- `DB_USER`: Database username
- `DB_PASS`: Database password
- `DB_SSL_MODE`: Set to `REQUIRED` or `1` for SSL-enforced databases

### 6.3 Persistent Image Storage Considerations
- **Current Behavior**: Admin photo uploads write to `assets/images/packages/`.
- **Serverless Reality**: On Vercel, the local container is read-only. Uploaded files saved locally are lost when the function instance terminates.
- **Recommended Strategy**:
  1. *Immediate/Preview Phase*: Keep bundled stock images in `assets/images/` (which deploy via Edge CDN) and document that new uploads require an external storage provider.
  2. *Production Phase*: Integrate an external media service such as Cloudinary or AWS S3 via an adapter in `admin/packages/create.php` and `gallery.php`.

---

## 7. Security Risks & Mitigations

1. **Accidental Credential Exposure**:
   - Database credentials must **never** be placed in `vercel.json`, `README.md`, or committed to Git.
   - `.env` files must be strictly excluded via `.gitignore` and `.vercelignore`.
2. **Directory Traversal in Front-Controller**:
   - The front controller (`api/index.php`) must use `realpath()` and verify that resolved paths reside strictly within the project directory.
   - Requests to `.git`, `.env`, `database.sql`, and `config/` must return HTTP 403 Forbidden.
3. **Public Exposure of Server Errors**:
   - `renderDatabaseErrorScreen()` must log technical exception details to `error_log` and only present clean, sanitized guidance to public visitors.

---

## 8. Recommended Deployment Roadmap

1. **Step 1 — Database Layer**: Update `config/database.php` to support SSL attributes (`PDO::MYSQL_ATTR_SSL_CA` / `PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT`) while maintaining local XAMPP fallback defaults.
2. **Step 2 — Front Controller**: Create `api/index.php` as a secure, clean dispatcher supporting both root and extension-less URLs.
3. **Step 3 — Vercel Configuration**: Replace `vercel.json` with the verified `functions` and `routes` specification targeting `vercel-php@0.9.4`. Create `.vercelignore` to exclude local scratch files, dumps, and test scripts.
4. **Step 4 — Local Verification**: Lint all PHP files, test the front controller with simulated Vercel serverless requests, and verify asset routes.
5. **Step 5 — Documentation**: Author `VERCEL_DEPLOYMENT_GUIDE.md` detailing the external database setup, environment variable configuration, and deployment promotion steps.
6. **Step 6 — Git Preparation**: Stage and review all verified changes, providing exact, safe commands for the user to commit and push to GitHub.
