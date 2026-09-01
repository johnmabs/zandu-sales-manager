# 52. Tests lock

Pendant `OPEN` ou `FINALIZING`, tenter :

```text
sale
goods receipt
adjustment
sale return
purchase return
transfer ship
transfer receive
receipt correction
```

sur un produit verrouillé.

Résultat :

```text
STOCK_COUNT_PRODUCT_LOCKED
```

Même opération sur autre produit :

```text
allowed
```

Commit :

```text
test(inventory): enforce stock count product lock
```

---
