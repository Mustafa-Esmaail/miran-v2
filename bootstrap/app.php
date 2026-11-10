<?php

use App\Support\ExceptionPresenter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', \App\Http\Middleware\SetLocale::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CaptureReferral::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\CaptureUtm::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\LogImpersonatedActivity::class);

        $middleware->alias([
            'api.client' => \App\Http\Middleware\AuthenticateApiClient::class,
            'api.ability' => \App\Http\Middleware\EnsureApiClientAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // On CSRF failure, redirect to the appropriate login page instead of showing a raw 419.
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null; // let Livewire / AJAX callers handle their own 419
            }
            if ($request->is('admin*')) {
                return redirect()->route('filament.admin.auth.login');
            }

            return redirect()->route('login')->withErrors(['session' => __('auth.session_expired')]);
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($exception instanceof ValidationException
                || $exception instanceof HttpExceptionInterface
                || $exception instanceof AuthenticationException) {
                return null;
            }

            return app(ExceptionPresenter::class)->toResponse($exception, $request);
        });

        // ValidationException/HttpExceptionInterface are excluded above and rendered by Laravel's
        // defaults, so they never reach ExceptionPresenter's logging. Still record 5xx cases here.
        $exceptions->reportable(function (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() < 500) {
                return;
            }

            Log::channel('filament_error')->error('[HTTP-'.$exception->getStatusCode().'] '.$exception->getMessage(), [
                'exception' => get_class($exception),
                'status' => $exception->getStatusCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => request()?->fullUrl(),
                'user_id' => auth()->id(),
                'trace' => $exception->getTraceAsString(),
            ]);
        });
    })->create();
