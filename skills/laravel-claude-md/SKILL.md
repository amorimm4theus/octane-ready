---
description: Creates or updates a CLAUDE.md for the current Laravel project from the kit template, filled with the real stack, commands and conventions found in the repository. Use when setting up Claude Code in a Laravel project or when CLAUDE.md is missing or outdated.
---

# Laravel CLAUDE.md

1. Read `composer.json`, `package.json`, `.env.example` (never `.env`), `phpunit.xml`, `config/octane.php` if present, CI files and `README.md`.
2. Detect: PHP/Laravel versions, Octane server, database, queue/cache drivers, test runner (Pest or PHPUnit), static analysis (PHPStan/Larastan), formatter (Pint), deploy method.
3. Start from [the template](../../templates/CLAUDE.md). Fill only what you verified. Delete sections that do not apply. Do not invent commands.
4. If a `CLAUDE.md` already exists (Laravel Boost may have generated guidelines), merge: keep existing content, add only missing project-specific sections, and show the diff before writing.
5. Keep it under 150 lines. Short, imperative, specific to this repo.
