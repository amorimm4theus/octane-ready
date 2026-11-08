# 0003. Tests emulate Octane worker reuse; a black-box check confirms it

## Context
A normal Laravel test boots a fresh application per test, which hides leaks between requests. Running Swoole inside the test suite would be slow and flaky.

## Decision
- `WorkerReuseTest` sends two requests to the same application instance and, in between, resets exactly what Octane resets: scoped instances (`forgetScopedInstances()`) and cached controller instances (`Route::flushController()`, the same call Octane's `ApplicationGateway` makes). Anything that survives that is a leak.
- `benchmark/leak-check.sh` checks the same three bugs against the real Swoole server over HTTP.

One difference is intentional. Octane also runs each request in a sandbox clone of the container, so a singleton first resolved *during* a request is discarded. The test helper does not clone the container, so such a singleton survives. A singleton that captures the `Request` is therefore reported by the tests even though Octane would only leak it once the service is resolved at boot or listed in `octane.warm`. We prefer to fail early on that latent bug.

## Consequences
- Leaks are caught in milliseconds in CI, and confirmed end-to-end on the real runtime.
- If a future Octane version resets more or less state, the emulation must follow it. The helper is one function (`nextRequestOnSameWorker()`), so that change is local.
