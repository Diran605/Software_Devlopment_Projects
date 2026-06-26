<?php

namespace App\Services;

use App\Models\DeletionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class DeletionLogService
{
    public function record(
        int $deletedBy,
        Model $record,
        string $reason,
        array $stockReversal = [],
        ?string $recordNumber = null
    ): DeletionLog {
        $branchId = $this->resolveBranchId($record);
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
            return DeletionLog::query()->create([
                'branch_id' => $branchId,
                'deleted_by' => $deletedBy,
                'record_type' => $record::class,
                'record_id' => $record->getKey(),
                'record_number' => $recordNumber ?? (string) $record->getKey(),
                'reason' => $reason,
                'snapshot' => $snapshot,
                'stock_reversal' => $stockReversal,
                'deleted_at' => now(),
            ]);
        });
    }

    private function resolveBranchId(Model $record): int
    {
        if ($record->getAttribute('branch_id')) {
            return (int) $record->branch_id;
        }

        foreach (['branch', 'inventoryCount', 'purchaseOrder', 'salesOrder', 'openingStockEntry', 'goodsReceivedNote', 'stockTransfer'] as $relation) {
            if (! method_exists($record, $relation)) {
                continue;
            }

            $related = $record->{$relation};

            if ($related?->getAttribute('branch_id')) {
                return (int) $related->branch_id;
            }

            if ($relation === 'branch' && $related) {
                return (int) $related->getKey();
            }
        }

        $branchId = auth()->user()?->branch_id
            ?? auth()->user()?->branches()->value('branches.id');

        if ($branchId) {
            return (int) $branchId;
        }

        throw new \RuntimeException('Cannot record deletion log: no branch could be resolved for this record.');
    }

    private function buildSnapshot(Model $record): array
    {
        $relationNames = $this->detectRelationMethods($record);

        if ($relationNames !== []) {
            $record->loadMissing($relationNames);
        }

        return $record->toArray();
    }

    /**
     * @return list<string>
     */
    private function detectRelationMethods(Model $record): array
    {
        $names = [];
        $reflection = new ReflectionClass($record);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $record::class) {
                continue;
            }

            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType && ! $returnType->isBuiltin()) {
                if (is_subclass_of($returnType->getName(), Relation::class)) {
                    $names[] = $method->getName();
                    continue;
                }
            }

            try {
                $result = $method->invoke($record);

                if ($result instanceof Relation) {
                    $names[] = $method->getName();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return array_values(array_unique($names));
    }
}
