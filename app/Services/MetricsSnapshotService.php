<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MetricsSnapshotService
{
    public function __construct(
        private readonly MetricsCollector $collector,
        private readonly ProcessMetricsService $processes,
    ) {}

    /**
     * Return the latest metrics snapshot, collecting one when cache is empty.
     *
     * @return array<string, mixed>
     */
    public function latest(): array
    {
        $key = (string) config('prometheus.cache.key', 'metrics.snapshot');

        return (array) Cache::get($key, fn (): array => $this->refresh());
    }

    /**
     * Collect metrics and persist a fresh snapshot in cache.
     *
     * @return array<string, mixed>
     */
    public function refresh(): array
    {
        $processTable = $this->processes->collectTable();

        $snapshot = [
            'metrics' => $this->collector->collect(),
            'processes' => [
                'count' => count($processTable),
                'table' => $processTable,
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        $key = (string) config('prometheus.cache.key', 'metrics.snapshot');
        $ttl = max(1, (int) config('prometheus.cache.ttl', 15));

        Cache::put($key, $snapshot, now()->addSeconds($ttl));

        return $snapshot;
    }
}
