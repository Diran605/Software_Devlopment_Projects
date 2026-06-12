# Inventory Management System — UI, Navigation, Views & Reports Spec

> **This is the third document in the spec series.**
> Read the original MVP spec and the Addendum first.
> This document defines every screen, sidebar structure, view layout, KPI widget,
> and report for the entire system. The agent must implement all of it exactly as described.

---

## B1. Visual Theme

Both panels use a **dark theme** throughout — dark sidebar, dark content area.

### Filament Theme Configuration

In both `AdminPanelProvider` and `AppPanelProvider`, add:

```php
->darkMode(DarkModeConfig::ForceEnabled)
->colors([
    'primary'   => Color::Blue,
    'gray'      => Color::Zinc,
    'info'      => Color::Sky,
    'success'   => Color::Emerald,
    'warning'   => Color::Amber,
    'danger'    => Color::Rose,
])
->font('Inter')
->brandLogo(null)           // replace with shop logo asset if available
->favicon(null)
```

### Status Badge Colour Map (used across all resources)

Apply these consistently everywhere a status column appears:

| Status | Filament Colour |
|---|---|
| `draft` | gray |
| `issued` / `pending_approval` | info |
| `approved` / `in_transit` | warning |
| `fully_received` / `received` / `posted` | success |
| `partially_received` | warning |
| `cancelled` / `rejected` | danger |
| `active` / `is_active = true` | success |
| `inactive` / `is_active = false` | gray |
| `low_margin` | warning |
| `negative_margin` | danger |

---

## B2. Admin Panel — Sidebar Navigation

Panel: `/admin`
Visible to: `super-admin` only

The admin panel sees **everything across all branches** — it mirrors the App Panel
sidebar completely but with no branch scoping. Every list view shows records from
all branches, with a Branch column and branch filter available everywhere.

```
ADMIN PANEL
│
├── 🏠  Dashboard
│
├── ─── SYSTEM ───────────────────────────
├── 🏢  Branches
│       ├── All Branches              (list)
│       ├── Create Branch             (form)
│
├── 🏬  Departments
│       ├── All Departments           (list — all branches)
│       ├── Create Department         (form)
│
├── 👤  Users
│       ├── All Users                 (list — all branches)
│       ├── Create User               (form)
│
├── 🔐  Roles & Permissions
│       ├── All Roles                 (list)
│       ├── Create Role               (form)
│
├── ─── CATALOGUE ────────────────────────
├── 📦  Items
│       ├── All Items                 (list — all branches)
│       ├── Create Item               (form)
│
├── 🗂️  Item Categories
│       ├── All Categories            (list — all branches)
│       ├── Create Category           (form)
│
├── 📏  Units of Measure
│       ├── All UOMs                  (list — all branches)
│       ├── Create UOM                (form)
│
├── 📦  Packaging Types
│       ├── All Packaging Types       (list — all branches)
│       ├── Create Packaging Type     (form)
│
├── ─── PARTIES ──────────────────────────
├── 🚚  Suppliers
│       ├── All Suppliers             (list — all branches)
│       ├── Create Supplier           (form)
│
├── 👥  Customers
│       ├── All Customers             (list — all branches)
│       ├── Create Customer           (form)
│
├── ─── PROCUREMENT ──────────────────────
├── 🛒  Purchase Orders
│       ├── All Purchase Orders       (list — all branches)
│       ├── Create PO                 (form)
│
├── ─── STOCK IN ─────────────────────────
├── 📂  Opening Stock
│       ├── All Entries               (list — all branches)
│       ├── Create Entry              (form)
│
├── 📥  Goods Received Notes
│       ├── All GRNs                  (list — all branches)
│       ├── Create GRN                (form)
│
├── ─── SALES ────────────────────────────
├── 🧾  Sales Orders
│       ├── All Sales                 (list — all branches)
│       ├── Create Sale               (form)
│
├── ─── STOCK CONTROL ────────────────────
├── 🔄  Stock Transfers
│       ├── All Transfers             (list — all branches)
│       ├── Create Transfer           (form)
│
├── 📊  Stock Movements               (list — all branches, read-only)
│
├── ─── REPORTS ──────────────────────────
├── 📈  Reports
│       ├── Sales Report
│       ├── Stock Valuation
│       ├── Purchase Report
│       └── Profit & Loss
│
├── ─── LOGS ─────────────────────────────
├── 📋  Audit Logs                    (list — all branches, read-only)
├── 🗑️  Deletion Logs                 (list — all branches, read-only)
```

### Admin Panel — Key Difference From App Panel

