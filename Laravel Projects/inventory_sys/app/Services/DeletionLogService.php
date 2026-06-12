<?php

namespace App\Services;

use App\Models\DeletionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeletionLogService
{
    public function record(
        int $deletedBy,
        Model $record,
        string $reason,
        array $stockReversal = [],
        ?string $recordNumber = null
    ): DeletionLog {
        $branchId = $record->branch_id ?? null;
        $snapshot = $this->buildSnapshot($record);

        return DB::transaction(function () use (
            $branchId,
            $deletedBy,
            $record,
            $reason,
            $stockReversal,
            $recordNumber,
            $snapshot
        ) {
            return DeletionLog::create([
                'branch_id' => $branchId,
                'deleted_by' => $deletedBy,
                'record_type' => get_class($record),
                'record_id' => $record->id,
                'record_number' => $recordNumber,
                'reason' => $reason,
                'snapshot' => $snapshot,
                'stock_reversal' => $stockReversal,
                'deleted_at' => now(),
            ]);
        });
    }

    private function buildSnapshot(Model $record): array
    {
        $snapshot = $record->toArray();

        // Load common relations to snapshot
        $relationMethods = collect(get_class_methods($record))
            ->filter(fn($method) => !in_array($method, ['__construct', 'boot', 'get', 'toArray']));

        foreach ($relationMethods as $method) {
            try {
                if (method_exists($record, $method) && is_callable([$record, $method])) {
                    $relation = $record->$method();
                    $snapshot[$method] = $relation->get()->toArray();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $snapshot;
    }
}
