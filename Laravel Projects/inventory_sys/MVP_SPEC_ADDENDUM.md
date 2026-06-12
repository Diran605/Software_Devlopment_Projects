# Inventory Management System — MVP Spec Addendum

> **This document is an addition to the original MVP spec.**
> Read the original spec first. This addendum overrides or extends any section it touches.
> Do not rebuild what is already done — only implement what is new here.

---

## A1. Project Route Setup

When the Laravel project is created, `routes/web.php` contains a default welcome route.
Replace its contents with the following:

```php
<?php

use Illuminate\Support\Facades\Route;

// Redirect root to the branch operations panel
Route::get('/', function () {
    return redirect('/app');
});

// Printable sales order receipt (the only Blade view in this project)
Route::get('/receipts/sales/{order}', \App\Http\Controllers\ReceiptController::class)
    ->name('receipts.sales')
    ->middleware(['auth']);
```

No other routes are needed. Filament registers all its own routes automatically.

---

## A2. Two-Panel Architecture

The system uses **two completely separate Filament panels**:

| Panel | Path | Who Uses It | Data Scope |
|---|---|---|---|
| Admin Panel | `/admin` | Super Admin only | Global — all branches |
| App Panel | `/app` | All branch staff | Scoped to user's branch via multi-tenancy |

### Install both panels

```bash
php artisan make:filament-panel admin
php artisan make:filament-panel app
```

This generates:
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Providers/Filament/AppPanelProvider.php`

Register both providers in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\Filament\AppPanelProvider::class,
];
```

---

## A3. Admin Panel — Configuration

The admin panel is for the super-admin only. It sees all branches and all data globally.
No multi-tenancy. No branch scoping.

### `app/Providers/Filament/AdminPanelProvider.php`

```php
<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(['primary' => Color::Slate])
            ->brandName('IMS — System Admin')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');
    }
}
```

### Admin Panel — Authorization Gate

Only users with the `super-admin` role can access this panel.
Add this to `app/Providers/AppServiceProvider.php`:

```php
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('access-admin-panel', function ($user) {
        return $user->hasRole('super-admin');
    });
}
```

Then in `AdminPanelProvider`, add inside the `panel()` method:

```php
->authorizationMiddleware('can:access-admin-panel')
```

### Admin Panel — Navigation Groups & Resources

Place all admin resources in `app/Filament/Admin/Resources/`.

Navigation groups for the admin panel:

```
System          → Branches, Users, Roles & Permissions
Catalogue       → Items, Item Categories, Units of Measure, Packaging Types
Parties         → Suppliers, Customers
Logs            → Audit Logs, Deletion Logs
```

The admin panel does **not** include Sales, GRNs, POs, Transfers, or Stock.
Those are branch operations handled in the App Panel.

---

## A4. App Panel — Configuration with Multi-Tenancy

The app panel uses **Filament's built-in multi-tenancy** scoped to `Branch`.
Every logged-in user is associated with one or more branches.
Filament automatically appends `WHERE branch_id = {current_tenant_id}` to all queries.

### Configure the `Branch` model for tenancy

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Branch extends Model implements HasTenants
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'address', 'phone', 'is_active'];

    public function getTenants(Panel $panel): Collection
    {
        return $this->users; // branches the user belongs to
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->users()->whereKey(auth()->id())->exists();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'branch_user');
    }
}
```

### Configure the `User` model for tenancy

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasRoles, SoftDeletes;

    protected $fillable = ['branch_id', 'department_id', 'name', 'email', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    // Branches this user can access (for multi-tenancy)
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user');
    }

    // Default branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Filament — which tenants (branches) can this user access?
    public function getTenants(Panel $panel): Collection
    {
        return $this->branches;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->branches()->whereKey($tenant->getKey())->exists();
    }

    // Filament — can this user access a panel?
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole('super-admin');
        }
        if ($panel->getId() === 'app') {
            return $this->hasAnyRole(['branch-manager', 'inventory-manager', 'cashier', 'auditor'])
                && $this->is_active;
        }
        return false;
    }
}
```