In the Admin Panel, every resource:
- Has no automatic branch scoping (no multi-tenancy)
- Always shows a **Branch** column in list views
- Always has a **Branch filter** in the filter bar
- The Create form always includes a **Branch selector** as the first field
- Reports have an additional **Branch** filter (supports "All Branches" option)

---

## B3. App Panel — Sidebar Navigation

Panel: `/app`
Visible to: branch staff (branch-manager, inventory-manager, cashier, auditor)
All navigation items respect Spatie permissions — hidden if user lacks `view.{module}`

```
APP PANEL
│
├── 🏠  Dashboard
│
├── ─── CATALOGUE ────────────────────────
├── 📦  Items
│       ├── All Items          (list — view only for cashier)
│       ├── Create Item        (form — hidden for cashier/auditor)
│
├── 🗂️  Categories
│       ├── All Categories     (list)
│       ├── Create Category    (form)
│
├── 📏  Units of Measure
│       ├── All UOMs           (list)
│       ├── Create UOM         (form)
│
├── 📦  Packaging Types
│       ├── All Packaging      (list)
│       ├── Create Packaging   (form)
│
├── ─── PARTIES ──────────────────────────
├── 🚚  Suppliers
│       ├── All Suppliers      (list)
│       ├── Create Supplier    (form)
│
├── 👥  Customers
│       ├── All Customers      (list)
│       ├── Create Customer    (form)
│
├── ─── PROCUREMENT ──────────────────────
├── 🛒  Purchase Orders
│       ├── All Purchase Orders (list)
│       ├── Create PO           (form)
│
├── ─── STOCK IN ─────────────────────────
├── 📂  Opening Stock
│       ├── All Entries        (list)
│       ├── Create Entry       (form)
│
├── 📥  Goods Received Notes
│       ├── All GRNs           (list)
│       ├── Create GRN         (form)
│
├── ─── SALES ────────────────────────────
├── 🧾  Sales Orders
│       ├── All Sales          (list)
│       ├── Create Sale        (form)
│
├── ─── STOCK CONTROL ────────────────────
├── 🔄  Stock Transfers
│       ├── All Transfers      (list)
│       ├── Create Transfer    (form)
│
├── 📊  Stock Movements        (list, read-only)
│
├── ─── REPORTS ──────────────────────────
├── 📈  Reports
│       ├── Sales Report
│       ├── Stock Valuation
│       ├── Purchase Report
│       └── Profit & Loss
│
├── ─── LOGS ─────────────────────────────
├── 📋  Audit Logs             (list, read-only)
├── 🗑️  Deletion Logs          (list, read-only)
```

---

## B4. Dashboard — App Panel

The dashboard is the first screen after login. It is a **custom Filament Page** with widgets arranged in a grid.

### Layout (top to bottom)

```
┌─────────────────────────────────────────────────────────┐
│  Period Selector: [Today] [This Week] [This Month] [Custom Range]  │
├──────────────┬──────────────┬──────────────┬────────────┤
│  Total       │  Total       │  Gross       │  Avg Order │
│  Revenue     │  Orders      │  Profit      │  Value     │
│  FCFA x,xxx  │  xx orders   │  FCFA x,xxx  │  FCFA xxx  │
├──────────────┴──────────────┼─────────────────────────  │
│  Stock Summary               │  Low Stock Alerts         │
│  ─────────────────────────   │  ───────────────────────  │
│  Total SKUs:      xx         │  Item     Qty  Reorder    │
│  Total Units:     xxx        │  [name]   0    10   🔴    │
│  Total Value:     FCFA x,xxx │  [name]   3    10   🟡    │
│  Items Below Reorder: xx     │  [view all →]             │
├──────────────────────────────┴───────────────────────────┤
│  Top Selling Items (by period)                           │
│  ─────────────────────────────────────────────────────── │
│  #  Item Name       Category    Qty Sold   Revenue       │
│  1  [name]          Provisions  xx         FCFA x,xxx    │
│  2  ...                                                  │
├──────────────────────────────────────────────────────────┤
│  Recent Transactions (last 10 sales)                     │
│  ─────────────────────────────────────────────────────── │
│  Order #     Customer    Items  Total        Served By   │
│  SO-HQ-...   Walk-in     3      FCFA 5,500   John        │
│  SO-HQ-...   Mary Fon    1      FCFA 1,200   Jane        │
└──────────────────────────────────────────────────────────┘
```

### KPI Card Definitions

