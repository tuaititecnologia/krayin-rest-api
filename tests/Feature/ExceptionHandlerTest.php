<?php

namespace Webkul\RestApi\Tests\Feature;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\RestApi\Tests\TestCase;

/**
 * Locks in the JSON error contract produced by the package's custom exception
 * handler. This is the highest-risk area of the Laravel 12 migration: the
 * handler was rewritten from the removed `App\Exceptions\Handler` base to the
 * L11/12 `register()` + `renderable()` idiom. Every branch of
 * renderCustomResponse() is covered here.
 *
 * The custom rendering only runs when APP_DEBUG is false (set in phpunit.xml).
 */
class ExceptionHandlerTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware('api')->group(function ($router) {
            $router->get('/throw/auth', fn () => throw new AuthenticationException);
            $router->get('/throw/model', fn () => throw (new ModelNotFoundException)->setModel('Lead'));
            $router->get('/throw/forbidden', fn () => abort(403));
            $router->get('/throw/teapot', fn () => abort(418));
            $router->get('/throw/pdo', fn () => throw new \PDOException('db is down'));
        });
    }

    public function test_authentication_exception_renders_json_401(): void
    {
        $this->getJson('/throw/auth')
            ->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    public function test_model_not_found_renders_json_404(): void
    {
        $this->getJson('/throw/model')
            ->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    public function test_forbidden_http_exception_renders_json_403(): void
    {
        $this->getJson('/throw/forbidden')
            ->assertStatus(403)
            ->assertJsonStructure(['message']);
    }

    public function test_unlisted_http_status_falls_back_to_500(): void
    {
        // 418 is not in the handler's {401,403,404,503} whitelist, so it must
        // collapse to a generic 500 rather than leak the teapot status.
        $this->getJson('/throw/teapot')
            ->assertStatus(500)
            ->assertJsonStructure(['message']);
    }

    public function test_pdo_exception_renders_json_500(): void
    {
        $this->getJson('/throw/pdo')
            ->assertStatus(500)
            ->assertJsonStructure(['message']);
    }
}
