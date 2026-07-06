<?php

declare(strict_types=1);

use App\Domain\Shared\Exceptions\DomainException;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SetLocaleFromHeader;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum stateful middleware for SPA / first-party clients.
        $middleware->statefulApi();

        // Resolve the request locale from the Accept-Language header (en|ar),
        // then the tenant (header / subdomain / default) so public routes are
        // tenant-scoped before auth runs.
        $middleware->api(append: [
            SetLocaleFromHeader::class,
            ResolveTenant::class,
        ]);

        $middleware->alias([
            'locale' => SetLocaleFromHeader::class,
            'tenant' => ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON for all API requests so the Flutter client always
        // receives the { message, errors? } envelope it expects.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            // Domain exceptions carry their own HTTP status + key.
            if ($e instanceof DomainException) {
                return response()->json(array_filter([
                    'code' => $e->errorCode(),
                    'message' => $e->getMessage(),
                    'errors' => $e->errors() ?: null,
                ], static fn ($v): bool => $v !== null), $e->status());
            }

            // 422 — validation.
            if ($e instanceof ValidationException) {
                return response()->json([
                    'code' => 'common.validation',
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], $e->status);
            }

            // 401 — invalid / missing / expired token.
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => __('api.unauthenticated'),
                ], 401);
            }

            // 404 — missing model or route.
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'code' => 'common.not_found',
                    'message' => __('api.not_found'),
                ], 404);
            }

            // Any other HTTP exception keeps its status.
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: __('api.server_error'),
                ], $e->getStatusCode());
            }

            // 500 — unexpected. Hide details outside local/debug.
            $status = 500;
            $message = config('app.debug')
                ? $e->getMessage()
                : __('api.server_error');

            return response()->json([
                'message' => $message,
            ], $status);
        });
    })->create();
