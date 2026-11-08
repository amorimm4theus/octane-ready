#!/usr/bin/env bash
# PreToolUse: blocks Claude from reading or writing .env files (secrets).
# .env.example, .env.testing.example and similar templates are allowed.
file=$(php "$(dirname "$0")/hook-file-path.php")
name=$(basename "$file")
case "$name" in
  *.example|*.dist|*.sample) exit 0 ;;
  .env|.env.*)
    echo "Blocked by octane-ready: $name may contain secrets. Use .env.example or config/*.php instead." >&2
    exit 2 ;;
esac
exit 0
