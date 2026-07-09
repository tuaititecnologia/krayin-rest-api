<?php

namespace Webkul\RestApi\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Webkul\RestApi\Tests\Fixtures\User;
use Webkul\RestApi\Tests\TestCase;

/**
 * Exercises the `sanctum.admin` middleware end-to-end through a throwaway route,
 * covering the token-based authentication branch (the API path) and the
 * unauthenticated rejection. The middleware grants access only to tokens that
 * carry the `role:admin` ability.
 */
class AdminMiddlewareTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware(['api', 'sanctum.admin'])->get('/test-admin', function () {
            return response()->json(['ok' => true]);
        });
    }

    public function test_token_with_admin_ability_is_allowed(): void
    {
        Sanctum::actingAs(new User(['id' => 1]), ['role:admin']);

        $this->getJson('/test-admin')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_token_without_admin_ability_is_rejected(): void
    {
        Sanctum::actingAs(new User(['id' => 1]), ['role:agent']);

        $this->getJson('/test-admin')->assertStatus(401);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/test-admin')->assertStatus(401);
    }
}
