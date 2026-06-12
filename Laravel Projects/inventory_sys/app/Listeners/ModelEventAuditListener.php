<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\DeletionLog;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ModelEventAuditListener
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function handle(object $event): void
    {
        \Log::info('LISTENER CALLED: ' . get_class($event));

        try {
            if (!property_exists($event, 'model')) {
                \Log::info('LISTENER: No model property found');
                return;
            }

            /** @var Model $model */
            $model = $event->model;
            $eventName = $this->getEventName($event);

            \Log::info('LISTENER: Event name: ' . $eventName . ', Model: ' . get_class($model));

            if (!$eventName) {
                \Log::info('LISTENER: No event name matched');
                return;
            }

            try {
                $userId = auth()->id();
            } catch (\Throwable $e) {
                $userId = null;
            }

            $branchId = $model->branch_id ?? null;
            $oldValues = method_exists($model, 'getOriginal') ? $model->getOriginal() : null;
            $newValues = $model->toArray();

            \Log::info('LISTENER: Saving to AuditLog', [
                'event' => $eventName,
                'model' => get_class($model),
                'user_id' => $userId,
                'branch_id' => $branchId,
            ]);

            // Record to AuditLog
            AuditLog::create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'event' => $eventName,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);

            \Log::info('LISTENER: AuditLog saved successfully');

            // Record to DeletionLog if this is a deletion
            if ($eventName === 'deleted') {
                $recordNumber = $model->record_number ?? $model->name ?? $model->title ?? $model->count_number ?? null;

                DeletionLog::create([
                    'branch_id' => $branchId,
                    'deleted_by' => $userId,
                    'record_type' => get_class($model),
                    'record_id' => $model->id,
                    'record_number' => $recordNumber,
                    'reason' => null,
                    'snapshot' => $newValues,
                    'deleted_at' => now(),
                ]);

                \Log::info('LISTENER: DeletionLog saved successfully');
            }
        } catch (\Throwable $e) {
            \Log::error('ModelEventAuditListener error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function getEventName(object $event): ?string
    {
        $class = get_class($event);

        return match (true) {
            str_contains($class, 'Created') => 'created',
            str_contains($class, 'Updated') => 'updated',
            str_contains($class, 'Deleted') => 'deleted',
            str_contains($class, 'Restored') => 'restored',
            default => null
        };
    }
}
