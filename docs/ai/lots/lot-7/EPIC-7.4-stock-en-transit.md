# 10. Epic 7.4 — Stock en transit

Le MVP ne crée pas :

```text
TransitStock
```

comme stock physique distinct.

Le transit est expliqué par :

```text
StockTransfer.status = SHIPPED
shippedQuantity
receivedQuantity = null
TRANSFER_OUT déjà créé
TRANSFER_IN absent
```

La quantité expédiée n’appartient plus au Stock source et n’appartient pas encore au Stock destination.

Pour les lectures futures, une projection pourra calculer :

```text
inTransitQuantity
```

mais aucun invariant critique ne dépend d’une projection.

---
