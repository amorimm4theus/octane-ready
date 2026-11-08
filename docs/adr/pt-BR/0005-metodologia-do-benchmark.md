# 0005. Metodologia do benchmark

[English](../0005-benchmark-methodology.md)

## Contexto
Muitos benchmarks de Octane na internet comparam um Octane otimizado com um FPM sem ajuste nenhum (sem OPcache, com `validate_timestamps=1`, workers dinâmicos), ou mostram só a melhor rodada. Os números impressionam e não se repetem em apps reais.

## Decisão
- Mesma instância de MySQL, mesmo volume de dados (3000 produtos, 3 tenants), mesmos limites de container (2 CPUs, 512 MB) para os dois apps.
- A base com FPM é ajustada como em produção: OPcache ligado, `validate_timestamps=0`, `pm = static` com 8 workers. O Octane roda Swoole com 8 workers e config e rotas em cache.
- k6 com 50 usuários virtuais, 10 s de aquecimento descartados e 30 s medidos, em dois cenários rodados separados: uma busca simples por índice (onde o custo do framework pesa mais) e uma listagem paginada (onde o banco pesa mais).
- A saída bruta do k6 fica no repositório, em `benchmark/results/`. Os números do README saem desses arquivos.

## Consequências
- O resultado é modesto para o Lumen (que já é um microframework) e honesto sobre de onde vem o ganho: o custo do bootstrap tirado de cada requisição. Apps com bootstrap mais pesado ganham mais.
- Os resultados variam cerca de 10% entre rodadas no mesmo notebook. Rode o `benchmark/run.sh` na sua máquina antes de citar um número.
