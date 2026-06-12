<?php

namespace App\Filament\App\Resources\GoodsReceivedNotes\Pages;

use App\Filament\App\Resources\GoodsReceivedNotes\GoodsReceivedNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $lines = $data['grnLineItems'] ?? [];
        unset($data['grnLineItems']);
        
        $data['received_by'] = auth()->id();

        $grn = parent::handleRecordCreation($data);

        app(\App\Services\GoodsReceiptService::class)->receive($grn, $lines);

        return $grn;
    }

    public function mount(): void
    {
        parent::mount();

        $poId = request()->query('purchase_order_id');
        if ($poId) {
            $po = \App\Models\PurchaseOrder::with('purchaseOrderLines.item')->find($poId);
            if ($po) {
                $lines = [];
                foreach ($po->purchaseOrderLines as $poLine) {
                    $remaining = $poLine->qty_ordered - $poLine->qty_received;
                    if ($remaining > 0) {
                        $lines[] = [
                            'item_id' => $poLine->item_id,
                            'entry_mode' => 'unit',
                            'pack_quantity' => 0,
                            'units_per_pack' => 1,
                            'qty_received' => $remaining,
                            'unit_cost' => $poLine->unit_cost,
                            'line_total' => $remaining * $poLine->unit_cost,
                            'batch_number' => 'BCH-' . strtoupper(uniqid()),
                            'expiry_date' => null,
                        ];
                    }
                }

                $totalQty = array_sum(array_column($lines, 'qty_received'));
                $totalCost = array_sum(array_column($lines, 'line_total'));

                $this->form->fill([
                    'purchase_order_id' => $po->id,
                    'supplier_id' => $po->supplier_id,
                    'grnLineItems' => $lines,
                    'total_qty' => $totalQty,
                    'total_cost' => $totalCost,
                    'grn_number' => 'GRN-' . strtoupper(uniqid()),
                    'received_at' => now(),
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
