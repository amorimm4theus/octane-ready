# 0008. Stock is taken with an atomic conditional update

[Português](pt-BR/0008-baixa-de-estoque-com-update-atomico.md)

Partially supersedes [0004](0004-orders-use-an-action-with-row-locks.md): the Action, the transaction and the domain exception stay; the row lock is replaced.

## Context
`PlaceOrder` read each product with `lockForUpdate()`, checked the stock in PHP and then decremented it. That is correct, but it takes two statements per item and holds the row lock from the read until the end of the transaction. Stock is a single counter, so the check and the change can be one statement.

## Decision
- Each item is taken with one conditional update:
  ```php
  Product::query()->whereKey($id)->where('stock', '>=', $quantity)->decrement('stock', $quantity);
  ```
  The database checks and decrements in the same statement. If it changes 0 rows, there was not enough stock and `InsufficientStock` is thrown.
- The product is still read first (without a lock) with `forTenant()->findOrFail()`: a product of another tenant keeps returning 404, and the price comes from that read.
- `DB::transaction` stays. It is what makes a multi-item order all or nothing: if the second item fails, the decrement of the first is rolled back.
- On failure the product is refreshed before building the error, so the message reports the current stock, not the value read earlier.

## Alternatives considered
- **Keep `lockForUpdate()`**: still the right tool when the decision needs several columns or rows read together (a balance plus a credit limit, for example). Not needed for one counter.
- **Optimistic locking (version column)**: needs retries in the application. Fits edits of whole records, not a counter under contention.
- **`Cache::lock`**: adds Redis as a dependency for a guarantee the database already gives.
- **Database constraint (`UNSIGNED` / `CHECK stock >= 0`)**: a good extra safety net, but as the only guard it turns a business rule into a database error.
- **Queue per product**: serializes everything; only worth it for extreme contention.

## Consequences
- One statement per item instead of a locked read plus an update; the row is locked only for the duration of the update and until commit.
- The price is read before the decrement. If the price changes in that window, the order keeps the price that was read. That is a business decision (which price the customer accepted), not a stock race.
- A test simulates a concurrent order changing the stock right after the read; the order is rejected with 422 and nothing is saved.
