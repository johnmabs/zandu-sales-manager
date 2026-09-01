# 9. Epic 6.3 — PurchasingPolicy

Décision applicable : [ADR-0023 — Politique de réception fournisseur du
MVP](../architecture/adr/0023-purchasing-receipt-policy.md).

Créer une policy possédée par Purchasing.

Minimum :

```text
PurchasingPolicy
├── purchaseOrderRequiredForReceipt
└── overReceiptPolicy
```

Baseline :

```text
direct receipt allowed by default
```

Pour l’over receipt :

```text
default = forbidden
```

Exception uniquement avec :

```text
PURCHASING_OVER_RECEIPT
reason
authorizedBy
```

Commit :

```text
feat(purchasing): add purchasing policy
```

---
