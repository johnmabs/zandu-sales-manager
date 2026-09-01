# 39. Events StockTransfer

```text
StockTransferCreated
StockTransferShipped
StockTransferReceived
StockTransferCancelled
```

Le `StockTransferReceived` peut inclure un résumé :

```text
lineCount
hasDiscrepancy
```

sans gonfler l’event avec des détails massifs inutilement.

---
