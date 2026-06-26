<?php

namespace App\Filament\Admin\Resources\Disposals\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DisposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('branch_id')
                    ->required()
                    ->numeric(),
                TextInput::make('department_id')
                    ->numeric(),
                TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                TextInput::make('disposal_number')
                    ->required(),
                Select::make('reason')
                    ->options([
                        'damage' => 'Damaged',
                        'expired' => 'Expired',
                        'obsolescence' => 'Quality Issue / Obsolescence',
                        'other' => 'Other',
                    ])
                    ->required(),
                TextInput::make('disposal_method')
                    ->label('Disposal Method'),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('disposed_at')
                    ->required(),
            ]);
    }
}
