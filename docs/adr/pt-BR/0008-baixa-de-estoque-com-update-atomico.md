# 0008. Baixa de estoque com update atômico condicional

[English](../0008-atomic-conditional-stock-update.md)

Substitui em parte o [0004](0004-pedidos-por-action-com-lock-de-linha.md): a Action, a transação e a exceção de domínio continuam; o lock de linha sai.

## Contexto
O `PlaceOrder` lia cada produto com `lockForUpdate()`, conferia o estoque no PHP e depois dava a baixa. Funciona, mas são dois comandos por item e a linha fica travada desde a leitura até o fim da transação. Estoque é um contador só, então a conferência e a baixa podem ser um comando só.

## Decisão
- Cada item é baixado com um update condicional:
  ```php
  Product::query()->whereKey($id)->where('stock', '>=', $quantity)->decrement('stock', $quantity);
  ```
  O banco confere e baixa no mesmo comando. Se nenhuma linha mudar, não havia estoque suficiente e é lançada a `InsufficientStock`.
- O produto continua sendo lido antes (sem lock) com `forTenant()->findOrFail()`: produto de outro tenant continua dando 404, e o preço vem dessa leitura.
- O `DB::transaction` continua. É ele que faz o pedido com vários itens ser tudo ou nada: se o segundo item falhar, a baixa do primeiro é desfeita.
- Quando falha, o produto é recarregado antes de montar o erro, para a mensagem mostrar o estoque atual e não o valor lido antes.

## Alternativas consideradas
- **Manter o `lockForUpdate()`**: continua sendo a ferramenta certa quando a decisão depende de várias colunas ou linhas lidas juntas (um saldo e um limite de crédito, por exemplo). Para um contador só, não precisa.
- **Lock otimista (coluna de versão)**: exige tentar de novo na aplicação. Serve para edição de registros inteiros, não para um contador muito disputado.
- **`Cache::lock`**: coloca o Redis como dependência para uma garantia que o banco já dá.
- **Restrição no banco (`UNSIGNED` / `CHECK stock >= 0`)**: boa rede de segurança extra, mas sozinha transforma uma regra de negócio em erro de banco.
- **Fila por produto**: coloca tudo em série; só vale a pena com disputa extrema.

## Consequências
- Um comando por item em vez de leitura travada mais update; a linha fica travada só a partir do update até o commit.
- O preço é lido antes da baixa. Se o preço mudar nesse intervalo, o pedido fica com o preço lido. Isso é uma decisão de negócio (qual preço o cliente aceitou), não uma race condition de estoque.
- Um teste simula um pedido concorrente mudando o estoque logo depois da leitura; o pedido é recusado com 422 e nada é salvo.
