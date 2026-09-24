<?php
$f = "app/Http/Controllers/ReportPdfController.php";
$c = file_get_contents($f);
$method = <<<EOT

    public function detailedSales(Request \$request)
    {
        \$page = new \App\Filament\App\Pages\DetailedSalesReportPage();
        \$page->data = \$request->all();
        
        \$branchId = \$request->input("branch_id");
        \$branchName = \$this->getBranchName(\$branchId);

        \$reportData = \$page->getReportData();
        
        \$pdf = Pdf::loadView("reports.detailed-sales", [
            "reportData" => \$reportData,
            "branchName" => \$branchName,
            "from" => \$reportData["date_from"],
            "to" => \$reportData["date_to"],
        ])->setPaper("a4", "landscape");
        
        return \$pdf->stream("detailed-sales-report.pdf");
    }
EOT;
$c = preg_replace("/\}\s*$/", $method . "\n}", $c);
file_put_contents($f, $c);

