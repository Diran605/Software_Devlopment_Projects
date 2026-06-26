<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'branch_id',
    'department_id',
    'created_by',
    'approved_by',
    'posted_by',
    'count_number',
    'status',
    'count_at',
    'approved_at',
    'posted_at',
    'notes'
])]
class InventoryCount extends Model
{
    use SoftDeletes;

    protected $casts = [
        'count_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'status' => 'string',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function lines()
    {
        return $this->hasMany(InventoryCountLine::class);
    }

    /**
     * @return array{total: int, counted: int, remaining: int, progress_percent: int, net_variance_value: float}
     */
    public function countSummary(): array
    {
        $lines = $this->relationLoaded('lines')
            ? $this->lines
            : $this->lines()->get(['qty_counted', 'variance_value']);

        $total = $lines->count();
        $counted = $lines->whereNotNull('qty_counted')->count();
        $remaining = $total - $counted;

        return [
            'total' => $total,
            'counted' => $counted,
            'remaining' => $remaining,
            'progress_percent' => $total > 0 ? (int) round(($counted / $total) * 100) : 0,
            'net_variance_value' => (float) $lines->sum('variance_value'),
        ];
    }

    /**
     * @return array{match_count: int, match_percent: int, shortage_count: int, shortage_value: float, surplus_count: int, surplus_value: float, net_value: float}
     */
    public function varianceSummary(): array
    {
        $lines = $this->relationLoaded('lines')
            ? $this->lines
            : $this->lines()->get(['qty_counted', 'qty_variance', 'variance_value']);

        $countedLines = $lines->whereNotNull('qty_counted');
        $matchCount = $countedLines->where('qty_variance', 0)->count();
        $shortageLines = $countedLines->where('qty_variance', '<', 0);
        $surplusLines = $countedLines->where('qty_variance', '>', 0);
        $countedTotal = $countedLines->count();

        return [
            'match_count' => $matchCount,
            'match_percent' => $countedTotal > 0 ? (int) round(($matchCount / $countedTotal) * 100) : 0,
            'shortage_count' => $shortageLines->count(),
            'shortage_value' => (float) $shortageLines->sum('variance_value'),
            'surplus_count' => $surplusLines->count(),
            'surplus_value' => (float) $surplusLines->sum('variance_value'),
            'net_value' => (float) $countedLines->sum('variance_value'),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{
     *     item_id: int,
     *     title: string,
     *     batch_count: int,
     *     has_variance: bool,
     *     has_pending: bool,
     *     description: string,
     * }>
     */
    public function itemGroupMeta(): \Illuminate\Support\Collection
    {
        $lines = $this->relationLoaded('lines')
            ? $this->lines->loadMissing('item')
            : $this->lines()->with('item')->get();

        return $lines
            ->groupBy('item_id')
            ->map(function ($lines, $itemId) {
                $first = $lines->first();
                $name = $first->item?->name ?? 'Unknown Item';
                $batchCount = $lines->count();
                $hasVariance = $lines->contains(fn ($line) => ($line->qty_variance ?? 0) !== 0);
                $hasPending = $lines->contains(fn ($line) => $line->qty_counted === null);

                $description = match (true) {
                    $hasVariance && $hasPending => 'Variances and uncounted batches',
                    $hasVariance => 'Has variances — review required',
                    $hasPending => 'Uncounted batches remaining',
                    default => 'All batches matched',
                };

                return (object) [
                    'item_id' => (int) $itemId,
                    'title' => "{$name} — {$batchCount} ".str('batch')->plural($batchCount),
                    'batch_count' => $batchCount,
                    'has_variance' => $hasVariance,
                    'has_pending' => $hasPending,
                    'description' => $description,
                ];
            })
            ->values();
    }
}
