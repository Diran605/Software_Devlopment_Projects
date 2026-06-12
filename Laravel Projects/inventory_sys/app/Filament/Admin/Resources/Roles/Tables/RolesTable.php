<?php

namespace App\Filament\Admin\Resources\Roles\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guard_name')
                    ->sortable(),
                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No roles yet')
            ->emptyStateDescription('Create your first role.');
    }
}
