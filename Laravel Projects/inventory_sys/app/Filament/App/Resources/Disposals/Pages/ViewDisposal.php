<?php

namespace App\Filament\App\Resources\Disposals\Pages;

use App\Filament\App\Resources\Disposals\DisposalResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewDisposal extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DisposalResource::class;

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
                Section::make('Disposal Lines')
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
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
                TextColumn::make('qty_disposed')
                    ->label('Qty Disposed')
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('total_value')
                    ->label('Total Loss')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('notes')
                    ->placeholder('—'),
            ])
            ->emptyStateHeading('No disposal lines')
            ->emptyStateDescription('This disposal has no line items.');
    }
}
