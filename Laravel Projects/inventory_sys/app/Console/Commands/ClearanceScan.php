<?php

namespace App\Console\Commands;

use App\Models\BatchInventory;
use App\Models\ClearanceRule;
use App\Models\ClearanceItem;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('clearance:scan')]
#[Description('Scans batches and flags them for clearance based on rules')]
class ClearanceScan extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting clearance scan...');

        $rules = ClearanceRule::where('is_active', true)->get();
        $this->info('Found ' . $rules->count() . ' active clearance rules');

        $batches = BatchInventory::whereNotNull('expiry_date')
            ->where('qty_remaining', '>', 0)
            ->get();

        $this->info('Scanning ' . $batches->count() . ' batches with expiry dates');

        foreach ($batches as $batch) {
            $daysToExpiry = Carbon::parse($batch->expiry_date)->diffInDays(now(), false);

            // Find matching rule
            $matchingRule = $rules->first(function ($rule) use ($daysToExpiry) {
                if ($rule->days_min === null && $rule->days_max !== null) {
                    return $daysToExpiry <= $rule->days_max;
                }
                if ($rule->days_max === null && $rule->days_min !== null) {
                    return $daysToExpiry >= $rule->days_min;
                }
                return $daysToExpiry >= $rule->days_min && $daysToExpiry <= $rule->days_max;
            });

            if ($matchingRule) {
                $this->info('Batch ' . $batch->batch_number . ' matches rule: ' . $matchingRule->name);

                // Check if clearance item already exists
                $clearanceItem = ClearanceItem::where('batch_inventory_id', $batch->id)->first();

                if (!$clearanceItem) {
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
                } else {
                    $clearanceItem->update([
                        'days_to_expiry' => $daysToExpiry,
                        'urgency_status' => $matchingRule->name,
                        'qty_flagged' => $batch->qty_remaining,
                    ]);
                }
            }
        }

        $this->info('Clearance scan complete!');
    }
}
