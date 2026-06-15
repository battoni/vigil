<?php

use App\Http\Middleware\EnsureUserPermission;
use App\Modules\Check\Console\Commands\DispatchDueChecksCommand;
use App\Modules\Check\Console\Commands\SweepHeartbeatsCommand;
use App\Modules\Monitor\Console\Commands\MaintainCheckPartitionsCommand;
use App\Modules\Monitor\Console\Commands\RollupDailyCommand;
use App\Modules\Monitor\Console\Commands\RollupHourlyCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        DispatchDueChecksCommand::class,
        SweepHeartbeatsCommand::class,
        RollupHourlyCommand::class,
        RollupDailyCommand::class,
        MaintainCheckPartitionsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            HandleCors::class,
            StartSession::class,
        ]);

        $middleware->web(append: [
            HandleCors::class,
        ]);

        $middleware->alias([
            'permission' => EnsureUserPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
