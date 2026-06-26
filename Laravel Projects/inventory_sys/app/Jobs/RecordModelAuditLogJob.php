<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\DeletionLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordModelAuditLogJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $event,
        public string $auditableType,
        public int|string $auditableId,
        public ?int $userId,
        public ?int $branchId,
        public ?array $oldValues,
        public ?array $newValues,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $recordNumber = null,
    ) {}

    public function handle(): void
    {
        AuditLog::withoutEvents(function (): void {
            AuditLog::create([
                'branch_id' => $this->branchId,
                'user_id' => $this->userId,
                'event' => $this->event,
                'auditable_type' => $this->auditableType,
                'auditable_id' => $this->auditableId,
                'old_values' => $this->oldValues,
                'new_values' => $this->newValues,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
            ]);
        });

        if ($this->event !== 'deleted' || ! $this->branchId || ! $this->userId) {
            return;
        }

        DeletionLog::withoutEvents(function (): void {
            DeletionLog::create([
                'branch_id' => $this->branchId,
                'deleted_by' => $this->userId,
                'record_type' => $this->auditableType,
                'record_id' => $this->auditableId,
                'record_number' => $this->recordNumber ?? (string) $this->auditableId,
                'reason' => 'Automatic deletion log entry',
                'snapshot' => $this->newValues ?? [],
                'stock_reversal' => [],
                'deleted_at' => now(),
            ]);
        });
    }
}
