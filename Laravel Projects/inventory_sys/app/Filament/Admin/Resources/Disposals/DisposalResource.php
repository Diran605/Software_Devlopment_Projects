<?php

namespace App\Filament\Admin\Resources\Disposals;

use App\Filament\Admin\Resources\Disposals\Pages\CreateDisposal;
use App\Filament\Admin\Resources\Disposals\Pages\EditDisposal;
use App\Filament\Admin\Resources\Disposals\Pages\ListDisposals;
use App\Filament\Admin\Resources\Disposals\Schemas\DisposalForm;
use App\Filament\Admin\Resources\Disposals\Tables\DisposalsTable;
use App\Models\Disposal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DisposalResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-trash';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Stock Control';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function getNavigationLabel(): string
    {
        return 'Disposals';
    }

    public static function getLabel(): ?string
    {
        return 'Disposal';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Disposals';
    }
protected static ?string $model = Disposal::class;

    protected static ?string $recordTitleAttribute = 'disposal_number';

    public static function form(Schema $schema): Schema
    {
        return DisposalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DisposalsTable::configure($table);
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
            'index' => ListDisposals::route('/'),
            'create' => CreateDisposal::route('/create'),
            'edit' => EditDisposal::route('/{record}/edit'),
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
