# 60. Concurrency StockCountLine

Deux opérateurs comptent deux produits différents :

```text
→ no contention global
```

Deux opérateurs modifient la même ligne :

```text
ExpectedVersion
→ one succeeds
→ one conflict
```

Le client recharge et revalide.

---
