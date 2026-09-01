# 55. Failure matrix ShipStockTransfer

Injecter erreur :

```text
after line 1 Stock update
after line 1 StockMovement
after source StockValuation
after valuation movement
after transfer status
after Outbox
before COMMIT
```

Résultat :

```text
ROLLBACK TOTAL
```

Aucune expédition partielle.

---