### `app/Providers/Filament/AppPanelProvider.php`

```php
<?php

namespace App\Providers\Filament;

use App\Models\Branch;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->colors(['primary' => Color::Blue])
            ->brandName('IMS')
            ->tenant(Branch::class, slugAttribute: 'code')
            ->tenantMenuItems([
                // Shows branch switcher in the top nav when user has multiple branches
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web');
    }
}
```

### App Panel — Navigation Groups & Resources

Place all app resources in `app/Filament/App/Resources/`.

```
Catalogue       → Items (view only), Categories, Units of Measure, Packaging Types
Parties         → Suppliers, Customers
Procurement     → Purchase Orders
Stock In        → Opening Stock, Goods Received Notes
Sales           → Sales Orders
Stock Control   → Stock Transfers, Stock Movements (view only)
Logs            → Audit Logs, Deletion Logs
```

### Multi-tenancy automatic scoping

Because the App Panel uses `->tenant(Branch::class)`, Filament automatically:
- Scopes all resource queries to the current branch
- Adds `branch_id` to all create/save operations
- Shows a branch switcher in the top nav if the user belongs to multiple branches

**You do not need to manually add `->where('branch_id', ...)` anywhere in App Panel resources.**
Filament handles it entirely.

For models to participate in tenancy, add `TenantScope` or implement `HasTenant`.
The simplest approach — add this to every App Panel resource:

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withoutGlobalScopes([SoftDeletingScope::class]);
}
```

Filament's tenancy injects the branch scope automatically on top of this.

---

## A5. File & Folder Structure

After setup, the app directory should be organised as:

```
app/
  Filament/
    Admin/
      Pages/
        Dashboard.php
      Resources/
        BranchResource.php
        UserResource.php
        ItemResource.php
        ItemCategoryResource.php
        UnitOfMeasureResource.php
        PackagingTypeResource.php
        SupplierResource.php
        CustomerResource.php
        AuditLogResource.php
        DeletionLogResource.php
      Widgets/
        SystemStatsOverview.php
    App/
      Pages/
        Dashboard.php
      Resources/
        ItemResource.php              ← view only in app panel
        ItemCategoryResource.php
        UnitOfMeasureResource.php
        PackagingTypeResource.php
        SupplierResource.php
        CustomerResource.php
        PurchaseOrderResource.php
        OpeningStockResource.php
        GoodsReceivedNoteResource.php
        SalesOrderResource.php
        StockTransferResource.php
        StockMovementResource.php
        AuditLogResource.php
        DeletionLogResource.php
      Widgets/
        BranchStatsOverview.php
        LowStockAlert.php
        RecentSalesTable.php
  Http/
    Controllers/
      ReceiptController.php
  Models/
    ...all models
  Services/
    InventoryService.php
    BatchInventoryService.php
    OpeningStockService.php
    GoodsReceiptService.php
    SalesOrderService.php
    StockTransferService.php
    StockMovementService.php
    DeletionLogService.php
    AuditLogService.php
    NumberGeneratorService.php
  Exceptions/
    InsufficientStockException.php
    BatchConsumedException.php
    EditBlockedException.php

resources/
  views/
    welcome.blade.php       ← leave as is, unused
    receipts/
      sales.blade.php       ← the only custom Blade view

database/
  migrations/               ← all 12 migration files from original spec
  seeders/
    DatabaseSeeder.php
    RolesAndPermissionsSeeder.php
