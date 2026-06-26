<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesReportBranch;
use App\Models\BatchInventory;
use App\Models\ClearanceAction;
use App\Models\InventoryCount;
use App\Models\ItemStockLevel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportPdfController extends Controller
{
    use ResolvesReportBranch;

    public function sales(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'group_by' => ['nullable', 'in:date,item,customer,cashier'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;
        $groupBy = $filters['group_by'] ?? 'date';

        $query = \App\Models\SalesOrderLine::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->where('sales_orders.branch_id', $branchId)
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to));

        if ($groupBy === 'item') {
            $data = $query
                ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
                ->select(
                    'items.name as label',
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy('sales_order_lines.item_id', 'items.name')
                ->orderBy('total_revenue', 'desc')
                ->get();
        } elseif ($groupBy === 'customer') {
            $data = $query
                ->leftJoin('customers', 'customers.id', '=', 'sales_orders.customer_id')
                ->select(
                    DB::raw('COALESCE(customers.name, sales_orders.customer_name, "Walk-in") as label'),
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy(DB::raw('COALESCE(customers.name, sales_orders.customer_name, "Walk-in")'))
                ->orderBy('total_revenue', 'desc')
                ->get();
        } elseif ($groupBy === 'cashier') {
            $data = $query
                ->join('users', 'users.id', '=', 'sales_orders.served_by')
                ->select(
                    'users.name as label',
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy('sales_orders.served_by', 'users.name')
                ->orderBy('total_revenue', 'desc')
                ->get();
        } else {
            $data = $query
                ->select(
                    DB::raw('DATE(sales_orders.sold_at) as label'),
                    DB::raw('COUNT(DISTINCT sales_orders.id) as order_count'),
                    DB::raw('SUM(sales_order_lines.qty_sold) as total_qty'),
                    DB::raw('SUM(sales_order_lines.line_total) as total_revenue'),
                    DB::raw('SUM(sales_order_lines.gross_profit) as total_profit')
                )
                ->groupBy(DB::raw('DATE(sales_orders.sold_at)'))
                ->orderBy('label', 'desc')
                ->get();
        }

        return Pdf::loadView('reports.sales', compact('data', 'filters', 'groupBy', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('sales-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function stockValuation(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'include_zero_stock' => ['nullable'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $categoryId = $filters['category_id'] ?? null;
        $includeZero = filter_var($filters['include_zero_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $data = ItemStockLevel::query()
            ->select('item_stock_levels.*')
            ->join('items', 'items.id', '=', 'item_stock_levels.item_id')
            ->where('item_stock_levels.branch_id', $branchId)
            ->whereNotNull('item_stock_levels.department_id')
            ->when($categoryId, fn ($q) => $q->where('items.category_id', $categoryId))
            ->when(! $includeZero, fn ($q) => $q->where('item_stock_levels.qty_on_hand', '>', 0))
            ->with(['item', 'item.category', 'item.uom'])
            ->get();

        return Pdf::loadView('reports.stock-valuation', compact('data', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('stock-valuation-'.now()->format('Y-m-d').'.pdf');
    }

    public function purchases(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'supplier_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'array'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $data = \App\Models\PurchaseOrder::query()
            ->where('branch_id', $branchId)
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('ordered_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('ordered_at', '<=', $v))
            ->when($filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when(! empty($filters['status'] ?? []), fn ($q) => $q->whereIn('status', $filters['status']))
            ->with(['supplier', 'purchaseOrderLines', 'purchaseOrderLines.item'])
            ->orderBy('ordered_at', 'desc')
            ->get();

        return Pdf::loadView('reports.purchases', compact('data', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('purchase-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function profitLoss(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        $salesAgg = \App\Models\SalesOrderLine::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->where('sales_orders.branch_id', $branchId)
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
            ->select(
                DB::raw('COALESCE(SUM(sales_order_lines.line_total), 0) as revenue'),
                DB::raw('COALESCE(SUM(sales_order_lines.line_cost), 0) as cogs'),
                DB::raw('COALESCE(SUM(sales_order_lines.gross_profit), 0) as gross_profit')
            )
            ->first();

        $revenue = (float) ($salesAgg->revenue ?? 0);
        $cogs = (float) ($salesAgg->cogs ?? 0);
        $grossProfit = (float) ($salesAgg->gross_profit ?? 0);

        $totalExpenses = (float) \App\Models\Expense::query()
            ->where('branch_id', $branchId)
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to))
            ->sum('amount');

        $netProfit = $grossProfit - $totalExpenses;

        $expenseBreakdown = \App\Models\Expense::query()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->where('expenses.branch_id', $branchId)
            ->when($from, fn ($q) => $q->whereDate('expenses.expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expenses.expense_date', '<=', $to))
            ->select(
                DB::raw('COALESCE(expense_categories.name, "Uncategorized") as category_name'),
                DB::raw('SUM(expenses.amount) as total_amount')
            )
            ->groupBy('expense_categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        $data = compact('revenue', 'cogs', 'grossProfit', 'totalExpenses', 'netProfit', 'expenseBreakdown');

        return Pdf::loadView('reports.profit-loss', compact('data', 'filters', 'branch'))
            ->download('profit-loss-'.now()->format('Y-m-d').'.pdf');
    }

    public function expiry(Request $request)
    {
        $filters = $request->validate([
            'days_threshold' => ['required', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'urgency_band' => ['required', 'string'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $data = $this->buildExpiryReportData(
            $branchId,
            (int) $filters['days_threshold'],
            $filters['category_id'] ?? null,
            $filters['urgency_band']
        );

        return Pdf::loadView('reports.expiry', compact('data', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('expiry-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function lowStock(Request $request)
    {
        $request->validate(['branch_id' => ['nullable', 'integer']]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $data = ItemStockLevel::query()
            ->join('items', 'items.id', '=', 'item_stock_levels.item_id')
            ->where('item_stock_levels.branch_id', $branchId)
            ->whereNotNull('item_stock_levels.department_id')
            ->where('items.reorder_level', '>', 0)
            ->whereColumn('item_stock_levels.qty_on_hand', '<=', 'items.reorder_level')
            ->select('item_stock_levels.*')
            ->with(['item', 'item.category'])
            ->orderBy('item_stock_levels.qty_on_hand')
            ->get();

        return Pdf::loadView('reports.low-stock', compact('data', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('low-stock-'.now()->format('Y-m-d').'.pdf');
    }

    public function inventoryCount(Request $request, InventoryCount $inventoryCount)
    {
        abort_unless(auth()->user()?->can('view', $inventoryCount), 403);

        $inventoryCount->load([
            'branch',
            'department',
            'createdBy',
            'lines.item.category',
            'lines.item.uom',
            'lines.batchInventory',
        ]);

        return Pdf::loadView('reports.inventory-count', ['count' => $inventoryCount])
            ->setPaper('a4', 'landscape')
            ->download('inventory-count-'.$inventoryCount->count_number.'.pdf');
    }

    public function clearanceActivity(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'action_type' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $data = ClearanceAction::query()
            ->where('branch_id', $branchId)
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['action_type'] ?? null, fn ($q, $v) => $q->where('action_type', $v))
            ->with(['item', 'clearanceStock', 'salesOrder', 'donation', 'disposal'])
            ->orderByDesc('created_at')
            ->get();

        return Pdf::loadView('reports.clearance-activity', compact('data', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('clearance-activity-'.now()->format('Y-m-d').'.pdf');
    }

    public function itemsList(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);
        $categoryId = $filters['category_id'] ?? null;

        $data = \App\Models\Item::query()
            ->where('branch_id', $branchId)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['category', 'uom', 'itemStockLevels'])
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                // Get the stock level for the current branch
                $stockLevel = $item->itemStockLevels->firstWhere('branch_id', $item->branch_id);
                $item->qty_on_hand = $stockLevel->qty_on_hand ?? 0;
                return $item;
            });

        return Pdf::loadView('reports.items-list', compact('data', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('items-list-'.now()->format('Y-m-d').'.pdf');
    }

    public function expenses(Request $request)
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);

        $rows = \App\Models\Expense::query()
            ->with(['category', 'department', 'createdBy'])
            ->where('branch_id', $branchId)
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('expense_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('expense_date', '<=', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->orderByDesc('expense_date')
            ->get();

        $categorySummary = \App\Models\Expense::query()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->where('expenses.branch_id', $branchId)
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('expenses.expense_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('expenses.expense_date', '<=', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('expenses.category_id', $v))
            ->selectRaw('COALESCE(expense_categories.name, "Uncategorized") as category_name')
            ->selectRaw('SUM(expenses.amount) as total_amount')
            ->selectRaw('COUNT(*) as expense_count')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        $data = [
            'rows' => $rows,
            'total_amount' => $rows->sum('amount'),
            'expense_count' => $rows->count(),
            'category_summary' => $categorySummary,
        ];

        return Pdf::loadView('reports.expenses', compact('data', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('expense-report-'.now()->format('Y-m-d').'.pdf');
    }

    protected function buildExpiryReportData(int $branchId, int $daysThreshold, ?int $categoryId, string $urgencyBand)
    {
        $batches = BatchInventory::query()
            ->with(['item.category'])
            ->whereNotNull('expiry_date')
            ->where('qty_remaining', '>', 0)
            ->where('branch_id', $branchId)
            ->when($categoryId, fn ($q) => $q->whereHas('item', fn ($qi) => $qi->where('category_id', $categoryId)))
            ->get()
            ->map(function ($batch) {
                $daysToExpiry = (int) now()->startOfDay()->diffInDays($batch->expiry_date?->startOfDay(), false);
                $batch->days_to_expiry = $daysToExpiry;

                if ($daysToExpiry <= 0) {
                    $batch->urgency_band = 'expired';
                    $batch->urgency_label = 'Expired';
                } elseif ($daysToExpiry <= 6) {
                    $batch->urgency_band = 'critical';
                    $batch->urgency_label = 'Critical';
                } elseif ($daysToExpiry <= 14) {
                    $batch->urgency_band = 'urgent';
                    $batch->urgency_label = 'Urgent';
                } elseif ($daysToExpiry <= 30) {
                    $batch->urgency_band = 'approaching';
                    $batch->urgency_label = 'Approaching';
                } else {
                    $batch->urgency_band = 'safe';
                    $batch->urgency_label = 'Safe';
                }

                $batch->total_cost = $batch->qty_remaining * $batch->unit_cost;

                return $batch;
            });

        return $batches->filter(function ($batch) use ($daysThreshold, $urgencyBand) {
            if ($batch->days_to_expiry > $daysThreshold) {
                return false;
            }

            if ($urgencyBand !== 'all' && $batch->urgency_band !== $urgencyBand) {
                return false;
            }

            return true;
        })->sortBy('days_to_expiry')->values();
    }
}
