# 18. Epic 6.12 — Over receipt

Invariant par défaut :

```text
cumulativeReceivedQuantity <= orderedQuantity
```

Exemple :

```text
ordered = 100
already received = 90
new receipt = 20
```

Sans autorisation :

```text
→ OVER_RECEIPT_NOT_ALLOWED
```

Avec :

```text
PURCHASING_OVER_RECEIPT
reason
authorizedBy
```

la quantité physique réellement reçue est enregistrée.

Ne jamais tronquer silencieusement la quantité à 10.

Commit :

```text
feat(purchasing): add over receipt authorization
```

---
