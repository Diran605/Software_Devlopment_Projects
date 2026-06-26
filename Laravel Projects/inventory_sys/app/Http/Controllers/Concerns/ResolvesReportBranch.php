<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use Illuminate\Http\Request;

trait ResolvesReportBranch
{
    protected function resolveReportBranchId(Request $request): int
    {
        $branchId = $request->integer('branch_id') ?: null;

        if (! $branchId && filament()->getTenant()) {
            $branchId = filament()->getTenant()->getKey();
        }

        if (! $branchId) {
            $branchId = auth()->user()?->branch_id;
        }

        if (! $branchId && auth()->user()) {
            $branchId = auth()->user()->branches()->value('branches.id');
        }

        if (! $branchId && auth()->user()?->hasRole('super-admin')) {
            $branchId = Branch::query()->value('id');
        }

        if (! $branchId) {
            abort(403, 'No branch assigned. Select a branch or open the app panel under a branch tenant.');
        }

        return (int) $branchId;
    }

    protected function resolveReportBranch(Request $request): Branch
    {
        return Branch::findOrFail($this->resolveReportBranchId($request));
    }
}
