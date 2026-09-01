# 40. Persistence

Tables :

```text
purchasing.supplier
purchasing.purchase_order
purchasing.purchase_order_line
purchasing.goods_receipt
purchasing.goods_receipt_line
purchasing.goods_receipt_correction
purchasing.goods_receipt_correction_line
purchasing.purchase_return
purchasing.purchase_return_line
```

Contraintes :

```text
unique organization + purchase_order.number
unique organization + goods_receipt.number
```

Un produit apparaît au maximum une fois par PO.

Versioning optimiste et RLS obligatoires.

---
