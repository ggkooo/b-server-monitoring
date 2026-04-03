<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PrometheusService
{
    private string $baseUrl;
    private int $timeout;
    private int $connectTimeout;
    private ?string $username;
    private ?string $password;

    public function __construct()
    {
        $this->baseUrl        = rtrim((string) config('prometheus.base_url'), '/');
        $this->timeout        = (int) config('prometheus.timeout', 5);
        $this->connectTimeout = (int) config('prometheus.connect_timeout', 2);
        $this->username       = config('prometheus.username') ?: null;
        $this->password       = config('prometheus.password') ?: null;
    }

    /**
     * Execute an instant PromQL query and return the first scalar result value.
     *
     * @throws RuntimeException When Prometheus returns an error or an unexpected payload.
     * @throws ConnectionException When the connection to Prometheus fails.
     */
    public function queryScalar(string $promQL): float
    {
        $result = $this->queryResult($promQL);

        if (empty($result)) {
            throw new RuntimeException('Prometheus returned an empty result set for the given query.');
        }

        // For instant queries the value is [ timestamp, "value_string" ]
        return (float) ($result[0]['value'][1] ?? 0.0);
    }

    /**
     * Execute an instant PromQL query and return all vector results.
     *
     * @return array<int, array{labels: array<string, string>, value: float}>
     */
    public function queryVector(string $promQL): array
    {
        $vector = [];

        foreach ($this->queryResult($promQL) as $item) {
            $vector[] = [
                'labels' => (array) ($item['metric'] ?? []),
                'value' => (float) ($item['value'][1] ?? 0.0),
            ];
        }

        return $vector;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function queryResult(string $promQL): array
    {
        $response = $this->request()->get("{$this->baseUrl}/api/v1/query", ['query' => $promQL]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Prometheus query failed with HTTP {$response->status()}: {$response->body()}"
            );
        }

        $payload = $response->json();

        if (($payload['status'] ?? '') !== 'success') {
            throw new RuntimeException(
                'Prometheus returned an error: ' . ($payload['error'] ?? 'unknown error')
            );
        }

        return (array) ($payload['data']['result'] ?? []);
    }

    private function request(): PendingRequest
    {
        $request = Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout);

        if ($this->username !== null && $this->password !== null) {
            $request = $request->withBasicAuth($this->username, $this->password);
        }

        return $request;
    }
}
