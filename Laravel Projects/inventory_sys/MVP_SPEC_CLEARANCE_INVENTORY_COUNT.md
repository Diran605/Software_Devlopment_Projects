# Inventory Management System — Clearance Manager & Inventory Count Spec

> **This is the fourth document in the spec series.**
> Read documents 1 (MVP Spec), 2 (Addendum), and 3 (UI/Views/Reports) first.
> This document fully defines the Clearance Manager and Inventory Count modules
> which are Phase 2 but must be designed completely now.

---

## C1. Clearance Manager

### Overview

The Clearance Manager handles stock that is nearing or past its expiry date.
It is a lifecycle pipeline: auto-flag → review → approve action → move to clearance stock → sell / donate / dispose.

### Database Tables (already in migrations — no new migrations needed)

```
clearance_rules         ← defines thresholds and discount % per urgency band
clearance_items         ← one record per flagged batch, tracks the full lifecycle
clearance_stock         ← staging area: stock moved here when clearance is approved
clearance_actions       ← records what was done (sold, donated, disposed)
```

### Additional Migration (Phase 2 — new)

The original spec is missing `clearance_stock`. Add this migration in Phase 2:

```php
Schema::create('clearance_stock', function (Blueprint $table) {
    $table->id();
    $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
    $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('clearance_item_id')->constrained()->cascadeOnDelete();
    $table->foreignId('item_id')->constrained()->restrictOnDelete();
    $table->foreignId('batch_inventory_id')->constrained()->restrictOnDelete();
    $table->string('batch_number');
    $table->date('expiry_date')->nullable();
    $table->unsignedInteger('qty_on_clearance');
    $table->unsignedInteger('qty_remaining');    // decremented as clearance stock is sold/donated/disposed
    $table->decimal('original_price', 12, 2);   // item.selling_price at time of clearance
    $table->decimal('clearance_price', 12, 2);  // discounted price (original × (1 - discount%))
    $table->decimal('unit_cost', 12, 2);        // from batch — for P&L calculation
    $table->softDeletes();
    $table->timestamps();

    $table->index(['branch_id', 'item_id']);
    $table->index(['clearance_item_id']);
});
```

---

### Clearance Rules

**What they define:** urgency bands based on days to expiry, with a discount % for each band.

**Example setup a shop might use:**

| Urgency Label | Days to Expiry | Discount % |
|---|---|---|
| Approaching | 30–15 days | 10% |
| Urgent | 14–7 days | 25% |
| Critical | 6–1 days | 50% |
| Expired | 0 or past | 100% (donate/dispose only) |

**List View**
Columns: Urgency Label, Days Min, Days Max, Discount %, Is Active badge, Actions.
Row actions: Edit, Delete.

**Create / Edit Form**
```
Urgency Label (text)       | Is Active (toggle)
Days to Expiry Min (number)| Days to Expiry Max (number)
Discount % (number, 0–100)
```

---

### Clearance Items — The Pipeline

#### How Auto-Flagging Works

A Laravel scheduled command `php artisan clearance:scan` runs daily (schedule in `routes/console.php`).

For each `batch_inventory` record where:
- `expiry_date IS NOT NULL`
- `qty_remaining > 0`
- `deleted_at IS NULL`

The command calculates `days_to_expiry = expiry_date - today()`.

It then checks against `clearance_rules` to find a matching band.
If a match is found AND no `clearance_items` record already exists for this batch → create one.
If a record already exists → update `days_to_expiry` and `urgency_status`.

```php
// Schedule in routes/console.php
Schedule::command('clearance:scan')->dailyAt('06:00');
```

#### Clearance Items List View

This is the main screen of the Clearance Manager.

**Filter Bar:**
```
Urgency Status (multiselect) | Approval Status (multiselect)
Category (select)            | Date Range (expiry_date)
[Search: item name, batch #]
```

**Table Columns:**
| Item Name | Category | Batch # | Expiry Date | Days to Expiry | Qty Flagged | Urgency badge | Approval badge | Suggested Discount | Clearance Price | Actions |

**Urgency badge colours:**
- Approaching → warning (amber)
- Urgent → orange (use `Color::Orange`)
- Critical → danger (red)
- Expired → gray with strikethrough

