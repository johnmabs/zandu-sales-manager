# 40. POS cart

Même avant offline, le panier POS mérite une abstraction séparée :

```text
CartState
```

Il ne doit pas devenir un faux `Sale` domain aggregate frontend.

Le serveur reste autorité lors de :

```text
CompleteSale
```

Le panier représente :

```text
user intent / draft UX
```

---
