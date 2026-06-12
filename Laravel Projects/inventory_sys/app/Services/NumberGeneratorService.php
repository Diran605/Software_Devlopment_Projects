<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NumberGeneratorService
{
    private function generate(string $prefix, int $branchId, string $modelTable): string
    {
        $branch = Branch::find($branchId);
        $branchCode = $branch->code ?? '00';
        $date = now()->format('Ymd');

        return DB::transaction(function () use ($prefix, $branchCode, $date, $modelTable) {
            $record = DB::table('number_sequences')
                ->where('prefix', $prefix)
                ->where('branch_code', $branchCode)
                ->where('date', $date)
                ->lockForUpdate()
                ->first();

            if ($record) {
                $sequence = $record->sequence + 1;
                DB::table('number_sequences')
                    ->where('id', $record->id)
                    ->update(['sequence' => $sequence]);
            } else {
                $sequence = 1;
                DB::table('number_sequences')->insert([
                    'prefix' => $prefix,
                    'branch_code' => $branchCode,
                    'date' => $date,
                    'sequence' => $sequence,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            return sprintf('%s-%s-%s-%04d', $prefix, $branchCode, $date, $sequence);
        });
    }

    public function generatePoNumber(int $branchId): string
    {
        return $this->generate('PO', $branchId, 'purchase_orders');
    }

    public function generateGrnNumber(int $branchId): string
    {
        return $this->generate('GRN', $branchId, 'goods_received_notes');
    }

    public function generateOrderNumber(int $branchId): string
    {
        return $this->generate('SO', $branchId, 'sales_orders');
    }

    public function generateTransferNumber(int $branchId): string
    {
        return $this->generate('TRF', $branchId, 'stock_transfers');
    }

    public function generateEntryNumber(int $branchId): string
    {
        return $this->generate('OSE', $branchId, 'opening_stock_entries');
    }

    public function generateCountNumber(int $branchId): string
    {
        return $this->generate('CNT', $branchId, 'inventory_counts');
    }
}
