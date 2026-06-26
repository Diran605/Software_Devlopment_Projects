<?php

namespace App\Filament\Concerns;

use App\Models\ClearanceStock;
use App\Services\ClearanceStockReversalService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

trait HasClearanceStockReversalAction
{
    protected static function makeClearanceStockReversalAction(): DeleteAction
    {
        return DeleteAction::make()
            ->label('Reverse to Stock')
            ->modalHeading('Reverse clearance stock')
            ->modalDescription('This returns the remaining clearance quantity back to normal batch inventory, restores branch stock levels, and removes this clearance record.')
            ->requiresConfirmation()
            ->visible(fn (ClearanceStock $record): bool => auth()->user()?->can('delete', $record) ?? false)
            ->action(function (ClearanceStock $record, ClearanceStockReversalService $service): void {
                $service->reverse($record);

                Notification::make()
                    ->title('Clearance stock reversed')
                    ->body('Remaining quantity was returned to normal inventory.')
                    ->success()
                    ->send();
            });
    }
}
