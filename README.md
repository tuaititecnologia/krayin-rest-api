# Krayin REST API

![tests](https://github.com/tuaititecnologia/krayin-rest-api/actions/workflows/tests.yml/badge.svg)

Krayin REST API is a medium to use the features of the core Krayin System. By using Krayin REST API, you can integrate your application to serve the default content of Krayin.

> **This is a community-maintained fork** modernizing the package for **Laravel 12**. It ships a test suite and CI so the project can be safely built on and evolved, while staying clean enough to merge back upstream. See [Testing](#3-testing) and [CHANGELOG.md](CHANGELOG.md).

## 1. Requirements

* **Krayin**: v2.x (running on Laravel 12)
* **PHP**: v8.2 or higher
* **Laravel**: v12

## 2. Installation

> This fork keeps the original `krayin/rest-api` package name so it stays a drop-in
> replacement (and can be merged upstream), but it is **not published on Packagist**.
> Installing it therefore means pointing Composer at this Git repository — running a
> plain `composer require krayin/rest-api` would pull the abandoned, Laravel 11
> version from Packagist instead.

### Step 1 — Register this repository in your Krayin project

Add it to the `repositories` section of your Krayin application's `composer.json`:

~~~json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/tuaititecnologia/krayin-rest-api"
    }
]
~~~

### Step 2 — Require the package from the console

~~~shell
composer require krayin/rest-api:^3.0
~~~

Composer resolves `krayin/rest-api` from the VCS repository above (it takes
precedence over Packagist) and installs the latest `v3.x` release (Laravel 12).
To track the development branch instead of a tagged release, require `dev-main`.

### Add the following options to your .env file

~~~env
SANCTUM_STATEFUL_DOMAINS="${APP_URL}"
~~~

~~~env
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=true
~~~

### To configure the REST API with L5-Swagger documentation, run the following command

~~~shell
php artisan krayin-rest-api:install
~~~

After executing the above command, you will see the API endpoint displayed in the shell.

### Alternatively, you can check the API documentation by visiting the following URL in your browser

~~~shell
http://localhost/api/documentation
~~~

This matches the `l5-swagger` `routes.api` value and the URL printed by the
install command (`{APP_URL}/api/documentation`).

* You can check the [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) guidelines too regarding the configuration the API documentation.

### Known gotchas on an existing Krayin install

* **`personal_access_tokens` missing `expires_at`** — Sanctum 4.x reads/writes
  an `expires_at` column that didn't exist in the older Sanctum 2.x migration.
  Installs upgraded from Krayin's original 2.1.x scaffold are missing it,
  which breaks token issuance/validation. This package ships a migration that
  backfills the column automatically (a no-op if it's already there) — just
  run `php artisan migrate` after requiring the package.
* **Guest API requests must not redirect to `login`** — Laravel's `Authenticate`
  middleware can be configured (by the host app, or by a callback registered
  via `Authenticate::redirectUsing()`) to redirect unauthenticated users to a
  named `login` route. Krayin's admin panel has no such route reachable from
  the API guard, so this fork registers its own `redirectUsing()` callback
  that always returns `null` for any `api/*` request — guests get a clean
  JSON `401` instead of a `Route [login] not defined` crash. This is
  registered automatically in `RestApiServiceProvider::boot()`; no action
  needed on your end.

## 3. Testing

This package ships a fast, self-contained test suite built on [Orchestra Testbench](https://packages.tools/testbench). It boots the package inside a minimal Laravel 12 app — **no full Krayin CRM or database is required** — and verifies the surface the package owns and that the Laravel 12 upgrade touched: service-provider wiring, route registration, the `sanctum.admin` middleware, the custom exception handler's JSON contract, the OpenAPI attribute docs, the mass-action form requests, and the API resource transformers.

### Running the tests

~~~shell
composer install        # pull dev dependencies (Testbench, PHPUnit)
composer test           # run the whole suite
~~~

Useful variations:

~~~shell
vendor/bin/phpunit --testsuite Unit          # only the Unit suite
vendor/bin/phpunit --testsuite Feature       # only the Feature suite
vendor/bin/phpunit --filter test_valid_payload_passes   # a single test
composer test-coverage                       # text coverage report (needs Xdebug/PCOV)
~~~

### Layout & philosophy

* `tests/Unit/` — pure classes exercised in isolation (form requests, resource transformers, the install command's metadata).
* `tests/Feature/` — the package booted in a container: provider wiring, route table, middleware, exception rendering, and Swagger generation.
* `tests/TestCase.php` — the shared Testbench base; it registers only `RestApiServiceProvider` + Sanctum, keeping the suite portable and CI-friendly.

Because the tests never dispatch to controllers that depend on Krayin domain packages, they run anywhere in seconds. Full end-to-end controller tests against a live Krayin install are the natural next layer to add on top of this foundation.

CI runs the suite on PHP 8.2, 8.3, and 8.4 via GitHub Actions (`.github/workflows/tests.yml`).
