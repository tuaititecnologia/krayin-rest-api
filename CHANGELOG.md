# CHANGELOG
This changelog consists of the bug & security fixes and new features being included in the releases listed below.

## **v3.2.0 (22nd of July 2026)** - *Published on Packagist*

Distribution change only — no `/v1/` API behavior changes. The fork is now an
installable package (`composer require tuaititecnologia/krayin-rest-api`) instead
of a manual VCS-repository install.

* Renamed the Composer package from `krayin/rest-api` to
  `tuaititecnologia/krayin-rest-api` and published it on Packagist. The
  `Webkul\RestApi` namespace and the auto-discovered `RestApiServiceProvider`
  are unchanged, and the package declares `replace: krayin/rest-api`, so it stays
  a **drop-in replacement**: no application code changes are required.
* Added a `LICENSE` file (MIT), preserving attribution to the original
  `krayin/rest-api` authors (Webkul) alongside the fork's copyright, and filled
  in Packagist metadata (`description`, `keywords`, `homepage`, `support`).
* Fixed the L5-Swagger `annotations` path, which was hardcoded to
  `vendor/krayin/rest-api/src/Docs`. It now points at the package's real install
  directory (`vendor/tuaititecnologia/krayin-rest-api/src/Docs`) so the API
  documentation still generates after the rename.
* Updated the README install instructions from the VCS-repository workaround to a
  plain `composer require`, with a migration note for existing installs.

## **v3.1.0 (21st of July 2026)** - *Participants, custom fields & pipeline PUT*

Three REST-layer bug fixes plus the matching OpenAPI docs. All changes are
backward compatible — the `/v1/` contract is unchanged for existing consumers:
the participants and custom-field changes are additive, and the pipeline PUT fix
repairs a previously-broken (500) endpoint. Covered by unit tests and by the
live-Krayin integration suite, including error cases (malformed bodies → clean
422, never a 500).

* Fixed activity `participants` never being linked on `POST`/`PUT /activities`.
  The core `ActivityRepository` only understands the nested
  `participants[users][]` / `participants[persons][]` shape, so a flat
  `participants:[1,2]` (the natural REST shape) silently linked nobody, and
  `store()` did not handle participants at all. The controller now normalizes
  either shape into the nested form before delegating to the core repository
  (a flat array is treated as user ids; persons still require the nested shape),
  validates that each id exists (`exists:users,id` / `exists:persons,id`), and
  drops the duplicate participant loop `update()` carried (it read the wrong,
  flat keys). This also fixes the meeting-overlap check, which previously
  received a flat array and skipped participant filtering.
* Custom (EAV) fields are now returned by the Lead, Organization and Person
  resources. They were saved correctly but never read back — the resources
  exposed only a fixed whitelist. A new `InteractsWithCustomAttributes` concern
  merges each entity's user-defined attribute values (`code => value`) at the
  top level, exactly as the panel shows them, using Krayin's public
  `getCustomAttributes()` / `getCustomAttributeValue()`. The whitelist is merged
  last so existing response keys never change, and the list endpoints eager-load
  `attribute_values` to avoid N+1.
* Fixed `PUT settings/pipelines/{id}` returning a 500 and leaving a partial write
  (new stages piled on top of the old ones). The controller now validates
  `stages` (required, non-empty), normalizes the incoming list into the
  associative shape the core `PipelineRepository` expects (existing stages keyed
  by id → update, new stages keyed `stage_<n>` → create, omitted stages → delete
  = sync), rejects a stage id belonging to another pipeline, and wraps the write
  in a transaction so a mid-write failure rolls back instead of half-updating.
  Also stopped type-hinting the core `PipelineForm`, whose create-vs-update
  detection keys off `request('id')` (a route parameter, not body input, over
  REST) and mis-applied the create ruleset on updates. The stage request contract
  is now a list of `{id?, name, code, probability?, sort_order?}`.
* Updated the OpenAPI docs to match: the pipeline `stages` request body is now
  documented as a list of `{id?, name, code, sort_order?, probability?}`, the
  activity `participants` field documents the flat user-id array, and the Lead /
  Organization / Person schemas note the additional user-defined custom-field
  properties.

## **v3.0.1 (9th of July 2026)** - *Upstream issue backlog sweep*

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
* Added a migration that backfills the `expires_at` column on
  `personal_access_tokens` for installs upgraded from a pre-Sanctum-4 schema
  (Krayin's original 2.1.x scaffold never had it); safe no-op otherwise.
* Registered `Authenticate::redirectUsing()` so a guest hitting any `api/*`
  route always gets a clean JSON 401 instead of crashing with
  `Route [login] not defined` (Krayin's admin panel has no named `login`
  route reachable from the API guard). The exception handler's catch-all also
  now always renders JSON for `api/*` requests, even in debug mode, so no
  unmapped exception can leak an HTML debug trace to an API client.
* Fixed `Webkul\Admin\AdminServiceProvider` silently disabling this package's
  JSON error contract: it also rebinds `ExceptionHandler::class` (for its own
  error views), and since provider boot order between packages isn't
  something we control, whichever provider's `boot()` ran last used to win.
  The binding is now deferred to the application's `booted()` callback queue,
  which always fires after every provider's `boot()` has completed, so our
  handler wins regardless of provider order.

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
