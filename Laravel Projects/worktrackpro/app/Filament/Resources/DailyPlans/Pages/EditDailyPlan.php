<?php

namespace App\Filament\Resources\DailyPlans\Pages;

use App\Filament\Resources\DailyPlans\DailyPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyPlan extends EditRecord
{
    protected static string $resource = DailyPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
