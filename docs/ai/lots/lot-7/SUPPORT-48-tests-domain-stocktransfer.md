# 48. Tests Domain StockTransfer

Couvrir :

```text
create transfer
same store rejected
cross-tenant stores rejected
add line
duplicate product rejected
requested quantity > 0
cancel draft
reject cancel shipped
ship
receive
reject second ship
reject second receive
zero shipped line no movement
zero received line no movement
received <= shipped
shipped <= requested
```

Commit :

```text
test(inventory): cover stock transfer lifecycle
```

---