```

---

## A6. Dashboard Widgets

### Admin Panel Dashboard (`Filament/Admin/Widgets/SystemStatsOverview.php`)

Show system-wide stats using Filament's `StatsOverviewWidget`:

```
Total Branches      → Branch::count()
Total Users         → User::count()
Total Items         → Item::count()
Total Sales Today   → SalesOrder::whereDate('sold_at', today())->sum('grand_total')
```

### App Panel Dashboard (`Filament/App/Widgets/`)

**BranchStatsOverview** — 4 stat cards:
```
Today's Revenue     → SalesOrder (current branch, today) sum of grand_total
Today's Orders      → SalesOrder (current branch, today) count
Items Below Reorder → ItemStockLevel where qty_on_hand <= reorder_level, current branch
Pending Transfers   → StockTransfer where status = pending_approval, current branch
```

**LowStockAlert** — table widget listing items where `qty_on_hand <= reorder_level`, columns: item name, category, qty_on_hand, reorder_level, reorder_quantity. Red badge if qty_on_hand = 0.

**RecentSalesTable** — last 10 sales orders for current branch. Columns: order_number, customer, grand_total, served_by, sold_at.

---

## A7. Printable Sales Receipt

This is the **only Blade view** in the entire project. It is a simple print-optimised HTML page.

### `app/Http/Controllers/ReceiptController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __invoke(SalesOrder $order)
    {
        // Authorise: user must belong to same branch as the order
        abort_unless(
            auth()->user()->branches->contains($order->branch_id)
            || auth()->user()->hasRole('super-admin'),
            403
        );

        $order->load([
            'salesOrderLines.item.uom',
            'salesOrderLines.salesStockAllocations.batchInventory',
            'customer',
            'servedBy',
            'branch',
            'department',
        ]);

        return view('receipts.sales', compact('order'));
    }
}
```

### `resources/views/receipts/sales.blade.php`

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: monospace; font-size: 12px; width: 80mm; margin: 0 auto; padding: 8px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; margin: 2px 0; }
        .row-3 { display: grid; grid-template-columns: 1fr auto auto; gap: 4px; margin: 2px 0; }
        .right { text-align: right; }
        .total-row { font-weight: bold; font-size: 13px; }
        @media print {
            body { width: 80mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="center bold">{{ strtoupper($order->branch->name) }}</div>
    @if($order->branch->address)
    <div class="center">{{ $order->branch->address }}</div>
    @endif
    @if($order->branch->phone)
    <div class="center">Tel: {{ $order->branch->phone }}</div>
    @endif

    <div class="divider"></div>

    <div class="row">
        <span>Receipt #:</span>
        <span class="bold">{{ $order->order_number }}</span>
    </div>
    <div class="row">
        <span>Date:</span>
        <span>{{ $order->sold_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row">
        <span>Served by:</span>
        <span>{{ $order->servedBy->name }}</span>
    </div>
    @if($order->customer_name || $order->customer)
    <div class="row">
        <span>Customer:</span>
        <span>{{ $order->customer?->name ?? $order->customer_name }}</span>
    </div>
    @endif

    <div class="divider"></div>

    {{-- Column headers --}}
    <div class="row-3">
        <span class="bold">Item</span>
        <span class="bold right">Qty</span>
        <span class="bold right">Total</span>
    </div>
    <div class="divider"></div>

    {{-- Lines --}}
    @foreach($order->salesOrderLines as $line)
    <div class="row-3">
        <span>{{ $line->item->name }}</span>
        <span class="right">{{ $line->qty_sold }}</span>
        <span class="right">{{ number_format($line->line_total, 0) }}</span>
    </div>
    <div style="font-size:10px; color:#555; padding-left:4px;">
        {{ number_format($line->unit_price, 0) }} / {{ $line->item->uom->abbreviation }}
    </div>
    @endforeach

    <div class="divider"></div>

    {{-- Totals --}}
    @if($order->discount_total > 0)
    <div class="row">
        <span>Subtotal</span>
        <span>{{ number_format($order->subtotal, 0) }}</span>
    </div>
    <div class="row">
        <span>Discount</span>
        <span>- {{ number_format($order->discount_total, 0) }}</span>
    </div>
    @endif

    <div class="row total-row">
        <span>TOTAL</span>
        <span>{{ number_format($order->grand_total, 0) }} FCFA</span>
    </div>
    <div class="row">
        <span>Cash Tendered</span>
        <span>{{ number_format($order->amount_tendered, 0) }}</span>
    </div>
    @if($order->amount_tendered > $order->grand_total)
    <div class="row">
        <span>Change</span>
        <span>{{ number_format($order->amount_tendered - $order->grand_total, 0) }}</span>
    </div>
    @endif

    <div class="divider"></div>

    <div class="center" style="margin-top: 8px;">Thank you for your purchase!</div>
    <div class="center" style="font-size: 10px; margin-top: 4px; color: #555;">
        {{ $order->sold_at->format('d/m/Y H:i:s') }}
    </div>

    {{-- Print button — hidden when printing --}}
    <div class="no-print center" style="margin-top: 16px;">
        <button onclick="window.print()" style="padding: 8px 24px; cursor: pointer;">
            Print Receipt
        </button>
    </div>

</body>
</html>
```

