<?php

namespace App\Filament\App\Resources\Disposals\Pages;

use App\Filament\App\Resources\Disposals\DisposalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDisposal extends EditRecord
{
    protected static string $resource = DisposalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