**Approval badge colours:**
- Pending → gray
- Approved → success
- Declined → danger
- Actioned → info

**Row Actions:**
- View
- Approve (pending only) → opens action modal
- Decline (pending only)
- View Clearance Stock (approved only) → links to clearance_stock record

**Bulk Actions:**
- Bulk Approve selected items

---

#### Clearance Items — View (Detail) Page

Header: Item Name, Batch #, Expiry Date, Days to Expiry, Urgency badge.

**Info Section:**
```
┌─────────────────────────────────────────────────────┐
│  Item:          [name]      Category:   [category]  │
│  Batch #:       [batch]     UOM:        [uom]        │
│  Expiry Date:   DD/MM/YYYY  Unit Cost:  FCFA x,xxx   │
│  Days to Expiry: xx days    Original Price: FCFA xxx │
├─────────────────────────────────────────────────────┤
│  Qty in Batch:    xxx units                         │
│  Qty Remaining:   xxx units  (from batch_inventory) │
│  Qty on Clearance: xx units  (from clearance_stock) │
├─────────────────────────────────────────────────────┤
│  Suggested Discount: xx%    (from matched rule)     │
│  Clearance Price:  FCFA xxx (auto-calc, editable)   │
└─────────────────────────────────────────────────────┘
```

**Action Buttons (based on approval_status):**

If `pending`:
- **Approve & Set Action** → opens approval modal (see below)
- **Decline** → soft declines, item stays in normal batch

If `approved`:
- **View Clearance Stock** → links to clearance stock record
- **Mark as Sold** → opens clearance sale form
- **Mark as Donated** → opens donation form pre-filled
- **Mark as Disposed** → opens disposal form pre-filled

**Approval Modal:**

When the manager clicks Approve, a modal opens:

```
┌──────────────────────────────────────────────────────┐
│  Approve Clearance — [Item Name] Batch [#]           │
├──────────────────────────────────────────────────────┤
│  Expiry Date:        DD/MM/YYYY                      │
│  Days to Expiry:     xx days                         │
│  Qty Remaining:      xxx units                       │
│                                                      │
│  Qty to Move to Clearance: [___]  (default = all)   │
│                                                      │
│  Original Price:     FCFA xxx                        │
│  Suggested Discount: xx%  (from rule, editable)      │
│  Clearance Price:    FCFA xxx  (auto-calc, editable) │
│                                                      │
│  Action Type:  ○ Discount & Sell                     │
│                ○ Donate                              │
│                ○ Dispose                             │
│                                                      │
│  Notes: [textarea]                                   │
├──────────────────────────────────────────────────────┤
│               [Cancel]    [Approve & Move to Clearance]│
└──────────────────────────────────────────────────────┘
```

On confirmation:
1. `qty_to_move` is deducted from `batch_inventory.qty_remaining`
2. A `clearance_stock` record is created with `clearance_price` set
3. A `stock_movements` entry of type `clearance_out` is posted from normal batch
4. `clearance_items.approval_status` set to `approved`
5. `clearance_items.action_type` set to selected action

---

### Clearance Stock

This is the staging area. Once stock is approved for clearance it lives here until sold, donated, or disposed.

**List View**
Columns: Item, Batch #, Expiry Date, Days to Expiry, Qty on Clearance, Qty Remaining, Original Price, Clearance Price, Unit Cost, Status badge, Actions.

Status badge:
- Active → info (has qty_remaining > 0)
- Depleted → gray (qty_remaining = 0)

Row actions: View, Sell, Donate, Dispose.

---

### Clearance Sales

When clearance stock is sold, it goes through a **normal sales order** but the line pulls from `clearance_stock` instead of `batch_inventory`, and `unit_price` is set to `clearance_price`.

**Create Clearance Sale Form:**

```
Customer (select or walk-in name)
Sold At (datetime, default = now)
Notes

Lines (repeater):
  Clearance Item (select — shows: item name | batch # | expiry | clearance price | qty remaining)
  Qty Sold
  Unit Price (pre-filled with clearance_price, editable — cannot go below 0)
  Line Total (auto-calc)
```

