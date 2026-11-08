# octane-ready

[![CI](https://github.com/amorimm4theus/octane-ready/actions/workflows/ci.yml/badge.svg)](https://github.com/amorimm4theus/octane-ready/actions/workflows/ci.yml)

**Deixe apps Laravel prontos para o Octane com Claude Code.** Migre de Lumen para Laravel + Octane, encontre os vazamentos de estado que só aparecem no Swoole e prove com testes e um benchmark que você mesmo roda.

[Read in English](README.md)

---

O Octane tira o bootstrap do framework de cada requisição. O detalhe: no PHP-FPM cada requisição começa do zero, no Octane o mesmo worker atende milhares de requisições. Código que guardava dado da requisição numa propriedade `static` funcionou por anos e vira **um cliente vendo dado de outro cliente**.

O repositório tem duas partes:

1. **Um plugin do Claude Code** com skills, agentes e hooks para essa migração.
2. **Um exemplo completo** contado pelo histórico de commits: API legada em Lumen, port ingênuo que vaza, testes que expõem o vazamento, correção, refatoração e benchmark.

## Instalação

```bash
claude plugin marketplace add amorimm4theus/octane-ready
claude plugin install octane-ready@octane-ready
```

| Tipo | Nome | Para que serve |
|---|---|---|
| Skill | `/octane-ready:migrate-lumen` | Migra um app Lumen para Laravel passo a passo, compara as respostas e opcionalmente liga o Octane |
| Skill | `/octane-ready:octane-audit` | Audita o projeto atrás de bugs de worker de longa duração e corrige com testes quando pedido (`--fix`) |
| Skill | `/octane-ready:laravel-claude-md` | Escreve um `CLAUDE.md` com a stack e os comandos reais do projeto |
| Agente | `octane-auditor` | Análise somente leitura: estado em `static`, singletons que guardam o Request, caches estáticos sem limite |
| Agente | `laravel-reviewer` | Revisão do diff: N+1, autorização, validação, transação e lock, segurança no Octane |
| Agente | `pest-writer` | Escreve testes Pest, inclusive "requisição A, depois requisição B no mesmo worker" |
| Hook | `protect-env` | Impede ler ou escrever `.env` (o `.env.example` é liberado) |
| Hook | `octane-guard` | Em apps com `laravel/octane`, avisa o Claude assim que ele escreve uma propriedade `static` em `app/` |
| Hook | `pint-on-edit` | Formata cada arquivo PHP editado com o Pint do projeto |

Funciona junto com o [Laravel Boost](https://github.com/laravel/boost).

## O exemplo, commit a commit

| Etapa | Commit | Estado |
|---|---|---|
| 1 | `feat(lumen-api)` API legada | Lumen no PHP-FPM funcionando |
| 2 | `test(octane-api)` contrato da API | **vermelho**: nada portado ainda |
| 3 | `feat(octane-api)` port do código como está | contrato **verde** |
| 4 | `test(octane-api)` reproduz os vazamentos | **vermelho**: tenant, locale e cache vazam |
| 5 | `fix(octane-api)` estado da requisição fora de static e singleton | **verde** |
| 6 | `refactor(octane-api)` Form Request, Action, API Resource | continua verde, controllers enxutos |
| 7 | `test(octane-api)` regras de arquitetura | estado estático e controller gordo quebram o build |

## Decisões de arquitetura

Cada decisão tem um [ADR](docs/adr) com contexto e consequências:

- **Contrato da API mantido** (mesmas URLs, sem wrapper `data`): trocar framework e contrato ao mesmo tempo dobra o risco.
- **Estado da requisição é `scoped`, serviços não têm estado**: o Octane limpa os `scoped` entre requisições; singletons sem estado são seguros e fáceis de testar.
- **Testes simulam o reuso do worker**: duas requisições na mesma instância, limpando o que o Octane limpa. O vazamento quebra o CI em milissegundos.
- **Pedido via Action com lock de linha**: `lockForUpdate()` dentro da transação, dois pedidos simultâneos não vendem a última unidade duas vezes.
- **Benchmark honesto**: FPM com OPcache e workers fixos, mesmos limites, aquecimento e saída bruta publicada.
- **Qualidade no CI**: `strict_types`, classes `final`, `Model::shouldBeStrict()`, Larastan nível 8, Pint e testes de arquitetura do Pest.

## Benchmark

Mesmo MySQL 8.4, mesmo volume (3000 produtos), mesmos limites (2 CPUs e 512 MB por app), k6 com 50 usuários por 30 s depois de 10 s de aquecimento.

| Cenário | Lumen 10 no PHP-FPM | Laravel 13 Octane (Swoole) | |
|---|---|---|---|
| `GET /products/{id}` req/s | 569 | **1000** | 1,8x |
| `GET /products/{id}` p50 | 87 ms | **20 ms** | 4,3x menor |
| `GET /products/{id}` p95 | 184 ms | **90 ms** | 2x menor |
| `GET /products?page=N` req/s | 530 | **596** | 1,1x |
| `GET /products?page=N` p95 | 186 ms | **102 ms** | 1,8x menor |

Medido num notebook (Ryzen 7 5825U). O Lumen já é um microframework, então o ganho aqui é moderado; a listagem é dominada pelo banco. Quanto mais pesado o bootstrap do app, maior o ganho. Numa API em produção que migrei, o ganho foi bem maior. Como rodar está no [README em inglês](README.md#benchmark).

## Licença

MIT. Feito por [Matheus Amorim](https://amorim-co.de).
