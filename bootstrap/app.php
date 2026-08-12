<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            // Semua halaman (termasuk login publik) dilarang di-cache CDN/proxy
            // agar CSRF token & cookie sesi tidak pernah basi. Ini juga
            // melindungi route login dari bug 419 yang sama seperti ujian.
            \App\Http\Middleware\PreventPageCache::class,
        ]);
        $middleware->alias([
            'student' => \App\Http\Middleware\AuthStudent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
