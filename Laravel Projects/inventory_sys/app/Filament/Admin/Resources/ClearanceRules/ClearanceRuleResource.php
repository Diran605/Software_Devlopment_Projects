<?php

namespace App\Filament\Admin\Resources\ClearanceRules;

use App\Filament\Admin\Resources\ClearanceRules\Pages\CreateClearanceRule;
use App\Filament\Admin\Resources\ClearanceRules\Pages\EditClearanceRule;
use App\Filament\Admin\Resources\ClearanceRules\Pages\ListClearanceRules;
use App\Filament\Admin\Resources\ClearanceRules\Pages\ViewClearanceRule;
use App\Filament\Admin\Resources\ClearanceRules\Schemas\ClearanceRuleForm;
use App\Filament\Admin\Resources\ClearanceRules\Schemas\ClearanceRuleInfolist;
use App\Filament\Admin\Resources\ClearanceRules\Tables\ClearanceRulesTable;
use App\Models\ClearanceRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClearanceRuleResource extends Resource
{
    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-tag';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Sales';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationLabel(): string
    {
        return 'Clearance Rules';
    }

    public static function getLabel(): ?string
    {
        return 'Clearance Rule';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Clearance Rules';
    }
protected static ?string $model = ClearanceRule::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ClearanceRuleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClearanceRuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClearanceRulesTable::configure($table);
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
            'index' => ListClearanceRules::route('/'),
            'create' => CreateClearanceRule::route('/create'),
            'view' => ViewClearanceRule::route('/{record}'),
            'edit' => EditClearanceRule::route('/{record}/edit'),
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
