# 29. Epic 7.16 — Crash recovery

Pourquoi `FINALIZING` existe :

```text
10 000 lignes
```

ne doivent pas être réconciliées dans une seule transaction gigantesque.

Exemple :

```text
Batch 1 → 500 lines RECONCILED
Batch 2 → 500 lines RECONCILED
crash
```

Après redémarrage :

```text
ne reprendre que PENDING
```

Jamais rejouer les lignes déjà `RECONCILED`.

Quand :

```text
PENDING count = 0
```

alors :

```text
StockCount → COMPLETED
```

puis libérer les scopes.

Commit :

```text
test(inventory): verify stock count crash recovery
```

---
