# 57. Failure matrix StockCount start

Injecter erreur pendant :

```text
snapshot lines
scope locks
StockCount OPEN
outbox
```

Résultat :

```text
aucun lock orphelin
aucun StockCount partiellement ouvert
```

---
