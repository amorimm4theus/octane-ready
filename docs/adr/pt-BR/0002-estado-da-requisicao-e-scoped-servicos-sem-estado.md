# 0002. Estado da requisição é `scoped`, serviços não têm estado

[English](../0002-request-state-is-scoped-services-are-stateless.md)

## Contexto
No PHP-FPM a memória do processo é descartada depois de cada requisição. No Octane um worker atende milhares de requisições, então uma propriedade `static` ou um singleton que guardou o `Request` mantém os dados da requisição anterior. O código legado tinha três casos assim: o tenant numa propriedade estática, um array estático usado como cache e um singleton guardando o `Request`.

## Decisão
- Dado da requisição fica em objetos registrados com `$app->scoped()` (`TenantContext`). O Octane limpa as instâncias `scoped` entre uma requisição e outra.
- Serviços compartilhados não têm estado e recebem o que precisam como argumento (`PriceFormatter::format($cents, $locale)`), então podem ser singleton com segurança.
- Nenhuma propriedade estática mutável em `App\`. Se precisar de memoização, ela vai para um cache com tempo de expiração e chave que inclui o tenant, nunca para um array estático.

## Consequências
- A regra é garantida em três lugares: um teste de arquitetura (`tests/Arch`) que falha com qualquer propriedade estática em `App\`, o hook `octane-guard` que avisa o Claude enquanto ele escreve código, e o agente `octane-auditor` para código que já existe.
- O código fica um pouco mais explícito (o idioma é passado como argumento), em troca de serviços muito fáceis de testar.
