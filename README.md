# octane-ready

[![CI](https://github.com/amorimm4theus/octane-ready/actions/workflows/ci.yml/badge.svg)](https://github.com/amorimm4theus/octane-ready/actions/workflows/ci.yml)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4)
![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20)
![License MIT](https://img.shields.io/badge/license-MIT-green)

**Make Laravel apps Octane-ready with Claude Code.** Migrate Lumen to Laravel + Octane, catch the state leaks that only appear under Swoole, and prove it with tests and a reproducible benchmark.

[Leia em português](README.pt-BR.md)

---

Moving a PHP API to Laravel Octane removes the framework bootstrap from every request. The catch: under PHP-FPM each request starts from zero, under Octane one worker serves thousands of requests. Code that kept request data in a `static` property was harmless for years and becomes **one customer seeing another customer's data**.

This repository has two parts:

1. **A Claude Code plugin** with skills, agents and hooks for that migration.
2. **A worked example** that shows the whole path in its commit history: a legacy Lumen API, a naive port that leaks, tests that expose the leaks, the fix, a refactor, and a benchmark you can run yourself.

## Install the plugin

```bash
claude plugin marketplace add amorimm4theus/octane-ready
claude plugin install octane-ready@octane-ready
```

| Type | Name | Purpose |
|---|---|---|
| Skill | `/octane-ready:migrate-lumen` | Ports a Lumen app to Laravel step by step, compares responses, then optionally enables Octane |
| Skill | `/octane-ready:octane-audit` | Audits the project for long-lived-worker bugs, fixes them with tests on request (`--fix`) |
| Skill | `/octane-ready:laravel-claude-md` | Writes a `CLAUDE.md` with the real stack and commands (merges with Laravel Boost's) |
| Agent | `octane-auditor` | Read-only analysis: static request state, singletons capturing the Request, unbounded static caches |
| Agent | `laravel-reviewer` | Read-only diff review: N+1, authorization, validation, transactions and locks, Octane safety |
| Agent | `pest-writer` | Writes Pest tests, including "request A, then request B on the same worker" |
| Hook | `protect-env` | Blocks reading or writing `.env` files (`.env.example` is allowed) |
| Hook | `octane-guard` | In apps with `laravel/octane`, warns Claude right after it writes a static property in `app/` |
| Hook | `pint-on-edit` | Formats each edited PHP file with the project's Pint |

Works alongside [Laravel Boost](https://github.com/laravel/boost): Boost covers general Laravel guidelines, this kit stays focused on migration and long-lived workers. Hooks only need `php` on the PATH.

## The worked example

[`examples/lumen-api`](examples/lumen-api) is a small multi-tenant store API in Lumen 10, written the way a lot of legacy code is written:

```php
class TenantContext
{
    private static ?string $tenant = null;          // request data in a static property
    public static function set(string $t): void { self::$tenant = $t; }
    public static function id(): string { return self::$tenant ?? 'public'; }
}
```

[`examples/octane-api`](examples/octane-api) is the same API on Laravel 13 + Octane (Swoole). Read the history with `git log --oneline --reverse`. Each step is one commit:

| Step | Commit | State |
|---|---|---|
| 1 | `feat(lumen-api)` legacy baseline | Lumen on PHP-FPM, works |
| 2 | `test(octane-api)` public API contract | **red**: nothing ported yet |
| 3 | `feat(octane-api)` port Lumen code as-is | contract **green** |
| 4 | `test(octane-api)` reproduce leaks between requests | **red**: tenant, locale and cache leak |
| 5 | `fix(octane-api)` move request state out of statics and singletons | **green** |
| 6 | `refactor(octane-api)` Form Request, Action, API Resource | still green, controllers thin |
| 7 | `test(octane-api)` architecture rules | static state and fat controllers now fail the build |

The naive port against the real Swoole server ([`benchmark/leak-check.sh`](benchmark/leak-check.sh)):

```
FAIL tenant: request without header got 'acme' (leaked from a previous request)
FAIL memory: static cache grew to 2001 entries and never shrinks
```

After the fix, all checks pass ([`benchmark/results`](benchmark/results)).

## Architecture

```
HTTP request
  └─ ResolveTenant middleware ──> TenantContext        (scoped: new per request)
       └─ Controller (thin)
            ├─ StoreOrderRequest                       (validation)
            ├─ PlaceOrder action                       (transaction + row locks)
            │     └─ InsufficientStock exception       (renders 422 JSON)
            └─ ProductResource ──> PriceFormatter      (singleton, stateless)
```

### Decisions and why

Every decision has an [Architecture Decision Record](docs/adr) (also [in Portuguese](docs/adr/pt-BR)). In short:

| Decision | Why |
|---|---|
| [Keep the Lumen API contract](docs/adr/0001-keep-the-public-api-contract.md) (same URLs, no `data` wrapper) | Changing the framework and the contract at once doubles the risk. Clients do not change, rollback is a load balancer switch. |
| [Request state is scoped, services are stateless](docs/adr/0002-request-state-is-scoped-services-are-stateless.md) | `$app->scoped()` is flushed by Octane between requests. Stateless singletons are safe to share and trivial to unit test. |
| [Tests emulate worker reuse](docs/adr/0003-tests-emulate-worker-reuse.md) | Two requests on one app instance, resetting what Octane resets. Leaks fail in milliseconds in CI, no Swoole needed. |
| [Orders through an Action with row locks](docs/adr/0004-orders-use-an-action-with-row-locks.md) | `lockForUpdate()` inside one transaction: two concurrent orders cannot sell the last unit twice. The Action is reusable from jobs and commands. |
| [Honest benchmark](docs/adr/0005-benchmark-methodology.md) | Tuned FPM baseline (OPcache, static workers), same limits, warm-up, raw output committed. |
| [Skills, agents and hooks](docs/adr/0006-plugin-skills-agents-hooks.md) | Skills for workflows the user starts, read-only agents for analysis, hooks for rules that must not depend on the model remembering them. |
| [Quality gates](docs/adr/0007-quality-gates.md) | Strict types, final classes, `Model::shouldBeStrict()`, Larastan level 8, Pint, Pest architecture tests, all in CI. |

### Practices applied

- **TDD**: the contract and the leaks were written as failing tests before the code that satisfies them (see the commit table).
- **SOLID**: validation (`StoreOrderRequest`), business rule (`PlaceOrder`), presentation (`ProductResource`) and request state (`TenantContext`) each have one reason to change. Dependencies come in through constructors or method parameters.
- **Clean code**: small final classes, named constants instead of magic strings, test names that read as behavior (`it('does not leak the tenant to the next request')`).
- **Performance**: no per-request work that can be done once per worker; eager loading (`with('items.product')`) and N+1 detection in development; indexed tenant columns; row locks only where money and stock change.
- **Security**: tenant scoping on every query, validated input, no mass assignment of unvalidated data, `.env` blocked for the AI by a hook.

## Benchmark

Same MySQL 8.4, same data volume (3000 products), same limits (2 CPUs, 512 MB per app). k6 with 50 virtual users for 30 s after a 10 s warm-up. FPM baseline with OPcache (`validate_timestamps=0`) and 8 static workers; Octane with Swoole, 8 workers, cached config and routes.

| Scenario | Lumen 10 on PHP-FPM | Laravel 13 Octane (Swoole) | |
|---|---|---|---|
| `GET /products/{id}` throughput | 569 req/s | **1000 req/s** | 1.8x |
| `GET /products/{id}` p50 latency | 87 ms | **20 ms** | 4.3x lower |
| `GET /products/{id}` p95 latency | 184 ms | **90 ms** | 2x lower |
| `GET /products?page=N` throughput | 530 req/s | **596 req/s** | 1.1x |
| `GET /products?page=N` p95 latency | 186 ms | **102 ms** | 1.8x lower |

Measured on a laptop (AMD Ryzen 7 5825U, Docker 29, PHP 8.4, Swoole 6.2). Raw output in [`benchmark/results`](benchmark/results). Runs vary about 10%. The ~90/180 ms steps come from the 2-CPU limit (CFS throttling) and affect both apps.

**How to read it.** Octane removes the bootstrap cost from each request. Lumen is already a micro framework, so the lookup endpoint gains 1.8x and the listing, dominated by two SQL queries, barely moves in throughput. Apps with heavier bootstraps (many providers, packages, config) gain much more; in a production API I migrated, the gain was far larger. Measure your own app:

```bash
cd benchmark
docker compose up -d --build
docker compose exec lumen php artisan migrate --seed --force
docker compose exec octane php artisan migrate --seed --force
./leak-check.sh http://localhost:8001   # Lumen on PHP-FPM
./leak-check.sh http://localhost:8002   # Laravel Octane
./run.sh                                # k6, output in benchmark/results
```

## Development

```bash
cd examples/octane-api
composer install --ignore-platform-req=ext-swoole
vendor/bin/pest                 # 24 tests: contract, worker reuse, architecture, unit
vendor/bin/phpstan analyse      # Larastan level 8
vendor/bin/pint --test          # code style
../../tests/hooks/run.sh        # plugin hook tests
```

CI runs all of the above plus ShellCheck on every push and pull request.

## Repository layout

```
.claude-plugin/   plugin and marketplace manifests
agents/           octane-auditor, laravel-reviewer, pest-writer
skills/           migrate-lumen, octane-audit, laravel-claude-md
hooks/ scripts/   protect-env, octane-guard, pint-on-edit
templates/        CLAUDE.md template for Laravel projects
examples/         lumen-api (legacy) and octane-api (migrated)
benchmark/        docker compose, k6 scenarios, leak check, raw results
docs/adr/         architecture decision records (English and pt-BR)
tests/hooks/      tests for the hook scripts
```

## License

MIT. Made by [Matheus Amorim](https://amorim-co.de).
