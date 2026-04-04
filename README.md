# b-server-monitoring

Real-time server monitoring backend powered by Prometheus + Laravel Reverb.

This project collects host and process metrics from Prometheus and streams live updates over WebSocket. There is no HTTP API — all data is delivered through the WebSocket channel.

## ✨ Highlights

- 📡 Real-time updates over WebSocket channel `metrics`.
- 🧠 Process table (htop-like) grouped by `groupname` from `namedprocess_*` metrics.
- 🏎️ Per-core CPU usage returned as an array in the broadcast payload.
- 🪶 Stateless runtime defaults (no database required).

## 🏗️ Architecture

### 🔄 Data Flow

1. Every 5 seconds the scheduler fires the `metrics:broadcast` command.
2. `MetricsCollector` queries Prometheus for global metrics (`cpu`, `memory`, `disk`, `network`, `system`).
3. `ProcessMetricsService` queries Prometheus for per-process metrics grouped by `groupname`.
4. `MetricsSnapshotService` merges both sets and caches the result.
5. `MetricsBroadcast` event fires and pushes the payload over Reverb to channel `metrics`.

### 🧩 Main Components

| Class | Responsibility |
|---|---|
| `PrometheusService` | Executes PromQL queries (scalar and vector) with optional Basic Auth. |
| `MetricsCollector` | Collects and normalises global grouped metrics. |
| `ProcessMetricsService` | Builds process table by `groupname`. |
| `MetricsSnapshotService` | Merges metrics + processes and manages cache. |
| `BroadcastMetrics` (command) | Refreshes snapshot and dispatches the broadcast event. |
| `MetricsBroadcast` (event) | Sends payload over Reverb. |

## 📋 Requirements

- PHP 8.3+
- Composer
- Running Prometheus endpoint
- `process-exporter` with `namedprocess_*` metrics available in Prometheus

## 🚀 Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set required environment variables in `.env`:

- `PROMETHEUS_BASE_URL`
- `PROMETHEUS_USERNAME` and `PROMETHEUS_PASSWORD` (if Prometheus requires Basic Auth)
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`
- `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`

## 🧪 Run Locally

```bash
# Terminal 1 — WebSocket server
php artisan reverb:start

# Terminal 2 — Scheduler (triggers every 5 s)
php artisan schedule:work
```

No `php artisan serve` needed — there are no HTTP endpoints.

## 📶 WebSocket

- **Channel:** `metrics`
- **Event:** `metrics.updated`

Connect using any Pusher-protocol client (e.g. Laravel Echo, Pusher JS, or MQTTX WebSocket tester with Pusher handshake). Every scheduler tick emits a fresh payload.

### 📦 Payload Shape

```json
{
    "metrics": {
        "cpu": {
            "usage_percent": 0.33,
            "cores_usage_percent": [0.12, 0.45, 0.21, 0.38],
            "iowait_percent": 0.03,
            "load_average_1m": 0.23,
            "load_average_5m": 0.08,
            "load_average_15m": 0.05,
            "context_switches_per_second": 408
        },
        "memory": {
            "usage_percent": 4.62,
            "swap_percent": 0,
            "cached_mb": 695.77
        },
        "disk": {
            "root_used_percent": 23.87,
            "write_mb": 0.01,
            "read_mb": 0
        },
        "network": {
            "download_mb": 0.0001,
            "upload_mb": 0.0026,
            "errors_total": 0
        },
        "system": {
            "uptime_seconds": 8706.33,
            "processes_running": 2,
            "file_descriptors_allocated": 1088
        }
    },
    "processes": {
        "count": 2,
        "table": [
            {
                "name": "process-exporter",
                "cpu_percent": 1,
                "memory_resident_bytes": 20889600,
                "memory_virtual_bytes": 1268699136,
                "threads": 12,
                "disk_read_bytes_per_second": 0,
                "disk_write_bytes_per_second": 0,
                "context_switches_per_second": 45.8,
                "open_filedesc": 7,
                "uptime_seconds": 1507.32
            }
        ]
    },
    "generated_at": "2026-04-03T22:47:10+00:00"
}
```

## 🖥️ Process Table Metrics

Built from `namedprocess_*` metrics, grouped by `groupname`. Columns returned per process:

| Field | Description |
|---|---|
| `name` | Process group name |
| `cpu_percent` | CPU usage % |
| `memory_resident_bytes` | RSS in bytes |
| `memory_virtual_bytes` | VSZ in bytes |
| `threads` | Thread count |
| `disk_read_bytes_per_second` | Disk read rate |
| `disk_write_bytes_per_second` | Disk write rate |
| `context_switches_per_second` | Context switch rate |
| `open_filedesc` | Open file descriptors |
| `uptime_seconds` | Process uptime |

PromQL queries are configured in `.env` / `.env.example` as `PROMETHEUS_QUERY_PROCESS_*`.

## ⚙️ Key Environment Variables

### 📡 Broadcast / Cache

| Variable | Description |
|---|---|
| `BROADCAST_CONNECTION` | Must be `reverb` |
| `METRICS_BROADCAST_CHANNEL` | Channel name (default: `metrics`) |
| `METRICS_BROADCAST_EVENT` | Event name (default: `metrics.updated`) |
| `METRICS_BROADCAST_INTERVAL` | Scheduler interval in seconds |
| `METRICS_CACHE_KEY` | Cache key for snapshot |
| `METRICS_CACHE_TTL` | Cache TTL in seconds |

### 📈 Prometheus

| Variable | Description |
|---|---|
| `PROMETHEUS_BASE_URL` | Prometheus base URL |
| `PROMETHEUS_USERNAME` | Basic Auth username (optional) |
| `PROMETHEUS_PASSWORD` | Basic Auth password (optional) |
| `PROMETHEUS_TIMEOUT` | HTTP timeout in seconds |
| `PROMETHEUS_CONNECT_TIMEOUT` | Connection timeout in seconds |
| `PROMETHEUS_QUERY_*` | Individual PromQL queries |

### 🛰️ Reverb

| Variable | Description |
|---|---|
| `REVERB_APP_ID` | App ID |
| `REVERB_APP_KEY` | App key (used by clients) |
| `REVERB_APP_SECRET` | App secret |
| `REVERB_HOST` | Reverb host |
| `REVERB_PORT` | Reverb port (default: `8080`) |
| `REVERB_SCHEME` | `http` or `https` |

## 🛠️ Operational Notes

- **No updates arriving over WebSocket?**
  - Check that `reverb:start` is running.
  - Check that `schedule:work` is running.
  - Confirm `BROADCAST_CONNECTION=reverb` and Reverb app credentials match on server and client.
- **Prometheus returns empty values?**
  - Check `PROMETHEUS_BASE_URL` is reachable.
  - Verify `PROMETHEUS_USERNAME` / `PROMETHEUS_PASSWORD` if endpoint is protected.
  - Inspect individual `PROMETHEUS_QUERY_*` entries against your Prometheus UI.
- **WebSocket disconnects (~2 min)?**
  - The client must respond to Pusher ping frames. Ensure your client library handles pings automatically (Laravel Echo does this out of the box).

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE).
