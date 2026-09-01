# 66. Gate M3 — Gestion complète du stock

Le jalon M3 est atteint lorsque les Lots 5, 6 et 7 sont tous validés.

Le système sait alors expliquer et valoriser :

```text
INITIAL_STOCK

PURCHASE_RECEIPT
GOODS_RECEIPT_CORRECTION_IN
GOODS_RECEIPT_CORRECTION_OUT
PURCHASE_RETURN

SALE
SALE_RETURN

ADJUSTMENT_IN
ADJUSTMENT_OUT

TRANSFER_OUT
TRANSFER_IN

STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

Chaque changement :

- possède une cause ;
- respecte le tenant ;
- respecte le store ;
- est idempotent ;
- est transactionnel ;
- conserve un ledger ;
- maintient Stock et Costing cohérents ;
- ne rend jamais le stock négatif ;
- est testé sur PostgreSQL réel.

---
