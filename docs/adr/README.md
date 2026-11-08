# Architecture Decision Records

[Português](pt-BR/README.md)

Short records of the decisions behind this repository: context, decision, consequences.

| # | Decision |
|---|---|
| [0001](0001-keep-the-public-api-contract.md) | Keep the Lumen public API contract during the migration |
| [0002](0002-request-state-is-scoped-services-are-stateless.md) | Request state is scoped, services are stateless |
| [0003](0003-tests-emulate-worker-reuse.md) | Tests emulate Octane worker reuse; a black-box check confirms it |
| [0004](0004-orders-use-an-action-with-row-locks.md) | Orders go through an Action with a transaction and row locks (lock replaced by 0008) |
| [0005](0005-benchmark-methodology.md) | Benchmark methodology: same limits, tuned baseline, raw output published |
| [0006](0006-plugin-skills-agents-hooks.md) | Plugin design: skills for workflows, agents for analysis, hooks for guardrails |
| [0007](0007-quality-gates.md) | Quality gates: strict types, final classes, Larastan level 8, Pint, CI |
| [0008](0008-atomic-conditional-stock-update.md) | Stock is taken with an atomic conditional update (partially supersedes 0004) |
