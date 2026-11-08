---
description: Audits the current Laravel project for Octane/Swoole state leaks and memory growth, then proposes or applies fixes with tests. Use before turning on Laravel Octane, after porting legacy code, or when users see data from other users/tenants.
argument-hint: "[--fix]"
---

# Octane audit

1. Confirm the project uses or plans to use `laravel/octane` (check `composer.json` and `config/octane.php`).
2. Delegate the analysis to the `octane-auditor` agent and wait for its table of findings.
3. Show the findings to the user ordered by severity.
4. Only with `--fix` or explicit approval:
   - fix one finding at a time, smallest change first;
   - for each critical finding, add a Pest test that reproduces the leak (request A, `app()->forgetScopedInstances()`, request B) using the `pest-writer` agent;
   - run the test suite after each fix.
5. Recommend a safety net in production: `--max-requests` on `octane:start` and memory monitoring per worker. Make clear it is a safety net, not the fix.
