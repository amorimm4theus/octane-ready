#!/usr/bin/env bash
# Tests for the plugin hook scripts. Usage: tests/hooks/run.sh
set -u
root="$(cd "$(dirname "$0")/../.." && pwd)"
fixtures="$root/tests/hooks/fixtures"
passed=0 failed=0

# expect <script> <file_path> <expected exit code> <description>
expect() {
  local actual
  printf '{"tool_input":{"file_path":"%s"}}' "$2" | "$root/scripts/$1" >/dev/null 2>&1
  actual=$?
  if [[ "$actual" == "$3" ]]; then
    passed=$((passed + 1)); echo "ok   $4"
  else
    failed=$((failed + 1)); echo "FAIL $4 (expected exit $3, got $actual)"
  fi
}

# expect_stderr <script> <file_path> <text> <description>
expect_stderr() {
  if printf '{"tool_input":{"file_path":"%s"}}' "$2" | "$root/scripts/$1" 2>&1 >/dev/null | grep -q "$3"; then
    passed=$((passed + 1)); echo "ok   $4"
  else
    failed=$((failed + 1)); echo "FAIL $4 (stderr does not mention '$3')"
  fi
}

expect protect-env.sh /app/.env                 2 "protect-env blocks .env"
expect protect-env.sh /app/.env.production      2 "protect-env blocks .env.production"
expect protect-env.sh /app/.env.example         0 "protect-env allows .env.example"
expect protect-env.sh /app/app/Models/User.php  0 "protect-env ignores other files"

expect octane-guard.sh "$fixtures/octane-app/app/Leaky.php" 2 "octane-guard warns on static property in an Octane app"
expect octane-guard.sh "$fixtures/octane-app/app/Clean.php" 0 "octane-guard ignores constants and static methods"
expect octane-guard.sh "$fixtures/plain-app/app/Leaky.php"  0 "octane-guard stays quiet without laravel/octane"
expect octane-guard.sh "$fixtures/octane-app/composer.json" 0 "octane-guard ignores non-PHP files"

expect octane-guard.sh "$fixtures/octane-app/app/Untyped.php"      2 "octane-guard warns on an untyped static property"
expect octane-guard.sh "$fixtures/octane-app/app/LocalStatic.php"  2 "octane-guard warns on a function-local static"
expect octane-guard.sh "$fixtures/octane-app/app/ObjectIdMemo.php" 2 "octane-guard warns on a memo keyed by object id"
expect_stderr octane-guard.sh "$fixtures/octane-app/app/ObjectIdMemo.php" "spl_object_id" "octane-guard explains object id reuse"
expect_stderr octane-guard.sh "$fixtures/octane-app/app/Leaky.php" "every path" "octane-guard asks whether every path resets the state"

expect pint-on-edit.sh "$fixtures/plain-app/app/Leaky.php" 0 "pint-on-edit never blocks, even without Pint"

echo "$passed passed, $failed failed"
[[ "$failed" == 0 ]]
