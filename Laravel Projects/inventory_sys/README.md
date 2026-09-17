# Enterprise Multi-Branch Inventory & POS System

A robust, multi-tenant inventory management and Point of Sale (POS) system built with **Laravel**, **Filament v3**, and **Livewire**. Designed to handle complex retail operations across multiple branches with strict role-based access control, real-time financial reporting, and comprehensive stock movement tracking.

## 🚀 Key Features

### 🏢 Multi-Tenant Architecture
* **Admin Panel (`/admin`)**: For Superadmins to manage global settings, oversee all branches, view consolidated reports, and administer system backups.
* **App Panel (`/app`)**: For Branch Managers and Cashiers to manage localized daily operations, isolated strictly to their assigned branch.

### 📦 Advanced Inventory Management
* **Batch & Expiry Tracking**: Track specific item batches from receiving to sale. Warns on expiring stock.
* **Smart GRN (Goods Received Notes)**: Intelligent conversion of Purchase Orders into stock with "Pack Mode" logic (receive bulk packs, automatically unpack into sellable singular units).
* **Inventory Counts (Stocktakes)**: Perform blind counts, auto-calculate variances, and enforce manager approval workflows before posting stock adjustments.
* **Clearance Module**: Dedicated tracking for damaged, expired, or lost stock with direct integration into Profit & Loss shrinkage.

### 💰 Sales & Reporting
* **Point of Sale (POS)**: Streamlined sales order processing.
* **Comprehensive Analytics**: 11+ dynamic reports including:
  * Profit & Loss (Revenue vs COGS vs Expenses)
  * Top Selling Products
  * Cashier Performance
  * Sales Price Audits
* **Exporting Engine**: Native support for exporting massive reports and tables to **PDF**, **Excel (.xlsx)**, and **CSV**.

### 🔒 Security & Maintenance
* **Administer Backups**: Built-in GUI to trigger instant MySQL database dumps, securely storing them locally in `.zip` format. 
* **Role-Based Access Control (RBAC)**: Strict permission boundaries (e.g., Cashiers cannot approve inventory counts or access P&L reports).

---

## 💻 Tech Stack

* **Backend**: PHP 8.4, Laravel 13.x
* **Frontend**: Filament PHP v3, Livewire 3, Tailwind CSS, Alpine.js
* **Database**: MySQL
* **PDF Engine**: `barryvdh/laravel-dompdf`
* **Excel Engine**: `phpoffice/phpspreadsheet`
* **Backups**: `spatie/laravel-backup`

---

## 🛠️ Installation & Setup

Follow these steps to get the application running on your local machine.

### 1. Prerequisites
* PHP >= 8.2 (8.4 recommended)
* Composer
* MySQL Server
* Node.js & NPM (optional, for asset compiling)

### 2. Clone and Configure
Clone the repository and install the PHP dependencies:
```bash
composer install
```

Copy the environment file:
```bash
cp .env.example .env
```

Generate the application key:
```bash
php artisan key:generate
```

### 3. Database Setup
Create a new MySQL database (e.g., `inventoryss`) and update your `.env` file with the connection details:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventoryss
DB_USERNAME=root
DB_PASSWORD=
```

Run the database migrations and seed the initial roles, permissions, and test data:
```bash
php artisan migrate --seed
```

### 4. Storage Link
Link the storage directory to make local backups and uploads accessible:
```bash
php artisan storage:link
```

### 5. Start the Server
Start the local Laravel development server:
```bash
php artisan serve
```

---

## 🚦 Usage Guide

Once the server is running (usually at `http://localhost:8000` or `http://inventory_sys.test` via Herd/Valet):

1. **Superadmin Login**
   * Navigate to `http://localhost:8000/admin`
   * Login with your seeded superadmin credentials.
2. **Branch Manager Login**
   * Navigate to `http://localhost:8000/app`
   * Login with branch manager credentials to access the localized store dashboard.

### 🗄️ Database Backups
To trigger a manual database backup via the command line (if you don't want to use the GUI):
```bash
php artisan backup:run --only-db
```
Backups are securely stored in `storage/app/private/backups/`.

---

## ⚠️ Important Configuration Notes
* **Queue Connection**: The `.env` file must have `QUEUE_CONNECTION=sync` for table exports (like the massive Items list) to process immediately without a background worker.
* **Environment Name**: Ensure `APP_NAME` is set correctly in `.env` as it dictates certain system naming conventions.
