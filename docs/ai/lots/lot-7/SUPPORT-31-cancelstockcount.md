# 31. CancelStockCount

Autorisé :

```text
DRAFT → CANCELLED
OPEN → CANCELLED
```

Interdit :

```text
FINALIZING → CANCELLED
```

Lors d’une annulation depuis `OPEN` :

- supprimer/libérer les `OpenStockCountScope` ;
- conserver le StockCount et ses lignes à des fins historiques ;
- ne créer aucun mouvement.

Commit :

```text
feat(inventory): cancel stock count
```

---
