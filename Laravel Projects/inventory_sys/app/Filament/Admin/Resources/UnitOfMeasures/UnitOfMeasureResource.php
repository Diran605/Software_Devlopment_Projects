<?php

namespace App\Filament\Admin\Resources\UnitOfMeasures;

use App\Filament\Admin\Resources\UnitOfMeasures\Pages\CreateUnitOfMeasure;
use App\Filament\Admin\Resources\UnitOfMeasures\Pages\EditUnitOfMeasure;
use App\Filament\Admin\Resources\UnitOfMeasures\Pages\ListUnitOfMeasures;
use App\Filament\Admin\Resources\UnitOfMeasures\Pages\ViewUnitOfMeasure;
use App\Filament\Admin\Resources\UnitOfMeasures\Schemas\UnitOfMeasureForm;
use App\Filament\Admin\Resources\UnitOfMeasures\Tables\UnitOfMeasuresTable;
use App\Models\UnitOfMeasure;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitOfMeasureResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-scale';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catalogue';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function getNavigationLabel(): string
    {
        return 'Units of Measure';
    }

    public static function getLabel(): ?string
    {
        return 'Unit of Measure';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Units of Measure';
    }
protected static ?string $model = UnitOfMeasure::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UnitOfMeasureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitOfMeasuresTable::configure($table);
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
            'index' => ListUnitOfMeasures::route('/'),
            'create' => CreateUnitOfMeasure::route('/create'),
            'view' => ViewUnitOfMeasure::route('/{record}'),
            'edit' => EditUnitOfMeasure::route('/{record}/edit'),
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
