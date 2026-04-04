<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class MetricsCollector
{
    public function __construct(private readonly PrometheusService $prometheus) {}

    /**
     * @return array<string, array<string, mixed>>
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
     * @param array<string, array{query?: string, type?: string, label?: string}> $metrics
     * @return array<string, mixed>
     */
    private function collectGroup(array $metrics): array
    {
        $values = [];

        foreach ($metrics as $name => $definition) {
            $values[$name] = $this->queryMetricDefinition((array) $definition);
        }

        return $values;
    }

    /**
     * @param array{query?: string, type?: string, label?: string} $definition
     */
    private function queryMetricDefinition(array $definition): mixed
    {
        $query = (string) ($definition['query'] ?? '');

        if ($query === '') {
            return null;
        }

        if (($definition['type'] ?? '') === 'vector_by_label') {
            return $this->queryVectorByLabel($query, (string) ($definition['label'] ?? ''));
        }

        return $this->queryScalarMetric($query);
    }

    /**
     * @return float|null
     */
    private function queryScalarMetric(string $query): ?float
    {
        try {
            return round($this->prometheus->queryScalar($query), 4);
        } catch (ConnectionException|RuntimeException) {
            return null;
        }
    }

    /**
     * @return array<int, array{label: string, value: float}>
     */
    private function queryVectorByLabel(string $query, string $label): array
    {
        if ($label === '') {
            return [];
        }

        try {
            $series = $this->prometheus->queryVector($query);
        } catch (ConnectionException|RuntimeException) {
            return [];
        }

        $values = [];

        foreach ($series as $item) {
            $labelValue = (string) ($item['labels'][$label] ?? '');

            if ($labelValue === '') {
                continue;
            }

            $values[] = [
                'label' => $labelValue,
                'value' => round((float) $item['value'], 4),
            ];
        }

        usort($values, function (array $left, array $right): int {
            return strnatcmp($left['label'], $right['label']);
        });

        return $values;
    }

}