#!/usr/bin/env bash
# PostToolUse: in projects that use Laravel Octane, warns Claude when an edited
# PHP file under app/ keeps mutable state in static properties.
file=$(php "$(dirname "$0")/hook-file-path.php")
[[ "$file" == *.php && "$file" == */app/* && -f "$file" ]] || exit 0

dir=$(dirname "$file")
while [[ "$dir" != "/" && ! -f "$dir/composer.json" ]]; do dir=$(dirname "$dir"); done
grep -q '"laravel/octane"' "$dir/composer.json" 2>/dev/null || exit 0

hits=$(grep -nE '^\s*(public|protected|private)?\s*static\s+(\??[A-Za-z_\\]+\s+)?\$[A-Za-z_]+\s*(=|;)' "$file" | grep -v 'readonly')
[[ -z "$hits" ]] && exit 0

{
  echo "Octane warning (octane-ready): static properties in $file:"
  echo "$hits"
  echo "Under Octane, static state survives between requests. If it holds per-request data (user, tenant, locale, request, results), use \$app->scoped() or pass it as an argument. If it is a bounded, immutable lookup, ignore this."
} >&2
exit 2
