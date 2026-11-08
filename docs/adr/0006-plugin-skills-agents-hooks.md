# 0006. Plugin design: skills for workflows, agents for analysis, hooks for guardrails

## Context
Claude Code offers several extension points. Putting everything in one long prompt makes behavior hard to predict and hard to review.

## Decision
- **Skills** (`/octane-ready:migrate-lumen`, `octane-audit`, `laravel-claude-md`) describe multi-step workflows the user starts on purpose. They show a plan before changing code and verify each step.
- **Agents** (`octane-auditor`, `laravel-reviewer`) analyze in their own context with read-only tools, so a long audit does not fill the main conversation and cannot modify code by accident. `pest-writer` is the only agent allowed to write, and only tests.
- **Hooks** are deterministic guardrails that do not depend on the model remembering a rule: block `.env`, warn on static properties in Octane apps, format with Pint.
- Hook scripts parse the hook JSON with PHP instead of `jq`, because every Laravel machine has PHP and not every one has `jq`.
- `octane-guard` exits with code 2 so its message reaches Claude right after the edit; `pint-on-edit` never fails the edit.

## Consequences
- Each piece can be tested on its own: hook scripts have a test suite (`tests/hooks/run.sh`) run in CI with ShellCheck.
- Works alongside Laravel Boost, which covers general Laravel guidelines; this kit stays focused on migration and long-lived workers.
