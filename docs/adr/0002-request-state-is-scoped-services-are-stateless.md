# 0002. Request state is scoped, services are stateless

## Context
Under PHP-FPM the process memory is discarded after every request. Under Octane one worker serves thousands of requests, so a `static` property or a singleton that captured the `Request` keeps the previous request's data. The legacy code had three such cases: the tenant in a static property, a static array used as a cache, and a singleton holding the `Request`.

## Decision
- Per-request data lives in objects bound with `$app->scoped()` (`TenantContext`). Octane flushes scoped instances between requests.
- Shared services are stateless and receive what they need as arguments (`PriceFormatter::format($cents, $locale)`), so they can be singletons safely.
- No mutable static properties in `App\`. Memoization, when needed, goes to a cache store with a TTL and a tenant-aware key, never to a static array.

## Consequences
- The rule is enforced in three places: an architecture test (`tests/Arch`) that fails on any static property in `App\`, the `octane-guard` hook that warns Claude while it writes code, and the `octane-auditor` agent for existing code.
- Slightly more explicit code (passing the locale), in exchange for services that are trivial to unit test.
