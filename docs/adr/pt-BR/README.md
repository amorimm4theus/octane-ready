# Registros de decisão de arquitetura (ADRs)

[English](../README.md)

Registros curtos das decisões por trás deste repositório: contexto, decisão e consequências.

| # | Decisão |
|---|---|
| [0001](0001-manter-o-contrato-da-api.md) | Manter o contrato público da API do Lumen durante a migração |
| [0002](0002-estado-da-requisicao-e-scoped-servicos-sem-estado.md) | Estado da requisição é `scoped`, serviços não têm estado |
| [0003](0003-testes-simulam-reuso-do-worker.md) | Os testes simulam o reuso do worker do Octane; uma checagem de fora confirma |
| [0004](0004-pedidos-por-action-com-lock-de-linha.md) | Pedidos passam por uma Action com transação e lock de linha (lock substituído pelo 0008) |
| [0005](0005-metodologia-do-benchmark.md) | Metodologia do benchmark: mesmos limites, base otimizada, saída bruta publicada |
| [0006](0006-plugin-skills-agentes-hooks.md) | Desenho do plugin: skills para fluxos, agentes para análise, hooks para travas |
| [0007](0007-portoes-de-qualidade.md) | Portões de qualidade: tipos estritos, classes finais, Larastan nível 8, Pint, CI |
| [0008](0008-baixa-de-estoque-com-update-atomico.md) | Baixa de estoque com update atômico condicional (substitui em parte o 0004) |
