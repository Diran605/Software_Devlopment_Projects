<?php

namespace App\Filament\Admin\Resources\Disposals\Pages;

use App\Filament\Admin\Resources\Disposals\DisposalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDisposal extends CreateRecord
{
    protected static string $resource = DisposalResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