On save:
1. Deducts from `clearance_stock.qty_remaining`
2. Posts `stock_movements` of type `clearance_sale`
3. Creates a normal `sales_order` record (so it appears in sales reports) with a flag `is_clearance = true`
4. Calculates loss: `(original_price - clearance_price) × qty_sold` → stored in `clearance_actions.loss_value`

> Add `is_clearance (bool, default false)` to `sales_orders` table in Phase 2 migration.

---

### Disposals

**List View**
Columns: Disposal Number, Disposed By, Disposed At, Reason, Total Items, Total Loss Value, Actions.
Filters: Reason, Date Range.
Row actions: View, Delete (soft delete, reversal not needed since stock already deducted on clearance approval).

**Create Form:**
```
Disposal Reason (select: expired | damaged | quality_issue | other)
Disposal Method (text — e.g. bin, incinerate)
Disposed At (datetime)
Notes

Lines (repeater):
  Clearance Stock Item (select — shows item | batch # | expiry | qty remaining on clearance)
  Qty Disposed
  Unit Cost (auto-filled from clearance_stock, read-only)
  Total Loss (auto-calc: qty_disposed × unit_cost)
```

Footer: Total Items, Total Loss Value.

On save:
1. Deducts from `clearance_stock.qty_remaining`
2. Posts `stock_movements` of type `disposal`
3. Creates `clearance_actions` record with `action_type = dispose`

**View (Detail) Page**
Header: Disposal Number, Disposed By, Disposed At, Reason.
Lines table: Item, Batch #, Expiry Date, Qty Disposed, Unit Cost, Total Loss.
Footer: Total Loss Value.

---

### Donations

**List View**
Columns: Donation Number, Recipient Name, Donated By, Donated At, Total Items, Total Value, Actions.
Filters: Date Range.
Row actions: View, Delete.

**Create Form:**
```
Recipient Name (text)
Recipient Contact (text)
Recipient Address (textarea)
Donated At (datetime)
Notes

Lines (repeater):
  Clearance Stock Item (select — shows item | batch # | expiry | qty remaining)
  Qty Donated
  Unit Value (auto-filled from clearance_stock.unit_cost, read-only)
  Total Value (auto-calc)
```

Footer: Total Items, Total Value (total cost value being donated).

On save:
1. Deducts from `clearance_stock.qty_remaining`
2. Posts `stock_movements` of type `donation`
3. Creates `clearance_actions` record with `action_type = donate`

**View (Detail) Page**
Header: Donation Number, Recipient, Donated By, Donated At.
Lines table: Item, Batch #, Expiry Date, Qty Donated, Unit Value, Total Value.
Footer: Total Value.

---

## C2. Inventory Count

### Overview

The Inventory Count module allows staff to physically count stock and reconcile it
against what the system believes is on hand. Counts are done **per batch** so
discrepancies can be traced to an exact batch. A manager must approve before
the system updates any stock levels.

### Module Flow

```
Create Count → In Progress (staff enter counts) → Submit for Approval
→ Manager Reviews Variances → Approve or Reject
→ If Approved: Post → system updates batch_inventory + stock_movements
→ If Rejected: back to In Progress for recount
```

---

### Inventory Counts — List View

**Columns:**
| Count # | Type badge | Branch | Department | Initiated By | Status badge | Count Date | Variance Value | Actions |

**Filters:**
```
Status (multiselect) | Count Type | Date Range | Branch | Department
```

**Row Actions:** View, Edit (draft/in_progress only), Delete (draft only).

---

### Create Inventory Count Form

```
Count Type (select: Full | Partial | Spot Check)
Branch (select)                | Department (select, optional)
Notes (textarea)

─── Items to Count ───────────────────────────────────────
Item Selection (only for Partial/Spot Check):
  → Full Count: system auto-populates ALL active items for the branch/department
  → Partial/Spot Check: user selects specific items from a searchable multi-select
```

On save (status = draft):
- System snapshots `qty_theoretical` from `batch_inventory.qty_remaining`
  for each item/batch at the moment the count is created.
- Status moves to `in_progress` immediately after creation.

