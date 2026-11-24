<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
        $middleware->preventRequestForgery(except: [
            'support/*/conversations',
            'support/conversations/*/poll',
            'support/conversations/*/messages',
            'support/conversations/*/rating',
            'support/conversations/*/end',
            'support/broadcasting/auth',
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('agent/*')
            ? route('agent.login')
            : route('login'));

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user?->isAgent()) {
                return route('agent.dashboard');
            }

            return $user?->hasVerifiedEmail()
                ? route('admin.dashboard')
                : route('verification.notice');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
