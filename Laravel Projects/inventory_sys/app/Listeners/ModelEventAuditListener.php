<?php

namespace App\Listeners;

use App\Jobs\RecordModelAuditLogJob;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DeletionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ModelEventAuditListener
{
    /** @var list<class-string<Model>> */
    protected array $excludedModels = [
        AuditLog::class,
        DeletionLog::class,
    ];

    public function handleCreated(Model $model): void
    {
        $this->record('created', $model);
    }

    public function handleUpdated(Model $model): void
    {
        $this->record('updated', $model, $model->getOriginal());
    }

    public function handleDeleted(Model $model): void
    {
        $this->record('deleted', $model, $model->getOriginal());
    }

    public function handleRestored(Model $model): void
    {
        $this->record('restored', $model);
    }

    protected function record(string $event, Model $model, ?array $oldValues = null): void
    {
        if (in_array($model::class, $this->excludedModels, true)) {
            return;
        }

        try {
            $userId = auth()->id();
        } catch (\Throwable) {
            $userId = null;
        }

        RecordModelAuditLogJob::dispatchSync(
            event: $event,
            auditableType: $model::class,
            auditableId: $model->getKey(),
            userId: $userId,
            branchId: $this->resolveBranchId($model),
            oldValues: $oldValues,
            newValues: $model->toArray(),
            ipAddress: Request::ip(),
            userAgent: Request::userAgent(),
            recordNumber: $this->resolveRecordNumber($model),
        );
    }

    protected function resolveBranchId(Model $model): ?int
    {
        if ($model instanceof Branch) {
            return (int) $model->getKey();
        }

        if ($model->getAttribute('branch_id')) {
            return (int) $model->branch_id;
        }

        foreach (['inventoryCount', 'purchaseOrder', 'salesOrder', 'openingStockEntry', 'goodsReceivedNote', 'stockTransfer', 'disposal', 'donation', 'expense'] as $relation) {
            if (! method_exists($model, $relation)) {
                continue;
            }

            $parent = $model->{$relation};

            if ($parent?->branch_id) {
                return (int) $parent->branch_id;
            }
        }

        try {
            return auth()->user()?->branch_id;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveRecordNumber(Model $model): ?string
    {
        foreach (['count_number', 'order_number', 'po_number', 'grn_number', 'transfer_number', 'record_number', 'name', 'title'] as $attribute) {
            if ($value = $model->getAttribute($attribute)) {
                return (string) $value;
            }
        }

        return null;
    }
}
