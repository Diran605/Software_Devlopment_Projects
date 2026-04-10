<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ExportService
{
    /**
     * Generate a PDF from a view and data.
     */
    public function exportToPdf(string $viewPath, array $data, string $filename = 'export.pdf')
    {
        $this->injectBranding($data);
        $pdf = Pdf::loadView($viewPath, $data);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Preview the PDF in the browser.
     */
    public function previewPdf(string $viewPath, array $data)
    {
        $this->injectBranding($data);
        $pdf = Pdf::loadView($viewPath, $data);
        
        return $pdf->stream();
    }

    /**
     * Inject organisation branding into the view data for standard letterhead support.
     */
    private function injectBranding(array &$data): void
    {
        $user = auth()->user();
        if ($user && $user->organisation_id) {
            $user->loadMissing('organisation');
            if (!isset($data['organisation'])) {
                $data['organisation'] = $user->organisation;
            }
        }
    }
}
