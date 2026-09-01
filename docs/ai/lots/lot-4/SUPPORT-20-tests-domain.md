# 20. Tests Domain

Couvrir :

```text
create draft
add line
update line
remove line
cancel draft
reject empty completion
reject cancelled completion
reject completed edit
reject second completion
```

SaleLine :

```text
quantity precision
minimumQuantity
quantityIncrement
conversion exactness
snapshot immutability
```

Pricing :

```text
price resolution
price not found
pricing changed
currency mismatch
deterministic recalculation
```

Commits :

```text
test(sales): cover sale lifecycle
test(sales): cover sale line snapshots
test(sales): cover sale pricing
```

---
