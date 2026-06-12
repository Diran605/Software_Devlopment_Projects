<?php

namespace App\Filament\Admin\Resources\ClearanceRules\Pages;

use App\Filament\Admin\Resources\ClearanceRules\ClearanceRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditClearanceRule extends EditRecord
{
    protected static string $resource = ClearanceRuleResource::class;

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
