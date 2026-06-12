<?php

namespace App\Filament\App\Resources\AuditLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Time'),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('User'),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'posted' => 'info',
                        'cancelled' => 'danger',
                        'received' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('auditable_type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable()
                    ->label('Record Type'),
                TextColumn::make('auditable_id')
                    ->sortable()
                    ->label('Record ID'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'posted' => 'Posted',
                        'cancelled' => 'Cancelled',
                        'received' => 'Received',
                    ])
                    ->multiple(),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('User')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No audit logs yet')
            ->emptyStateDescription('Activity will be logged here.');
    }
}