---

### Inventory Count — Detail / Counting View

This is the main working screen where staff enter physical counts.
It is the most important view in this module.

**Header:**
```
┌────────────────────────────────────────────────────────────┐
│  Count #: CNT-HQ-20260601-0001     Status: In Progress     │
│  Type: Full Count                  Branch: HQ              │
│  Department: Warehouse             Initiated By: John Doe  │
│  Count Date: 01/06/2026                                    │
├────────────────────────────────────────────────────────────┤
│  Summary:                                                  │
│  Total Items: 91   Counted: 45   Remaining: 46             │
│  Total Variance: FCFA -12,500  (negative = shortage)       │
└────────────────────────────────────────────────────────────┘
```

**Progress Bar:**
A visual bar showing % of items counted (counted / total).

**Action Buttons:**
- **Submit for Approval** (visible when status = in_progress, all items counted)
- **Save Progress** (saves without submitting — staff can return later)
- **Approve** (visible to manager when status = pending_approval)
- **Reject** (visible to manager when status = pending_approval)
- **Post** (visible when status = approved)

---

### Count Lines Table

This is the heart of the screen. Each row is one batch of one item.

**Columns:**

| # | Item Name | SKU | Category | UOM | Batch # | Expiry Date | Cost Price | Selling Price | Theoretical Qty | Actual Qty | Variance | Variance Value | Notes |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|

**Column Details:**

**Item Name** — read-only, item name from `items` table.

**SKU** — read-only.

**Category** — read-only.

**UOM** — read-only, unit of measure abbreviation.

**Batch #** — read-only, from `batch_inventory.batch_number`.

**Expiry Date** — read-only, from `batch_inventory.expiry_date`. Shows "—" if non-perishable. Highlighted red if expired, amber if within 30 days.

**Cost Price** — **editable inline**. Pre-filled from `batch_inventory.unit_cost`. If manager changes this, it updates `batch_inventory.unit_cost` and `item_stock_levels.unit_cost` on post. Useful for correcting wrong costs entered during GRN.

**Selling Price** — **editable inline**. Pre-filled from `items.selling_price`. If changed, updates `items.selling_price` on post.

**Theoretical Qty** — read-only. Snapshotted from `batch_inventory.qty_remaining` at count creation. Cannot be changed — this is the system's expected count.

**Actual Qty** — **editable inline**. Default is empty/null until staff enters the count. Input field, numeric only, min 0. Highlighted yellow until filled in. Once filled, locks unless count is rejected.

**Variance** — auto-calculated, read-only. `actual_qty - theoretical_qty`. Shown as:
- `0` → green badge "Match"
- Positive number → blue badge "+ x" (surplus)
- Negative number → red badge "- x" (shortage)

**Variance Value** — auto-calculated, read-only. `variance × unit_cost`. Shown in FCFA. Red if negative, blue if positive.

**Notes** — editable inline. Free text per line for the counter to explain discrepancies.

---

### Count Lines — Grouping

Lines are grouped by **Item** in the table. Within each item group, each batch is a sub-row.

```
▼ Ovaltine 400g                              [3 batches]
    BATCH-001   01/06/2026   2,500   2,500   25    25     0      0       Match ✅
    BATCH-002   15/08/2026   2,500   2,500   10    8      -2     -5,000  Shortage 🔴
    BATCH-003   01/01/2027   2,500   2,500   30    [   ]  —      —       Pending ⏳

▼ Power Malt 330ml                           [1 batch]
    BATCH-009   30/09/2026   800     800     48    48     0      0       Match ✅
```

Collapsed by default for items with no variance. Expanded by default for items with variance.
A **"Expand All / Collapse All"** toggle at the top of the table.

---

### Count Lines — Filter & Search

Above the table:
```
[Search: item name, SKU, batch #]
[Filter: Category]  [Filter: Status (Matched | Shortage | Surplus | Pending)]
[Show Only Variances toggle]   [Show Only Uncounted toggle]
```

---

### Approval View

When status = `pending_approval`, the manager sees the same table but:
- **Actual Qty** is now read-only (locked)
- **Cost Price** and **Selling Price** remain editable (manager can still adjust)
- A **Variance Summary** section appears above the table:

