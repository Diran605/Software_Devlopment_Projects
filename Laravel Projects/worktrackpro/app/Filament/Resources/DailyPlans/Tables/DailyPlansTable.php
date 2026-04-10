<?php

namespace App\Filament\Resources\DailyPlans\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DailyPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Worker')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('task_name')
                    ->label('Task')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('projectClient.name')
                    ->label('Project/Client')
                    ->placeholder('—'),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        'done' => 'success',
                        'pending' => 'warning',
                        'carried_over' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('expected_duration_minutes')
                    ->label('Est. Time')
                    ->suffix(' mins')
                    ->sortable(),
                TextColumn::make('assignedByUser.name')
                    ->label('Assigned By')
                    ->placeholder('Self'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'done' => 'Done',
                        'carried_over' => 'Carried Over',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
