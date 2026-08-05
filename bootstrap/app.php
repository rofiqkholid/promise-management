<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__.'/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SyncAllowedMenus::class,
        ]);
        $middleware->redirectGuestsTo(fn () => config('services.portal_login_url'));
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
