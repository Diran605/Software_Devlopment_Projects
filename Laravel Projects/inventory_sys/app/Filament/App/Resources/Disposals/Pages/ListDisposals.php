<?php

namespace App\Filament\App\Resources\Disposals\Pages;

use App\Filament\App\Resources\Disposals\DisposalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisposals extends ListRecords
{
    protected static string $resource = DisposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
