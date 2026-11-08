# 0004. Pedidos passam por uma Action com transação e lock de linha

[English](../0004-orders-use-an-action-with-row-locks.md)

> Substituído em parte pelo [0008](0008-baixa-de-estoque-com-update-atomico.md): o lock de linha deu lugar a um update atômico condicional.

## Contexto
O controller do Lumen validava a entrada, abria a transação, conferia o estoque e montava a resposta num método só. Dois pedidos ao mesmo tempo podiam ler "1 em estoque" e os dois darem certo.

## Decisão
- O `StoreOrderRequest` valida e normaliza a entrada (responsabilidade única).
- O `PlaceOrder` é uma Action final com um único método público, `handle()`. Ele roda dentro de `DB::transaction` e trava cada linha de produto com `lockForUpdate()` antes de conferir e baixar o estoque.
- Falta de estoque é uma exceção de domínio (`InsufficientStock`) que se renderiza como resposta 422 em JSON. A transação é desfeita, então um pedido parcialmente válido não deixa rastro.
- Os controllers ficam enxutos. Um teste de arquitetura proíbe a facade `DB` em `App\Http\Controllers`.

## Consequências
- A regra de pedido pode ser reaproveitada num job de fila ou num comando de console, sem HTTP.
- O lock de linha coloca em fila os pedidos do mesmo produto. É a troca certa para estoque; um produto muito disputado, com concorrência altíssima, precisaria de uma tabela de reservas ou de uma fila, o que está fora do escopo aqui.
