<?php

use App\Console\Commands\AppSetup;
use App\Console\Commands\RunConfiguration;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Configurations;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Schema;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        RunConfiguration::class,
        AppSetup::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        if (!Schema::hasTable('configurations')) {
            return;
        }
        $configs = Configurations::all();
        foreach ($configs as $config) {
            if (!$config->cron_expression) continue;
            try {
                new \Cron\CronExpression($config->cron_expression);
            } catch (Throwable $e) {
                continue;
            }
            $schedule->command("configuration:run {$config->id} --method=sequential -v")
                ->cron($config->cron_expression)
                ->withoutOverlapping()
                ->onOneServer();
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
