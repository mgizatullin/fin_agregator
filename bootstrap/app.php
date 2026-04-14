<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('web', [
            \App\Http\Middleware\RedirectTrailingSlash::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'livewire/*',
            'livewire-*/update',
        ]);

        // Some stacks still register VerifyCsrfToken directly; ensure both variants are excluded.
        ValidateCsrfToken::except(['livewire/*', 'livewire-*/update']);
        VerifyCsrfToken::except(['livewire/*', 'livewire-*/update']);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('cbr:fetch-rates')->cron('0 */3 * * *');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
