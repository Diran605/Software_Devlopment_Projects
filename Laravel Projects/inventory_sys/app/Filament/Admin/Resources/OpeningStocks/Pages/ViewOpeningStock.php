<?php

namespace App\Filament\Admin\Resources\OpeningStocks\Pages;

use App\Filament\Admin\Resources\OpeningStocks\OpeningStockResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewOpeningStock extends ViewRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = OpeningStockResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load(['branch', 'department', 'postedBy', 'openingStockLines.item']);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getInfolistContentComponent(),
                Section::make('Stock Lines')
                    ->schema([
                        EmbeddedTable::make(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->record->openingStockLines()->with('item'))
            ->columns([
                TextColumn::make('item.name')
                    ->label('Item')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('item.sku')
                    ->label('SKU')
                    ->placeholder('—'),
                TextColumn::make('batch_number')
                    ->label('Batch #')
                    ->searchable(),
                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->color(fn ($record) => $record->expiry_date?->isPast() ? 'danger' : ($record->expiry_date && $record->expiry_date->diffInDays(now()) <= 30 ? 'warning' : 'gray')),
                TextColumn::make('qty_on_hand')
                    ->label('Qty On Hand')
                    ->sortable(),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('xaf')
                    ->sortable(),
                TextColumn::make('line_total')
                    ->label('Line Total')
                    ->money('xaf')
                    ->state(fn ($record) => $record->qty_on_hand * $record->unit_cost),
                IconColumn::make('is_consumed')
                    ->label('Consumed')
                    ->boolean(),
                TextColumn::make('edit_count')
                    ->label('Edits')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('edited_at')
                    ->label('Last Edited')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('No stock lines')
            ->emptyStateDescription('This opening stock entry has no line items.');
    }
}
