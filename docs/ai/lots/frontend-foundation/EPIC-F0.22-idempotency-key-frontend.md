# 54. Epic F0.22 — Idempotency-Key frontend

Les commandes critiques doivent pouvoir transmettre :

```text
Idempotency-Key
```

conformément au backend.

Le client doit conserver la même clé pour le retry de la même intention.

Il ne doit pas générer une nouvelle clé après simple timeout réseau si l’utilisateur tente de reprendre exactement la même commande.

Exemples :

```text
CompleteSale
PostGoodsReceipt
ShipStockTransfer
ReceiveStockTransfer
```

---
