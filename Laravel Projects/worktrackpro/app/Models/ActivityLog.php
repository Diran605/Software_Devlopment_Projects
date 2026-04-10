<?php

namespace App\Models;

use App\Enums\CompletionType;
use App\Enums\WorkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class ActivityLog extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organisation_id',
        'daily_plan_id',
        'date',
        'task_name',
        'project_client',
        'work_type',
        'work_type_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'output',
        'completion_type',
        'is_planned',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'work_type' => WorkType::class,
        'completion_type' => CompletionType::class,
        'is_planned' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function dailyPlan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class);
    }

    public function workTypeRecord(): BelongsTo
    {
        return $this->belongsTo(WorkType::class, 'work_type_id');
    }
}