```
┌──────────────────────────────────────────────────────────┐
│  VARIANCE SUMMARY                                        │
├────────────────────┬─────────────────────────────────────┤
│  Items with Match  │  65 items (71%)                     │
│  Items with Shortage│  20 items — Total: FCFA -45,000   │
│  Items with Surplus │  6 items  — Total: FCFA +8,000    │
│  Net Variance Value │  FCFA -37,000                      │
└────────────────────┴─────────────────────────────────────┘
```

**Approve action:**
- Confirmation modal: "This will update stock levels for all [x] items with variances. This cannot be undone."
- On confirm: posts count, updates batch_inventory, posts stock_movements

**Reject action:**
- Modal asks for rejection reason
- Status goes back to `in_progress`
- Actual Qty fields unlock for recount

---

### What Happens on Post (Approved)

For each count line where `variance != 0`:

1. `batch_inventory.qty_remaining` updated to `qty_counted`
2. `item_stock_levels.qty_on_hand` adjusted by the variance
3. If `cost_price` was edited: `batch_inventory.unit_cost` updated
4. If `selling_price` was edited: `items.selling_price` updated
5. `stock_movements` record created:
   - `movement_type = count_adjustment`
   - `qty_in = variance` if positive (surplus)
   - `qty_out = abs(variance)` if negative (shortage)
   - `qty_before = qty_theoretical`
   - `qty_after = qty_counted`
   - `reference_type = InventoryCountLine`
   - `reference_id = line.id`

For lines where `variance = 0`: no stock movement posted (no change needed).

---

### Count Number Format

```
CNT-{BRANCH}-{YYYYMMDD}-{SEQ}
Example: CNT-HQ-20260601-0001
```

Add to `NumberGeneratorService`:
```php
public function generateCountNumber(int $branchId): string { ... }
```

---

### Inventory Count — Sidebar Navigation (App Panel)

```
├── 🔢  Inventory Counts
│       ├── All Counts              (list)
│       ├── Create Count            (form)
```

Permission gates:
- List: `view.inventory-counts`
- Create: `create.inventory-counts`
- Edit/Count entry: `edit.inventory-counts`
- Approve/Reject: `approve.inventory-counts`
- Post: `post.inventory-counts`
- Delete: `delete.inventory-counts`

---

### Inventory Count — Empty States

| State | Message |
|---|---|
| No counts yet | "No inventory counts yet. Create a count to begin reconciling your stock." |
| All items matched | "✅ All items matched. No variances found." |
| Pending approval | "This count is awaiting manager approval." |
| Rejected | "This count was rejected. Review the notes and recount." |

---

## C3. Expenses

### Overview

Tracks day-to-day operational expenses per branch and department.
Simple module — no approval workflow needed.

### Expense Categories

**List View**
Columns: Name, Description, Is Active badge, Actions.
Row actions: Edit, Delete.

**Create / Edit Form**
```
Name (text) | Is Active (toggle)
Description (textarea)
```

---

### Expenses

**List View**
Columns: Expense #, Category, Date, Amount (FCFA), Payment Method, Incurred By, Department, Description (truncated), Actions.
Filters: Category, Date Range, Department, Incurred By.
Search: expense_number, description.
Row actions: View, Edit, Delete.
Default sort: expense_date DESC.

**Create / Edit Form**
```
Expense Category (select + create inline)  | Expense Date (date, default today)
Department (optional select)               | Amount (FCFA)
Description (text)
Notes (textarea)
```

**View (Detail) Page**
All fields in read-only grid.

---

## C4. Updated Sidebar — Including All Phase 2 Modules

### App Panel — Complete Sidebar (replaces B3 in UI spec)

