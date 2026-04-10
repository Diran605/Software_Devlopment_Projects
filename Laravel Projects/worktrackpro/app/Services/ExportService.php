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
        $pdf = Pdf::loadView($viewPath, $data);
        
        return $pdf->download($filename);
    }

    /**
     * Preview the PDF in the browser.
     */
    public function previewPdf(string $viewPath, array $data)
    {
        $pdf = Pdf::loadView($viewPath, $data);
        
        return $pdf->stream();
    }
}
