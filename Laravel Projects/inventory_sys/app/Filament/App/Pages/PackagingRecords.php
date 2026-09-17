<?php

namespace App\Filament\App\Pages;

use App\Models\Item;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class PackagingRecords extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.app.pages.packaging-records';

    public static function getNavigationIcon(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Packaging Records';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view.packaging-records');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Item::query()->where('branch_id', Filament::getTenant()->id))
            ->columns([
                TextColumn::make('name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextInputColumn::make('packaging_notes')
                    ->label('Units per Package (Notes)')
                    ->searchable()
                    ->extraAttributes(['style' => 'min-width: 300px']),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([50, 100, 'all']);
    }
}

