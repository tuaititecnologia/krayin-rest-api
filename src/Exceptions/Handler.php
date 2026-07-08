<?php

namespace Webkul\RestApi\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use PDOException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        /**
         * Suppress reporting, mirroring the previous no-op report() override.
         */
        $this->reportable(fn (Throwable $e) => false);

        /**
         * Always answer authentication failures with a JSON 401 for this API.
         */
        $this->renderable(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'message' => $this->jsonErrorMessages()[401],
            ], 401);
        });

        /**
         * Render custom error responses when not running in debug mode.
         */
        $this->renderable(function (Throwable $e, Request $request) {
            if (config('app.debug')) {
                return null;
            }

            return $this->renderCustomResponse($e, $request);
        });
    }

    /**
     * Localized JSON error messages keyed by status code.
     *
     * @return array<int, string>
     */
    protected function jsonErrorMessages(): array
    {
        return [
            401 => trans('rest-api::app.common.unauthenticated'),
            403 => trans('rest-api::app.common.forbidden-error'),
            404 => trans('rest-api::app.common.resource-not-found'),
            500 => trans('rest-api::app.common.internal-server-error'),
        ];
    }

    /**
     * Render a custom response for known exception types.
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    protected function renderCustomResponse(Throwable $exception, Request $request)
    {
        if ($exception instanceof HttpException) {
            $statusCode = in_array($exception->getStatusCode(), [401, 403, 404, 503])
                ? $exception->getStatusCode()
                : 500;

            return $this->response($request, $statusCode);
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->response($request, 404);
        }

        if ($exception instanceof PDOException || $exception instanceof \ParseError) {
            return $this->response($request, 500);
        }

        return null;
    }

    /**
     * Build the response for a given status code.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function response(Request $request, int $statusCode)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->jsonErrorMessages()[$statusCode]
                    ?? trans('admin::app.common.something-went-wrong'),
            ], $statusCode);
        }

        return response()->view("admin::errors.{$statusCode}", [], $statusCode);
    }
}
