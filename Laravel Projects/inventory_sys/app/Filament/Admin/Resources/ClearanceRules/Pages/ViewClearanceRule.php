<?php

namespace App\Filament\Admin\Resources\ClearanceRules\Pages;

use App\Filament\Admin\Resources\ClearanceRules\ClearanceRuleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewClearanceRule extends ViewRecord
{
    protected static string $resource = ClearanceRuleResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->record->load('branch');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([$this->getInfolistContentComponent()]);
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
