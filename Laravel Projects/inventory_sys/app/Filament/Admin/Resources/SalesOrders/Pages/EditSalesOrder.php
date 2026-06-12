<?php

namespace App\Filament\Admin\Resources\SalesOrders\Pages;

use App\Filament\Admin\Resources\SalesOrders\SalesOrderResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['salesOrderLines'] = $this->record->salesOrderLines->map(function ($line) {
            $marginStatus = 'normal';
            if ($line->is_negative_margin) {
                $marginStatus = 'negative';
            } elseif ($line->is_low_margin) {
                $marginStatus = 'low';
            }
            return [
                'id' => $line->id,
                'item_id' => $line->item_id,
                'batch_inventory_id' => $line->batch_inventory_id,
                'entry_mode' => $line->entry_mode,
                'packaging_type_id' => $line->packaging_type_id,
                'pack_quantity' => $line->pack_quantity,
                'units_per_pack' => $line->units_per_pack,
                'qty_sold' => $line->qty_sold,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
                'gross_profit' => $line->gross_profit,
                'margin_status' => $marginStatus,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $incomingLines = $data['salesOrderLines'] ?? [];
        unset($data['salesOrderLines']);

        $record->fill($data);
        $record->save();

        $dbLines = $record->salesOrderLines()->get()->keyBy('id');
        $incomingLineIds = collect($incomingLines)->pluck('id')->filter()->toArray();

        $service = app(\App\Services\SalesOrderService::class);

        // 1. Delete lines no longer present
        foreach ($dbLines as $id => $dbLine) {
            if (!in_array($id, $incomingLineIds)) {
                $service->deleteLine($dbLine);
            }
        }

        // 2. Add / Edit lines
        foreach ($incomingLines as $incomingLine) {
            if (isset($incomingLine['id']) && $dbLines->has($incomingLine['id'])) {
                $dbLine = $dbLines->get($incomingLine['id']);
                $qtyChanged = intval($incomingLine['qty_sold']) !== intval($dbLine->qty_sold);
                $priceChanged = floatval($incomingLine['unit_price']) !== floatval($dbLine->unit_price);
                
                if ($qtyChanged || $priceChanged) {
                    $service->editLine($dbLine, intval($incomingLine['qty_sold']), floatval($incomingLine['unit_price']));
                }
            } else {
                // New line in existing order
                $line = new \App\Models\SalesOrderLine($incomingLine);
                $line->line_total = $line->qty_sold * $line->unit_price;
                $record->salesOrderLines()->save($line);

                $allocations = app(\App\Services\BatchInventoryService::class)->allocateStock($line);

                $lineCost = 0;
                foreach ($allocations as $alloc) {
                    $lineCost += $alloc['qty_allocated'] * $alloc['unit_cost'];
                    $line->salesStockAllocations()->create([
                        'batch_inventory_id' => $alloc['batch_inventory_id'],
                        'qty_allocated' => $alloc['qty_allocated'],
                        'unit_cost' => $alloc['unit_cost'],
                    ]);
                }

                $line->unit_cost = $allocations[0]['unit_cost'] ?? 0;
                $line->line_cost = $lineCost;
                $line->gross_profit = $line->line_total - $lineCost;
                $line->is_low_margin = $line->gross_profit < ($line->line_total * 0.2);
                $line->is_negative_margin = $line->gross_profit < 0;
                $line->save();

                app(\App\Services\InventoryService::class)->updateStockLevel(
                    branchId: $record->branch_id,
                    departmentId: $record->department_id,
                    itemId: $line->item_id,
                    qtyChange: -$line->qty_sold,
                );

                app(\App\Services\StockMovementService::class)->record(
                    branchId: $record->branch_id,
                    departmentId: $record->department_id,
                    itemId: $line->item_id,
                    batchInventoryId: $allocations[0]['batch_inventory_id'],
                    recordedBy: $record->served_by,
                    movementType: 'sale',
                    qtyIn: 0,
                    qtyOut: $line->qty_sold,
                    qtyBefore: null,
                    qtyAfter: null,
                    unitCost: $line->unit_cost,
                    unitPrice: $line->unit_price,
                    referenceType: \App\Models\SalesOrder::class,
                    referenceId: $record->id,
                );
            }
        }

        // Recalculate totals
        $subtotal = $record->salesOrderLines()->sum('line_total');
        $cogsTotal = $record->salesOrderLines()->sum('line_cost');

        $record->subtotal = $subtotal;
        $record->grand_total = $subtotal - $record->discount_total;
        $record->cogs_total = $cogsTotal;
        $record->gross_profit = $record->grand_total - $cogsTotal;
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
