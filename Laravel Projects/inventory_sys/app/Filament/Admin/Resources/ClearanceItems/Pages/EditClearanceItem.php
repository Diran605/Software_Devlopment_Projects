<?php

namespace App\Filament\Admin\Resources\ClearanceItems\Pages;

use App\Filament\Admin\Resources\ClearanceItems\ClearanceItemResource;
use Filament\Resources\Pages\EditRecord;

class EditClearanceItem extends EditRecord
{
    protected static string $resource = ClearanceItemResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if ($this->record->approval_status !== 'pending') {
            abort(403, 'This clearance item can no longer be edited after approval.');
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
