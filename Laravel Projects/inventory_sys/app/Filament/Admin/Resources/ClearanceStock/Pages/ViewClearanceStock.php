<?php

namespace App\Filament\Admin\Resources\ClearanceStock\Pages;

use App\Filament\Admin\Resources\ClearanceStock\ClearanceStockResource;
use App\Filament\Concerns\HasClearanceStockReversalAction;
use App\Services\ClearanceStockActionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewClearanceStock extends ViewRecord
{
    use HasClearanceStockReversalAction;

    protected static string $resource = ClearanceStockResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['item', 'department']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getInfolistContentComponent(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            static::makeClearanceStockReversalAction()
                ->successRedirectUrl(ClearanceStockResource::getUrl('index')),
            Action::make('donate')
                ->label('Donate')
                ->color('info')
                ->visible(fn () => $this->record->qty_remaining > 0 && auth()->user()->can('donate', $this->record))
                ->form([
                    TextInput::make('qty')
                        ->label('Qty to Donate')
                        ->numeric()
                        ->default(fn () => $this->record->qty_remaining)
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn () => $this->record->qty_remaining),
                    TextInput::make('recipient')
                        ->label('Recipient')
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data, ClearanceStockActionService $service): void {
                    $service->donate($this->record, $data);
                    $this->record->refresh();
                    Notification::make()->title('Donation recorded')->success()->send();
                }),
            Action::make('dispose')
                ->label('Dispose')
                ->color('danger')
                ->visible(fn () => $this->record->qty_remaining > 0 && auth()->user()->can('dispose', $this->record))
                ->form([
                    TextInput::make('qty')
                        ->label('Qty to Dispose')
                        ->numeric()
                        ->default(fn () => $this->record->qty_remaining)
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn () => $this->record->qty_remaining),
                    Select::make('reason')
                        ->label('Disposal Reason')
                        ->options([
                            'expired' => 'Expired',
                            'damaged' => 'Damaged',
                            'quality' => 'Quality Issue',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data, ClearanceStockActionService $service): void {
                    $service->dispose($this->record, $data);
                    $this->record->refresh();
                    Notification::make()->title('Disposal recorded')->success()->send();
                }),
        ];
    }
}
