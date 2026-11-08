# octane-ready

[![CI](https://github.com/amorimm4theus/octane-ready/actions/workflows/ci.yml/badge.svg)](https://github.com/amorimm4theus/octane-ready/actions/workflows/ci.yml)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4)
![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20)
![Licença MIT](https://img.shields.io/badge/licen%C3%A7a-MIT-green)

**Deixe apps Laravel prontos para o Octane com Claude Code.** Migre de Lumen para Laravel + Octane, encontre os vazamentos de estado que só aparecem no Swoole e prove com testes e um benchmark que você mesmo roda.

[Read in English](README.md) · [Site e tutorial](https://amorim-co.de/octane-ready)

---

Levar uma API PHP para o Laravel Octane tira o bootstrap do framework de cada requisição. O detalhe: no PHP-FPM cada requisição começa do zero, no Octane um worker atende milhares de requisições. Código que guardava dado da requisição numa propriedade `static` funcionou por anos e vira **um cliente vendo os dados de outro cliente**.

O repositório tem duas partes:

1. **Um plugin do Claude Code** com skills, agentes e hooks para essa migração. As skills seguem o formato aberto [Agent Skills](https://agentskills.io), então também funcionam no Codex, Cursor, Gemini CLI, GitHub Copilot e outros agentes.
2. **Um exemplo completo** contado pelo histórico de commits: uma API legada em Lumen, um port ingênuo que vaza, testes que expõem os vazamentos, a correção, uma refatoração e um benchmark que você mesmo roda.

## Instalar

**Claude Code** (tudo: skills, agentes e hooks):

```bash
claude plugin marketplace add amorimm4theus/octane-ready
claude plugin install octane-ready@octane-ready
```

Depois, dentro do Claude Code: `/octane-ready:octane-audit`.

**Codex, Cursor, Gemini CLI, GitHub Copilot, OpenCode e outros agentes** (as três skills):

```bash
npx skills add amorimm4theus/octane-ready
```

Ou copie as pastas de [`skills/`](skills) para a pasta de skills do seu agente (por exemplo `.claude/skills/` ou `.agents/skills/`). Cada skill é autocontida: as instruções de agente que ela usa estão na pasta `references/` dela. Depois peça ao agente para usar a skill `octane-audit`. Hooks são um recurso do Claude Code e não são instalados assim.

**Sem instalar nada**: cole isto em qualquer agente de código, na raiz de um projeto Laravel:

```text
Audite este projeto Laravel procurando bugs que só aparecem no Laravel Octane, onde um worker atende muitas requisições e o estado pode vazar entre usuários. Siga este checklist, só lendo o código: https://raw.githubusercontent.com/amorimm4theus/octane-ready/main/skills/octane-audit/references/auditor.md
Não altere nenhum código. Mostre primeiro os achados críticos com arquivo:linha e depois o que você conferiu e está seguro.
```

| Tipo | Nome | Para que serve |
|---|---|---|
| Skill | `/octane-ready:migrate-lumen` | Migra um app Lumen para Laravel passo a passo, compara as respostas e opcionalmente liga o Octane |
| Skill | `/octane-ready:octane-audit` | Audita o projeto atrás de bugs de worker de longa duração e corrige com testes quando pedido (`--fix`) |
| Skill | `/octane-ready:laravel-claude-md` | Escreve um `CLAUDE.md` com a stack e os comandos reais do projeto (junta com o do Laravel Boost) |
| Agente | `octane-auditor` | Análise só de leitura: estado da requisição em `static`, memos indexados por id de objeto, estado limpo só em alguns caminhos, singletons que guardam o Request, caches por worker e sem limite, config do Octane |
| Agente | `laravel-reviewer` | Revisão do diff só de leitura: N+1, autorização, validação, transação e lock, segurança no Octane |
| Agente | `pest-writer` | Escreve testes Pest, inclusive "requisição A, depois requisição B no mesmo worker" |
| Hook | `protect-env` | Impede ler ou escrever arquivos `.env` (o `.env.example` é liberado) |
| Hook | `octane-guard` | Em apps com `laravel/octane`, avisa o Claude logo depois que ele escreve uma propriedade estática em `app/`, com aviso extra para memos indexados por `spl_object_id` |
| Hook | `pint-on-edit` | Formata cada arquivo PHP editado com o Pint do projeto |

Agentes e hooks são recursos do Claude Code. Em outros harnesses as skills usam as mesmas instruções, guardadas na pasta `references/` de cada skill e sincronizadas a partir de `agents/` pelo `scripts/sync-skills.sh` (conferido no CI).

Funciona junto com o [Laravel Boost](https://github.com/laravel/boost): o Boost cobre as diretrizes gerais do Laravel, este kit fica focado em migração e workers de longa duração. Os hooks só precisam de `php` no PATH.

## O exemplo

[`examples/lumen-api`](examples/lumen-api) é uma pequena API de loja multi-tenant em Lumen 10, escrita do jeito que muito código legado é escrito:

```php
class TenantContext
{
    private static ?string $tenant = null;          // dado da requisição numa propriedade static
    public static function set(string $t): void { self::$tenant = $t; }
    public static function id(): string { return self::$tenant ?? 'public'; }
}
```

[`examples/octane-api`](examples/octane-api) é a mesma API em Laravel 13 + Octane (Swoole). Leia o histórico com `git log --oneline --reverse`. Cada etapa é um commit:

| Etapa | Commit | Estado |
|---|---|---|
| 1 | `feat(lumen-api)` base legada | Lumen no PHP-FPM, funcionando |
| 2 | `test(octane-api)` contrato público da API | **vermelho**: nada portado ainda |
| 3 | `feat(octane-api)` port do código do Lumen como está | contrato **verde** |
| 4 | `test(octane-api)` reproduz os vazamentos entre requisições | **vermelho**: tenant, idioma e cache vazam |
| 5 | `fix(octane-api)` tira o estado da requisição de static e singleton | **verde** |
| 6 | `refactor(octane-api)` Form Request, Action, API Resource | continua verde, controllers enxutos |
| 7 | `test(octane-api)` regras de arquitetura | estado estático e controller gordo agora quebram o build |

O port ingênuo contra o servidor Swoole de verdade ([`benchmark/leak-check.sh`](benchmark/leak-check.sh)):

```
FAIL tenant: request without header got 'acme' (leaked from a previous request)
FAIL memory: static cache grew to 2001 entries and never shrinks
```

Depois da correção, todas as checagens passam ([`benchmark/results`](benchmark/results)).

## Arquitetura

```
Requisição HTTP
  └─ middleware ResolveTenant ──> TenantContext        (scoped: novo a cada requisição)
       └─ Controller (enxuto)
            ├─ StoreOrderRequest                       (validação)
            ├─ action PlaceOrder                       (transação + update atômico)
            │     └─ exceção InsufficientStock         (vira resposta 422 em JSON)
            └─ ProductResource ──> PriceFormatter      (singleton, sem estado)
```

### Decisões e por quê

Cada decisão tem um [registro de decisão de arquitetura (ADR)](docs/adr/pt-BR) com contexto e consequências. Em resumo:

| Decisão | Por quê |
|---|---|
| [Manter o contrato da API do Lumen](docs/adr/pt-BR/0001-manter-o-contrato-da-api.md) (mesmas URLs, sem envelope `data`) | Trocar o framework e o contrato ao mesmo tempo dobra o risco. Os clientes não mudam e voltar atrás é trocar o destino no load balancer. |
| [Estado da requisição é scoped, serviços sem estado](docs/adr/pt-BR/0002-estado-da-requisicao-e-scoped-servicos-sem-estado.md) | O Octane limpa o `$app->scoped()` entre requisições. Singletons sem estado são seguros para compartilhar e fáceis de testar. |
| [Testes simulam o reuso do worker](docs/adr/pt-BR/0003-testes-simulam-reuso-do-worker.md) | Duas requisições na mesma instância do app, limpando o que o Octane limpa. O vazamento falha em milissegundos no CI, sem precisar do Swoole. |
| [Pedidos por Action](docs/adr/pt-BR/0004-pedidos-por-action-com-lock-de-linha.md) | Uma transação por pedido, então um pedido com vários itens é tudo ou nada. A Action pode ser usada por jobs e comandos. |
| [Baixa de estoque com update atômico](docs/adr/pt-BR/0008-baixa-de-estoque-com-update-atomico.md) | `UPDATE ... WHERE stock >= ?` confere e baixa num comando só: dois pedidos ao mesmo tempo não vendem a última unidade duas vezes, sem travar a linha desde a leitura. |
| [Benchmark honesto](docs/adr/pt-BR/0005-metodologia-do-benchmark.md) | Base com FPM otimizado (OPcache, workers fixos), mesmos limites, aquecimento e saída bruta publicada. |
| [Skills, agentes e hooks](docs/adr/pt-BR/0006-plugin-skills-agentes-hooks.md) | Skills para fluxos que o usuário inicia, agentes só de leitura para análise, hooks para regras que não podem depender do modelo lembrar. |
| [Portões de qualidade](docs/adr/pt-BR/0007-portoes-de-qualidade.md) | Tipos estritos, classes finais, `Model::shouldBeStrict()`, Larastan nível 8, Pint e testes de arquitetura do Pest, tudo no CI. |

### Práticas aplicadas

- **TDD**: o contrato e os vazamentos foram escritos como testes que falham antes do código que os faz passar (veja a tabela de commits).
- **SOLID**: validação (`StoreOrderRequest`), regra de negócio (`PlaceOrder`), apresentação (`ProductResource`) e estado da requisição (`TenantContext`) têm, cada um, um único motivo para mudar. As dependências entram pelo construtor ou por parâmetro de método.
- **Código limpo**: classes pequenas e finais, constantes com nome em vez de texto solto, nomes de teste que descrevem comportamento (`it('does not leak the tenant to the next request')`).
- **Performance**: nada por requisição que possa ser feito uma vez por worker; eager loading (`with('items.product')`) e detecção de N+1 em desenvolvimento; colunas de tenant com índice; estoque conferido e baixado num único comando atômico.
- **Segurança**: filtro por tenant em toda consulta, entrada validada, nada de mass assignment com dado não validado, `.env` bloqueado para a IA por um hook.

## Benchmark

Mesmo MySQL 8.4, mesmo volume de dados (3000 produtos), mesmos limites (2 CPUs e 512 MB por app). k6 com 50 usuários virtuais por 30 s depois de 10 s de aquecimento. Base com FPM com OPcache (`validate_timestamps=0`) e 8 workers fixos; Octane com Swoole, 8 workers, config e rotas em cache.

| Cenário | Lumen 10 no PHP-FPM | Laravel 13 Octane (Swoole) | |
|---|---|---|---|
| `GET /products/{id}` vazão | 569 req/s | **1000 req/s** | 1,8x |
| `GET /products/{id}` latência p50 | 87 ms | **20 ms** | 4,3x menor |
| `GET /products/{id}` latência p95 | 184 ms | **90 ms** | 2x menor |
| `GET /products?page=N` vazão | 530 req/s | **596 req/s** | 1,1x |
| `GET /products?page=N` latência p95 | 186 ms | **102 ms** | 1,8x menor |

Medido num notebook (AMD Ryzen 7 5825U, Docker 29, PHP 8.4, Swoole 6.2). Saída bruta em [`benchmark/results`](benchmark/results). As rodadas variam cerca de 10%. Os degraus de ~90 e ~180 ms vêm do limite de 2 CPUs e afetam os dois apps.

**Como ler.** O Octane tira o custo do bootstrap de cada requisição. O Lumen já é um microframework, então a busca por id ganha 1,8x e a listagem, que é dominada por duas consultas SQL, quase não muda na vazão. Apps com bootstrap mais pesado (muitos providers, pacotes e configs) ganham bem mais; numa API em produção que migrei, o ganho foi bem maior. Meça o seu app:

```bash
cd benchmark
docker compose up -d --build
docker compose exec lumen php artisan migrate --seed --force
docker compose exec octane php artisan migrate --seed --force
./leak-check.sh http://localhost:8001   # Lumen no PHP-FPM
./leak-check.sh http://localhost:8002   # Laravel Octane
./run.sh                                # k6, saída em benchmark/results
```

## Desenvolvimento

```bash
cd examples/octane-api
composer install --ignore-platform-req=ext-swoole
vendor/bin/pest                 # 25 testes: contrato, reuso do worker, arquitetura, unitários
vendor/bin/phpstan analyse      # Larastan nível 8
vendor/bin/pint --test          # estilo de código
../../tests/hooks/run.sh        # testes dos hooks do plugin
```

O CI roda tudo isso mais o ShellCheck a cada push e pull request.

## Estrutura do repositório

```
.claude-plugin/   manifestos do plugin e do marketplace
agents/           octane-auditor, laravel-reviewer, pest-writer
skills/           migrate-lumen, octane-audit, laravel-claude-md (formato Agent Skills)
hooks/ scripts/   protect-env, octane-guard, pint-on-edit
templates/        modelo de CLAUDE.md para projetos Laravel
examples/         lumen-api (legado) e octane-api (migrado)
benchmark/        docker compose, cenários k6, checagem de vazamento, resultados brutos
site/             redireciona para amorim-co.de/octane-ready (endereço antigo do GitHub Pages)
docs/adr/         registros de decisão de arquitetura (inglês e pt-BR)
tests/hooks/      testes dos scripts dos hooks
```

## Licença

MIT. Feito por [Matheus Amorim](https://amorim-co.de).
