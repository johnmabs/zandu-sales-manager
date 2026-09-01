# 15. Transfer idempotence

Clé logique :

```text
stockTransferId
+
productId
+
phase
```

Phases :

```text
TRANSFER_OUT
TRANSFER_IN
```

Un replay identique :

```text
→ retourne résultat existant
```

Même `commandId`, payload différent :

```text
→ IdempotencyConflict
```

Aucun double mouvement.

Commit :

```text
test(inventory): verify stock transfer idempotence
```

---
