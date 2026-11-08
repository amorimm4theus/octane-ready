# 0006. Desenho do plugin: skills para fluxos, agentes para análise, hooks para travas

[English](../0006-plugin-skills-agents-hooks.md)

## Contexto
O Claude Code tem vários pontos de extensão. Colocar tudo num prompt gigante deixa o comportamento difícil de prever e de revisar.

## Decisão
- **Skills** (`/octane-ready:migrate-lumen`, `octane-audit`, `laravel-claude-md`) descrevem fluxos de vários passos que o usuário inicia de propósito. Elas mostram um plano antes de mexer no código e conferem cada passo.
- **Agentes** (`octane-auditor`, `laravel-reviewer`) analisam num contexto separado, só com ferramentas de leitura, para que uma auditoria longa não encha a conversa principal e não altere código sem querer. O `pest-writer` é o único agente que pode escrever, e só testes.
- **Hooks** são travas determinísticas que não dependem do modelo lembrar de uma regra: bloquear o `.env`, avisar sobre propriedades estáticas em apps com Octane, formatar com o Pint.
- Os scripts dos hooks leem o JSON de entrada com PHP em vez de `jq`, porque toda máquina com Laravel tem PHP e nem toda tem `jq`.
- O `octane-guard` sai com código 2 para que a mensagem chegue ao Claude logo depois da edição; o `pint-on-edit` nunca faz a edição falhar.

## Consequências
- Cada peça pode ser testada sozinha: os scripts dos hooks têm uma suíte de testes (`tests/hooks/run.sh`) que roda no CI junto com o ShellCheck.
- Funciona junto com o Laravel Boost, que cobre as diretrizes gerais do Laravel; este kit fica focado em migração e workers de longa duração.
