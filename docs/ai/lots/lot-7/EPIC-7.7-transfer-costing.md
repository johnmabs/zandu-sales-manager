# 13. Epic 7.7 — Transfer costing

La baseline impose :

```text
un transfert transporte la valeur du store source
jusqu’au store destination
```

## À l’expédition

Pour chaque ligne :

```text
shippedUnitCost
=
source currentAverageUnitCost
```

```text
shippedValue
=
shippedQuantity × shippedUnitCost
```

Le store source retire :

```text
quantity
+
value
```

dans la même transaction.

Conserver un snapshot :

```text
TransferLineCostSnapshot
```

ou équivalent :

```text
StockTransferLine
├── shippedUnitCostSnapshot
└── shippedValueSnapshot
```

## À la réception

La valeur reçue doit dériver du snapshot expédié.

Si :

```text
received = shipped
```

alors toute la valeur est transférée.

Si :

```text
received < shipped
```

répartir la valeur de manière déterministe.

La valeur non reçue reste attachée au transfert comme :

```text
transitLossValue
```

potentielle.

Ne jamais recalculer la valeur avec le coût moyen du store destination.

## Destination average

```text
destinationNewTotalValue
=
destinationPreviousValue
+
receivedTransferredValue
```

puis recalcul du coût moyen.

Commit :

```text
feat(costing): transfer stock value between stores
```

---
