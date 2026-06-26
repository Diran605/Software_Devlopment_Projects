<?php

namespace App\Filament\Admin\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('guard_name')
                    ->default('web')
                    ->readOnly(),
                Tabs::make('Permissions By Module')
                    ->tabs([
                        Tab::make('System')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->whereIn('name', [
                                        'view.branches', 'create.branches', 'edit.branches', 'delete.branches',
                                        'view.departments', 'create.departments', 'edit.departments', 'delete.departments',
                                        'view.users', 'create.users', 'edit.users', 'delete.users',
                                        'view.roles', 'create.roles', 'edit.roles', 'delete.roles',
                                    ]))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Catalogue')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.items')
                                          ->orWhere('name', 'like', '%.item-categories')
                                          ->orWhere('name', 'like', '%.unit-of-measures')
                                          ->orWhere('name', 'like', '%.packaging-types')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Parties')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.suppliers')
                                          ->orWhere('name', 'like', '%.customers')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Procurement')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where('name', 'like', '%.purchase-orders'))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Stock In')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.opening-stock')
                                          ->orWhere('name', 'like', '%.goods-received-notes')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Sales')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where('name', 'like', '%.sales-orders'))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Stock Control')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.stock-transfers')
                                          ->orWhere('name', 'like', '%.stock-movements')
                                          ->orWhere('name', 'like', '%.inventory-counts')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Expiry')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.clearance-manager')
                                          ->orWhere('name', 'like', '%.clearance-sales')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Write-Offs')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.disposals')
                                          ->orWhere('name', 'like', '%.donations')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Expenses')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.expenses')
                                          ->orWhere('name', 'like', '%.expense-categories')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                        Tab::make('Logs & Reports')
                            ->schema([
                                CheckboxList::make('permissions')
                                    ->relationship('permissions', 'name', modifyQueryUsing: fn ($query) => $query->where(fn ($q) => 
                                        $q->where('name', 'like', '%.audit-logs')
                                          ->orWhere('name', 'like', '%.deletion-logs')
                                          ->orWhere('name', 'like', '%.reports')
                                    ))
                                    ->columns(2)
                                    ->label(''),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
