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
        $filters = $request->all();
        $branch = $this->resolveReportBranch($request);
        
        $page = new \App\Filament\App\Pages\ProfitLossReportPage();
        $page->data = $filters;
        $data = $page->getData();

        return Pdf::loadView('reports.profit-loss', compact('data', 'filters', 'branch'))
            ->download('profit-loss-'.now()->format('Y-m-d').'.pdf');
    }

    public function productProfitLoss(Request $request)
    {
        $filters = $request->all();
        $branch = $this->resolveReportBranch($request);
        
        $page = new \App\Filament\App\Pages\ProductProfitLossReportPage();
        $page->data = $filters;
        $reportData = $page->getData();

        return Pdf::loadView('reports.product-profit-loss', compact('reportData', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('product-profit-loss-'.now()->format('Y-m-d').'.pdf');
    }

    public function trendingProducts(Request $request)
    {
        $filters = $request->all();
        $branch = $this->resolveReportBranch($request);
        
        $page = new \App\Filament\App\Pages\TrendingProductsReportPage();
        $page->data = $filters;
        $reportData = $page->getData();

        return Pdf::loadView('reports.trending-products', compact('reportData', 'filters', 'branch'))
            ->setPaper('a4', 'landscape')
            ->download('trending-products-'.now()->format('Y-m-d').'.pdf');
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

    public function inventoryCountSheet(Request $request, InventoryCount $inventoryCount)
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

        return Pdf::loadView('reports.inventory-count-sheet', ['count' => $inventoryCount])
            ->setPaper('a4', 'landscape')
            ->download('count-sheet-'.$inventoryCount->count_number.'.pdf');
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
            'search' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch = $this->resolveReportBranch($request);
        $categoryId = $filters['category_id'] ?? null;
        $search = $filters['search'] ?? null;

        $data = \App\Models\Item::query()
            ->where('branch_id', $branchId)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->with(['category', 'uom', 'itemStockLevels'])
            ->orderBy('name')
            ->get()
            ->map(function ($item) use ($branchId) {
                // Sum qty across all department-level stock records for this branch
                // (excludes the null-dept aggregate row which may show 0)
                $qty = $item->itemStockLevels
                    ->where('branch_id', $branchId)
                    ->whereNotNull('department_id')
                    ->sum('qty_on_hand');
                $item->qty_on_hand = $qty;
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

    public function salesPriceAudit(Request $request)
    {
        $filters = $request->validate([
            'branch_id'     => ['nullable', 'integer'],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date'],
            'department_id' => ['nullable', 'integer'],
            'category_id'   => ['nullable', 'integer'],
        ]);

        $branchId = $this->resolveReportBranchId($request);
        $branch   = $this->resolveReportBranch($request);
        $from     = $filters['date_from'] ?? null;
        $to       = $filters['date_to'] ?? null;
        $deptId   = $filters['department_id'] ?? null;
        $catId    = $filters['category_id'] ?? null;
        $summaryOnly = $request->boolean('summary_only');

        $department = $deptId ? \App\Models\Department::find($deptId)?->name : null;
        $category   = $catId  ? \App\Models\ItemCategory::find($catId)?->name : null;

        $products = DB::table('sales_order_lines')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
            ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
            ->leftJoin('item_categories', 'item_categories.id', '=', 'items.category_id')
            ->whereNull('sales_orders.deleted_at')
            ->whereNull('sales_order_lines.deleted_at')
            ->when($branchId, fn ($q) => $q->where('sales_orders.branch_id', $branchId))
            ->when($deptId,   fn ($q) => $q->where('sales_orders.department_id', $deptId))
            ->when($catId,    fn ($q) => $q->where('items.category_id', $catId))
            ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
            ->select([
                'sales_order_lines.item_id',
                'items.name as item_name',
                'item_categories.name as category_name',
                'items.selling_price as standard_price',
                DB::raw('SUM(sales_order_lines.qty_sold) as total_qty_sold'),
                DB::raw('SUM(sales_order_lines.line_total) as actual_revenue'),
                DB::raw('ROUND(SUM(sales_order_lines.qty_sold * items.selling_price), 2) as expected_revenue'),
            ])
            ->groupBy('sales_order_lines.item_id', 'items.name', 'item_categories.name', 'items.selling_price')
            ->orderBy('items.name')
            ->get();

        foreach ($products as $product) {
            $currentQty = DB::table('item_stock_levels')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->sum('qty_on_hand');

            $netAfterEnd = (float) DB::table('stock_movements')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
                ->when($to, fn ($q) => $q->whereDate('moved_at', '>', $to))
                ->sum(DB::raw('CAST(qty_in AS SIGNED) - CAST(qty_out AS SIGNED)'));

            $qtyAtEnd = $currentQty - $netAfterEnd;

            $netInRange = (float) DB::table('stock_movements')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
                ->when($from, fn ($q) => $q->whereDate('moved_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('moved_at', '<=', $to))
                ->sum(DB::raw('CAST(qty_in AS SIGNED) - CAST(qty_out AS SIGNED)'));

            $inwardsDuringPeriod = (float) DB::table('stock_movements')
                ->where('item_id', $product->item_id)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($from, fn ($q) => $q->whereDate('moved_at', '>=', $from))
                ->when($to,   fn ($q) => $q->whereDate('moved_at', '<=', $to))
                ->sum('qty_in');

            $startStock = max(0, round($qtyAtEnd - $netInRange));
            
            $product->qty_available = $startStock + round($inwardsDuringPeriod);
            $product->qty_remaining = max(0, round($qtyAtEnd));
            $product->discrepancy   = round($product->actual_revenue - $product->expected_revenue, 2);

            if (!$summaryOnly) {
                $product->transactions = DB::table('sales_order_lines')
                    ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_lines.sales_order_id')
                    ->join('items', 'items.id', '=', 'sales_order_lines.item_id')
                    ->leftJoin('users', 'users.id', '=', 'sales_orders.served_by')
                    ->whereNull('sales_orders.deleted_at')
                    ->whereNull('sales_order_lines.deleted_at')
                    ->where('sales_order_lines.item_id', $product->item_id)
                    ->when($branchId, fn ($q) => $q->where('sales_orders.branch_id', $branchId))
                    ->when($deptId,   fn ($q) => $q->where('sales_orders.department_id', $deptId))
                    ->when($from, fn ($q) => $q->whereDate('sales_orders.sold_at', '>=', $from))
                    ->when($to,   fn ($q) => $q->whereDate('sales_orders.sold_at', '<=', $to))
                    ->select([
                        'sales_orders.sold_at',
                        'sales_orders.order_number',
                        'users.name as cashier',
                        'sales_order_lines.qty_sold',
                        'sales_order_lines.unit_price as used_price',
                        'items.selling_price as standard_price',
                        'sales_order_lines.line_total as actual_line_total',
                        DB::raw('ROUND(sales_order_lines.qty_sold * items.selling_price, 2) as expected_line_total'),
                        DB::raw('ROUND(sales_order_lines.line_total - (sales_order_lines.qty_sold * items.selling_price), 2) as line_discrepancy'),
                    ])
                    ->orderBy('sales_orders.sold_at', 'desc')
                    ->get();
            } else {
                $product->transactions = collect([]);
            }
        }

        $reportData = $products;

        return Pdf::loadView('reports.sales-price-audit', compact(
            'reportData', 'filters', 'branch', 'from', 'to', 'department', 'category', 'summaryOnly'
        ))
            ->setPaper('a4', 'landscape')
            ->download('sales-price-audit-'.now()->format('Y-m-d').'.pdf');
    }
}
