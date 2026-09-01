# 17. Epic 6.11 — Partial receipts

Exemple :

```text
PurchaseOrder:
ordered = 100
received = 0
```

Receipt 1 :

```text
+40
→ received = 40
→ PARTIALLY_RECEIVED
```

Receipt 2 :

```text
+60
→ received = 100
→ FULLY_RECEIVED
```

`receivedQuantity` est persistée et protégée par version.

Commit :

```text
feat(purchasing): support partial goods receipts
```

---
