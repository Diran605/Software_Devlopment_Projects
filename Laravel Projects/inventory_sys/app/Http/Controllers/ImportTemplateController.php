<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ImportTemplateController extends Controller
{
    /**
     * Download the Items import Excel template.
     */
    public function items()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Items');

        // ── Headers ──────────────────────────────────────────────────────────
        $headers = [
            'name'              => 'Name *',
            'description'       => 'Description',
            'category'          => 'Category Name',
            'uom'               => 'UOM (Name or Abbreviation)',
            'packaging_type'    => 'Packaging Type',
            'unit_cost'         => 'Cost Price (FCFA) *',
            'min_selling_price' => 'Min Selling Price (FCFA) *',
            'selling_price'     => 'Selling Price (FCFA) *',
            'reorder_level'     => 'Reorder Level',
            'reorder_quantity'  => 'Reorder Quantity',
            'is_packaged'       => 'Is Packaged (TRUE/FALSE)',
            'is_active'         => 'Is Active (TRUE/FALSE)',
            'branch'            => 'Branch Name',
        ];

        // ── Sample rows ───────────────────────────────────────────────────────
        $examples = [
            [
                'Paracetamol 500mg', 'Pain relief tablet', 'Pharmaceuticals',
                'Box', 'Blister Pack', 500, 700, 900, 50, 100, 'TRUE', 'TRUE', 'Main Branch',
            ],
            [
                'Amoxicillin 250mg', 'Antibiotic capsule', 'Pharmaceuticals',
                'Box', '', 800, 1200, 1500, 20, 50, 'FALSE', 'TRUE', 'Main Branch',
            ],
        ];

        $this->buildSheet($sheet, array_values($headers), $examples);

        // ── Instructions sheet ───────────────────────────────────────────────
        $this->addInstructionsSheet($spreadsheet, [
            '* Required columns must not be left blank.',
            'Category, UOM, Branch must match EXACTLY what is already in the system.',
            'is_packaged and is_active accept: TRUE or FALSE (case-insensitive).',
            'Cost Price and Selling Prices must be plain numbers (no currency symbol).',
            'Leave optional columns blank if not applicable.',
        ]);

        return $this->download($spreadsheet, 'items_import_template.xlsx');
    }

    /**
     * Download the Opening Stock import Excel template.
     */
    public function openingStock()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Opening Stock');

        $headers = [
            'item'         => 'Item Name *',
            'batch_number' => 'Batch Number *',
            'expiry_date'  => 'Expiry Date (YYYY-MM-DD)',
            'qty_on_hand'  => 'Quantity *',
            'unit_cost'    => 'Unit Cost (FCFA) *',
            'branch'       => 'Branch Name',
            'department'   => 'Department Name',
            'notes'        => 'Notes',
        ];

        $examples = [
            ['Paracetamol 500mg', 'BATCH-001', '2025-12-31', 200, 500, 'Main Branch', 'Pharmacy', 'Opening balance'],
            ['Amoxicillin 250mg', 'BATCH-002', '2026-06-30', 100, 800, 'Main Branch', '',         'Opening balance'],
        ];

        $this->buildSheet($sheet, array_values($headers), $examples);

        $this->addInstructionsSheet($spreadsheet, [
            '* Required columns must not be left blank.',
            'Item Name must exactly match the item name already in the system.',
            'Batch Number is required and must be unique per item per branch.',
            'Expiry Date format: YYYY-MM-DD (e.g. 2025-12-31). Leave blank if not applicable.',
            'Quantity must be a whole number.',
            'Unit Cost must be a plain number (no currency symbol).',
            'Branch and Department must match exactly what is in the system.',
        ]);

        return $this->download($spreadsheet, 'opening_stock_import_template.xlsx');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function buildSheet($sheet, array $headers, array $examples): void
    {
        $colCount = count($headers);

        // Write headers in row 1
        foreach ($headers as $i => $label) {
            $col  = $i + 1;
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
            $sheet->setCellValue($cell, $label);
        }

        // Style header row
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'FFFFFF'],
                ],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // Write example rows
        foreach ($examples as $rowIdx => $row) {
            $excelRow = $rowIdx + 2;
            foreach ($row as $colIdx => $value) {
                $col  = $colIdx + 1;
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $excelRow;
                $sheet->setCellValue($cell, $value);
            }
            // Light blue alternating rows
            $bgColor = ($rowIdx % 2 === 0) ? 'EFF6FF' : 'DBEAFE';
            $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            ]);
        }

        // Auto-fit column widths
        foreach (range(1, $colCount) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        // Freeze the header row
        $sheet->freezePane('A2');
    }

    private function addInstructionsSheet(Spreadsheet $spreadsheet, array $notes): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Instructions');
        $sheet->setCellValue('A1', 'How to fill this template');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E40AF']],
        ]);

        foreach ($notes as $i => $note) {
            $row = $i + 3;
            $sheet->setCellValue("A{$row}", ($i + 1) . '. ' . $note);
        }

        $sheet->getColumnDimension('A')->setWidth(80);
        $sheet->getStyle('A3:A' . (count($notes) + 2))->applyFromArray([
            'alignment' => ['wrapText' => true],
        ]);

        $spreadsheet->setActiveSheetIndex(0);
    }

    private function download(Spreadsheet $spreadsheet, string $filename)
    {
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
