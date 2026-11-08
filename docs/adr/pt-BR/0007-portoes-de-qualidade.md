# 0007. Portões de qualidade

[English](../0007-quality-gates.md)

## Decisão
- `declare(strict_types=1)` em todas as classes (garantido por um teste de arquitetura).
- Classes `final`, a menos que tenham sido pensadas para extensão; dependências entram pelo construtor ou por parâmetro de método, nunca por variáveis globais.
- `Model::shouldBeStrict()` fora de produção: lazy loading (N+1), atributos descartados em silêncio e atributos inexistentes geram erro em desenvolvimento e nos testes.
- Larastan nível 8 em `app`, `routes` e `database`; Pint para estilo; Pest com os presets de arquitetura (`php`, `security`).
- O CI roda tudo isso a cada push e pull request, mais o ShellCheck e os testes dos hooks.

## Consequências
Código novo que quebra uma regra falha no CI com uma mensagem dizendo qual regra, em vez de depender de alguém notar na revisão.
