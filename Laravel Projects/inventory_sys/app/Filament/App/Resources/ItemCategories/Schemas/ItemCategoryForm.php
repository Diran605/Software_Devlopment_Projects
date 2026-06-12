<?php

namespace App\Filament\App\Resources\ItemCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

class ItemCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->rows(3),
                Hidden::make('branch_id')
                    ->default(fn () => Filament::getTenant()?->id),
            ]);
    }
}
