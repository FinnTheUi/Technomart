<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RoleMiddleware;
use App\Support\SafeMail;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Deactivated accounts are signed out on their next request.
        $middleware->web(append: [EnsureUserIsActive::class]);

        // Webhooks authenticate by signature, not by session.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // A mail-server problem (e.g. wrong SMTP password) sends the user back with a
        // friendly message instead of an error page. It is still logged for the admin.
        $exceptions->render(function (TransportExceptionInterface $e, Request $request) {
            if ($request->expectsJson() || $request->isMethod('GET')) {
                return null;
            }

            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['mail' => SafeMail::FAILURE_MESSAGE])
                ->with('error', SafeMail::FAILURE_MESSAGE);
        });
    })->create();
