# VERCEL DEPLOYMENT GUIDE
## Patel Travels — Tourism Management System (College Project)

> **Complete, beginner-friendly deployment guide for publishing the Patel Travels application to Vercel with an external cloud MySQL database while retaining 100% local XAMPP compatibility.**

---

## 1. Prerequisites Checklist

Before you begin, ensure you have:
1. **GitHub Account**: Access to your repository at [https://github.com/poojanpatel-tech/tourism-management-system.git](https://github.com/poojanpatel-tech/tourism-management-system.git).
2. **Vercel Account**: Free Hobby account at [https://vercel.com](https://vercel.com).
3. **Cloud MySQL Database**: An external MySQL 8-compatible database (TiDB Cloud Serverless is free and recommended; see Section 3).
4. **Local Git Installed**: To review, commit, and push your verified files to GitHub.

---

## 2. Reviewing and Pushing the Verified Code to GitHub

The local codebase has been verified and hardened for Vercel serverless execution.

### Step 2.1 — Review Working Tree
Open PowerShell or your terminal in the project directory:
```bash
cd C:\Users\dixit\OneDrive\Desktop\COLLEGE_PROJECT\POOJAN_PHP_WEBSITE
git status
```
You will see 6 modified code and configuration files:
- `vercel.json` (runtime pinned to `vercel-php@0.8.0`)
- `api/index.php` (front-controller with session `/tmp` guard, query preservation, static streaming)
- `config/database.php` (cloud MySQL TLS/SSL configuration)
- `tour-details.php` (guarded session start for serverless)
- `book.php` (guarded session start for serverless)
- `.vercelignore` (bundle exclusion rules)
- `VERCEL_DEPLOYMENT_AUDIT.md` (updated audit report)
- `VERCEL_DEPLOYMENT_GUIDE.md` (this guide)

### Step 2.2 — Review the Exact Code Diff
Verify the changes with:
```bash
git diff
```

### Step 2.3 — Stage and Commit Your Verified Files
```bash
git add vercel.json api/index.php config/database.php tour-details.php book.php .vercelignore VERCEL_DEPLOYMENT_AUDIT.md VERCEL_DEPLOYMENT_GUIDE.md
git commit -m "feat: complete Vercel serverless PHP routing, cloud SSL, and deployment documentation"
```

### Step 2.4 — Push to GitHub
```bash
git push origin main
```
*Your GitHub repository is now synchronized with all verified serverless deployment files.*

---

## 3. Creating an External Cloud MySQL Database (Free & Fast)

Because Vercel serverless functions run in ephemeral cloud containers, they cannot connect to `localhost` or `127.0.0.1` (your personal computer's XAMPP). An external cloud database is required.

### Recommended Provider: **TiDB Cloud (Serverless)**
* **Why TiDB?**: Fully MySQL 8.0 wire-compatible, native TLS/SSL encryption, PDO-ready, and has a permanent free tier with **5 GB storage and 50 million Request Units/month**. No credit card required.

#### Setup Steps:
1. Navigate to [https://tidbcloud.com](https://tidbcloud.com) and click **Sign Up** (sign in using your GitHub or Google account).
2. Click **Create Cluster** and select **Serverless** (Free).
3. Choose a cloud region close to your target visitors (e.g., `AWS / Mumbai (ap-south-1)` or `AWS / Singapore (ap-southeast-1)` or `AWS / N. Virginia (us-east-1)`).
4. Name your cluster (e.g., `tourism-db`) and click **Create**. The cluster will initialize in approximately 20–30 seconds.
5. In your cluster dashboard, click **Connect**:
   - Select **Connect with: General / MySQL CLI**.
   - Note the **Host** (e.g. `gateway01.ap-south-1.prod.aws.tidbcloud.com`).
   - Note the **Port** (`4000`).
   - Note the **User** (e.g. `2xAbCdEf.root`).
   - Click **Reset Password** or **Generate Password**, copy the generated password, and save it in a secure note.
   - Note the default database name (`test`, or create `tourism_management`).

*(Alternative Providers: **Aiven for MySQL** free trial, **Clever Cloud MySQL** free personal plan, or **Railway MySQL** developer tier).*

---

## 4. Importing `database.sql` Safely into Your Cloud Database

Before visiting the website on Vercel, import the database schema and sample data once into your new cloud database.

> [!CAUTION]
> Always verify you are connected to your intended cloud database before importing. The `database.sql` file creates the 14 application tables and seeds sample destinations, packages, settings, and the default admin user. It does not overwrite databases outside its connection.

### Method A: Using MySQL Command-Line (CLI)
From your terminal, run the following command (replace with your cloud credentials):
```bash
mysql -h <YOUR_DB_HOST> -P <YOUR_DB_PORT> -u <YOUR_DB_USER> -p --ssl-mode=REQUIRED <YOUR_DB_NAME> < database.sql
```

*Example for TiDB Cloud:*
```bash
mysql -h gateway01.ap-south-1.prod.aws.tidbcloud.com -P 4000 -u 2xAbCdEf.root -p --ssl-mode=REQUIRED test < database.sql
```
When prompted, enter your cloud database password. The command will run quietly and complete in 2–5 seconds.

### Method B: Using DBeaver, HeidiSQL, or MySQL Workbench (GUI)
1. Open DBeaver or MySQL Workbench.
2. Create a new MySQL Connection:
   - **Host:** Your cloud DB Host
   - **Port:** `4000` (or `3306`)
   - **Database:** `test` (or `tourism_management`)
   - **Username:** Your cloud DB user
   - **Password:** Your cloud DB password
   - **SSL Tab:** Check **Require SSL**
3. Test Connection and click **Finish**.
4. Open `database.sql` inside the SQL Editor.
5. Run the entire script (Execute Script / Alt+X).
6. Refresh the schema to verify that all 14 tables (`admins`, `packages`, `destinations`, `enquiries`, `settings`, etc.) appear.

---

## 5. Deploying the Project on Vercel

### Step 5.1 — Import Repository to Vercel
1. Log in to [https://vercel.com](https://vercel.com).
2. On your Vercel Dashboard, click **Add New...** $\to$ **Project**.
3. Under "Import Git Repository", find `poojanpatel-tech/tourism-management-system`.
4. Click **Import**.

### Step 5.2 — Configure Project Settings
In the **Configure Project** setup screen:
- **Project Name**: `tourism-management-system` (or any URL name you prefer).
- **Framework Preset**: Leave as **Other** (do NOT select Next.js, Vite, or React).
- **Root Directory**: Leave as `./` (default).
- **Build and Output Settings**: Leave completely blank/default. Our `vercel.json` instructs Vercel to use the PHP runtime.

### Step 5.3 — Configure Environment Variables
Expand the **Environment Variables** panel and add the following keys from your cloud database credentials:

| Key | Value | Description |
| :--- | :--- | :--- |
| `DB_HOST` | `gateway01.ap-south-1.prod.aws.tidbcloud.com` | Cloud database hostname |
| `DB_PORT` | `4000` (or `3306`) | Cloud database port |
| `DB_NAME` | `test` (or `tourism_management`) | Cloud database name |
| `DB_USER` | `2xAbCdEf.root` | Cloud database username |
| `DB_PASS` | `YourActualCloudPassword123!` | Cloud database password |
| `DB_SSL_MODE` | `REQUIRED` | Enforces TLS/SSL transport |
| `DB_SSL_VERIFY` | `true` | Validates server certificate |

> [!IMPORTANT]
> - Do NOT include quotes (`"`) or whitespace around environment variable values in Vercel.
> - Ensure all 5 variables are added before clicking Deploy.

### Step 5.4 — Deploy
Click **Deploy**.
Vercel will:
1. Detect `vercel.json`.
2. Download and activate `vercel-php@0.8.0`.
3. Bundle the application into a single Edge serverless function (`api/index.php`) and static Edge CDN assets.
4. Output your live deployment URL: `https://tourism-management-system-xxxx.vercel.app`.

---

## 6. Verifying the Live Deployment

Once deployment completes, open your live Vercel URL and run through this verification checklist:

1. **Public Homepage (`/`)**:
   - Verifies: Hero photography, luxury typography, company tagline, and featured tour packages load dynamically from the cloud database.
2. **Destinations Page (`/destinations.php` or `/destinations`)**:
   - Verifies: Destination cards load with countries and tour counts.
3. **Tour Packages & Filters (`/tours.php` or `/tours`)**:
   - Verifies: Filter forms submit and query parameters (`?destination_id=...`, `?duration=...`) function without being stripped.
4. **Tour Details (`/tour-details.php?id=1`)**:
   - Verifies: Itinerary tabs, highlights, inclusions, and package pricing render accurately.
5. **Contact Page (`/contact.php` or `/contact`)**:
   - Verifies: Contact phone, email, and working hours load from the `settings` database table. Submit an enquiry to test database write capability.
6. **Booking Funnel (`/book.php?package_id=1`)**:
   - Verifies: Form submits cleanly and generates a CRM enquiry record.
7. **Admin Authentication (`/auth/login.php`)**:
   - Credentials:
     - **Username:** `admin`
     - **Password:** `admin123`
   - Verifies: Successful CSRF check, bcrypt password verification, session regeneration, and redirect to dashboard.
8. **Admin Dashboard (`/admin/dashboard.php`)**:
   - Verifies: KPI counters, recent enquiries table, and database-backed Confirmed Booking Value calculation.
9. **Admin Route Security Guard**:
   - In a private/incognito window, visit `https://<YOUR-APP>.vercel.app/admin/dashboard.php`.
   - Verifies: Access is denied and you are redirected to `/auth/login.php` with a warning message.

---

## 7. Diagnosing Issues & Inspecting Logs

If anything behaves unexpectedly on Vercel:

### 1. View Real-Time Function Logs
1. Go to your Vercel Project Dashboard.
2. Click the **Logs** tab (or **Runtime Logs** under your current deployment).
3. Filter by **Errors** or look for messages logged by `error_log()`.

### 2. "Database Connection Notice" Screen
- **What it means:** The application could not reach your external MySQL database within the timeout window.
- **Diagnosis Checklist:**
  1. Check Vercel **Project Settings $\to$ Environment Variables**. Did you misspell any key?
  2. Is `DB_PORT` set to `4000` (for TiDB Cloud) or `3306` (for standard MySQL)?
  3. Is `DB_SSL_MODE` set to `REQUIRED`?
  4. **Firewall / IP Whitelist:** Ensure your cloud database allows connections from all IP addresses (`0.0.0.0/0`) because Vercel functions run on dynamic IP addresses. In TiDB Cloud, check **Security $\to$ IP Access List** is set to `0.0.0.0/0`.

### 3. Session Issues
- All sessions are configured to save to `/tmp` in the serverless environment with secure cookie parameters. If you ever experience session logout, ensure third-party cookies are not blocked in your browser for the `.vercel.app` domain.

---

## 8. Feature Availability & External Requirements

| Application Feature | Free-Plan Status | Configuration Needed |
| :--- | :---: | :--- |
| **Public Catalog (Home, About, Destinations, Tours)** | **Available Immediately** | Requires external MySQL credentials |
| **Customer Enquiries & Contact Submissions** | **Available Immediately** | Saves directly to MySQL `enquiries` table |
| **Admin Panel Login & Session Management** | **Available Immediately** | Handled in serverless `/tmp` & cookies |
| **Admin CRUD (Destinations, Packages, Settings, CRM)** | **Available Immediately** | Modifies records in external MySQL |
| **Stock Imagery & Fallback Photography** | **Available Immediately** | Bundled static assets served via Edge CDN |
| **Admin Uploading New Local Package Images** | **Requires External Storage** | Because Vercel has a read-only filesystem, images uploaded via admin cannot be permanently saved to local disk. For production persistence, connect Cloudinary or AWS S3. Admins can currently use existing image filenames or external HTTPS image URLs. |

---

## 9. Promoting, Rolling Back, and Local XAMPP Use

### How to Roll Back a Deployment
1. Go to the **Deployments** tab in your Vercel Dashboard.
2. Locate any previous successful deployment.
3. Click the three dots (`...`) on the right and select **Promote to Production**.
4. The rollback takes effect globally in less than 5 seconds with zero downtime.

### How to Continue Using Local XAMPP
The deployment configuration maintains **100% backward compatibility** with local XAMPP:
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. In your local development environment (where `VERCEL` environment variable is not defined), `config/database.php` automatically falls back to:
   - `DB_HOST`: `127.0.0.1`
   - `DB_PORT`: `3306`
   - `DB_NAME`: `tourism_management`
   - `DB_USER`: `root`
   - `DB_PASS`: `""` (blank)
3. Your local development workflow remains completely unaffected.

---

*Patel Travels — Tourism Management System Vercel Deployment Guide Complete.*
