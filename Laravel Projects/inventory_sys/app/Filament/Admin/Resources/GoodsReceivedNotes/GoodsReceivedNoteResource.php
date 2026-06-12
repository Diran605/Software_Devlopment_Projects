<?php

namespace App\Filament\Admin\Resources\GoodsReceivedNotes;

use App\Filament\Admin\Resources\GoodsReceivedNotes\Pages\CreateGoodsReceivedNote;
use App\Filament\Admin\Resources\GoodsReceivedNotes\Pages\ListGoodsReceivedNotes;
use App\Filament\Admin\Resources\GoodsReceivedNotes\Pages\ViewGoodsReceivedNote;
use App\Filament\Admin\Resources\GoodsReceivedNotes\Schemas\GoodsReceivedNoteForm;
use App\Filament\Admin\Resources\GoodsReceivedNotes\Tables\GoodsReceivedNotesTable;
use App\Models\GoodsReceivedNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GoodsReceivedNoteResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-inbox-arrow-down';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Stock In';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Goods Received Notes';
    }

    public static function getLabel(): ?string
    {
        return 'Goods Received Note';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Goods Received Notes';
    }
protected static ?string $model = GoodsReceivedNote::class;

    protected static ?string $recordTitleAttribute = 'grn_number';

    public static function form(Schema $schema): Schema
    {
        return GoodsReceivedNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoodsReceivedNotesTable::configure($table);
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
            'index' => ListGoodsReceivedNotes::route('/'),
            'create' => CreateGoodsReceivedNote::route('/create'),
            'view' => ViewGoodsReceivedNote::route('/{record}'),
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
