# 0001. Keep the Lumen public API contract during the migration

[Português](pt-BR/0001-manter-o-contrato-da-api.md)

## Context
Clients already call the Lumen API: `/products`, `/orders`, no `/api` prefix, product JSON without a `data` wrapper. A framework migration that also changes the contract turns one risky change into two.

## Decision
- Same URLs: `withRouting(api: ..., apiPrefix: '')`.
- Same shapes: `JsonResource::withoutWrapping()`, same keys in every response.
- The contract is written as Pest feature tests **before** the code is ported (`ProductApiTest`, `OrderApiTest`), and the naive port has to pass them.

## Consequences
- Clients do not change. The migration can be rolled out behind the load balancer and rolled back.
- Two behavior changes are deliberate and documented: errors are JSON (Lumen rendered `abort()` as HTML) and insufficient stock returns a JSON message. Both are improvements for API clients, and both are covered by tests.
