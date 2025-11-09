<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Daftarkan middleware route khusus (alias)
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Jika ingin juga menambah global middleware (berlaku di semua request):
        // $middleware->append(\App\Http\Middleware\EnsureRole::class);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
