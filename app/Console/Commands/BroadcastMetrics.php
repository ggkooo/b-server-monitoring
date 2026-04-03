<?php

namespace App\Console\Commands;

use App\Events\MetricsBroadcast;
use App\Services\MetricsSnapshotService;
use Illuminate\Console\Command;

class BroadcastMetrics extends Command
{
    protected $signature = 'metrics:broadcast {--loop : Keep broadcasting forever} {--interval=5 : Seconds between broadcasts in loop mode}';

    protected $description = 'Fetch all metrics from Prometheus and broadcast them via WebSocket.';

    public function handle(MetricsSnapshotService $snapshots): int
    {
        $defaultInterval = (int) config('prometheus.broadcast.interval', 5);
        $interval = max(1, (int) ($this->option('interval') ?: $defaultInterval));

        if (! $this->option('loop')) {
            MetricsBroadcast::dispatch($snapshots->refresh());
            $this->info('Metrics broadcast sent.');

            return self::SUCCESS;
        }

        $this->info("Broadcast loop started (interval: {$interval}s). Press Ctrl+C to stop.");

        while (true) {
            MetricsBroadcast::dispatch($snapshots->refresh());
            sleep($interval);
        }

        return self::SUCCESS;
    }
}
