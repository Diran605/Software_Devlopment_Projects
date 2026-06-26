<?php

namespace App\Filament\App\Resources\ClearanceStock;

use App\Filament\App\Resources\ClearanceStock\Pages\ListClearanceStock;
use App\Filament\App\Resources\ClearanceStock\Pages\ViewClearanceStock;
use App\Filament\App\Resources\ClearanceStock\Schemas\ClearanceStockInfolist;
use App\Filament\App\Resources\ClearanceStock\Tables\ClearanceStockTable;
use App\Models\ClearanceStock;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClearanceStockResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Expiry Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Clearance Stock';
    }

    public static function getLabel(): ?string
    {
        return 'Clearance Stock';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Clearance Stock';
    }

    protected static ?string $model = ClearanceStock::class;
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClearanceStockInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClearanceStockTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClearanceStock::route('/'),
            'view' => ViewClearanceStock::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getNavigationItems(): array
    {
        try {
            $children = [];
            $defaultItems = parent::getNavigationItems();
            if (!empty($defaultItems)) {
                $listItem = $defaultItems[0];
                $listItem->label('List ' . static::getNavigationLabel());
                $listItem->icon('heroicon-o-list-bullet');
                $children[] = $listItem;
            }
            if (empty($children)) {
                return [];
            }
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
