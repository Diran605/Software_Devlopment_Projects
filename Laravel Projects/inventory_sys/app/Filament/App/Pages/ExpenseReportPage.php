<?php

namespace App\Filament\App\Pages;

use App\Filament\Concerns\ProvidesReportPdfParams;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ExpenseReportPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesReportPdfParams;

    protected string $view = 'filament.app.pages.reports.expense-report';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Reports';
    }

    public static function getNavigationSort(): ?int
    {
        return 8;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.reports');
    }

    public function getTitle(): string
    {
        return 'Expense Report';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'branch_id' => Filament::getTenant()?->id,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'category_id' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(Filament::getTenant() ? 3 : 4)
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(\App\Models\Branch::orderBy('name')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(! Filament::getTenant()),
                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->live(),
                        DatePicker::make('date_to')
                            ->label('To Date')
                            ->live(),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(function () {
                                $branchId = Filament::getTenant()?->id ?? ($this->data['branch_id'] ?? null);

                                return ExpenseCategory::query()
                                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function getReportData()
    {
        $branchId = Filament::getTenant()?->id ?? ($this->data['branch_id'] ?? null);
        $from = $this->data['date_from'] ?? null;
        $to = $this->data['date_to'] ?? null;
        $categoryId = $this->data['category_id'] ?? null;

        $rows = Expense::query()
            ->with(['category', 'department', 'createdBy'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderByDesc('expense_date')
            ->get();

        $categorySummary = Expense::query()
            ->leftJoin('expense_categories', 'expense_categories.id', '=', 'expenses.category_id')
            ->when($branchId, fn ($q) => $q->where('expenses.branch_id', $branchId))
            ->when($from, fn ($q) => $q->whereDate('expenses.expense_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('expenses.expense_date', '<=', $to))
            ->when($categoryId, fn ($q) => $q->where('expenses.category_id', $categoryId))
            ->selectRaw('COALESCE(expense_categories.name, "Uncategorized") as category_name')
            ->selectRaw('SUM(expenses.amount) as total_amount')
            ->selectRaw('COUNT(*) as expense_count')
            ->groupBy('expense_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'rows' => $rows,
            'total_amount' => $rows->sum('amount'),
            'expense_count' => $rows->count(),
            'category_summary' => $categorySummary,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->getReportData(),
            'pdfParams' => $this->getPdfParams(),
        ];
    }
}
