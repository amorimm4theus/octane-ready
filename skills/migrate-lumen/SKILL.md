---
name: migrate-lumen
description: Migrates a Lumen application to Laravel (optionally with Octane) step by step, keeping the same routes and behavior, and verifying with tests and a request-by-request comparison. Use when asked to migrate, upgrade or port a Lumen app to Laravel or Laravel Octane.
argument-hint: "[path-to-lumen-app] [--octane]"
---

# Migrate Lumen to Laravel (and Octane)

Lumen is no longer recommended for new projects by the Laravel team. This skill ports a Lumen app to a fresh Laravel app with the same routes, then (optionally) makes it safe for Octane.

Work in small, verifiable steps. Commit after each step. Never delete the Lumen app until the comparison passes.

## 0. Inventory (read only)

Read and summarize, then show the plan before changing anything:
- `composer.json` (Lumen version, PHP version, packages that are Lumen-specific)
- `bootstrap/app.php`: `withFacades`, `withEloquent`, `$app->configure(...)`, global and route middleware, providers, singletons
- `routes/*.php`: all routes, including regex constraints like `{id:[0-9]+}`
- `app/Http/Controllers`, middleware, jobs, events, console commands, `config/` files
- tests and how they boot the app

Use [checklist.md](checklist.md) as the mapping reference.

## 1. New Laravel app beside the old one

`composer create-project laravel/laravel <name>-laravel`. Keep the same PHP version as production unless asked otherwise.

## 2. Port in this order

1. Config and `.env` keys (Lumen reads `env()` everywhere; move to `config/*.php` and call `config()` in code, because `config:cache` makes `env()` return null outside config files).
2. Migrations, models, factories, seeders (usually copy as-is).
3. Providers and container bindings (`bootstrap/app.php` -> `AppServiceProvider` / `bootstrap/providers.php`).
4. Middleware (global -> `$middleware->append`, route middleware -> aliases in `bootstrap/app.php`).
5. Routes: keep the exact same URLs. Lumen routes have no `/api` prefix; in Laravel 11+ use `apiPrefix: ''` in `withRouting` if needed. Convert `{id:[0-9]+}` to `->whereNumber('id')`.
6. Controllers: `$this->validate($request, ...)` -> `$request->validate(...)` or add the `ValidatesRequests` trait to the base controller.
7. Exception handler: Lumen renders HTML for `abort()` unless told otherwise; decide with the user whether the API should now return JSON (`shouldRenderJsonWhen`).
8. Auth: Lumen `AuthServiceProvider::boot` `viaRequest` -> a Laravel guard or Sanctum.
9. Jobs, events, scheduled commands.

## 3. Verify behavior

- Run the existing test suite against the new app. Port tests to Pest if the project wants it (use the `pest-writer` agent, or follow [references/pest-writer.md](references/pest-writer.md)).
- Compare responses: run both apps and replay the same requests (status code + JSON body) for every route. Report every difference.

## 4. Octane (only with --octane or if the user asks)

1. `composer require laravel/octane` and `php artisan octane:install --server=swoole` (or frankenphp/roadrunner).
2. Run the `octane-auditor` agent on the ported code (or follow [references/auditor.md](references/auditor.md) yourself). Legacy Lumen code often keeps request data in static properties; this is harmless under PHP-FPM and a data leak under Octane.
3. Fix every critical/high finding, then prove it: request A with tenant/user X, request B without it, the response of B must not contain X.
4. Benchmark before/after with the same CPU/RAM limits, same database and OPcache enabled on the FPM baseline. Report real numbers only, with the hardware and limits used.

## Output

At the end, give: what was migrated, what changed in behavior (on purpose), open risks, and the benchmark numbers if measured.
