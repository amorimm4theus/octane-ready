# Project guide for Claude Code

## Stack
- PHP {{php}} · Laravel {{laravel}} · {{octane_server}} via Laravel Octane
- Database: {{database}} · Cache/queue: {{cache}} / {{queue}}
- Tests: {{pest_or_phpunit}} · Static analysis: {{phpstan}} · Formatter: Pint

## Commands
- Run tests: `{{test_command}}`
- Single test: `{{test_command}} --filter=SomeTest`
- Format: `vendor/bin/pint --dirty`
- Static analysis: `{{phpstan_command}}`
- Local server: `{{serve_command}}`

## Rules
- This app runs on Octane (long-lived workers). Never keep request data (user, tenant, locale, request) in static properties or singletons. Use `$app->scoped()` or pass values as arguments.
- Use Form Requests for validation and policies for authorization.
- Money and stock changes go inside `DB::transaction`. For a single counter use an atomic conditional update (`where('stock', '>=', $q)->decrement(...)` and check affected rows); use `lockForUpdate()` when several values decide together.
- Every new endpoint gets a Pest feature test (happy path, 422, 403/404).
- Use `config()` in code; `env()` only inside `config/*.php`.
- Never read or print `.env` or secrets.

## Workflow
- Branch from `main`, open a PR (GitHub Flow). Small PRs.
- Before finishing: tests green, Pint clean, PHPStan clean.
- For reviews use the `laravel-reviewer` agent; for Octane risks use `octane-auditor`.
