<?php

namespace Webkul\RestApi\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\RestApi\Exceptions\Handler;

class RestApiServiceProvider extends ServiceProvider
{
    /**
     * Register your middleware aliases here.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'sanctum.admin' => \Webkul\RestApi\Http\Middleware\AdminMiddleware::class,
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->activateMiddlewareAliases();

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'rest-api');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            __DIR__.'/../Config/l5-swagger.php' => config_path('l5-swagger.php'),
        ], 'krayin-rest-api-swagger');

        $this->app->singleton(ExceptionHandler::class, Handler::class);

        $this->configureGuestAuthRedirect();
    }

    /**
     * Ensure an unauthenticated api/* request never falls back to a
     * `route('login')` redirect.
     *
     * Laravel's Authenticate middleware calls this callback (if one is
     * registered by any provider) to build the AuthenticationException's
     * redirect target. Krayin's admin panel has no named `login` route
     * reachable from the API guard, so resolving it throws
     * RouteNotFoundException — surfacing as a raw 500 instead of the 401 JSON
     * our own exception handler otherwise renders for AuthenticationException.
     * Scoping the null-return to api/* preserves whatever redirect behavior
     * the host app relies on for its own web/admin routes.
     *
     * @return void
     */
    protected function configureGuestAuthRedirect()
    {
        Authenticate::redirectUsing(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            return Route::has('login') ? route('login') : null;
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mapApiRoutes();

        $this->registerCommands();
    }

    /**
     * Define the "api" routes for the application.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['api', \Webkul\RestApi\Http\Middleware\ForceJsonResponse::class])
            ->group(__DIR__.'/../Routes/api.php');
    }

    /**
     * Register commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Webkul\RestApi\Console\Commands\Install::class,
            ]);
        }
    }

    /**
     * Activate middleware aliases.
     *
     * @return void
     */
    protected function activateMiddlewareAliases()
    {
        collect($this->middlewareAliases)->each(function ($className, $alias) {
            $this->app['router']->aliasMiddleware($alias, $className);
        });
    }
}
