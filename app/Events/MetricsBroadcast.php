<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MetricsBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly array $snapshot) {}

    public function broadcastOn(): Channel
    {
        return new Channel((string) config('prometheus.broadcast.channel', 'metrics'));
    }

    public function broadcastAs(): string
    {
        return (string) config('prometheus.broadcast.event', 'metrics.updated');
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'metrics' => $this->snapshot['metrics'] ?? [],
            'processes' => $this->snapshot['processes'] ?? [
                'count' => 0,
                'table' => [],
            ],
            'generated_at' => $this->snapshot['generated_at'] ?? null,
        ];
    }
}
