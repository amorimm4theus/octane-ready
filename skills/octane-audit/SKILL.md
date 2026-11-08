---
description: Audits the current Laravel project for Octane/Swoole state leaks and memory growth, then proposes or applies fixes with tests. Use before turning on Laravel Octane, after porting legacy code, or when users see data from other users/tenants.
argument-hint: "[--fix]"
---

# Octane audit

1. Confirm the project uses or plans to use `laravel/octane` (check `composer.json` and `config/octane.php`).
2. Delegate the analysis to the `octane-auditor` agent and wait for its table of findings.
3. Before showing a critical finding, open the file yourself and confirm the leak path: what holds the state, which entry point reads it without resetting it. Drop or downgrade anything you cannot confirm.
4. Show the findings to the user ordered by severity, then the "checked and safe" list and the Octane configuration summary.
5. Only with `--fix` or explicit approval:
   - fix one finding at a time, smallest change first; prefer removing the state (scoped binding, argument) over adding more resets;
   - for each critical finding, write the Pest test first with the `pest-writer` agent, run it and show that it fails, then fix and show it passes;
   - run the whole test suite after each fix.
6. Recommend a safety net in production: `--max-requests` on `octane:start` (Octane uses 500 when the flag is absent) and memory monitoring per worker. Make clear it is a safety net, not the fix. Remind that queue workers are long-lived too.
