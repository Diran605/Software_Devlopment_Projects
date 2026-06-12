<?php

namespace App\Filament\App\Resources\Donations\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;

class DonationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('donation_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('recipient')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('donated_at')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable(),
                TextColumn::make('lines_count')
                    ->counts('lines')
                    ->label('Items'),
            ])
            ->defaultSort('donated_at', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No donations yet')
            ->emptyStateDescription('Record your first donation.');
    }
}
