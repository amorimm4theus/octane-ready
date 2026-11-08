# 0003. Os testes simulam o reuso do worker do Octane; uma checagem de fora confirma

[English](../0003-tests-emulate-worker-reuse.md)

## Contexto
Um teste normal do Laravel sobe uma aplicação nova para cada teste, o que esconde vazamentos entre requisições. Rodar o Swoole dentro da suíte de testes seria lento e instável.

## Decisão
- O `WorkerReuseTest` manda duas requisições para a mesma instância da aplicação e, entre elas, limpa exatamente o que o Octane limpa: as instâncias `scoped` (`forgetScopedInstances()`) e os controllers guardados em cache (`Route::flushController()`, a mesma chamada que o `ApplicationGateway` do Octane faz). O que sobreviver a isso é vazamento.
- O `benchmark/leak-check.sh` confere os mesmos três bugs contra o servidor Swoole de verdade, via HTTP.

Uma diferença é proposital. O Octane também roda cada requisição numa cópia (sandbox) do container, então um singleton criado *durante* uma requisição é descartado no fim dela. O teste não faz essa cópia, então esse singleton sobrevive. Por isso um singleton que guarda o `Request` é apontado pelos testes, mesmo que no Octane ele só vaze quando o serviço é criado no boot ou listado em `octane.warm`. Preferimos falhar cedo nesse bug que está escondido.

## Consequências
- Vazamentos são pegos em milissegundos no CI e confirmados de ponta a ponta no runtime real.
- Se uma versão futura do Octane limpar mais ou menos coisas, a simulação precisa acompanhar. O auxiliar é uma função só (`nextRequestOnSameWorker()`), então a mudança fica em um lugar.
