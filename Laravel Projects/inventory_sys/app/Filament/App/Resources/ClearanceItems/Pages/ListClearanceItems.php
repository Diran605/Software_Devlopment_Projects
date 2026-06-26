<?php

namespace App\Filament\App\Resources\ClearanceItems\Pages;

use App\Filament\App\Resources\ClearanceItems\ClearanceItemResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListClearanceItems extends ListRecords
{
    protected static string $resource = ClearanceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runScan')
                ->label('Run Clearance Scan')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()->can('create.clearance-manager'))
                ->requiresConfirmation()
                ->modalHeading('Run clearance scan now?')
                ->modalDescription('This checks all batches with expiry dates against your active clearance rules and flags matching items.')
                ->action(function (): void {
                    Artisan::call('clearance:scan');

                    Notification::make()
                        ->title('Clearance scan completed')
                        ->body(trim(Artisan::output()) ?: 'Scan finished. Refresh the list to see flagged items.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
