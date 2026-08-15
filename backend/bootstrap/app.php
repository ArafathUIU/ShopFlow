<?php

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidCheckoutException;
use App\Exceptions\InvalidCouponException;
use App\Http\Middleware\EnsureRoleIs;
use App\Services\Orders\PricingService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSingletons([
        PricingService::class => fn (): PricingService => PricingService::fromConfig(),
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRoleIs::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $forApi = fn (string $requestPath): bool => str_starts_with($requestPath, 'api/');

        $exceptions->render(function (ValidationException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error('The given data was invalid.', 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error('Unauthenticated.', 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error('You are not authorized to perform this action.', 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error('Resource not found.', 404);
            }
        });

        $exceptions->render(function (InsufficientStockException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error($e->getMessage(), 422);
            }
        });

        $exceptions->render(function (InvalidCouponException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error($e->getMessage(), 422);
            }
        });

        $exceptions->render(function (InvalidCheckoutException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error($e->getMessage(), 422);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error('Route not found.', 404);
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error('Method not allowed.', 405);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($forApi) {
            if ($forApi($request->path())) {
                return ApiResponse::error('Too many requests.', 429);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($forApi) {
            if (! $forApi($request->path())) {
                return;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if (config('app.debug')) {
                return ApiResponse::error($e->getMessage(), $status);
            }

            Log::error('Unhandled exception', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'path' => $request->path(),
            ]);

            return ApiResponse::error(
                $status >= 500 ? 'Server error.' : 'Request failed.',
                $status,
            );
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*');
        });
    })->create();
