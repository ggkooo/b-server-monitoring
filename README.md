# b-server-monitoring

Real-time server monitoring backend powered by Prometheus + Laravel Reverb.

This project collects host and process metrics from Prometheus, exposes an authenticated snapshot API, and broadcasts live updates over WebSocket.

## Highlights

- ⚡ Instant metrics snapshot for first page load.
- 📡 Real-time updates over WebSocket channel `metrics`.
- 🧠 Process table (htop-like) grouped by `groupname` from `namedprocess_*` metrics.
- 🔐 API key middleware for protected API access.
- 🪶 Stateless runtime defaults (no database required for normal operation).

## 🏗️ Architecture

### 🔄 Data Flow

1. Prometheus is queried by service classes.
2. A snapshot is assembled with:
	- Global metrics (`cpu`, `memory`, `disk`, `network`, `system`)
	- Process table (`processes.table`)
3. Snapshot is cached for fast API response.
4. Snapshot is broadcast as `metrics.updated` on channel `metrics`.

### 🧩 Main Components

- `PrometheusService`: executes PromQL queries (scalar and vector).
- `MetricsCollector`: collects global grouped metrics.
- `ProcessMetricsService`: builds process table by `groupname`.
- `MetricsSnapshotService`: merges data and manages cache.
- `BroadcastMetrics` command: refreshes snapshot and dispatches broadcast event.
- `MetricsBroadcast` event: sends payload over Reverb.
- `ApiKeyMiddleware`: protects API endpoints via `X-API-Key` header.

## 📋 Requirements

- PHP 8.3+
- Composer
- Running Prometheus endpoint
- namedprocess exporter metrics available in Prometheus (for process table)

## 🚀 Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set required environment variables in `.env`:

- `API_KEY`
- `PROMETHEUS_BASE_URL`
- `PROMETHEUS_USERNAME` and `PROMETHEUS_PASSWORD` (if needed)
- `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`
- `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`

## 🧪 Run Locally

Use three terminals:

```bash
# Terminal 1
php artisan serve

# Terminal 2
php artisan reverb:start

# Terminal 3
php artisan schedule:work
```

## 🔌 API

### 📥 Get Snapshot

Endpoint:

```text
GET /api/metrics/snapshot
```

Required header:

```text
X-API-Key: <your_api_key>
```

Example:

```bash
curl -H "X-API-Key: YOUR_API_KEY" http://127.0.0.1:8000/api/metrics/snapshot
```

### 🧾 Snapshot Response Shape

```json
{
    "metrics": {
        "cpu": {
            "usage_percent": 0.3333,
            "cores_usage_percent": 0,
            "iowait_percent": 0.0333,
            "temperature_celsius": null,
            "load_average_1m": 0.23,
            "load_average_5m": 0.08,
            "load_average_15m": 0.05,
            "context_switches_per_second": 408
        },
        "memory": {
            "usage_percent": 4.6193,
            "swap_percent": 0,
            "cached_mb": 695.7734
        },
        "disk": {
            "root_used_percent": 23.8693,
            "write_mb": 0.0141,
            "read_mb": 0
        },
        "network": {
            "download_mb": 0.0001,
            "upload_mb": 0.0026,
            "errors_total": 0
        },
        "system": {
            "uptime_seconds": 8706.332,
            "processes_running": 2,
            "file_descriptors_allocated": 1088
        }
    },
    "processes": {
        "count": 2,
        "table": [
            {
                "name": "process-exporte",
                "cpu_percent": 1,
                "memory_resident_bytes": 20889600,
                "memory_virtual_bytes": 1268699136,
                "threads": 12,
                "disk_read_bytes_per_second": 0,
                "disk_write_bytes_per_second": 0,
                "context_switches_per_second": 45.8,
                "open_filedesc": 7,
                "uptime_seconds": 1507.322
            },
            {
                "name": "htop",
                "cpu_percent": 0,
                "memory_resident_bytes": 0,
                "memory_virtual_bytes": 0,
                "threads": 0,
                "disk_read_bytes_per_second": 0,
                "disk_write_bytes_per_second": 0,
                "context_switches_per_second": 0,
                "open_filedesc": 0,
                "uptime_seconds": 63910853230.322
            }
        ]
    },
    "generated_at": "2026-04-03T22:47:10+00:00"
}
```

## 📶 WebSocket

- Channel: `metrics`
- Event: `metrics.updated`

Connect to Reverb using Pusher protocol and subscribe to `metrics`. Every schedule tick sends a fresh snapshot payload.

## 🖥️ Process Table Metrics

The process table is built from `namedprocess_*` metrics and grouped by `groupname`.

Columns returned:

- `name`
- `cpu_percent`
- `memory_resident_bytes`
- `memory_virtual_bytes`
- `threads`
- `disk_read_bytes_per_second`
- `disk_write_bytes_per_second`
- `context_switches_per_second`
- `open_filedesc`
- `uptime_seconds`

Configured PromQL entries are available in `.env.example` as `PROMETHEUS_QUERY_PROCESS_*`.

## ⚙️ Important Environment Variables

### 🔐 App / Auth

- `API_KEY`

### 📡 Broadcast / Cache

- `BROADCAST_CONNECTION`
- `METRICS_BROADCAST_CHANNEL`
- `METRICS_BROADCAST_EVENT`
- `METRICS_BROADCAST_INTERVAL`
- `METRICS_CACHE_KEY`
- `METRICS_CACHE_TTL`

### 📈 Prometheus

- `PROMETHEUS_BASE_URL`
- `PROMETHEUS_USERNAME`
- `PROMETHEUS_PASSWORD`
- `PROMETHEUS_TIMEOUT`
- `PROMETHEUS_CONNECT_TIMEOUT`
- `PROMETHEUS_QUERY_*`

### 🛰️ Reverb

- `REVERB_APP_ID`
- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`
- `REVERB_HOST`
- `REVERB_PORT`
- `REVERB_SCHEME`

## 🛠️ Operational Notes

- If first page load seems empty, check:
  - Prometheus connectivity
  - API key header
  - `schedule:work` process running
- If WebSocket connects but no updates arrive, check:
  - `reverb:start` process running
  - Scheduler running
  - Event and channel names in env/config

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE).
