# CHANGELOG
This changelog consists of the bug & security fixes and new features being included in the releases listed below.

## **Unreleased** - *Upstream issue backlog sweep*

Addresses the recurring bug patterns reported against `krayin/rest-api`. Shared
infrastructure fixes each pattern once for the ~40 controllers:

* Added a `ForceJsonResponse` middleware to the `api` group so every error is
  rendered as JSON instead of an HTML error page.
* Hardened the exception handler to render `ModelNotFoundException` /
  `NotFoundHttpException` as a JSON 404, `ValidationException` as 422 (with field
  errors) and `AuthorizationException` as 403 — even in debug mode — and dropped
  the host `admin::` fallback message key.
* Added CRUD helpers to the base V1 controller (`findOrFailResource`,
  `respondSuccess` / `respondError`, `destroyResource`, `massDestroyResources`)
  so `show`/`update`/`destroy` return a proper 404, `destroy` sends the real HTTP
  status (previously swallowed by `new JsonResource([...], 500)`), and mass-delete
  reports the real deleted count instead of a blanket success.
* `allResources()` now validates sort/filter columns against the table, returning
  422 for an unknown sort column and ignoring unknown filters instead of a 500.
* Per-module fixes across Settings (attributes, users, groups, roles, pipelines,
  webhooks, sources, types, email-templates, web-forms, tags, warehouses,
  locations, marketing, imports), Contacts, Quotes, Mails, Activities, Products,
  Leads and Configuration: `find` → `findOrFail`, missing validation (enums,
  negative numbers, unique names, boolean flags, FK `exists`, country/locale),
  corrected success/error messages, added missing translation keys in all five
  locales, and `DELETE` support on `mass-destroy` where the wrong verb was used.
* Removed the dead singular route files (`setting-routes.php`,
  `activity-routes.php`) and added numeric `{id}` route constraints.

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
