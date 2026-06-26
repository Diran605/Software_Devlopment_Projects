<?php

namespace App\Filament\Admin\Resources\ClearanceItems;

use App\Filament\Admin\Resources\ClearanceItems\Pages\ListClearanceItems;
use App\Filament\Admin\Resources\ClearanceItems\Pages\ViewClearanceItem;
use App\Filament\Admin\Resources\ClearanceItems\Pages\EditClearanceItem;
use App\Filament\Admin\Resources\ClearanceItems\Schemas\ClearanceItemInfolist;
use App\Models\ClearanceItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClearanceItemResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-exclamation-circle';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Expiry Management';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationLabel(): string
    {
        return 'Flagged Items';
    }

    public static function getLabel(): ?string
    {
        return 'Clearance Item';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Clearance Items';
    }

    protected static ?string $model = ClearanceItem::class;
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Admin\Resources\ClearanceItems\Schemas\ClearanceItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClearanceItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClearanceItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClearanceItems::route('/'),
            'view' => ViewClearanceItem::route('/{record}'),
            'edit' => EditClearanceItem::route('/{record}/edit'),
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
