<?php

namespace App\Filament\Admin\Resources\ClearanceRules\Pages;

use App\Filament\Admin\Resources\ClearanceRules\ClearanceRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClearanceRule extends CreateRecord
{
    protected static string $resource = ClearanceRuleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
