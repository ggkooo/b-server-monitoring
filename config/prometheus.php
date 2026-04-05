<?php

return [

    'base_url'        => env('PROMETHEUS_BASE_URL', 'http://prometheus:9090'),
    'timeout'         => (int) env('PROMETHEUS_TIMEOUT', 5),
    'connect_timeout' => (int) env('PROMETHEUS_CONNECT_TIMEOUT', 2),
    'username'        => env('PROMETHEUS_USERNAME'),
    'password'        => env('PROMETHEUS_PASSWORD'),

    'broadcast' => [
        'channel'  => env('METRICS_BROADCAST_CHANNEL', 'metrics'),
        'event'    => env('METRICS_BROADCAST_EVENT', 'metrics.updated'),
        'interval' => (int) env('METRICS_BROADCAST_INTERVAL', 5),
    ],

    'cache' => [
        'key' => env('METRICS_CACHE_KEY', 'metrics.snapshot'),
        'ttl' => (int) env('METRICS_CACHE_TTL', 15),
    ],

    'metrics' => [

        'cpu' => [
            'usage_percent' => [
                'query' => env('PROMETHEUS_QUERY_CPU_USAGE_PERCENT'),
                'unit' => '%',
            ],
            'cores_usage_percent' => [
                'query' => env('PROMETHEUS_QUERY_CPU_CORES_USAGE_PERCENT'),
                'type' => 'vector_by_label',
                'label' => 'cpu',
                'unit' => '%',
            ],
            'iowait_percent' => [
                'query' => env('PROMETHEUS_QUERY_CPU_IOWAIT_PERCENT'),
                'unit' => '%',
            ],
            'load_average_1m' => [
                'query' => env('PROMETHEUS_QUERY_LOAD_AVERAGE_1M'),
                'unit' => 'load',
            ],
            'load_average_5m' => [
                'query' => env('PROMETHEUS_QUERY_LOAD_AVERAGE_5M'),
                'unit' => 'load',
            ],
            'load_average_15m' => [
                'query' => env('PROMETHEUS_QUERY_LOAD_AVERAGE_15M'),
                'unit' => 'load',
            ],
            'context_switches_per_second' => [
                'query' => env('PROMETHEUS_QUERY_CONTEXT_SWITCHES_PER_SECOND'),
                'unit' => 'per_second',
            ],
        ],

        'memory' => [
            'usage_percent' => [
                'query' => env('PROMETHEUS_QUERY_MEMORY_USAGE_PERCENT'),
                'unit' => '%',
            ],
            'swap_percent' => [
                'query' => env('PROMETHEUS_QUERY_SWAP_USAGE_PERCENT'),
                'unit' => '%',
            ],
            'cached_mb' => [
                'query' => env('PROMETHEUS_QUERY_MEMORY_CACHED_MB'),
                'unit' => 'mb',
            ],
        ],

        'disk' => [
            'root_used_percent' => [
                'query' => env('PROMETHEUS_QUERY_DISK_ROOT_USED_PERCENT'),
                'unit' => '%',
            ],
            'write_mb' => [
                'query' => env('PROMETHEUS_QUERY_DISK_WRITE_MB'),
                'unit' => 'mb',
            ],
            'read_mb' => [
                'query' => env('PROMETHEUS_QUERY_DISK_READ_MB'),
                'unit' => 'mb',
            ],
        ],

        'network' => [
            'download_mb' => [
                'query' => env('PROMETHEUS_QUERY_NETWORK_DOWNLOAD_MB'),
                'unit' => 'mb',
            ],
            'upload_mb' => [
                'query' => env('PROMETHEUS_QUERY_NETWORK_UPLOAD_MB'),
                'unit' => 'mb',
            ],
            'errors_total' => [
                'query' => env('PROMETHEUS_QUERY_NETWORK_ERRORS_TOTAL'),
                'unit' => 'count',
            ],
        ],

        'system' => [
            'uptime_seconds' => [
                'query' => env('PROMETHEUS_QUERY_UPTIME_SECONDS'),
                'unit' => 'seconds',
            ],
            'processes_running' => [
                'query' => env('PROMETHEUS_QUERY_PROCESSES_RUNNING'),
                'unit' => 'count',
            ],
            'file_descriptors_allocated' => [
                'query' => env('PROMETHEUS_QUERY_FILE_DESCRIPTORS_ALLOCATED'),
                'unit' => 'count',
            ],
        ],

    ],

    'processes' => [
        'list_all' => env('PROMETHEUS_QUERY_PROCESS_LIST_ALL', 'namedprocess_namegroup_cpu_seconds_total'),
        'cpu_percent' => env('PROMETHEUS_QUERY_PROCESS_CPU_PERCENT', 'sum by (groupname) (irate(namedprocess_namegroup_cpu_seconds_total[1m])) * 100'),
        'memory_resident_bytes' => env('PROMETHEUS_QUERY_PROCESS_MEMORY_RESIDENT_BYTES', 'namedprocess_namegroup_memory_bytes{memtype="resident"}'),
        'memory_virtual_bytes' => env('PROMETHEUS_QUERY_PROCESS_MEMORY_VIRTUAL_BYTES', 'namedprocess_namegroup_memory_bytes{memtype="virtual"}'),
        'threads' => env('PROMETHEUS_QUERY_PROCESS_THREADS', 'namedprocess_namegroup_num_threads'),
        'disk_read_bytes_per_second' => env('PROMETHEUS_QUERY_PROCESS_DISK_READ_BYTES_PER_SECOND', 'irate(namedprocess_namegroup_read_bytes_total[1m])'),
        'disk_write_bytes_per_second' => env('PROMETHEUS_QUERY_PROCESS_DISK_WRITE_BYTES_PER_SECOND', 'irate(namedprocess_namegroup_write_bytes_total[1m])'),
        'context_switches_per_second' => env('PROMETHEUS_QUERY_PROCESS_CONTEXT_SWITCHES_PER_SECOND', 'irate(namedprocess_namegroup_context_switches_total[1m])'),
        'open_filedesc' => env('PROMETHEUS_QUERY_PROCESS_OPEN_FILEDESC', 'namedprocess_namegroup_open_filedesc'),
        'uptime_seconds' => env('PROMETHEUS_QUERY_PROCESS_UPTIME_SECONDS', 'time() - namedprocess_namegroup_oldest_start_time_seconds'),
    ],

];
