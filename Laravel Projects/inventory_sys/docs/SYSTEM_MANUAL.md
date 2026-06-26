# Inventory Management System (IMS) — User Manual

This guide explains how to use the system in plain language. It is written for branch staff, supervisors, and administrators who need to record stock, sell items, run reports, and manage day-to-day operations.

---

## 1. Getting started

### 1.1 Two ways to log in

| Panel | URL | Who uses it |
|-------|-----|-------------|
| **App panel (Branch operations)** | `/app` | Branch managers, cashiers, storekeepers working inside one branch |
| **Admin panel (System admin)** | `/admin` | Head office, super-admin, cross-branch setup |

After login to the **App panel**, you choose your **branch** (tenant). Everything you see is scoped to that branch unless your role allows more.

### 1.2 Basic navigation

- Use the **left sidebar** to open modules (Sales, Stock In, Reports, etc.).
- Many modules have **List** and **Create** sub-links.
- Use **View** on a row to see details and special actions (approve, post, print, reverse).
- Use **Edit** to change records that are still editable.
- Use the **trash icon** on list rows where delete is allowed.

### 1.3 Money and quantities

- Currency is **FCFA (XAF)** throughout the system.
- Item quantities are stored in **base units** (e.g. tablets, pieces). You can enter **pack quantities** on opening stock and sales orders when pack mode is enabled.

---

## 2. Setting up a branch (Admin / App)

Do this once per branch before daily operations.

1. **Branches** — create the branch (name, code, address).
2. **Departments** — optional subdivisions (e.g. Pharmacy, Shop floor).
3. **Users & roles** — assign users to branches and give permissions (Admin panel).
4. **Catalogue**
   - Item categories
   - Units of measure (UOM)
   - Packaging types (e.g. box of 10)
   - Items (name, cost, selling price, reorder level)
5. **Suppliers** and **Customers** (optional but recommended).

---

## 3. Bringing stock into the system

### 3.1 Opening stock

Use when you first load inventory or open a new branch.

1. Go to **Stock In → Opening Stock → Create**.
2. Add lines: item, batch number, expiry (if any), quantity.
3. Toggle **Enter as Packages** if you count in boxes/cartons; the system converts to base units.
4. Save — stock is posted to batches and branch totals immediately.

### 3.2 Purchase orders & goods received (GRN)

1. **Purchase Orders** — order from a supplier.
2. **Goods Received Notes (GRN)** — record what actually arrived; this increases stock.

### 3.3 Stock transfers

Move stock between branches or departments using **Stock Control → Stock Transfers** (request → approve → receive).

---

## 4. Selling items

### 4.1 Normal sales

1. **Sales → Sales Orders → Create**.
2. Add customer (or walk-in name).
3. Add lines: item, quantity, unit price.
4. Optional: enable **Pack Mode** and pick packaging type — pack qty and units per pack fill automatically when the item has a default packaging type.
5. Save — stock is deducted using FIFO batch logic.

### 4.2 Clearance (discounted) sales

Clearance stock is stock moved out of normal inventory at a reduced price (usually near expiry).

1. **Expiry → Flagged Items** — run clearance scan, approve items.
2. Approved items appear in **Clearance Stock**.
3. To sell clearance items, create a **Sales Order** and on each line select **Clearance Stock** (not the normal item picker alone).
4. The system sets the **clearance price** and **correct batch** automatically.
5. Clearance sales are tracked in **Reports → Clearance Activity Report**.

> Do **not** sell clearance from the clearance stock view directly — use sales orders.

---

## 5. Near-expiry and write-offs

### 5.1 Clearance workflow

1. **Clearance Rules** — define discount bands by days to expiry.
2. **Flagged Items** — items/batches identified for clearance; approve to move qty to clearance stock.
3. **Clearance Stock** — discounted stock ready to sell, donate, dispose, or **reverse back** to normal stock.

### 5.2 Donations & disposals

- **Donations** — give stock away (often from clearance).
- **Disposals** — destroy expired/damaged stock (often from clearance).

Both reduce stock and are logged for audit.

---

## 6. Stock control

### 6.1 Inventory counts

1. **Inventory Counts → Create** — lines are generated from current batches.
2. Count quantities on the floor; edit lines (including expiry dates if needed).
3. **Submit for Approval → Approve → Post** — posting updates real stock levels.
4. Use **Print Report** on the count view for a PDF snapshot.

### 6.2 Stock movements log

**Stock Movements** is a read-only history of every in/out (sales, GRN, clearance, adjustments, etc.).

---

## 7. Expenses

### 7.1 Recording expenses

1. **Expense Categories** — set up categories (e.g. Rent, Utilities). You can delete unused categories from the list (trash icon) if you have permission.
2. **Expenses → Create** — enter date, category, amount, payee, description.

### 7.2 Expenses in Profit & Loss

Expenses feed directly into **Reports → Profit & Loss**:

- **Revenue** = sales line totals in the date range  
- **COGS** = cost of goods sold on those sales  
- **Gross profit** = revenue − COGS  
- **Total expenses** = sum of expense records in the date range  
- **Net profit** = gross profit − total expenses  

Use **Reports → Expense Report** for a detailed list and category breakdown of the same expense data.

---

## 8. Reports

Open **Reports** in the sidebar (App or Admin panel).

| Report | Purpose |
|--------|---------|
| Sales Report | Revenue and profit by date, item, customer, or cashier |
| Stock Valuation | Value of stock on hand |
| Purchase Report | Purchase orders in a period |
| Profit & Loss | Revenue, COGS, expenses, net profit |
| Expiry Report | Batches nearing expiry |
| Low Stock Report | Items at or below reorder level |
| Clearance Activity | Sales, donations, disposals, reversals from clearance |
| Expense Report | Detailed expense lines and category totals |

### How to filter and export PDF

1. Set your filters (dates, branch, category, etc.).
2. Click **Filter Report** to refresh the on-screen table.
3. Click **Export PDF** — the PDF uses **the same filters currently shown on the form** (you do not need a separate step; the export reads your filter fields).

Super-admin users in the Admin panel should select a **branch** in the filter when exporting branch-specific reports.

---

## 9. Permissions (simple overview)

Your administrator assigns **roles** with permissions like:

- `view.items`, `create.sales-orders`, `approve.inventory-counts`
- `delete.clearance-manager` — reverse clearance stock
- `view.reports` — open report pages

If a button is missing, ask an admin to check your role in **Admin → Roles**.

---

## 10. Tips for daily use

- Always work inside the correct **branch** in the App panel.
- Use **batch numbers and expiry dates** on opening stock and GRNs for accurate FIFO and expiry reports.
- Post inventory counts only after physical counting is complete — **posting cannot be undone** from the UI.
- For mistakes on posted sales or GRNs, use the documented **delete with reversal** process (see `DELETE_AND_REVERSAL_GUIDE.md`).
- Check **Low Stock Report** regularly against reorder levels set on items.

---

## 11. Getting help

- **Audit Logs** and **Deletion Logs** (Admin) show who changed or deleted records.
- Stock history is in **Stock Movements**.
- Clearance history is in **Clearance Activity Report**.

For technical setup (server, database, backups), contact your system administrator.
