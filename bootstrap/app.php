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
        $middleware->redirectGuestsTo(env('PORTAL_LOGIN_URL', 'https://promise.summitadyawinsa.co.id/login'));
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: [
            'promise_auth_session'
        ]);
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'check.menu' => \App\Http\Middleware\CheckMenuAccess::class,
            'log.seen' => \App\Http\Middleware\LogLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
