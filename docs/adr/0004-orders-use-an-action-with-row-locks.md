# 0004. Orders go through an Action with a transaction and row locks

## Context
The Lumen controller validated input, opened a transaction, checked stock and built the response in one method. Two concurrent orders could both read "1 in stock" and both succeed.

## Decision
- `StoreOrderRequest` validates and normalizes input (Single Responsibility).
- `PlaceOrder` is a final Action with one public method, `handle()`. It runs in `DB::transaction` and locks each product row with `lockForUpdate()` before checking and decrementing stock.
- Running out of stock is a domain exception (`InsufficientStock`) that renders itself as a 422 JSON response. The transaction rolls back, so a partially valid order leaves no trace.
- Controllers stay thin. An architecture test forbids the `DB` facade in `App\Http\Controllers`.

## Consequences
- The ordering rule can be reused from a queue job or a console command without HTTP.
- Row locks serialize orders for the same product. That is the correct trade-off for stock; hot products under very high contention would need a reservation table or a queue, which is out of scope here.
