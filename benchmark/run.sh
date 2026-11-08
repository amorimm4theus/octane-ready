#!/usr/bin/env bash
# Runs the same k6 scenarios against Lumen (PHP-FPM) and Laravel Octane (Swoole), one app at a time.
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p results

targets=("lumen-fpm http://localhost:8001" "octane-swoole http://localhost:8002")

k6() {
  docker run --rm --network host -v "$PWD/k6:/k6" grafana/k6 run -q "$@" /k6/read-heavy.js
}

for scenario in detail listing; do
  for target in "${targets[@]}"; do
    read -r name url <<< "$target"
    k6 -e BASE="$url" -e SCENARIO="$scenario" -e DURATION=10s >/dev/null   # warm-up
    echo "== $scenario / $name"
    k6 -e BASE="$url" -e SCENARIO="$scenario" | tee "results/$scenario-$name.txt"
  done
done
