<?php

namespace App\Filament\Admin\Pages;

use App\Models\Item;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class PackagingRecords extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.packaging-records';

    public static function getNavigationIcon(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Inventory Management';
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
            ->query(Item::query())
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->badge(),
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
            ->filters([
                SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Branch'),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated([50, 100, 'all']);
    }
}

