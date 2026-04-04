<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('metrics:broadcast')->everyFiveSeconds();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
