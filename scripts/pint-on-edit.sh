#!/usr/bin/env bash
# PostToolUse: formats the edited PHP file with the project's Pint, if installed.
file=$(php "$(dirname "$0")/hook-file-path.php")
[[ "$file" == *.php && -f "$file" ]] || exit 0

dir=$(dirname "$file")
while [[ "$dir" != "/" && ! -x "$dir/vendor/bin/pint" ]]; do dir=$(dirname "$dir"); done
[[ -x "$dir/vendor/bin/pint" ]] || exit 0

(cd "$dir" && vendor/bin/pint --quiet "$file") || true
exit 0