```
APP PANEL
│
├── 🏠  Dashboard
│
├── ─── CATALOGUE ────────────────────────
├── 📦  Items
│       ├── All Items
│       ├── Create Item
│
├── 🗂️  Item Categories
│       ├── All Categories
│       ├── Create Category
│
├── 📏  Units of Measure
│       ├── All UOMs
│       ├── Create UOM
│
├── 📦  Packaging Types
│       ├── All Packaging Types
│       ├── Create Packaging Type
│
├── ─── PARTIES ──────────────────────────
├── 🚚  Suppliers
│       ├── All Suppliers
│       ├── Create Supplier
│
├── 👥  Customers
│       ├── All Customers
│       ├── Create Customer
│
├── ─── PROCUREMENT ──────────────────────
├── 🛒  Purchase Orders
│       ├── All Purchase Orders
│       ├── Create PO
│
├── ─── STOCK IN ─────────────────────────
├── 📂  Opening Stock
│       ├── All Entries
│       ├── Create Entry
│
├── 📥  Goods Received Notes
│       ├── All GRNs
│       ├── Create GRN
│
├── ─── SALES ────────────────────────────
├── 🧾  Sales Orders
│       ├── All Sales Orders
│       ├── Create Sale
│
├── ─── STOCK CONTROL ────────────────────
├── 🔄  Stock Transfers
│       ├── All Transfers
│       ├── Create Transfer
│
├── 📊  Stock Movements      (read-only)
│
├── 🔢  Inventory Counts
│       ├── All Counts
│       ├── Create Count
│
├── ─── EXPIRY MANAGEMENT ────────────────
├── ⚠️  Clearance Manager
│       ├── Flagged Items    (auto-flagged list)
│       ├── Clearance Rules  (manage thresholds)
│
├── 🏷️  Clearance Sales
│       ├── All Clearance Sales
│       ├── Create Clearance Sale
│
├── ─── WRITE-OFFS ───────────────────────
├── 🗑️  Disposals
│       ├── All Disposals
│       ├── Create Disposal
│
├── 🤝  Donations
│       ├── All Donations
│       ├── Create Donation
│
├── ─── EXPENSES ─────────────────────────
├── 💸  Expenses
│       ├── All Expenses
│       ├── Create Expense
│
├── 🗂️  Expense Categories
│       ├── All Categories
│       ├── Create Category
│
├── ─── REPORTS ──────────────────────────
├── 📈  Reports
│       ├── Sales Report
│       ├── Stock Valuation
│       ├── Purchase Report
│       ├── Profit & Loss
│       └── Expiry Report
│
├── ─── LOGS ─────────────────────────────
├── 📋  Audit Logs           (read-only)
└── 🗑️  Deletion Logs        (read-only)
```

### Admin Panel — Complete Sidebar (replaces B2 in UI spec)

Identical structure to App Panel above, plus:

```
├── ─── SYSTEM ─────────────────── (at the top, before Catalogue)
├── 🏢  Branches
│       ├── All Branches
│       ├── Create Branch
│
├── 🏬  Departments
│       ├── All Departments
│       ├── Create Department
│
├── 👤  Users
│       ├── All Users
│       ├── Create User
│
├── 🔐  Roles & Permissions
│       ├── All Roles
│       ├── Create Role
```

All list views in admin panel include a Branch column and Branch filter.
All create forms include a Branch selector as the first field.
No multi-tenancy scoping — admin sees all branches at all times.

---

## C5. Phase 2 — What to Build vs What Was Already Seeded

| Item | Already done in MVP | Phase 2 action |
|---|---|---|
| Permissions for all modules | ✅ Seeded | Just assign to roles |
| `clearance_rules` migration | ✅ Done | Build resource |
| `clearance_items` migration | ✅ Done | Build resource + scheduler |
| `clearance_stock` migration | ❌ Missing | Add new migration |
| `clearance_actions` migration | ✅ Done | Build resource |
| `disposals` + `disposal_lines` | ✅ Done | Build resource |
| `donations` + `donation_lines` | ✅ Done | Build resource |
| `inventory_counts` + `inventory_count_lines` | ✅ Done | Build resource |
| `expenses` + `expense_categories` | ✅ Done | Build resource |
| `is_clearance` on `sales_orders` | ❌ Missing | Add column in migration |
| `clearance_sale` movement type | ❌ Missing | Add to enum in migration |
| `ComingSoonPage` stubs | ✅ Built in MVP | Replace with real resources |
| Expiry Report | ❌ Not built | Build report page + PDF |