### Opening the receipt from Filament

In `SalesOrderResource.php`, add a table action:

```php
use Filament\Tables\Actions\Action;

Action::make('print_receipt')
    ->label('Print Receipt')
    ->icon('heroicon-o-printer')
    ->url(fn (SalesOrder $record) => route('receipts.sales', $record))
    ->openUrlInNewTab()
    ->color('gray'),
```

---

## A8. Custom Exceptions

Create these in `app/Exceptions/`:

### `InsufficientStockException.php`
```php
<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(int $itemId, int $qtyRequested, int $qtyAvailable = 0)
    {
        parent::__construct(
            "Insufficient stock for item ID {$itemId}. "
            . "Requested: {$qtyRequested}, Available: {$qtyAvailable}."
        );
    }
}
```

### `BatchConsumedException.php`
```php
<?php

namespace App\Exceptions;

use Exception;

class BatchConsumedException extends Exception
{
    public function __construct(string $batchNumber)
    {
        parent::__construct(
            "Batch '{$batchNumber}' has already been partially or fully consumed and cannot be edited or deleted."
        );
    }
}
```

### `EditBlockedException.php`
```php
<?php

namespace App\Exceptions;

use Exception;

class EditBlockedException extends Exception
{
    public function __construct(string $reason = 'This record cannot be edited.')
    {
        parent::__construct($reason);
    }
}
```

---

## A9. First-Run Checklist for the Agent

After the full build, run these in order:

```bash
# 1. Install dependencies
composer install

# 2. Copy env and generate key
cp .env.example .env
php artisan key:generate

# 3. Configure .env
# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD
# Set APP_TIMEZONE to Africa/Douala
# Set SESSION_DRIVER=database
# Set QUEUE_CONNECTION=sync

# 4. Run migrations
php artisan migrate

# 5. Seed roles and permissions
php artisan db:seed

# 6. Create the super admin user
php artisan make:filament-user
# Enter name, email, password when prompted

# 7. Assign super-admin role
php artisan tinker
>>> \App\Models\User::where('email', 'your@email.com')->first()->assignRole('super-admin');

# 8. Create a branch (in admin panel or tinker)
>>> \App\Models\Branch::create(['name' => 'Main Branch', 'code' => 'HQ', 'is_active' => true]);

# 9. Assign the super-admin user to the branch
>>> $user = \App\Models\User::first();
>>> $branch = \App\Models\Branch::first();
>>> $user->branches()->attach($branch->id);

# 10. Visit the panels
# Admin: http://localhost/admin
# Branch ops: http://localhost/app
```

---

## A10. What Does NOT Change From the Original Spec

Everything in the original spec still applies exactly as written:

- All 12 migration files — unchanged
- All seeders — unchanged
- All service classes and their pseudocode — unchanged
- All business logic rules (FIFO, deletion flow, edit guards) — unchanged
- All validation rules — unchanged
- All document number formats — unchanged
- Phase 2 exclusion list — unchanged

The only things this addendum adds are:
- Two Filament panels instead of one
- Multi-tenancy on the App Panel
- The route file setup
- The folder structure for Filament resources
- Dashboard widgets
- The printable receipt Blade view + controller
- Custom exceptions
- The first-run checklist
