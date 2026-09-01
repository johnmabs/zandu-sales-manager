# 59. Crash recovery test

Créer :

```text
1 200 StockCountLine
batch size = 200
```

Simuler :

```text
4 batches success
crash
```

Après reprise :

```text
800 lines RECONCILED
400 lines PENDING
```

Le système traite uniquement les 400 restantes.

Aucun mouvement doublé.

---
