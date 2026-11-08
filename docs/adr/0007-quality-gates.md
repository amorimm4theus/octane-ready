# 0007. Quality gates

[Português](pt-BR/0007-portoes-de-qualidade.md)

## Decision
- `declare(strict_types=1)` in every class (enforced by an architecture test).
- Classes are `final` unless designed for extension; dependencies are injected through constructors or method parameters, never pulled from globals.
- `Model::shouldBeStrict()` outside production: lazy loading (N+1), silently discarded attributes and missing attributes throw in development and tests.
- Larastan at level 8 on `app`, `routes` and `database`; Pint for style; Pest with architecture presets (`php`, `security`).
- CI runs all of it on every push and pull request, plus ShellCheck and the hook tests.

## Consequences
New code that breaks a rule fails in CI with a message that says which rule, instead of depending on a reviewer noticing it.