**Total Revenue**
- Value: `SalesOrder::sum('grand_total')` for period
- Comparison: show % change vs previous equivalent period
- Colour: primary (blue)
- Icon: `heroicon-o-banknotes`

**Total Orders**
- Value: `SalesOrder::count()` for period
- Comparison: % change vs previous period
- Colour: info (sky)
- Icon: `heroicon-o-shopping-cart`

**Gross Profit**
- Value: `SalesOrder::sum('gross_profit')` for period
- Comparison: % change vs previous period
- Colour: success if positive, danger if negative
- Icon: `heroicon-o-arrow-trending-up`

**Avg Order Value**
- Value: `grand_total / order_count` for period
- Colour: warning (amber)
- Icon: `heroicon-o-calculator`

### Period Selector Behaviour

The period selector is a toggle at the top of the dashboard. Selecting a period re-fetches all widget data via Livewire. Default is **Today**.

| Selection | Date Range |
|---|---|
| Today | `whereDate('sold_at', today())` |
| This Week | `whereBetween('sold_at', [startOfWeek, endOfWeek])` |
| This Month | `whereBetween('sold_at', [startOfMonth, endOfMonth])` |
| Custom Range | Date picker — from/to |

### Stock Summary Widget

Static (not period-filtered). Always shows current state:
- **Total SKUs**: `Item::where('is_active', true)->count()`
- **Total Units on Hand**: `ItemStockLevel::sum('qty_on_hand')`
- **Total Stock Value**: `SUM(qty_on_hand × unit_cost)` from `item_stock_levels`
- **Items Below Reorder**: `ItemStockLevel::whereColumn('qty_on_hand', '<=', 'reorder_level')->count()`

### Low Stock Alert Widget

Table showing items where `qty_on_hand <= reorder_level`, ordered by `qty_on_hand ASC`.
Columns: Item Name, Category, Qty On Hand, Reorder Level, Status badge.
Status badge: 🔴 `Out of Stock` if qty = 0, 🟡 `Low` if qty > 0 but <= reorder_level.
Max 5 rows shown, with a "View All" link to the Stock Movements page.

### Top Selling Items Widget

