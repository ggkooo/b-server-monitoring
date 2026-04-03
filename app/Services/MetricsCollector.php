<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class MetricsCollector
{
    public function __construct(private readonly PrometheusService $prometheus) {}

    /**
     * @return array<string, array<string, float|null>>
     */
    public function collect(): array
    {
        $snapshot = [];

        foreach ((array) config('prometheus.metrics', []) as $group => $metrics) {
            $snapshot[$group] = $this->collectGroup((array) $metrics);
        }

        return $snapshot;
    }

    /**
     * @param array<string, array{query?: string}> $metrics
     * @return array<string, float|null>
     */
    private function collectGroup(array $metrics): array
    {
        $values = [];

        foreach ($metrics as $name => $definition) {
            $values[$name] = $this->queryMetric((string) ($definition['query'] ?? ''));
        }

        return $values;
    }

    private function queryMetric(string $query): ?float
    {
        if ($query === '') {
            return null;
        }

        try {
            return round($this->prometheus->queryScalar($query), 4);
        } catch (ConnectionException|RuntimeException) {
            return null;
        }
    }
}