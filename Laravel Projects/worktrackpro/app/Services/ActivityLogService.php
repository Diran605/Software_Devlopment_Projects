<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\DailyPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ActivityLogService
{
    /**
     * Create a new Activity Log and calculate duration if absent.
     */
    public function createLog(User $user, array $data): ActivityLog
    {
        // 1. Assure ownership
        $data['user_id'] = $user->id;
        $data['organisation_id'] = $user->organisation_id;

        // 2. Validate linked plan if `is_planned` is true
        if (!empty($data['is_planned']) && $data['is_planned'] === true) {
            if (empty($data['daily_plan_id'])) {
                throw ValidationException::withMessages(['daily_plan_id' => 'A daily plan must be selected if this is a planned task.']);
            }

            // Ensure the plan actually belongs to the user
            $plan = DailyPlan::where('id', $data['daily_plan_id'])
                             ->where('user_id', $user->id)
                             ->first();
            
            if (!$plan) {
                throw ValidationException::withMessages(['daily_plan_id' => 'The selected daily plan is invalid or does not belong to you.']);
            }
        } else {
            $data['daily_plan_id'] = null;
        }

        // 3. Handle Duration Calculations
        if (empty($data['duration_minutes'])) {
            if (empty($data['start_time']) || empty($data['end_time'])) {
                 throw ValidationException::withMessages(['duration_minutes' => 'Duration must be provided if start and end times are not given.']);
            }
            
            $start = Carbon::parse($data['start_time']);
            $end = Carbon::parse($data['end_time']);

            if ($end->lessThan($start)) {
                throw ValidationException::withMessages(['end_time' => 'End time must be after start time.']);
            }

            $data['duration_minutes'] = $start->diffInMinutes($end);
        }

        return ActivityLog::create($data);
    }

    /**
     * Update an existing Activity Log and recalculate duration if times changed.
     */
    public function updateLog(ActivityLog $log, array $data): ActivityLog
    {
        // Handle changes in `is_planned`
        if (isset($data['is_planned'])) {
             if ($data['is_planned'] === true && empty($data['daily_plan_id']) && empty($log->daily_plan_id)) {
                 throw ValidationException::withMessages(['daily_plan_id' => 'A daily plan must be specified.']);
             } elseif ($data['is_planned'] === false) {
                 $data['daily_plan_id'] = null;
             }
        }

        // Handle potential duration recalculations
        $startTime = $data['start_time'] ?? $log->start_time;
        $endTime = $data['end_time'] ?? $log->end_time;
        
        if (isset($data['start_time']) || isset($data['end_time'])) {
            if ($startTime && $endTime && !isset($data['duration_minutes'])) {
                $start = Carbon::parse($startTime);
                $end = Carbon::parse($endTime);
                
                if ($end->lessThan($start)) {
                    throw ValidationException::withMessages(['end_time' => 'End time must be after start time.']);
                }
                $data['duration_minutes'] = $start->diffInMinutes($end);
            }
        }

        $log->update($data);
        return $log;
    }
}
