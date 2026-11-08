#!/usr/bin/env bash
# Checks the three classic long-lived-worker bugs against a running API.
# Usage: ./leak-check.sh http://localhost:8002
set -u
BASE="${1:-http://localhost:8002}"
fail=0

json() { python3 -c "import sys,json; print(json.load(sys.stdin)$1)"; }

# 1) Tenant leak: request with tenant "acme", then a request without the header.
curl -s -H 'X-Tenant: acme' "$BASE/health" >/dev/null
tenant=$(curl -s "$BASE/health" | json "['tenant']")
if [ "$tenant" = "public" ]; then echo "PASS tenant: request without header got 'public'"
else echo "FAIL tenant: request without header got '$tenant' (leaked from a previous request)"; fail=1; fi

# 2) Stale singleton: first request in English, then a request in Portuguese.
for i in $(seq 1 20); do curl -s -H 'Accept-Language: en' "$BASE/products" >/dev/null; done
locale=$(curl -s -H 'Accept-Language: pt-BR' "$BASE/products" | json "['locale']")
if [ "$locale" = "pt_BR" ]; then echo "PASS locale: pt-BR request got pt_BR"
else echo "FAIL locale: pt-BR request got '$locale' (singleton captured an old Request)"; fail=1; fi

# 3) Unbounded static cache: 2000 distinct URLs, then look at the cache size.
for i in $(seq 1 2000); do printf 'url = "%s"\noutput = "/dev/null"\n' "$BASE/products?search=x$i"; done | curl -s -K -
entries=$(curl -s "$BASE/health" | json ".get('search_cache_entries', 0)")
if [ "$entries" -lt 100 ]; then echo "PASS memory: static cache has $entries entries"
else echo "FAIL memory: static cache grew to $entries entries and never shrinks"; fail=1; fi

exit $fail
