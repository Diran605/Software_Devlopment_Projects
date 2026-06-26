<?php

namespace App\Filament\App\Resources\Donations\Pages;

use App\Filament\App\Resources\Donations\DonationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewDonation extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DonationResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['branch', 'department', 'createdBy', 'lines.item', 'lines.batchInventory']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Section::make('Donation Lines')
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->record->lines()->with('item', 'batchInventory'))
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('batchInventory.batch_number')
                    ->label('Batch #')
                    ->placeholder('—'),
                TextColumn::make('batchInventory.expiry_date')
                    ->label('Expiry Date')
                    ->date('M d, Y')
                    ->placeholder('—'),
                TextColumn::make('qty_donated')
                    ->label('Qty Donated')
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('notes')
                    ->placeholder('—'),
            ])
            ->emptyStateHeading('No donation lines')
            ->emptyStateDescription('This donation has no line items.');
    }
}
