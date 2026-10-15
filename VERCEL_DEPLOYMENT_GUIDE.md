# VERCEL DEPLOYMENT GUIDE
## Patel Travels — Tourism Management System

> Complete, step-by-step guide to deploying the Patel Travels application to Vercel with an external cloud MySQL database.

---

## 1. Prerequisites Checklist

Before beginning, ensure you have:
1. **GitHub Account**: Access to [https://github.com/poojanpatel-tech/tourism-management-system.git](https://github.com/poojanpatel-tech/tourism-management-system.git).
2. **Vercel Account**: Free account at [https://vercel.com](https://vercel.com).
3. **Cloud MySQL Database**: An external MySQL 8-compatible database (see Section 3 for free provider options).
4. **Local Git Installed**: To review, commit, and push the verified local changes.

---

## 2. Preparing and Pushing Your Code to GitHub

The local codebase contains verified bug fixes, the `api/index.php` serverless router, cloud SSL database support, and deployment configuration that must be committed to your repository.

### Step 2.1 — Check Working Tree
Open your terminal in the project root:
```bash
cd C:\Users\dixit\OneDrive\Desktop\COLLEGE_PROJECT\POOJAN_PHP_WEBSITE
git status
```

### Step 2.2 — Stage Verified Changes
Stage all modified files, the new Vercel serverless router, and configuration:
```bash
git add README.md admin/ dashboard.php admin/enquiries/view.php admin/settings.php contact.php database.sql includes/ seed.sql migration_fix.sql assets/images/packages/.htaccess api/index.php vercel.json .vercelignore config/database.php
```

### Step 2.3 — Commit
```bash
git commit -m "feat: configure Vercel serverless PHP runtime and cloud MySQL compatibility"
```

### Step 2.4 — Push to GitHub
```bash
git push origin main
```
*Your GitHub repository is now synchronized with all verified fixes and deployment files.*

---

## 3. Creating an External Cloud MySQL Database (Free Options)

Because Vercel functions execute in a serverless cloud environment, they cannot connect to `127.0.0.1` (local XAMPP). You need a hosted MySQL database.

### Recommended Free Provider: **TiDB Cloud (Serverless)**
* **Why TiDB?**: 100% MySQL 8.0 wire-compatible, native SSL/TLS encryption, and a generous permanent free tier (5 GB storage, 50 Million Request Units/month). No credit card required to start.

#### Setup Steps:
1. Go to [https://tidbcloud.com](https://tidbcloud.com) and sign up for free (using GitHub or Google).
2. Click **Create Cluster** and choose **Serverless** (Free).
3. Select a region close to your target visitors (e.g. `ap-southeast-1` Mumbai/Singapore or `us-east-1`).
4. Click **Create**. Your cluster will initialize within 30 seconds.
5. In the cluster overview, click **Connect**:
   - Choose **Connect with: General**.
   - Copy down your connection parameters:
     - **Host**: e.g., `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`
     - **Port**: `4000`
     - **User**: e.g., `xxxxxx.root`
     - **Password**: Click **Reset Password** or **Generate Password**, copy the generated password.
     - **Database Name**: Default is `test`, or create `tourism_management`.

*(Alternative providers: **Aiven for MySQL** free trial, or **Railway MySQL** developer plan).*

---

## 4. Importing `database.sql` into Your Cloud Database

You must import the canonical schema and sample data once into your new cloud database before opening the application.

### Method A: Using MySQL Command-Line Client (Fastest)
From your local terminal, run the following command (substitute your cloud credentials):
```bash
mysql -h <YOUR_DB_HOST> -P <YOUR_DB_PORT> -u <YOUR_DB_USER> -p --ssl-mode=REQUIRED <YOUR_DB_NAME> < database.sql
```
*Example for TiDB Cloud:*
```bash
mysql -h gateway01.ap-southeast-1.prod.aws.tidbcloud.com -P 4000 -u 3hXXXX.root -p --ssl-mode=REQUIRED test < database.sql
```
Enter your cloud database password when prompted.

### Method B: Using a Graphical Tool (DBeaver, HeidiSQL, or MySQL Workbench)
1. Open DBeaver or MySQL Workbench.
2. Create a new MySQL connection with your cloud Host, Port, User, Password, and check **Use SSL**.
3. Open `database.sql` in the SQL editor.
4. Execute the script. All 14 tables and sample records will be imported.

---

## 5. Deploying Your Project on Vercel

### Step 5.1 — Import Repository to Vercel
1. Log in to [https://vercel.com](https://vercel.com).
2. From the Vercel Dashboard, click **Add New...** $\to$ **Project**.
3. Find your repository: `poojanpatel-tech/tourism-management-system`.
4. Click **Import**.

### Step 5.2 — Configure Project Settings
In the **Configure Project** screen:
- **Project Name**: `tourism-management-system` (or your preferred name)
- **Framework Preset**: Leave as **Other** (do NOT select Next.js, Vite, or React).
- **Root Directory**: Leave as `./` (project root).
- **Build and Output Settings**: Leave default/empty (our `vercel.json` configures the serverless PHP runtime automatically).

### Step 5.3 — Configure Environment Variables
Expand the **Environment Variables** section and add the following keys with your external database credentials:

| Environment Variable | Description | Example Value |
| :--- | :--- | :--- |
| `DB_HOST` | Hostname of your cloud database | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` |
| `DB_PORT` | Port of your cloud database | `4000` (or `3306`) |
| `DB_NAME` | Database name | `tourism_management` (or `test`) |
| `DB_USER` | Database username | `xxxxxx.root` |
| `DB_PASS` | Database password | `<YOUR_DATABASE_PASSWORD>` |
| `DB_SSL_MODE` | Enable SSL encryption for cloud DB | `REQUIRED` |

> [!IMPORTANT]
> Never put quotes (`"`) around values in Vercel's environment variable fields.

### Step 5.4 — Deploy
Click the **Deploy** button. Vercel will install the `vercel-php@0.9.4` runtime, bundle the application, configure Edge routing, and provide your deployment URL (e.g. `https://tourism-management-system-xxxx.vercel.app`).

---

## 6. Verifying Your Deployment

Once Vercel reports **Deployment Complete**, test the following routes:

1. **Public Homepage**:
   - URL: `https://<YOUR-PROJECT>.vercel.app/`
   - Verify: Hero banner loads, navigation works, luxury styling renders via CDN.
2. **Destinations**:
   - URL: `https://<YOUR-PROJECT>.vercel.app/destinations.php` (or `/destinations`)
   - Verify: Destination cards load from cloud database.
3. **Tour Packages & Filters**:
   - URL: `https://<YOUR-PROJECT>.vercel.app/tours.php`
   - Verify: Tour cards, pricing, and filters function cleanly.
4. **Package Details & Itinerary**:
   - URL: `https://<YOUR-PROJECT>.vercel.app/tour-details.php?id=1`
   - Verify: Detailed day-by-day itinerary and inclusions render.
5. **Contact Page**:
   - URL: `https://<YOUR-PROJECT>.vercel.app/contact.php`
   - Verify: Company phone and email load from the `settings` table.
6. **Admin Login**:
   - URL: `https://<YOUR-PROJECT>.vercel.app/auth/login.php`
   - Credentials:
     - **Username**: `admin`
     - **Password**: `admin123`
   - Verify: Successfully redirects to `admin/dashboard.php`.
7. **Admin Dashboard**:
   - URL: `https://<YOUR-PROJECT>.vercel.app/admin/dashboard.php`
   - Verify: Operational KPIs and Confirmed Booking Value render.

---

## 7. Troubleshooting & Common Issues

### Issue 1: "Database Connection Notice" Screen
- **Cause**: The application cannot reach your external MySQL database.
- **Diagnosis**:
  1. Go to Vercel Dashboard $\to$ Project $\to$ **Logs**.
  2. Inspect the function logs for `Database Connection Error: ...`.
  3. Verify that `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` match your cloud provider.
  4. Ensure `DB_SSL_MODE` is set to `REQUIRED` if using TiDB Cloud or Aiven.
  5. Check cloud firewall / IP whitelist settings (ensure your cloud database allows connections from all IPs: `0.0.0.0/0` because Vercel functions use dynamic IP addresses).

### Issue 2: Static Stylesheets / Images Not Loading (404)
- **Cause**: Routing rules intercepting assets.
- **Solution**: Check that `vercel.json` has the asset route configured first:
  ```json
  { "src": "/assets/(.*)", "dest": "/assets/$1" }
  ```
  This ensures static files are delivered by Vercel CDN directly.

### Issue 3: Admin Image Uploads Reset After Cold Start
- **Cause**: Vercel Serverless Functions have an ephemeral filesystem. Newly uploaded files in `assets/images/packages/` are wiped when function containers restart.
- **Solution**:
  - The stock packages already use bundled assets or external Unsplash CDN image URLs, which display permanently.
  - To support persistent user uploads in production, see Section 8 below.

---

## 8. Persistent Image Uploads (Production Recommendation)

In local XAMPP, uploaded images save to your local hard drive. On serverless Vercel, storage is ephemeral.

To enable permanent image uploads in production:
1. **Cloudinary (Free Tier - Recommended for College Projects)**:
   - Provides 25 GB free monthly bandwidth.
   - Images are uploaded directly to Cloudinary and return a permanent HTTPS URL stored in the MySQL `image` column.
2. **AWS S3 / Cloudflare R2**:
   - Standard S3 bucket for production file hosting.

*For college project demonstrations, using the bundled high-resolution package images in `assets/images/` or external image URLs in `database.sql` works reliably out of the box without extra cloud storage.*

---

## 9. Rollback & Redeployment

If you need to redeploy or roll back:
1. **Redeploy**: Push any commit to `main`, or click **Deployments** $\to$ **Redeploy** on Vercel.
2. **Rollback**: In the Vercel Dashboard under **Deployments**, find any previous successful deployment, click the three dots (`...`), and select **Promote to Production** for instant zero-downtime rollback.

---

**Patel Travels Deployment Guide Complete.**
