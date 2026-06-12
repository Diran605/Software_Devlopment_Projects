<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function record(
        string $event,
        Model $auditable,
        ?int $userId = null,
        ?int $branchId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        try {
            $userId = $userId ?? auth()->id();
            $branchId = $branchId ?? (auth()->user()?->branch_id ?? $auditable->branch_id ?? null);
        } catch (\Throwable $e) {
            // If auth fails, try to get branch from auditable model
            $userId = $userId ?? null;
            $branchId = $branchId ?? ($auditable->branch_id ?? null);
        }

        return AuditLog::create([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
