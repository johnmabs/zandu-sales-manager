# 31. StoreClosure integration

Purchasing implémente le blocker attendu par `StoreClosure`.

Blockers possibles :

```text
OPEN_PURCHASE_ORDER
DRAFT_GOODS_RECEIPT
OPEN_PURCHASE_RETURN
OPEN_GOODS_RECEIPT_CORRECTION
```

Ne bloquer que les documents réellement ouverts selon la policy de fermeture.

Commit :

```text
feat(purchasing): provide store closure blockers
```

---
