# 23. Mouvement interdit pendant comptage

Tant qu’un produit appartient à un StockCount :

```text
OPEN
ou
FINALIZING
```

interdire tout mouvement affectant sa quantité, notamment :

```text
SALE
SALE_RETURN
PURCHASE_RECEIPT
PURCHASE_RETURN
ADJUSTMENT_IN
ADJUSTMENT_OUT
TRANSFER_OUT
TRANSFER_IN
GOODS_RECEIPT_CORRECTION_IN
GOODS_RECEIPT_CORRECTION_OUT
```

Exception :

```text
STOCK_COUNT_CORRECTION_IN
STOCK_COUNT_CORRECTION_OUT
```

produits par la réconciliation du comptage lui-même.

Le contrôle doit être centralisé dans Inventory.

---