Table, period-filtered. Joins `sales_order_lines` → `items`.
Columns: Rank (#), Item Name, Category, Qty Sold, Revenue (sum of line_total).
Max 5 rows. Sorted by Revenue DESC.

### Recent Transactions Widget

Last 10 `sales_orders` for the current branch, ordered by `sold_at DESC`.
Columns: Order Number (clickable → view record), Customer, No. of Items, Grand Total, Served By, Time.

---

## B5. Dashboard — Admin Panel

Simpler — system-wide overview only.

```
┌──────────────┬──────────────┬──────────────┬────────────┐
│  Total       │  Total       │  Total       │  Total     │
│  Branches    │  Users       │  Items       │  Sales     │
│  x branches  │  xx users    │  xxx items   │  Today     │
├──────────────┴──────────────┴──────────────┴────────────┤
│  Branch Performance Table (today)                        │
│  ─────────────────────────────────────────────────────── │
│  Branch     Revenue      Orders    Top Item              │
│  HQ         FCFA xx,xxx  xx        [item name]           │
│  Branch 2   FCFA x,xxx   x         [item name]           │
├──────────────────────────────────────────────────────────┤
│  Recent Audit Activity (last 10 audit_logs entries)      │
│  ─────────────────────────────────────────────────────── │
│  Time    User    Event     Record       Branch           │
│  09:12   John    created   SO-HQ-0042   HQ               │
└──────────────────────────────────────────────────────────┘
```

---

## B6. Module Views — Detailed Screen Specs

Each module has three view types: **List**, **Create/Edit Form**, and **View (Detail)**.

---

### Items

**List View**
Columns: SKU, Name, Category, UOM, Selling Price, Cost Price, Qty On Hand (from item_stock_levels), Reorder Level, Status badge, Actions.
Filters: Category (select), Is Active (toggle), Low Stock (toggle — filters where qty_on_hand <= reorder_level).
Search: name, SKU.
Bulk actions: Activate, Deactivate.
Row actions: View, Edit, Delete.
Default sort: name ASC.

**Create / Edit Form**
Two-column layout:
```
Left column:
  - SKU (auto-generated suggestion, editable)
  - Name
  - Description (textarea)
  - Category (select + create inline)
  - Unit of Measure (select + create inline)

Right column:
  - Cost Price
  - Min Selling Price
  - Selling Price
  - Reorder Level
  - Reorder Quantity
  - Is Packaged (toggle)
    → if true: show Packaging Type (select)
  - Is Active (toggle)
```

**View (Detail) Page**
Header: SKU, Name, Category, Status badge.
Tabs:
- **Overview** — all item fields in read-only grid
- **Stock Levels** — table of item_stock_levels per branch/department: Branch, Department, Qty On Hand, Qty Reserved, Unit Cost
- **Batch Inventory** — table of active batches: Batch #, Expiry Date, Qty Received, Qty Remaining, Unit Cost, Source, Received At. Ordered FIFO (expiry ASC).
- **Stock Movements** — paginated list of all movements for this item: Date, Type badge, Qty In, Qty Out, Before, After, Reference, Recorded By.

---

### Item Categories / Units of Measure / Packaging Types

**List View**
Simple table. Columns: Name, Description (truncated), Branch, Actions.
Search: name.
Row actions: Edit, Delete.

**Create / Edit Form**
Single column: Name, Description, plus type-specific fields (abbreviation for UOM, units_per_pack + base_uom for Packaging Types).

---

### Suppliers

**List View**
Columns: Code, Name, Contact Person, Phone, Email, Payment Terms, Is Active badge, Actions.
Search: name, code, phone.
Filters: Is Active toggle.
Row actions: View, Edit, Delete.

**Create / Edit Form**
Two-column layout:
```
Left: Name, Code, Contact Person, Phone, Email
Right: Address (textarea), Tax ID, Payment Terms, Notes (textarea), Is Active
```

**View (Detail) Page**
Header: Name, Code, Status badge.
Tabs:
- **Info** — all supplier fields read-only
- **Purchase Orders** — list of all POs for this supplier: PO Number, Date, Status badge, Total Amount
- **GRNs** — list of all GRNs for this supplier: GRN Number, Date, Total Qty, Total Cost

---

### Customers

**List View**
Columns: Name, Phone, Email, Is Active badge, Actions.
Search: name, phone.
Row actions: View, Edit, Delete.

**Create / Edit Form**
Single column: Name, Phone, Email, Address, Notes, Is Active.

**View (Detail) Page**
Header: Name, Status badge.
Tabs:
- **Info** — customer fields read-only
- **Purchase History** — list of sales_orders for this customer: Order #, Date, Grand Total, Served By

---

### Purchase Orders

**List View**
Columns: PO Number, Supplier, Created By, Ordered At, Expected Delivery, Total Amount, Status badge, Actions.
Search: po_number, supplier name.
Filters: Status (multiselect), Supplier (select), Date Range.
Row actions: View, Edit (draft only), Delete (draft only).
Bulk actions: none.

**Create Form**
Header section:
```
Supplier (searchable select) | Expected Delivery Date
Notes (textarea, full width)
```
Lines section (repeater):
```
Item (searchable select) | Qty Ordered | Unit Cost | Line Total (auto-calc, read-only)
```
Footer: Total Amount (auto-calc, read-only).

**View (Detail) Page**
Header: PO Number, Supplier, Status badge, Created By, Ordered At.
Action buttons (shown based on status + permission):
- **Approve** (draft → issued) — requires `approve.purchase-orders`
- **Cancel** (draft/issued → cancelled) — requires `cancel.purchase-orders`
- **Receive Stock** (issued/partially_received → opens Create GRN prefilled) — requires `create.goods-received-notes`

Lines table: Item, Qty Ordered, Qty Received (progress bar), Unit Cost, Line Total, Notes.
Footer: Total Amount.
Related GRNs section: list of GRNs linked to this PO.

---

### Opening Stock

**List View**
Columns: Entry Number, Branch, Department, Posted By, Posted At, Line Count, Actions.
Search: entry_number.
Filters: Date Range, Branch.
Row actions: View, Edit, Delete.

**Create / Edit Form**
Header:
```
Branch (select) | Department (select, optional)
Posted At (datetime) | Notes (textarea)
```
Lines (repeater):
```
Item (searchable select) | Batch Number | Expiry Date (optional) | Qty On Hand | Unit Cost
```
Edit guard: if any line has `is_consumed = true`, show a warning banner:
`"Some lines have been consumed and cannot be edited. Only unconsumed lines are shown."`
Only unconsumed lines appear in the repeater on edit.

**View (Detail) Page**
Header: Entry Number, Branch, Department, Posted By, Posted At.
Lines table: Item, Batch #, Expiry Date, Qty On Hand, Unit Cost, Line Total, Is Consumed badge.

---

### Goods Received Notes

**List View**
Columns: GRN Number, Supplier, PO Number (nullable), Received By, Received At, Total Qty, Total Cost, Actions.
Search: grn_number, supplier name.
Filters: Supplier, Date Range, Has PO (toggle).
Row actions: View, Delete.
No Edit action — delete and redo.

**Create Form**
Header:
```
Supplier (searchable select)        | Received At (datetime)
Purchase Order (optional select,    | Supplier Reference No
  filtered by selected supplier)    | Department (optional)
Notes (textarea, full width)
```
If PO selected: pre-fill lines from PO. User adjusts actuals.
Lines (repeater):
```
Item (searchable select)
Entry Mode (toggle: Unit / Pack)
  → if Pack: Packaging Type | Pack Quantity | Units Per Pack
  → Qty Received (auto-calc if pack, manual if unit)
Batch Number (required)
Expiry Date (optional)
Unit Cost | Line Total (auto-calc)
```
Footer: Total Qty, Total Cost (both auto-calc).

**View (Detail) Page**
Header: GRN Number, Supplier, PO link, Received By, Received At.
Lines table: Item, Entry Mode, Qty Received, Batch #, Expiry Date, Unit Cost, Line Total.
Footer: Total Qty, Total Cost.
Delete action at top right: requires reason input (min 10 chars) → confirmation modal.

---

### Sales Orders

**List View**
Columns: Order Number, Customer, Items Count, Grand Total, Gross Profit, Amount Tendered, Served By, Sold At, Actions.
Search: order_number, customer name.
Filters: Served By (select), Date Range, Customer (select).
Row actions: View, Edit, Delete, Print Receipt.
Default sort: sold_at DESC.

**Create / Edit Form**
Header:
```
Customer (searchable select — optional)  | Customer Name (text — walk-in, shown if no customer selected)
Sold At (datetime, default = now)        | Department (optional)
Notes (textarea, full width)
```
Lines (repeater):
```
Item (searchable select — shows qty_on_hand hint below field)
Entry Mode (toggle: Unit / Pack)
  → if Pack: Packaging Type | Pack Quantity | Units Per Pack
Qty Sold | Unit Price (defaults to item.selling_price, editable)
Line Total (auto-calc) | Gross Profit (auto-calc)
Margin warning badge: 🟡 Low Margin / 🔴 Negative Margin (shown inline)
```
Footer (live-updating):
```
Subtotal      FCFA x,xxx
Discount      FCFA -xxx     (editable)
Grand Total   FCFA x,xxx
Amount Tendered FCFA x,xxx  (editable)
```
Edit behaviour:
- Changing qty → service reverses and reallocates FIFO automatically
- Changing unit_price → updates financials only, no stock impact
- Cannot change the item on a line — show "To change item, delete this line and add a new one"
- Adding a new line to an existing order is allowed
- Removing a line from an existing order is allowed (reverses stock)

**View (Detail) Page**
Header: Order Number, Customer, Sold At, Served By, Status badge.
Action buttons: Edit, Delete (requires reason), Print Receipt.
Lines table: Item, Batch Allocated (from sales_stock_allocations — shows batch # and expiry), Qty Sold, Unit Price, Unit Cost, Line Total, Gross Profit, Margin badge.
Footer: Subtotal, Discount, Grand Total, COGS, Gross Profit, Amount Tendered.

---

### Stock Transfers

**List View**
Columns: Transfer Number, Type badge, From, To, Requested By, Status badge, Transferred At, Actions.
Search: transfer_number.
Filters: Status, Transfer Type, Date Range.
Row actions: View, Delete (draft only).

**Create Form**
```
Transfer Type (select: Inter-Department / Inter-Branch)
From Branch (select) | From Department (select, optional)
To Branch (select)   | To Department (select, optional)
Notes (textarea)
```
Lines (repeater):
```
Item (searchable select)
Batch (select — filtered by item + from branch, shows: batch_number | expiry_date | qty_remaining)
Qty Requested | Unit Cost (auto-filled from batch, read-only)
Batch Number (auto-filled) | Expiry Date (auto-filled)
```

**View (Detail) Page**
Header: Transfer Number, Type, From → To, Status badge, Requested By, Approved By.
Action buttons (based on status + permission):
- **Submit for Approval** (draft → pending_approval)
- **Approve** (pending_approval → approved) — requires `approve.stock-transfers`
- **Dispatch** (approved → in_transit) — requires `approve.stock-transfers`
- **Receive** (in_transit → received) — requires `receive.stock-transfers`
  → Receive opens a modal per line: confirm qty_received (may differ from qty_transferred)
- **Cancel** (draft/pending_approval → cancelled)

Lines table: Item, Batch #, Expiry, Qty Requested, Qty Transferred, Qty Received, Unit Cost.

---

### Stock Movements

**List View (read-only)**
Columns: Date, Item, Batch #, Expiry Date, Movement Type badge, Qty In, Qty Out, Before, After, Unit Cost, Reference (clickable link to source document), Recorded By.
Search: item name, batch number.
Filters: Movement Type (multiselect), Item (select), Date Range, Branch, Department.
No create/edit/delete actions — fully read-only.
Default sort: moved_at DESC.

---

### Audit Logs

**List View (read-only)**
Columns: Date, User, Event badge, Record Type, Record ID, Branch.
Filters: Event (multiselect), User (select), Record Type (select), Date Range.
Row action: View → shows old_values vs new_values in a side-by-side diff modal.
No create/edit/delete.

---

### Deletion Logs

**List View (read-only)**
Columns: Deleted At, Deleted By, Record Type, Record Number, Reason (truncated).
Filters: Record Type, Deleted By, Date Range.
Row action: View → shows full snapshot JSON in a formatted read-only modal, plus stock_reversal details.
No create/edit/delete.

---

### Roles & Permissions (Admin Panel only)

**List View**
Columns: Role Name, Permission Count, Users Count, Actions.
Row actions: View, Edit, Delete (only custom roles — built-in roles cannot be deleted).

**Create / Edit Form**
```
Role Name (text)
Permissions (grouped checkbox list, grouped by module):
  FOUNDATION
    □ view.branches  □ create.branches  □ edit.branches  □ delete.branches
    □ view.users     □ create.users     ...
  CATALOGUE
    □ view.items  □ create.items  ...
  ... etc per group
```

**View (Detail) Page**
Role name + list of assigned permissions grouped by module.
Users table: list of users assigned this role.

---

### Users (Admin Panel only)

**List View**
Columns: Name, Email, Branch, Department, Role(s), Is Active badge, Actions.
Search: name, email.
Filters: Branch, Role, Is Active.
Row actions: View, Edit, Delete.

**Create / Edit Form**
```
Name | Email
Password (create only) | Confirm Password
Branch (select, optional for super-admin)
Department (select, filtered by branch)
Role (select from seeded roles)
Is Active (toggle)
Branches Access (multi-select — which branches can this user access in App Panel)
```

---

## B7. Reports Module

Reports are **custom Filament Pages** (not resources). Each report has:
1. A filter bar at the top
2. A data table or summary grid
3. An **Export PDF** button that generates a print-ready PDF

All reports live under `app/Filament/App/Pages/Reports/`.
Navigation group: **Reports**.

---

### Report 1 — Sales Report

**Page:** `SalesReportPage.php`
**Route:** `/app/{tenant}/reports/sales`
**Permission:** `view.reports`

**Filter Bar:**
```
Date Range (from/to)  |  Group By (select: Date / Item / Customer / Cashier)
Branch (admin only)   |  [Generate Report] button  |  [Export PDF] button
```

**Group By: Date**
One row per day.
| Date | Orders Count | Revenue (FCFA) | COGS (FCFA) | Gross Profit (FCFA) | Top Item |
|---|---|---|---|---|---|
| 01/06/2026 | 12 | 145,000 | 98,000 | 47,000 | Ovaltine |
Footer row: Totals across all columns.

**Group By: Item**
One row per item sold in the period.
| Item | Category | Qty Sold | Revenue (FCFA) | COGS (FCFA) | Gross Profit (FCFA) | Margin % |
|---|---|---|---|---|---|---|

**Group By: Customer**
One row per customer (including Walk-in as a single grouped row).
| Customer | Orders Count | Revenue (FCFA) | COGS (FCFA) | Gross Profit (FCFA) |
|---|---|---|---|---|

**Group By: Cashier (Served By)**
One row per user who served sales.
| Cashier | Orders Count | Revenue (FCFA) | COGS (FCFA) | Gross Profit (FCFA) | Avg Order Value |
|---|---|---|---|---|---|

---

### Report 2 — Stock Valuation Report

**Page:** `StockValuationReportPage.php`
**Route:** `/app/{tenant}/reports/stock-valuation`
**Permission:** `view.reports`

**Filter Bar:**
```
Category (select, optional)  |  Branch  |  Department (optional)
Include Zero Stock (toggle)  |  [Generate]  |  [Export PDF]
```

**Summary Table (per item):**
| Item | SKU | Category | UOM | Qty On Hand | Unit Cost | Total Value (FCFA) |
|---|---|---|---|---|---|---|
Footer: Grand Total Value.

**Expandable Row (batch detail):**
When a row is expanded, show the batches for that item:
| Batch # | Expiry Date | Qty Remaining | Unit Cost | Batch Value | Source | Received At |
|---|---|---|---|---|---|---|
Batches ordered FIFO (expiry ASC).

---

### Report 3 — Purchase Report

**Page:** `PurchaseReportPage.php`
**Route:** `/app/{tenant}/reports/purchases`
**Permission:** `view.reports`

**Filter Bar:**
```
Date Range  |  Supplier (select)  |  Status (multiselect)  |  [Generate]  |  [Export PDF]
```

**Main Table:**
| PO Number | Supplier | Ordered At | Expected Delivery | Items Ordered (count) | Items Received (count) | Total Cost (FCFA) | Status badge |
|---|---|---|---|---|---|---|---|

**Expandable Row (PO lines):**
| Item | Qty Ordered | Qty Received | Unit Cost | Line Total |
|---|---|---|---|---|

Footer: Total Cost across all POs in period.

---

### Report 4 — Profit & Loss Report

**Page:** `ProfitLossReportPage.php`
**Route:** `/app/{tenant}/reports/profit-loss`
**Permission:** `view.reports`

**Filter Bar:**
```
Date Range (from/to)  |  Branch  |  [Generate]  |  [Export PDF]
```

**Layout — Summary Cards at top:**
```
┌────────────────┬────────────────┬────────────────┐
│  Total Revenue │  Total COGS    │  Gross Profit  │
│  FCFA x,xxx    │  FCFA x,xxx    │  FCFA x,xxx    │
│                │                │  Margin: xx%   │
└────────────────┴────────────────┴────────────────┘
```

**Breakdown Table (by category):**
| Category | Revenue (FCFA) | COGS (FCFA) | Gross Profit (FCFA) | Margin % |
|---|---|---|---|---|
| Provisions | 200,000 | 140,000 | 60,000 | 30% |
| Wine | 80,000 | 55,000 | 25,000 | 31.25% |
| **Total** | **280,000** | **195,000** | **85,000** | **30.4%** |

**Daily Trend Table (below):**
| Date | Revenue | COGS | Gross Profit | Orders |
|---|---|---|---|---|
One row per day in the selected range. Sorted date ASC.

---

### Report 5 — Expiry Report (Phase 2 — do not build now)

This report is excluded from MVP. Seed the permission `view.reports` now so it works when added.

---

## B8. PDF Export — Layout Rules

All exported PDFs share the same layout structure. Use a **Blade view** for each report PDF, rendered via a PHP PDF library (use `barryvdh/laravel-dompdf`).

```bash
composer require barryvdh/laravel-dompdf
```

### PDF Page Layout (all reports)

```
┌─────────────────────────────────────────────────────┐
│  [Branch Name]                     [Report Title]   │
│  [Branch Address]         Generated: DD/MM/YYYY HH:MM│
│  ─────────────────────────────────────────────────── │
│  Filter Applied: From: xx/xx/xxxx  To: xx/xx/xxxx   │
│  ─────────────────────────────────────────────────── │
│                                                      │
│  [Report Table — full width, borders]               │
│                                                      │
│  ─────────────────────────────────────────────────── │
│  [Footer totals row if applicable]                  │
│  ─────────────────────────────────────────────────── │
│  Page 1 of N                    [Branch Code]        │
└─────────────────────────────────────────────────────┘
```

### PDF Blade Views Location
```
resources/views/reports/
  sales.blade.php
  stock-valuation.blade.php
  purchases.blade.php
  profit-loss.blade.php
```

### PDF Controller
Create `app/Http/Controllers/ReportPdfController.php` with one method per report:
```php
public function sales(Request $request) { ... }
public function stockValuation(Request $request) { ... }
public function purchases(Request $request) { ... }
public function profitLoss(Request $request) { ... }
```

Each method:
1. Validates the filters from the request
2. Runs the same query as the on-screen report
3. Returns `PDF::loadView('reports.{name}', compact('data', 'filters', 'branch'))->download('{report}-{date}.pdf')`

Add routes to `web.php`:
```php
Route::prefix('reports')->middleware(['auth'])->group(function () {
    Route::get('/sales/pdf',             [ReportPdfController::class, 'sales'])->name('reports.sales.pdf');
    Route::get('/stock-valuation/pdf',   [ReportPdfController::class, 'stockValuation'])->name('reports.stock-valuation.pdf');
    Route::get('/purchases/pdf',         [ReportPdfController::class, 'purchases'])->name('reports.purchases.pdf');
    Route::get('/profit-loss/pdf',       [ReportPdfController::class, 'profitLoss'])->name('reports.profit-loss.pdf');
});
```

The **Export PDF** button in each Filament report page opens the corresponding route in a new tab, passing the current filters as query parameters.

---

## B9. Table Action Buttons — Standard Set Per Module

Apply these consistently:

| Module | View | Edit | Delete | Other Actions |
|---|---|---|---|---|
| Items | ✅ | ✅ | ✅ (if no active batches) | — |
| Item Categories | ✅ | ✅ | ✅ | — |
| UOM | ✅ | ✅ | ✅ | — |
| Packaging Types | ✅ | ✅ | ✅ | — |
| Suppliers | ✅ | ✅ | ✅ | — |
| Customers | ✅ | ✅ | ✅ | — |
| Purchase Orders | ✅ | ✅ (draft only) | ✅ (draft only) | Approve, Cancel, Receive Stock |
| Opening Stock | ✅ | ✅ (if unconsumed) | ✅ (if unconsumed) | — |
| GRNs | ✅ | ❌ | ✅ + reason modal | — |
| Sales Orders | ✅ | ✅ | ✅ + reason modal | Print Receipt |
| Stock Transfers | ✅ | ❌ | ✅ (draft only) | Submit, Approve, Dispatch, Receive |
| Stock Movements | ✅ | ❌ | ❌ | — |
| Audit Logs | ✅ | ❌ | ❌ | — |
| Deletion Logs | ✅ | ❌ | ❌ | — |
| Branches | ✅ | ✅ | ✅ | — |
| Departments | ✅ | ✅ | ✅ | — |
| Users | ✅ | ✅ | ✅ | — |
| Roles | ✅ | ✅ | ✅ (custom only) | — |

---

## B10. Empty States

Every list view must have a meaningful empty state when no records exist.

| Module | Empty State Message |
|---|---|
| Items | "No items yet. Create your first item to get started." |
| Purchase Orders | "No purchase orders. Create a PO to begin ordering stock." |
| GRNs | "No goods received yet. Create a GRN when stock arrives." |
| Sales Orders | "No sales recorded yet." |
| Stock Movements | "No stock movements yet. Movements appear automatically when stock is received or sold." |
| Stock Transfers | "No transfers created yet." |
| Opening Stock | "No opening stock entries. Create one to record your starting inventory." |
| Low Stock Widget | "✅ All items are sufficiently stocked." |
| Audit Logs | "No audit activity recorded yet." |
| Deletion Logs | "No records have been deleted." |

---

## B11. Confirmation Modals — Standard Wording

**Delete with reason (GRNs, Sales Orders):**
```
Title:   "Delete [Record Number]?"
Body:    "This will permanently delete this record and reverse all associated
          stock movements. This action cannot be undone."
Field:   Reason (textarea, required, min 10 characters)
Buttons: [Cancel]  [Delete Record] (danger colour)
```

**Delete without reason (catalogue items, categories, etc.):**
```
Title:   "Delete [Name]?"
Body:    "Are you sure you want to delete this record?"
Buttons: [Cancel]  [Delete] (danger colour)
```

**Approve actions:**
```
Title:   "Approve [Record Number]?"
Body:    "This will approve the [PO/Transfer] and it will proceed to the next stage."
Buttons: [Cancel]  [Approve] (success colour)
```

**Cancel actions:**
```
Title:   "Cancel [Record Number]?"
Body:    "This will cancel the record. This cannot be undone."
Buttons: [Back]  [Cancel Record] (danger colour)
```

---

## B12. Notifications (Filament Toast Messages)

Show Filament notifications after every action:

| Action | Type | Message |
|---|---|---|
| Record created | success | "[Record] created successfully." |
| Record updated | success | "[Record] updated successfully." |
| Record deleted | success | "[Record] deleted and stock reversed." |
| Insufficient stock | danger | "Insufficient stock for [Item Name]. Available: [x] units." |
| Batch consumed (edit blocked) | warning | "This batch has been consumed and cannot be edited." |
| PO approved | success | "Purchase Order [PO#] approved and issued to supplier." |
| Transfer approved | success | "Transfer [TRF#] approved." |
| Transfer dispatched | success | "Transfer [TRF#] marked as in transit." |
| Transfer received | success | "Transfer [TRF#] received. Stock updated at destination." |
| Low stock (on save) | warning | "[Item Name] is now below its reorder level ([x] units remaining)." |
| PDF export | success | "PDF generated. Download will start shortly." |
