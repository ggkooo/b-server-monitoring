<?php

use App\Http\Controllers\MetricsSnapshotController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.key')->group(function () {
    Route::get('/metrics/snapshot', MetricsSnapshotController::class);
});
