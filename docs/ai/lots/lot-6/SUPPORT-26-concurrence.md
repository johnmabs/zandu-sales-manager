# 26. Concurrence

## Partial receipts concurrentes

```text
ordered = 100
received = 70
A = +20
B = +20
```

Sans over-receipt autorisé :

```text
final cumulative <= 100
```

`PurchaseOrder.version` protège le cumul.

## Stock

Les entrées simultanées utilisent la stratégie Inventory du Lot 3.

## Corrections

`GoodsReceiptCorrection` vérifie la quantité effective courante avant application.

## PurchaseReturn

Le retour fournisseur revalide le stock disponible et le reliquat retournable dans la transaction.

---
