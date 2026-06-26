<?php

namespace App\Filament\Admin\Resources\SalesOrders\Pages;

use App\Filament\Admin\Resources\SalesOrders\SalesOrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewSalesOrder extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = SalesOrderResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['salesOrderLines.item']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Section::make('Order Lines')
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Sales Order')
                ->modalDescription('Are you sure you want to delete this Sales Order? All allocated stock will be returned to inventory and this action cannot be undone.')
                ->form([
                    TextInput::make('reason')
                        ->label('Reason for Deletion')
                        ->required()
                        ->minLength(10)
                        ->placeholder('Specify why you are deleting this Sales Order (min 10 characters).'),
                ])
                ->action(function ($record, array $data) {
                    try {
                        app(\App\Services\SalesOrderService::class)->delete($record, $data['reason']);
                        \Filament\Notifications\Notification::make()
                            ->title('Sales Order Deleted')
                            ->success()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error Deleting Sales Order')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
            Action::make('print')
                ->label('Print Receipt')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->openUrlInNewTab()
                ->url(fn ($record) => route('receipts.sales', ['order' => $record->id])),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->record->salesOrderLines()->with('item'))
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('qty_sold')
                    ->label('Qty Sold')
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('gross_profit')
                    ->label('Gross Profit')
                    ->money('xaf')
                    ->sortable(),
            ])
            ->emptyStateHeading('No order lines')
            ->emptyStateDescription('This sales order has no line items.');
    }
}
