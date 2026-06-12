<?php

namespace App\Filament\Admin\Resources\GoodsReceivedNotes\Pages;

use App\Filament\Admin\Resources\GoodsReceivedNotes\GoodsReceivedNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceivedNote extends ViewRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Goods Received Note')
                ->modalDescription('Are you sure you want to delete this GRN? This action will reverse all associated inventory additions and is irreversible.')
                ->form([
                    TextInput::make('reason')
                        ->label('Reason for Deletion')
                        ->required()
                        ->minLength(10)
                        ->placeholder('Specify why you are deleting this GRN (min 10 characters).'),
                ])
                ->action(function ($record, array $data) {
                    try {
                        app(\App\Services\GoodsReceiptService::class)->delete($record, $data['reason']);
                        \Filament\Notifications\Notification::make()
                            ->title('GRN Deleted Successfully')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error Deleting GRN')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
