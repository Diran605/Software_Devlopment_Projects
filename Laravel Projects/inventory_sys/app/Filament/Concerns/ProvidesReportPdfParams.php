<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


trait ProvidesReportPdfParams
{
    public function exportPdf(string $routeName, array $extraParams = []): mixed
    {
        $this->form->getState();

        $params = array_merge($this->getPdfParams(), $extraParams);

        return redirect()->route($routeName, $params);
    }

    public function exportCsv(string $filename = "report")
    {
        $data = $this->getData();
        if ($data->isEmpty()) {
            return;
        }

        $firstRow = $data->first();
        if (is_array($firstRow)) {
            $headers = array_keys($firstRow);
        } else {
            $headers = array_keys(is_object($firstRow) && method_exists($firstRow, "toArray") ? $firstRow->toArray() : (array) $firstRow);
        }

        $callback = function () use ($data, $headers) {
            $file = fopen("php://output", "w");
            fputcsv($file, $headers);
            foreach ($data as $row) {
                $rowArray = is_array($row) ? $row : (is_object($row) && method_exists($row, "toArray") ? $row->toArray() : (array) $row);
                fputcsv($file, $rowArray);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename . "_" . date("Y-m-d_H-i-s") . ".csv", [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=" . $filename . "_" . date("Y-m-d_H-i-s") . ".csv",
        ]);
    }

    
    public function exportExcel(string $filename = "report")
    {
        $data = $this->getData();
        if ($data->isEmpty()) {
            return;
        }

        $firstRow = $data->first();
        if (is_array($firstRow)) {
            $headers = array_keys($firstRow);
        } else {
            $headers = array_keys(is_object($firstRow) && method_exists($firstRow, "toArray") ? $firstRow->toArray() : (array) $firstRow);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Write headers
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col++, 1], $header);
        }
        
        // Write data
        $rowNum = 2;
        foreach ($data as $row) {
            $rowArray = is_array($row) ? $row : (is_object($row) && method_exists($row, "toArray") ? $row->toArray() : (array) $row);
            $col = 1;
            foreach ($rowArray as $value) {
                $sheet->setCellValue([$col++, $rowNum], $value);
            }
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);
        
        $callback = function () use ($writer) {
            $file = fopen("php://output", "w");
            $writer->save($file);
            fclose($file);
        };

        return response()->streamDownload($callback, $filename . "_" . date("Y-m-d_H-i-s") . ".xlsx", [
            "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=" . $filename . "_" . date("Y-m-d_H-i-s") . ".xlsx",
        ]);
    }

    protected function getPdfParams(): array
    {
        $params = $this->data ?? [];
        $params["branch_id"] = $params["branch_id"] ?? Filament::getTenant()?->id;

        return array_filter($params, function ($value) {
            if (is_bool($value)) {
                return true;
            }
            if ($value === null || $value === "") {
                return false;
            }
            if (is_array($value) && $value === []) {
                return false;
            }
            return true;
        });
    }
}

