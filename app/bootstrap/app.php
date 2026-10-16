<?php

use App\Http\Middleware\EnsureCadastroCompleto;
use App\Http\Middleware\HandleInertiaRequests;
use App\Services\ECidade\Exceptions\ECidadeUnavailableException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'cadastro' => EnsureCadastroCompleto::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // e-cidade fora do ar é esperado: não polui o log com stack trace
        $exceptions->dontReport(ECidadeUnavailableException::class);

        $exceptions->render(fn (ECidadeUnavailableException $e, Request $request) => $request->expectsJson()
            ? null
            : Inertia::render('erro', ['status' => 503, 'ecidade' => true])->toResponse($request)->setStatusCode(503));

        // Páginas de erro amigáveis; o erro 500 detalhado continua visível com APP_DEBUG
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($e instanceof ECidadeUnavailableException
                || $request->expectsJson()
                || ! in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            if ($status === 500 && config('app.debug')) {
                return $response;
            }

            if ($status === 419) {
                return back()->with('info', 'Sua sessão expirou. Tente novamente.');
            }

            return Inertia::render('erro', ['status' => $status])->toResponse($request)->setStatusCode($status);
        });
    })->create();
