# Inventory Concurrency Strategy

## Problem

When multiple customers attempt to purchase the same product simultaneously, a
naive `quantity - requested` update can oversell stock:

```
Initial stock = 10

Customer A -> buys 7
Customer B -> buys 7

Naive result: 10 - 7 - 7 = -4   (oversold!)
```

The backend must make inventory mutations atomic so this cannot happen.

## Solution: row locking inside a transaction

PostgreSQL row-level locks serialize concurrent writers. Every inventory mutation
runs inside a transaction that locks the affected inventory rows first.

### Checkout / reservation flow

```
BEGIN TRANSACTION

  -- 1. Lock the inventory rows in a deterministic order (by product_id)
  SELECT * FROM inventories
   WHERE product_id IN (?, ?, ...)
   ORDER BY product_id
   FOR UPDATE

  -- 2. For each line item: check availability
  IF inventory.quantity - inventory.reserved_quantity < requested THEN
    ROLLBACK
    throw InsufficientStockException
  END IF

  -- 3. Reserve stock (held while payment is pending)
  UPDATE inventories
     SET reserved_quantity = reserved_quantity + requested
   WHERE product_id = ?

  -- 4. Persist the order + order items (server-side, snapshot prices)
  INSERT INTO orders ...
  INSERT INTO order_items ...

  -- 5. Append audit trail
  INSERT INTO inventory_transactions (type = 'reservation', ...)

COMMIT
```

If any step fails, `ROLLBACK` releases the locks and no stock is lost.

### Payment confirmation flow

After Stripe confirms payment (via webhook), convert reservations to real
decrements, again under lock:

```
BEGIN TRANSACTION
  SELECT * FROM inventories WHERE product_id = ? FOR UPDATE

  UPDATE inventories
     SET quantity          = quantity - reserved_quantity,
         reserved_quantity = 0
   WHERE product_id = ?
     AND reserved_quantity >= requested

  INSERT INTO inventory_transactions (type = 'sale', ...)
COMMIT
```

### Cancellation / refund flow

Return stock the same way: under `FOR UPDATE`, increment `quantity` (or decrement
`reserved_quantity` when the order never paid), and append a `release` or `return`
transaction.

## Why a separate `inventories` table?

Stock lives on its own row, **not** on `products`:

1. Locking an inventory row during checkout never blocks reads of the hot catalog
   row (`products`).
2. Product metadata (name, description, price) and stock concerns stay separated.
3. The same pattern scales if inventory later moves to its own service.

## Lock ordering

Multiple line items in one cart must lock their inventory rows in a consistent
order (`ORDER BY product_id`) to avoid deadlocks between concurrent checkouts.

## Guarantees

| Guarantee | Mechanism |
| --- | --- |
| No overselling | `FOR UPDATE` serializes writers on the same row |
| Atomicity | All-or-nothing transaction |
| Auditability | `inventory_transactions` records every delta |
| No deadlocks | Deterministic lock ordering |
| Availability for sale | `available = quantity - reserved_quantity` |
