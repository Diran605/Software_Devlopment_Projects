# Delete & Reversal Guide

This document lists **where you can delete records**, what happens to **stock and money**, and whether the system **reverses** (puts back) inventory automatically.

> **Important:** Most deletes are **soft deletes** (hidden, not erased from database). Some actions are **irreversible** once posted. Always enter a **reason** when prompted — it is stored in the deletion log.

---

## Quick reference

| Action | Where | Reverses stock? | Notes |
|--------|-------|-----------------|-------|
| Delete sales order | Sales order view | **Yes** | All lines reversed; batches restored |
| Delete GRN | GRN view | **Yes** | Received qty removed from stock |
| Delete stock transfer (draft) | Transfer view | **Yes** | Only before approval/receipt |
| Reverse clearance stock | Clearance stock list/view | **Yes** | Returns remaining qty to normal batch |
| Delete opening stock line | Opening stock view | **Limited** | Blocked if batch already consumed |
| Post inventory count | Count view | **No undo** | Adjusts stock permanently |
| Delete expense | Expense list/edit | **No stock** | Financial record only |
| Delete expense category | Category list | **No stock** | Blocked if expenses use category (DB constraint) |
| Delete item / supplier / customer | List row | **No auto stock reversal** | Soft delete; may fail if referenced |
| Delete branch (soft) | Branch list | **No** | Data kept; branch hidden |
| Force delete branch | Branch edit | **Cascades** | Dangerous — see system admin |

---

## 1. Sales orders

**Where:** App or Admin → Sales → Sales Orders → **View** → Delete  
**Permission:** `delete.sales-orders`

**What happens:**

1. Each line’s batch allocation is **reversed** (qty returned to batches).
2. Branch **item stock levels** increase again.
3. **Stock movements** record reversal entries.
4. A **deletion log** stores the order snapshot and reason.
5. Clearance lines: if sold via clearance stock, reversal follows the sales delete path (clearance qty is **not** automatically restored to clearance stock — contact admin if clearance sale was wrong).

**When to use:** Wrong order entered, duplicate sale, customer cancelled before leaving store.

---

## 2. Goods received notes (GRN)

**Where:** Goods Received Notes → **View** → Delete  
**Permission:** `delete.goods-received-notes`

**What happens:**

1. Received quantities are **removed** from batch inventory and stock levels.
2. Stock movements record the reversal.
3. Deletion log created with reason.

**When to use:** GRN entered in error before the stock was used/sold.

**Caution:** Do not delete if items from that GRN were already sold — stock will go negative or allocation will fail on other records.

---

## 3. Stock transfers

**Where:** Stock Transfers → **View** → Delete  
**Permission:** `delete.stock-transfers`

**What happens:**

- **Draft / before approval:** transfer cancelled; no stock moved yet.
- **After approval or receipt:** delete may be **blocked** or require admin intervention — check status on the transfer.

Always read the confirmation message on the transfer view.

---

## 4. Opening stock

**Where:** Opening Stocks → **View** → Delete line / entry  
**Permission:** `delete.opening-stock`

**What happens:**

- Lines that are **not yet consumed** can be edited or removed with qty adjustments.
- Lines marked **consumed** (stock already used up) **cannot be edited** — prevents corrupting history.

---

## 5. Clearance stock — Reverse to stock

**Where:**  
- App or Admin → Clearance Stock → list row **Reverse to Stock**, or  
- Clearance Stock **View** → **Reverse to Stock**

**Permission:** `delete.clearance-manager` (and qty remaining > 0)

**What happens:**

1. **Remaining clearance quantity** is added back to the original **batch**.
2. Branch **item stock level** increases.
3. Stock movement type `clearance_reversal` is recorded.
4. Clearance activity log records action type **reverse**.
5. Clearance stock record is **removed** (soft deleted).

**When to use:** Item was moved to clearance by mistake, or clearance is no longer needed but stock is still good.

**Caution:**

- Only **remaining** qty is reversed. If part was sold/donated/disposed, that portion is **not** restored by this action.
- Cannot reverse when **qty remaining = 0** (already fully actioned).

---

## 6. Clearance flagged items

**Where:** Flagged Items list  
**Delete:** Generally **blocked** after approval (policy prevents reversing approved clearance decisions from the list). Use **Reverse to Stock** on clearance stock instead.

---

## 7. Inventory counts

| Step | Reversible? |
|------|-------------|
| Edit lines (draft / in progress) | Yes |
| Submit / approve | Yes (reject returns to in progress) |
| **Post** | **No** — stock levels updated permanently |

**Where:** Inventory Counts → View → Post  
**There is no “unpost” button.** To fix a posted count, create a new count or manual adjustment via supervisor process.

---

## 8. Expenses & expense categories

### Expenses

**Where:** Expenses list → Delete, or Edit → Delete  
**Permission:** `delete.expenses`

- Removes the expense record (soft delete).
- **Does not affect stock.**
- Profit & Loss report will **exclude** deleted expenses (only active records in date range).

### Expense categories

**Where:** Expense Categories list → Delete (trash icon on row)  
**Permission:** `delete.expense-categories`

- Soft deletes the category.
- **Fails** if expenses still reference the category (database protection).
- Deactivate (`is_active = false`) instead if the category is historical.

---

## 9. Catalogue master data

**Items, categories, suppliers, customers, packaging types, UOM**

**Where:** respective list → Delete on row  
**Permission:** `delete.{module}`

- Usually **soft delete** only.
- **No automatic stock reversal.**
- Delete may **fail** if the record is used on sales, batches, POs, etc.

Prefer **deactivate** (`is_active = false`) for items instead of delete when they have history.

---

## 10. Donations & disposals

**Where:** View page — typically **no delete** after posting  
These are audit-sensitive write-offs. Creating a correcting entry may require supervisor/admin process rather than delete.

---

## 11. Branches

| Action | Effect |
|--------|--------|
| **Delete** (App list) | Soft delete — branch hidden, **all data kept** |
| **Force delete** (Edit page) | Permanent — attempts DB cascade delete of branch data |

**Caution:** Force delete may **fail** if stock transfers reference the branch. Prefer deactivating branches (`is_active = false`).

---

## 12. Logs (audit trail)

| Log | Can delete? |
|-----|-------------|
| **Audit logs** | No (normal users) |
| **Deletion logs** | No (normal users) |
| **Stock movements** | No — permanent history |

Deletion logs capture: who deleted, reason, snapshot of record, and stock reversal summary for sales orders.

---

## 13. Decision flowchart

```
Made a mistake?
│
├─ Sale not yet saved? → Edit or discard the form
│
├─ Sale saved? → Delete sales order (reverses stock)
│
├─ GRN wrong? → Delete GRN if stock not yet sold
│
├─ Clearance approved by mistake? → Reverse to Stock (clearance stock list)
│
├─ Count posted wrong? → Cannot undo — new count / supervisor adjustment
│
└─ Expense wrong? → Delete expense (P&L updates automatically)
```

---

## 14. Permissions checklist for supervisors

Ensure roles include these for staff who must correct mistakes:

- `delete.sales-orders`
- `delete.goods-received-notes`
- `delete.stock-transfers`
- `delete.clearance-manager` (clearance reverse)
- `delete.expenses`
- `delete.expense-categories`

Assign narrowly — delete permissions can affect financial and stock integrity.

---

*Last updated: reflects IMS clearance reversal, expense category list delete, and filtered PDF export behaviour.*
