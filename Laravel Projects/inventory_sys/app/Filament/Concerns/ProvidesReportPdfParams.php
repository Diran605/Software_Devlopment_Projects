<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

trait ProvidesReportPdfParams
{
    public function exportPdf(string $routeName): mixed
    {
        $this->form->getState();

        return redirect()->route($routeName, $this->getPdfParams());
    }

    protected function getPdfParams(): array
    {
        $params = $this->data ?? [];
        $params['branch_id'] = $params['branch_id'] ?? Filament::getTenant()?->id;

        return array_filter($params, function ($value) {
            if (is_bool($value)) {
                // Preserve boolean values (true/false)
                return true;
            }

            if ($value === null || $value === '') {
                return false;
            }

            if (is_array($value) && $value === []) {
                return false;
            }

            return true;
        });
    }
}
