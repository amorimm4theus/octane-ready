# 0005. Benchmark methodology

[Português](pt-BR/0005-metodologia-do-benchmark.md)

## Context
Octane benchmarks online often compare a tuned Octane server with an untuned FPM (no OPcache, `validate_timestamps=1`, dynamic workers), or report a single best run. The numbers look impressive and do not transfer to real apps.

## Decision
- Same MySQL instance, same data volume (3000 products, 3 tenants), same container limits (2 CPUs, 512 MB) for both apps.
- FPM baseline tuned like production: OPcache on, `validate_timestamps=0`, `pm = static` with 8 workers. Octane: Swoole, 8 workers, cached config and routes.
- k6, 50 virtual users, 10 s warm-up discarded, 30 s measured, two scenarios run separately: a single indexed lookup (framework overhead dominates) and a paginated listing (database weighs more).
- Raw k6 output is committed in `benchmark/results/`. Numbers in the README come from those files.

## Consequences
- The result is modest for Lumen (already a micro framework) and honest about where the gain comes from: bootstrap cost removed from every request. Apps with heavier bootstraps gain more.
- Results vary roughly 10% between runs on the same laptop. Run `benchmark/run.sh` on your own hardware before quoting a number.
