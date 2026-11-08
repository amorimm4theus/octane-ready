# 0001. Manter o contrato público da API do Lumen durante a migração

[English](../0001-keep-the-public-api-contract.md)

## Contexto
Os clientes já chamam a API em Lumen: `/products`, `/orders`, sem prefixo `/api`, e o JSON do produto sem o envelope `data`. Uma migração de framework que também muda o contrato transforma uma mudança arriscada em duas.

## Decisão
- Mesmas URLs: `withRouting(api: ..., apiPrefix: '')`.
- Mesmo formato de resposta: `JsonResource::withoutWrapping()` e as mesmas chaves em todas as respostas.
- O contrato é escrito como testes de feature no Pest **antes** de portar o código (`ProductApiTest`, `OrderApiTest`), e o port ingênuo precisa passar neles.

## Consequências
- Os clientes não mudam nada. A migração pode ir para produção atrás do load balancer e voltar atrás se precisar.
- Duas mudanças de comportamento são de propósito e estão documentadas: erros agora saem em JSON (o Lumen renderizava o `abort()` em HTML) e falta de estoque devolve uma mensagem em JSON. As duas melhoram a vida de quem consome a API e têm teste.
