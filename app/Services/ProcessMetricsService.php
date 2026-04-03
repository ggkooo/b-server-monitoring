<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class ProcessMetricsService
{
    public function __construct(private readonly PrometheusService $prometheus) {}

    /**
     * Build an htop-like process table keyed by namedprocess groupname.
     *
     * @return array<int, array<string, float|string|null>>
     */
    public function collectTable(): array
    {
        $queries = (array) config('prometheus.processes', []);

        $groups = $this->extractGroups((string) ($queries['list_all'] ?? ''));

        $rows = [];
        foreach ($groups as $group) {
            $rows[$group] = [
                'name' => $group,
                'cpu_percent' => 0.0,
                'memory_resident_bytes' => 0.0,
                'memory_virtual_bytes' => 0.0,
                'threads' => 0.0,
                'disk_read_bytes_per_second' => 0.0,
                'disk_write_bytes_per_second' => 0.0,
                'context_switches_per_second' => 0.0,
                'open_filedesc' => 0.0,
                'uptime_seconds' => 0.0,
            ];
        }

        $this->mergeMetric($rows, (string) ($queries['cpu_percent'] ?? ''), 'cpu_percent');
        $this->mergeMetric($rows, (string) ($queries['memory_resident_bytes'] ?? ''), 'memory_resident_bytes');
        $this->mergeMetric($rows, (string) ($queries['memory_virtual_bytes'] ?? ''), 'memory_virtual_bytes');
        $this->mergeMetric($rows, (string) ($queries['threads'] ?? ''), 'threads');
        $this->mergeMetric($rows, (string) ($queries['disk_read_bytes_per_second'] ?? ''), 'disk_read_bytes_per_second');
        $this->mergeMetric($rows, (string) ($queries['disk_write_bytes_per_second'] ?? ''), 'disk_write_bytes_per_second');
        $this->mergeMetric($rows, (string) ($queries['context_switches_per_second'] ?? ''), 'context_switches_per_second');
        $this->mergeMetric($rows, (string) ($queries['open_filedesc'] ?? ''), 'open_filedesc');
        $this->mergeMetric($rows, (string) ($queries['uptime_seconds'] ?? ''), 'uptime_seconds', aggregator: 'max');

        $table = array_values($rows);

        usort($table, function (array $left, array $right): int {
            return ($right['cpu_percent'] <=> $left['cpu_percent']);
        });

        return $table;
    }

    /**
     * @return array<int, string>
     */
    private function extractGroups(string $listAllQuery): array
    {
        $vector = $this->safeVector($listAllQuery);
        $groups = [];

        foreach ($vector as $series) {
            $groupname = (string) ($series['labels']['groupname'] ?? '');

            if ($groupname !== '') {
                $groups[$groupname] = true;
            }
        }

        return array_keys($groups);
    }

    /**
     * @param array<string, array<string, float|string|null>> $rows
     */
    private function mergeMetric(array &$rows, string $query, string $field, string $aggregator = 'sum'): void
    {
        foreach ($this->safeVector($query) as $series) {
            $groupname = (string) ($series['labels']['groupname'] ?? '');

            if ($groupname === '') {
                continue;
            }

            if (! array_key_exists($groupname, $rows)) {
                $rows[$groupname] = [
                    'name' => $groupname,
                    'cpu_percent' => 0.0,
                    'memory_resident_bytes' => 0.0,
                    'memory_virtual_bytes' => 0.0,
                    'threads' => 0.0,
                    'disk_read_bytes_per_second' => 0.0,
                    'disk_write_bytes_per_second' => 0.0,
                    'context_switches_per_second' => 0.0,
                    'open_filedesc' => 0.0,
                    'uptime_seconds' => 0.0,
                ];
            }

            $current = (float) ($rows[$groupname][$field] ?? 0.0);
            $incoming = (float) ($series['value'] ?? 0.0);

            $rows[$groupname][$field] = $aggregator === 'max'
                ? max($current, $incoming)
                : ($current + $incoming);
        }

        foreach ($rows as &$row) {
            if (is_float($row[$field])) {
                $row[$field] = round((float) $row[$field], 4);
            }
        }
    }

    /**
     * @return array<int, array{labels: array<string, string>, value: float}>
     */
    private function safeVector(string $query): array
    {
        if ($query === '') {
            return [];
        }

        try {
            return $this->prometheus->queryVector($query);
        } catch (ConnectionException|RuntimeException) {
            return [];
        }
    }
}