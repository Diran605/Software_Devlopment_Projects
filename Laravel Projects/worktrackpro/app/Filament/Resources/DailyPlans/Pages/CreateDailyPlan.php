<?php

namespace App\Filament\Resources\DailyPlans\Pages;

use App\Filament\Resources\DailyPlans\DailyPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyPlan extends CreateRecord
{
    protected static string $resource = DailyPlanResource::class;

    protected static bool $canCreateAnother = false;

    protected function afterCreate(): void
    {
        $plan = $this->record;
        
        if ($plan->user_id !== auth()->id()) {
            $plan->user->notify(new \App\Notifications\DailyPlanAssigned($plan));
        }
    }
}
