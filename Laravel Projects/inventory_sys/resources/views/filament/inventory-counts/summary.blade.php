@php
    $summary = $summary ?? [];
    $variance = $variance ?? null;
    $showVariance = $showVariance ?? false;
    $progress = $summary['progress_percent'] ?? 0;
@endphp

<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-content p-6 space-y-6">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Lines</p>
                <p class="text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($summary['total'] ?? 0) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Counted</p>
                <p class="text-2xl font-semibold text-success-600">{{ number_format($summary['counted'] ?? 0) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Remaining</p>
                <p class="text-2xl font-semibold text-warning-600">{{ number_format($summary['remaining'] ?? 0) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Net Variance</p>
                @php $netVariance = $summary['net_variance_value'] ?? 0; @endphp
                <p @class([
                    'text-2xl font-semibold',
                    'text-danger-600' => $netVariance < 0,
                    'text-success-600' => $netVariance > 0,
                    'text-gray-500' => $netVariance == 0,
                ])>
                    {{ number_format($netVariance, 0) }} XAF
                </p>
            </div>
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Count Progress</span>
                <span class="font-medium text-gray-950 dark:text-white">{{ $progress }}%</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    class="h-full rounded-full bg-primary-600 transition-all duration-300"
                    style="width: {{ min(100, max(0, $progress)) }}%"
                ></div>
            </div>
        </div>

        @if ($showVariance && $variance)
            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Variance Summary
                </h3>
                <div class="grid gap-3 md:grid-cols-2">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600 dark:text-gray-300">Items with Match</span>
                        <span class="font-medium text-gray-950 dark:text-white">
                            {{ $variance['match_count'] }} ({{ $variance['match_percent'] }}%)
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600 dark:text-gray-300">Items with Shortage</span>
                        <span class="font-medium text-danger-600">
                            {{ $variance['shortage_count'] }} — {{ number_format($variance['shortage_value'], 0) }} XAF
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-600 dark:text-gray-300">Items with Surplus</span>
                        <span class="font-medium text-success-600">
                            {{ $variance['surplus_count'] }} — +{{ number_format($variance['surplus_value'], 0) }} XAF
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="font-semibold text-gray-950 dark:text-white">Net Variance Value</span>
                        <span @class([
                            'font-semibold',
                            'text-danger-600' => ($variance['net_value'] ?? 0) < 0,
                            'text-success-600' => ($variance['net_value'] ?? 0) > 0,
                        ])>
                            {{ number_format($variance['net_value'] ?? 0, 0) }} XAF
                        </span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
