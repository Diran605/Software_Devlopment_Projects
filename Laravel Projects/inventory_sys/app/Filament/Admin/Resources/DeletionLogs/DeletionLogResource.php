<?php

namespace App\Filament\Admin\Resources\DeletionLogs;

use App\Filament\Admin\Resources\DeletionLogs\Pages\ListDeletionLogs;
use App\Filament\Admin\Resources\DeletionLogs\Pages\ViewDeletionLog;
use App\Filament\Admin\Resources\DeletionLogs\Schemas\DeletionLogForm;
use App\Filament\Admin\Resources\DeletionLogs\Tables\DeletionLogsTable;
use App\Models\DeletionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeletionLogResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-trash';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Logs & Security';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Deletion Logs';
    }

    public static function getLabel(): ?string
    {
        return 'Deletion Log';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Deletion Logs';
    }
protected static ?string $model = DeletionLog::class;

    public static function form(Schema $schema): Schema
    {
        return DeletionLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeletionLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeletionLogs::route('/'),
            'view'  => ViewDeletionLog::route('/{record}'),
        ];
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
