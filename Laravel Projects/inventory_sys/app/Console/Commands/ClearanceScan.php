<?php

namespace App\Console\Commands;

use App\Models\BatchInventory;
use App\Models\ClearanceItem;
use App\Models\ClearanceRule;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('clearance:scan')]
#[Description('Scans batches and flags them for clearance based on rules')]
class ClearanceScan extends Command
{
    public function handle(): int
    {
        $this->info('Starting clearance scan...');

        $rules = ClearanceRule::query()
            ->where('is_active', true)
            ->orderBy('days_min')
            ->get();

        $this->info('Found '.$rules->count().' active clearance rules');

        $batches = BatchInventory::query()
            ->with('item')
            ->whereNotNull('expiry_date')
            ->where('qty_remaining', '>', 0)
            ->get();

        $this->info('Scanning '.$batches->count().' batches with expiry dates');

        $created = 0;
        $updated = 0;

        foreach ($batches as $batch) {
            $daysToExpiry = (int) now()->startOfDay()->diffInDays(
                Carbon::parse($batch->expiry_date)->startOfDay(),
                false
            );

            $matchingRule = $rules
                ->where('branch_id', $batch->branch_id)
                ->first(function (ClearanceRule $rule) use ($daysToExpiry): bool {
                    $daysMin = $rule->days_min;
                    $daysMax = $rule->days_max;

                    if ($daysMin !== null && $daysMax !== null && $daysMin > $daysMax) {
                        [$daysMin, $daysMax] = [$daysMax, $daysMin];
                    }

                    if ($daysMin === null && $daysMax !== null) {
                        return $daysToExpiry <= $daysMax;
                    }

                    if ($daysMax === null && $daysMin !== null) {
                        return $daysToExpiry >= $daysMin;
                    }

                    if ($daysMin === null && $daysMax === null) {
                        return true;
                    }

                    return $daysToExpiry >= $daysMin && $daysToExpiry <= $daysMax;
                });

            if (! $matchingRule) {
                continue;
            }

            $this->info("Batch {$batch->batch_number} matches rule: {$matchingRule->name} ({$daysToExpiry} days)");

            $clearanceItem = ClearanceItem::query()
                ->where('batch_inventory_id', $batch->id)
                ->first();

            if (! $clearanceItem) {
                ClearanceItem::create([
                    'branch_id' => $batch->branch_id,
                    'item_id' => $batch->item_id,
                    'batch_inventory_id' => $batch->id,
                    'rule_id' => $matchingRule->id,
                    'qty_flagged' => $batch->qty_remaining,
                    'days_to_expiry' => $daysToExpiry,
                    'urgency_status' => $matchingRule->name,
                    'approval_status' => 'pending',
                    'original_price' => $batch->item->selling_price,
                    'clearance_price' => $batch->item->selling_price * (1 - $matchingRule->discount / 100),
                ]);
                $created++;
            } else {
                $clearanceItem->update([
                    'rule_id' => $matchingRule->id,
                    'days_to_expiry' => $daysToExpiry,
                    'urgency_status' => $matchingRule->name,
                    'qty_flagged' => $batch->qty_remaining,
                ]);
                $updated++;
            }
        }

        $this->info("Clearance scan complete! Created: {$created}, updated: {$updated}.");

        return self::SUCCESS;
    }
}
