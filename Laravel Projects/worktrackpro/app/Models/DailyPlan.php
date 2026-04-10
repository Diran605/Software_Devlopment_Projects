<?php

namespace App\Models;

use App\Enums\PlanStatus;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class DailyPlan extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'assigned_by',
        'organisation_id',
        'date',
        'task_name',
        'project_client',
        'project_client_id',
        'priority',
        'expected_duration_minutes',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'priority' => Priority::class,
        'status' => PlanStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function projectClient(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class);
    }
}
