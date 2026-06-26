<?php

namespace App\Filament\App\Resources\ExpenseCategories;

use App\Filament\App\Resources\ExpenseCategories\Pages\CreateExpenseCategory;
use App\Filament\App\Resources\ExpenseCategories\Pages\EditExpenseCategory;
use App\Filament\App\Resources\ExpenseCategories\Pages\ListExpenseCategories;
use App\Filament\App\Resources\ExpenseCategories\Pages\ViewExpenseCategory;
use App\Filament\App\Resources\ExpenseCategories\Schemas\ExpenseCategoryForm;
use App\Filament\App\Resources\ExpenseCategories\Tables\ExpenseCategoriesTable;
use App\Models\ExpenseCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseCategoryResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-rectangle-group';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Expenses';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Expense Categories';
    }

    public static function getLabel(): ?string
    {
        return 'Expense Category';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Expense Categories';
    }
protected static ?string $model = ExpenseCategory::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ExpenseCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenseCategories::route('/'),
            'create' => CreateExpenseCategory::route('/create'),
            'view' => ViewExpenseCategory::route('/{record}'),
            'edit' => EditExpenseCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationItems(): array
    {
        try {
            $children = [];

            // "List Xxx" child — reuse the default item Filament builds
            $defaultItems = parent::getNavigationItems();
            if (!empty($defaultItems)) {
                $listItem = $defaultItems[0];
                $listItem->label('List ' . static::getNavigationLabel());
                $listItem->icon('heroicon-o-list-bullet');
                $children[] = $listItem;
            }

            // "Create Xxx" child — only shown when the resource has a create page
            if (in_array('create', array_keys(static::getPages()))) {
                $children[] = \Filament\Navigation\NavigationItem::make(
                    'Create ' . \Illuminate\Support\Str::title(\Illuminate\Support\Str::singular(static::getModelLabel()))
                )
                    ->icon('heroicon-o-plus-circle')
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.create'))
                    ->sort(fn () => (static::getNavigationSort() ?? 0) + 1)
                    ->url(fn () => static::getUrl('create'))
                    ->visible(fn () => static::canCreate());
            }

            if (empty($children)) {
                return [];
            }

            // Parent item — the resource name shown as a collapsible group entry
            return [
                \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                    ->group(static::getNavigationGroup())
                    ->icon(static::getNavigationIcon() ?? 'heroicon-o-squares-2x2')
                    ->sort(static::getNavigationSort())
                    ->childItems($children),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
