<?php

namespace App\Filament\App\Resources\GoodsReceivedNotes\Pages;

use App\Filament\App\Resources\GoodsReceivedNotes\GoodsReceivedNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewGoodsReceivedNote extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = GoodsReceivedNoteResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['grnLineItems.item']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Section::make('Received Lines')
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }

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

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->record->grnLineItems()->with('item'))
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('batch_number')
                    ->label('Batch #')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date('M d, Y')
                    ->placeholder('—'),
                TextColumn::make('qty_received')
                    ->label('Qty Received')
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money('xaf')
                    ->sortable(),
            ])
            ->emptyStateHeading('No received lines')
            ->emptyStateDescription('This GRN has no line items.');
    }
}
