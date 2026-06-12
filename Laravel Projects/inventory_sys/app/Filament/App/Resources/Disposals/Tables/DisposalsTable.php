<?php

namespace App\Filament\App\Resources\Disposals\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;

class DisposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('disposal_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('reason')
                    ->limit(40),
                TextColumn::make('disposed_at')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable(),
                TextColumn::make('lines_count')
                    ->counts('lines')
                    ->label('Items'),
            ])
            ->defaultSort('disposed_at', 'desc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No disposals yet')
            ->emptyStateDescription('Record your first disposal.');
    }
}
