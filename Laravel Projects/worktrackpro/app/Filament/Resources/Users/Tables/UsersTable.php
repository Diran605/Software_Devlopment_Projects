<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', Str::title($state)))
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'indigo',
                        'admin' => 'info', // Filament uses info for blue
                        default => 'success', // Filament uses success for green/teal
                    })
                    ->searchable(),
                TextColumn::make('department.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('organisation.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('export_pdf')
                    ->label('Productivity Report (PDF)')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('info')
                    ->action(function () {
                        $admin = auth()->user();
                        $statsService = app(\App\Services\StatsService::class);
                        $report = $statsService->getWorkerProductivityReport(
                            $admin, 
                            \Carbon\Carbon::now()->startOfWeek(), 
                            \Carbon\Carbon::now()->endOfWeek()
                        );
                        
                        return app(\App\Services\ExportService::class)->exportToPdf('exports.team', [
                            'report' => $report,
                        ], 'productivity-report.pdf');
                    })
            ]);
    }
}
