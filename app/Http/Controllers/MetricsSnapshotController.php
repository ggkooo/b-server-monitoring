<?php

namespace App\Http\Controllers;

use App\Services\MetricsSnapshotService;
use Illuminate\Http\JsonResponse;

class MetricsSnapshotController
{
    public function __invoke(MetricsSnapshotService $snapshots): JsonResponse
    {
        return response()->json($snapshots->latest());
    }
}
