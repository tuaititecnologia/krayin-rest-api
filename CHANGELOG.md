# CHANGELOG
This changelog consists of the bug & security fixes and new features being included in the releases listed below.

## **v3.0.0 (8th of July 2026)** - *Laravel 12 support*

* Added Laravel 12 support.
* Added a lightweight PHPUnit test suite (Orchestra Testbench, no full Krayin CRM required) and a GitHub Actions CI matrix across PHP 8.2 / 8.3 / 8.4.
* Bumped `darkaonline/l5-swagger` to `^9.0` (the first release line that supports Laravel 12) and added explicit `php: ^8.2` and `laravel/sanctum: ^4.0` constraints.
* Migrated `Webkul\RestApi\Exceptions\Handler` off the removed `App\Exceptions\Handler` base class; it now extends `Illuminate\Foundation\Exceptions\Handler` and registers its custom rendering via the Laravel 11/12 `register()` / `renderable()` idiom.
* Removed the `Illuminate\Foundation\Auth\SendsPasswordResetEmails` trait from `AuthController`. The trait was deleted in Laravel 11/12 and would fatally error (`Trait not found`) as soon as the controller loaded; it was unused, since `forgotPassword()` calls `Password::broker()->sendResetLink()` directly.
* Converted all Swagger/OpenAPI documentation from doctrine docblock annotations (`@OA\...`) to PHP 8 attributes (`#[OA\...]`) and added the previously-missing `sanctum_admin` security scheme.
* Fixed the `sanctum.admin` middleware alias, which pointed at a non-existent `Webkul\RestApi\Middleware\AdminMiddleware` class instead of `Webkul\RestApi\Http\Middleware\AdminMiddleware`.
* Fixed the `Workflow` model docs: the `conditions` and `actions` array items had a malformed nested schema that swagger-php silently dropped (rendering empty `items`); they now correctly document their object item structure.
* Cleaned up copy-pasted `Role` titles on the `Campaign` model's `marketing_email_template` / `marketing_event` properties.

## **v2.0.0 (16th of September 2024)** - *Release*

* Krayin version 2 compatibility has been completed.
